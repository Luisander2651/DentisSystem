#!/usr/bin/env bash
# Restores a PostgreSQL dump of Dentissa over the current database (spec 015, CA13).
#
#   restore.sh <dump>                  into the production stack
#   restore.sh --into-manual <dump>    into the manual stack in MANUAL_DIR
#   restore.sh --yes <dump>            without the interactive confirmation (rollback.sh)
#
# Everything written after the dump is lost, but a pre-restore dump of the current database
# is taken first. It is only ever run by hand or by rollback.sh.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=docker/prod/lib.sh
source "$SCRIPT_DIR/lib.sh"

INTO_MANUAL=false
CONFIRMED=false
DUMP=""
while [ $# -gt 0 ]; do
    case "$1" in
        --into-manual) INTO_MANUAL=true ;;
        --yes) CONFIRMED=true ;;
        -*) fail "unknown option: $1" ;;
        *) DUMP="$1" ;;
    esac
    shift
done
[ -n "$DUMP" ] || fail "usage: restore.sh [--into-manual] [--yes] <dump>"
[ -f "$DUMP" ] || fail "dump not found: $DUMP"
[ "$(head -c 5 "$DUMP")" = "PGDMP" ] || fail "not a PostgreSQL custom-format dump: $DUMP"

DUMP_NAME="$(basename "$DUMP")"
# The name goes into DEPLOY_LOG: nothing that could break or forge a line of it.
[[ "$DUMP_NAME" =~ ^[A-Za-z0-9._-]+$ ]] || fail "the dump name may only contain letters, digits, dots, dashes and underscores"

if [ "$CONFIRMED" != true ]; then
    printf 'This replaces the current database with %s. Everything written after it is lost.\n' "$DUMP_NAME"
    read -r -p "Type 'restore' to continue: " answer
    [ "$answer" = "restore" ] || fail "cancelled"
fi

on_error() {
    log_operation restore "$DUMP_NAME" failed
}
trap on_error ERR

# What is about to be replaced is kept: choosing the wrong dump must not be final. The
# manual stack is outside compose.sh, so --into-manual does not take this copy.
if [ "$INTO_MANUAL" != true ]; then
    "$SCRIPT_DIR/backup.sh" pre-restore > /dev/null
fi

# The dump is turned into SQL first, so a truncated or broken dump stops here without
# touching the database. Then the schema is reset and the SQL applied in one transaction:
# objects created after the dump (a newer version's tables) disappear too, and any error
# leaves the database as it was. pg_restore's clean option would only drop what the dump holds.
# shellcheck disable=SC2016 # expanded inside the container, on purpose
RESTORE_COMMAND='set -e
sql="$(mktemp)"
trap "rm -f \"\$sql\"" EXIT
pg_restore --no-owner -f "$sql"
{ printf "DROP SCHEMA public CASCADE;\nCREATE SCHEMA public;\n"; cat "$sql"; } |
    PGOPTIONS="--client-min-messages=warning" psql -X -q -v ON_ERROR_STOP=1 --single-transaction -U "$POSTGRES_USER" -d "$POSTGRES_DB" > /dev/null'
if [ "$INTO_MANUAL" = true ]; then
    (cd "$MANUAL_DIR" && docker compose exec -T db sh -c "$RESTORE_COMMAND") < "$DUMP"
else
    "$SCRIPT_DIR/compose.sh" exec -T db sh -c "$RESTORE_COMMAND" < "$DUMP"
fi

trap - ERR
log_operation restore "$DUMP_NAME" ok
printf 'restored %s\n' "$DUMP_NAME"
