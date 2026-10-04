#!/usr/bin/env bash
# Shared settings and helpers for the Dentissa production scripts (spec 015).
# Sourced by deploy.sh, rollback.sh, backup.sh, restore.sh, verify.sh and compose.sh.
# Every path can be overridden from the environment, which is how the scripts are
# exercised outside the droplet.

# Production clone, with docker-compose.prod.yml and its own .env.
DENTISSA_DIR="${DENTISSA_DIR:-/home/deploy/dentissa}"
# Manual stack from before spec 015. It is only stopped and started, never modified.
MANUAL_DIR="${MANUAL_DIR:-/home/deploy/DentisSystem}"
# Database dumps: folder 700, files 600, outside any image or served volume.
BACKUP_DIR="${BACKUP_DIR:-/home/deploy/backups}"
# Append-only record of deploys, rollbacks, restores and backups (CA15).
DEPLOY_LOG="${DEPLOY_LOG:-/home/deploy/deploys.log}"

# Shape of every line in DEPLOY_LOG; verify.sh checks the last line against it.
# shellcheck disable=SC2034
DEPLOY_LOG_LINE_REGEX='^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z user=[^ ]+ action=[a-z-]+ version=[^ ]+ result=(ok|failed)$'

# The person behind the SSH session, also when the script runs through sudo.
operator_name() {
    printf '%s' "${SUDO_USER:-${USER:-$(id -un)}}"
}

# log_operation <action> <version> <result>
# Appends one line to DEPLOY_LOG. Never rewrites or truncates it.
log_operation() {
    local action="$1" version="$2" result="$3"

    mkdir -p "$(dirname "$DEPLOY_LOG")"
    printf '%s user=%s action=%s version=%s result=%s\n' \
        "$(date -u +%Y-%m-%dT%H:%M:%SZ)" "$(operator_name)" "$action" "$version" "$result" \
        >> "$DEPLOY_LOG"
}

# assert_logged <action> <version>
# Fails unless the last line of DEPLOY_LOG is that operation with result=ok (CA15).
assert_logged() {
    local action="$1" version="$2" line

    line="$(tail -n 1 "$DEPLOY_LOG" 2> /dev/null || true)"
    [[ "$line" == *" action=$action version=$version result=ok" ]] ||
        fail "the operation was not recorded in $DEPLOY_LOG"
}

# fail <message>: prints to stderr and exits with a non-zero code.
fail() {
    printf 'ERROR: %s\n' "$1" >&2
    exit 1
}
