#!/usr/bin/env bash
# Checks a Dentissa production deployment (spec 015). Exits non-zero if any check fails.
#
#   verify.sh --local                            on the droplet, from the production clone
#   verify.sh --local --in-operation             the same, called by deploy.sh and rollback.sh
#   verify.sh --remote <domain> --origin <ip>    from a machine outside the droplet
#
# The checks are functions, so the tests can load this file with `source` and run them one
# by one (tests/Modules/Core/Unit/VerifyScriptBehaviourTest.php).
#
# --remote talks to the domain (through Cloudflare) and, for the checks Cloudflare would
# hide, straight to the droplet's IP: Docker publishes ports above ufw (CA9), and a forged
# client IP must not be accepted when the proxy is bypassed (CA17).

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=docker/prod/lib.sh
source "$SCRIPT_DIR/lib.sh"

DOMAIN="${DOMAIN:-dentissapp.com}"
MANUAL_DB_CONTAINER="${MANUAL_DB_CONTAINER:-laravel-postgres}"
NGINX_CONTAINER="${NGINX_CONTAINER:-dentissa-nginx}"
RATE_LIMITED_PATH="/api/v1/public/certifications"
CLOSED_PORTS=(5432 6379 3000 3100 5173 9000 12345)
# Set by --in-operation: a deploy or rollback has just written its own pre-<tag> dump.
IN_OPERATION="${IN_OPERATION:-false}"

failures=0

# check <description> <command...>: runs the command quietly and records the result.
check() {
    local description="$1"
    shift
    if "$@" > /dev/null 2>&1; then
        printf 'ok    %s\n' "$description"
    else
        printf 'FAIL  %s\n' "$description"
        failures=$((failures + 1))
    fi
}

compose() {
    "$SCRIPT_DIR/compose.sh" "$@"
}

# --- local checks -----------------------------------------------------------------------

health_answers() {
    # Local loopback check: certificate validity is --remote's job. Retried for up to 30 s:
    # right after a start, php-fpm may still be waiting for `artisan optimize` (nginx 502).
    for _ in $(seq 1 15); do
        curl -kfsS -o /dev/null --max-time 10 --resolve "$DOMAIN:443:127.0.0.1" "https://$DOMAIN/up" && return 0
        sleep 2
    done
    return 1
}

debug_is_off() {
    local about
    about="$(compose exec -T app php artisan about --only=environment --json)"
    [[ "$about" == *'"debug_mode":false'* ]]
}

queue_is_running() {
    local services
    services="$(compose ps --status running --services)"
    grep -qx queue <<< "$services"
}

runs_without_root() {
    local uid
    uid="$(compose exec -T "$1" id -u)"
    [ -n "$uid" ] && [ "$uid" != "0" ]
}

image_is_clean() {
    compose exec -T app sh -c '
        ! command -v node && ! command -v composer &&
        [ ! -e vendor/pestphp ] && [ ! -e .env ] && [ ! -e .git ] && [ ! -e public/hot ] &&
        [ -f public/build/manifest.json ]'
}

trace_args_are_ignored() {
    compose exec -T app php -r 'exit(ini_get("zend.exception_ignore_args") ? 0 : 1);'
}

manual_database_is_stopped() {
    [ -z "$(docker ps -q --filter "name=^${MANUAL_DB_CONTAINER}$" --filter status=running)" ]
}

recent_backup_exists() {
    # On its own, only a daily dump proves that the cron is running: inside an operation
    # the pre-<tag> dump always exists.
    local pattern='daily-*.dump'
    [ "$IN_OPERATION" = true ] && pattern='*.dump'
    [ -n "$(find "$BACKUP_DIR" -maxdepth 1 -name "$pattern" -mmin -1500)" ]
}

backups_are_private() {
    [ "$(stat -c %a "$BACKUP_DIR")" = "700" ] && [ -z "$(find "$BACKUP_DIR" -maxdepth 1 -name '*.dump' ! -perm 600)" ]
}

