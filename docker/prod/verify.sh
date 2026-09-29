#!/usr/bin/env bash
# Checks a Dentissa production deployment (spec 015). Exits non-zero if any check fails.
#
#   verify.sh --local                            on the droplet, from the production clone
#   verify.sh --remote <domain> --origin <ip>    from a machine outside the droplet
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
RATE_LIMITED_PATH="/api/v1/public/certifications"
CLOSED_PORTS=(5432 6379 3000 3100 5173 9000 12345)

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
    curl -fsS -o /dev/null --max-time 10 --resolve "$DOMAIN:443:127.0.0.1" "https://$DOMAIN/up"
}

debug_is_off() {
    compose exec -T app php artisan about --only=environment --json | grep -q '"debug_mode":false'
}

queue_is_running() {
    compose ps --status running --services | grep -qx queue
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
    find "$BACKUP_DIR" -maxdepth 1 -name '*.dump' -mmin -1500 | grep -q .
}

backups_are_private() {
    [ "$(stat -c %a "$BACKUP_DIR")" = "700" ] && ! find "$BACKUP_DIR" -maxdepth 1 -name '*.dump' ! -perm 600 | grep -q .
}

old_daily_backups_are_rotated() {
    ! find "$BACKUP_DIR" -maxdepth 1 -name 'daily-*.dump' -mtime +7 | grep -q .
}

last_operation_is_recorded() {
    local line
    line="$(tail -n 1 "$DEPLOY_LOG")"
    [[ "$line" =~ $DEPLOY_LOG_LINE_REGEX ]]
}

disk_has_room() {
    df -P "$DENTISSA_DIR" | awk 'NR == 2 { gsub("%", "", $5); exit ($5 < 80) ? 0 : 1 }'
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
    check "a backup from the last 25 hours exists" recent_backup_exists
    check "backups are private (folder 700, files 600)" backups_are_private
    check "daily backups older than 7 days are gone" old_daily_backups_are_rotated
    check "the last operation in deploys.log is well formed" last_operation_is_recorded
    check "disk usage is below 80%" disk_has_room
}

# --- remote checks ----------------------------------------------------------------------

headers_of() {
    curl -sS -D - -o /dev/null --max-time 10 "$@"
}

redirects_to_https() {
    local result
    result="$(curl -sS -o /dev/null --max-time 10 -w '%{http_code} %{redirect_url}' "http://$DOMAIN/")"
    [[ "$result" =~ ^30[18]\ https:// ]]
}

has_header() {
    headers_of "https://$DOMAIN/login" | grep -qiE "^$1"
}

lacks_header() {
    ! headers_of "https://$DOMAIN/login" | grep -qiE "^$1"
}

rejects_foreign_origin() {
    ! headers_of -X OPTIONS -H 'Origin: https://evil.example' -H 'Access-Control-Request-Method: GET' \
        "https://$DOMAIN$RATE_LIMITED_PATH" | grep -qi '^access-control-allow-origin'
}

status_is() {
    local expected="$1" path="$2"
    [ "$(curl -sS -o /dev/null --max-time 10 -w '%{http_code}' "https://$DOMAIN$path")" = "$expected" ]
}

manifest_asset_is_served() {
    local asset
    asset="$(curl -fsS --max-time 10 "https://$DOMAIN/build/manifest.json" | grep -oE '"file": *"[^"]+"' | head -n 1 | sed -E 's/.*"([^"]+)"$/\1/')"
    [ -n "$asset" ] && status_is 200 "/build/$asset"
}

origin_port_is_closed() {
    ! timeout 4 bash -c "</dev/tcp/$ORIGIN/$1"
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

verify_remote() {
    [ -n "${ORIGIN:-}" ] || fail "--remote needs --origin <ip of the droplet>"

    printf 'Dentissa, remote checks for %s (origin given)\n' "$DOMAIN"
    check "http redirects permanently to https" redirects_to_https
    check "HSTS is sent" has_header 'strict-transport-security: max-age='
    check "Content-Security-Policy is sent" has_header 'content-security-policy: '
    check "X-Frame-Options is DENY" has_header 'x-frame-options: DENY'
    check "X-Content-Type-Options is nosniff" has_header 'x-content-type-options: nosniff'
    check "Referrer-Policy is sent" has_header 'referrer-policy: '
    check "X-Powered-By is not sent" lacks_header 'x-powered-by:'
    check "the nginx version is not sent" lacks_header 'server: nginx/'
    check "another origin cannot read the API" rejects_foreign_origin
    check "/.env is not served" status_is 404 /.env
    check "/.git/ is not served" status_is 404 /.git/
    check "/backups/ is not served" status_is 404 /backups/
    check "/storage/login.jpg is served" status_is 200 /storage/login.jpg
    check "an asset of the build manifest is served" manifest_asset_is_served
    local port
    for port in "${CLOSED_PORTS[@]}"; do
        check "port $port is closed on the droplet IP" origin_port_is_closed "$port"
    done
    check "a forged client IP is ignored when the proxy is bypassed" forged_ip_is_ignored_at_origin
}

# --- entry point ------------------------------------------------------------------------

MODE=""
ORIGIN=""
while [ $# -gt 0 ]; do
    case "$1" in
        --local) MODE="local" ;;
        --remote) MODE="remote"; DOMAIN="${2:?--remote needs a domain}"; shift ;;
        --origin) ORIGIN="${2:?--origin needs an IP}"; shift ;;
        *) fail "unknown argument: $1" ;;
    esac
    shift
done

case "$MODE" in
    local) verify_local ;;
    remote) verify_remote ;;
    *) fail "usage: verify.sh --local | --remote <domain> --origin <ip>" ;;
esac

if [ "$failures" -gt 0 ]; then
    printf '%d check(s) failed\n' "$failures"
    exit 1
fi
printf 'all checks passed\n'
