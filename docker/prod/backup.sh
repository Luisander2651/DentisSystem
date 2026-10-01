#!/usr/bin/env bash
# PostgreSQL backup of Dentissa (spec 015, CA12).
#
#   backup.sh daily                  daily cron (03:00); keeps the last 7 days of daily dumps
#   backup.sh pre-<tag>              before a deploy (deploy.sh)
#   backup.sh --from-manual <label>  from the manual stack in MANUAL_DIR (first step)
#
# Dumps go to BACKUP_DIR (folder 700, files 600), outside every image and served volume.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=docker/prod/lib.sh
source "$SCRIPT_DIR/lib.sh"

FROM_MANUAL=false
LABEL=""
while [ $# -gt 0 ]; do
    case "$1" in
        --from-manual) FROM_MANUAL=true ;;
        -*) fail "unknown option: $1" ;;
        *) LABEL="$1" ;;
    esac
    shift
done
[ -n "$LABEL" ] || fail "usage: backup.sh [--from-manual] <daily|pre-<tag>|label>"
[[ "$LABEL" =~ ^[A-Za-z0-9._-]+$ ]] || fail "the label may only contain letters, digits, dots, dashes and underscores"

umask 077
mkdir -p "$BACKUP_DIR"
chmod 700 "$BACKUP_DIR"

DUMP="$BACKUP_DIR/${LABEL}-$(date -u +%Y%m%dT%H%M%SZ).dump"

on_error() {
    rm -f "$DUMP"
    log_operation backup "$LABEL" failed
}
trap on_error ERR

# The credentials never leave the container: pg_dump reads them from its environment.
# shellcheck disable=SC2016 # expanded inside the container, on purpose
DUMP_COMMAND='pg_dump -U "$POSTGRES_USER" -Fc "$POSTGRES_DB"'
if [ "$FROM_MANUAL" = true ]; then
    (cd "$MANUAL_DIR" && docker compose exec -T db sh -c "$DUMP_COMMAND") > "$DUMP"
else
    "$SCRIPT_DIR/compose.sh" exec -T db sh -c "$DUMP_COMMAND" > "$DUMP"
fi
chmod 600 "$DUMP"

# A custom-format dump always starts with PGDMP.
[ "$(head -c 5 "$DUMP")" = "PGDMP" ] || { printf 'ERROR: %s is not a valid dump\n' "$DUMP" >&2; false; }

if [ "$LABEL" = "daily" ]; then
    find "$BACKUP_DIR" -maxdepth 1 -name 'daily-*.dump' -mtime +7 -delete
fi

trap - ERR
log_operation backup "$LABEL" ok
printf '%s\n' "$DUMP"