# The folder of the dumps, anything inside it or a folder that contains it must not be
# reachable from the web server. No answer from docker proves nothing.
backups_are_not_mounted_in_nginx() {
    local mounts mount
    mounts="$(docker inspect "$NGINX_CONTAINER" --format '{{range .Mounts}}{{println .Source}}{{end}}')" || return 1
    [ -n "$mounts" ] || return 1
    while read -r mount; do
        [ -n "$mount" ] || continue
        case "$BACKUP_DIR/" in "$mount"/*) return 1 ;; esac
        case "$mount/" in "$BACKUP_DIR"/*) return 1 ;; esac
    done <<< "$mounts"
}

old_daily_backups_are_rotated() {
    # -mtime counts whole days: +6 means seven full days or more.
    [ -z "$(find "$BACKUP_DIR" -maxdepth 1 -name 'daily-*.dump' -mtime +6)" ]
}

last_operation_is_recorded() {
    local line
    line="$(tail -n 1 "$DEPLOY_LOG")"
    [[ "$line" =~ $DEPLOY_LOG_LINE_REGEX ]]
}

disk_has_room() {
    df -P "$DENTISSA_DIR" | awk 'NR == 2 { gsub("%", "", $5); exit (($5 + 0) < 80) ? 0 : 1 }'
}

verify_local() {
    printf 'Dentissa, local checks in %s\n' "$DENTISSA_DIR"
    check "/up answers through nginx" health_answers
    check "APP_DEBUG is off" debug_is_off
    check "the queue worker is running" queue_is_running
    check "app runs without root" runs_without_root app
    check "queue runs without root" runs_without_root queue
    check "image has no node, composer, dev packages, .env, .git or public/hot, and has the build" image_is_clean
    check "traces carry no arguments (zend.exception_ignore_args)" trace_args_are_ignored
    check "the manual stack database is not running at the same time" manual_database_is_stopped
    if [ "$IN_OPERATION" = true ]; then
        check "a backup from the last 25 hours exists" recent_backup_exists
    else
        check "a daily backup from the last 25 hours exists" recent_backup_exists
    fi
    check "backups are private (folder 700, files 600)" backups_are_private
    check "the backups folder is not mounted in nginx" backups_are_not_mounted_in_nginx
    check "daily backups of 7 days or more are gone" old_daily_backups_are_rotated
    check "the last operation in deploys.log is well formed" last_operation_is_recorded
    check "disk usage is below 80%" disk_has_room
}

# --- remote checks ----------------------------------------------------------------------

headers_of() {
    curl -sS -D - -o /dev/null --max-time 10 "$@"
}

# redirects_to_https [host]: through Cloudflare, which may answer the redirect itself.
redirects_to_https() {
    local host="${1:-$DOMAIN}" result
    result="$(curl -sS -o /dev/null --max-time 10 -w '%{http_code} %{redirect_url}' "http://$host/")"
    [[ "$result" =~ ^30[18]\ https:// ]]
}

# The same question asked to nginx on the droplet, where Cloudflare cannot answer for it.
origin_redirects_to_https() {
    local result
    result="$(curl -sS -o /dev/null --max-time 10 -w '%{http_code} %{redirect_url}' \
        --resolve "$DOMAIN:80:$ORIGIN" "http://$DOMAIN/")"
    [[ "$result" =~ ^30[18]\ https:// ]]
}

# has_header <pattern> [curl arguments]
has_header() {
    local pattern="$1"
    shift
    [ $# -gt 0 ] || set -- "https://$DOMAIN/login"
    grep -qiE "^$pattern" <<< "$(headers_of "$@")"
}

# lacks_header <pattern> [curl arguments]: a request that fails proves nothing.
lacks_header() {
    local pattern="$1" headers
    shift
    [ $# -gt 0 ] || set -- "https://$DOMAIN/login"
    headers="$(headers_of "$@")" || return 1
    [ -n "$headers" ] || return 1
    ! grep -qiE "^$pattern" <<< "$headers"
}

# Cloudflare rewrites or adds headers (it always answers "server: cloudflare"), so what
# nginx itself sends is asked to the droplet directly.
origin_has_header() {
    has_header "$1" --resolve "$DOMAIN:443:$ORIGIN" "https://$DOMAIN/login"
}

origin_lacks_header() {
    lacks_header "$1" --resolve "$DOMAIN:443:$ORIGIN" "https://$DOMAIN/login"
}

# Uploaded files are served by nginx, not by PHP: they need the header from nginx (CA5).
static_file_has_nosniff() {
    has_header 'x-content-type-options: nosniff' "https://$DOMAIN/storage/login.jpg"
}

rejects_foreign_origin() {
    # A browser only exposes the response when the header is '*' or the requesting origin;
    # Laravel answers with the site's own origin, which still blocks the foreign one.
    local headers
    headers="$(headers_of -X OPTIONS -H 'Origin: https://evil.example' -H 'Access-Control-Request-Method: GET' \
        "https://$DOMAIN$RATE_LIMITED_PATH")"
    ! grep -qiE '^access-control-allow-origin: *(\*|https://evil\.example) *$' <<< "${headers//$'\r'/}"
}

status_is() {
    local expected="$1" path="$2"
    [ "$(curl -sS -o /dev/null --max-time 10 -w '%{http_code}' "https://$DOMAIN$path")" = "$expected" ]
}

manifest_asset_is_served() {
    local manifest asset
    manifest="$(curl -fsS --max-time 10 "https://$DOMAIN/build/manifest.json")"
    asset="$(grep -oE '"file": *"[^"]+"' <<< "$manifest" | sed -nE '1s/.*"([^"]+)"$/\1/p')"
    [ -n "$asset" ] && status_is 200 "/build/$asset"
}

origin_port_is_closed() {
    # Host and port go as positional parameters, never inside the command text.
    # shellcheck disable=SC2016 # expanded by the inner bash, on purpose
    ! timeout 4 bash -c '</dev/tcp/$0/$1' "$ORIGIN" "$1"
}

forged_ip_is_ignored_at_origin() {
    local i code=""
    for i in $(seq 1 11); do
        code="$(curl -sS -o /dev/null --max-time 10 -w '%{http_code}' \
            --resolve "$DOMAIN:443:$ORIGIN" \
            -H "X-Forwarded-For: 203.0.113.$i" -H "CF-Connecting-IP: 203.0.113.$i" \
            "https://$DOMAIN$RATE_LIMITED_PATH")"
    done
    [ "$code" = "429" ]
}

# Through Cloudflare the limit must follow the real visitor, whatever X-Forwarded-For the
# visitor sends (CA16): eleven requests with eleven different addresses still end in 429.
forged_forwarded_ip_is_ignored_through_proxy() {
    local i code=""
    for i in $(seq 1 11); do
        code="$(curl -sS -o /dev/null --max-time 10 -w '%{http_code}' \
            -H "X-Forwarded-For: 203.0.113.$i" "https://$DOMAIN$RATE_LIMITED_PATH")"
    done
    [ "$code" = "429" ]
}

verify_remote() {
    [ -n "${ORIGIN:-}" ] || fail "--remote needs --origin <ip of the droplet>"
    if ! [[ "$ORIGIN" =~ ^([0-9]{1,3}\.){3}[0-9]{1,3}$ || ( "$ORIGIN" == *:* && "$ORIGIN" =~ ^[0-9A-Fa-f:]+$ ) ]]; then
        fail "--origin must be an IPv4 or IPv6 address"
    fi
    # Without it every port check would fail to run and be read as "closed".
    command -v timeout > /dev/null || fail "timeout is not installed: the port checks cannot run"

    printf 'Dentissa, remote checks for %s (origin given)\n' "$DOMAIN"
    check "http redirects permanently to https" redirects_to_https
    check "HSTS is sent" has_header 'strict-transport-security: max-age='
    check "Content-Security-Policy is sent" has_header 'content-security-policy: '
    check "X-Frame-Options is DENY" has_header 'x-frame-options: DENY'
    check "X-Content-Type-Options is nosniff" has_header 'x-content-type-options: nosniff'
    check "Referrer-Policy is sent" has_header 'referrer-policy: '
    check "X-Powered-By is not sent" lacks_header 'x-powered-by:'
    check "X-Powered-By is not sent by the origin" origin_lacks_header 'x-powered-by:'
    check "the nginx version is not sent by the origin" origin_lacks_header 'server: nginx/'
    check "the origin redirects http to https" origin_redirects_to_https
    check "HSTS is sent by the origin" origin_has_header 'strict-transport-security: max-age='
    check "http://www redirects permanently to https" redirects_to_https "www.$DOMAIN"
    check "HSTS is sent for www" has_header 'strict-transport-security: max-age=' "https://www.$DOMAIN/login"
    check "another origin cannot read the API" rejects_foreign_origin
    check "/.env is not served" status_is 404 /.env
    check "/.git/ is not served" status_is 404 /.git/
    check "/backups/ is not served" status_is 404 /backups/
    check "/storage/login.jpg is served" status_is 200 /storage/login.jpg
    check "/storage/login.jpg is served with nosniff" static_file_has_nosniff
    check "an asset of the build manifest is served" manifest_asset_is_served
    local port
    for port in "${CLOSED_PORTS[@]}"; do
        check "port $port is closed on the droplet IP" origin_port_is_closed "$port"
    done
    check "a forged client IP is ignored when the proxy is bypassed" forged_ip_is_ignored_at_origin
    check "a forged X-Forwarded-For is ignored through the proxy" forged_forwarded_ip_is_ignored_through_proxy
}

# --- entry point ------------------------------------------------------------------------

main() {
    local mode=""
    ORIGIN=""
    while [ $# -gt 0 ]; do
        case "$1" in
            --local) mode="local" ;;
            --in-operation) IN_OPERATION=true ;;
            --remote) mode="remote"; DOMAIN="${2:?--remote needs a domain}"; shift ;;
            --origin) ORIGIN="${2:?--origin needs an IP}"; shift ;;
            *) fail "unknown argument: $1" ;;
        esac
        shift
    done

    case "$mode" in
        local) verify_local ;;
        remote) verify_remote ;;
        *) fail "usage: verify.sh --local [--in-operation] | --remote <domain> --origin <ip>" ;;
    esac

    if [ "$failures" -gt 0 ]; then
        printf '%d check(s) failed\n' "$failures"
        exit 1
    fi
    printf 'all checks passed\n'
}

# Only when executed: `source verify.sh` just defines the checks.
if [[ "${BASH_SOURCE[0]}" == "$0" ]]; then
    main "$@"
fi
