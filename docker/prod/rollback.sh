#!/usr/bin/env bash
# Rolls Dentissa back (spec 015, CA13).
#
#   rollback.sh                        to the previous version (.deploy/previous)
#   rollback.sh <tag>                  to a version whose images are still on the droplet
#   rollback.sh <tag> --restore <dump> also restores that database dump (asks first)
#   rollback.sh --to-manual            back to the manual stack in MANUAL_DIR, untouched
#
# Every rollback is recorded in DEPLOY_LOG and ends with verify.sh --local (except
# --to-manual, whose stack predates the checks).

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=docker/prod/lib.sh
source "$SCRIPT_DIR/lib.sh"

TO_MANUAL=false
TAG=""
DUMP=""
while [ $# -gt 0 ]; do
    case "$1" in
        --to-manual) TO_MANUAL=true ;;
        --restore) DUMP="${2:?--restore needs a dump}"; shift ;;
        -*) fail "unknown option: $1" ;;
        *) TAG="$1" ;;
    esac
    shift
done

# Before the cd: a relative dump path is relative to whoever runs the script.
if [ -n "$DUMP" ]; then
    [ -f "$DUMP" ] || fail "dump not found: $DUMP"
    DUMP="$(cd "$(dirname "$DUMP")" && pwd)/$(basename "$DUMP")"
fi

cd "$DENTISSA_DIR"

wait_for_app() {
    for _ in $(seq 1 30); do
        "$@" php artisan --version > /dev/null 2>&1 && return 0
        sleep 2
    done
    return 1
}

if [ "$TO_MANUAL" = true ]; then
    trap 'log_operation rollback-to-manual manual failed' ERR

    echo "==> Stopping production and starting the manual stack in $MANUAL_DIR"
    "$SCRIPT_DIR/compose.sh" stop
    (cd "$MANUAL_DIR" && docker compose start)
    wait_for_app docker compose --project-directory "$MANUAL_DIR" exec -T app

    trap - ERR
    log_operation rollback-to-manual manual ok
    assert_logged rollback-to-manual manual
    echo "==> The manual stack is serving again"
    exit 0
fi

CURRENT="$(cat .deploy/current 2> /dev/null || true)"
if [ -z "$TAG" ]; then
    TAG="$(cat .deploy/previous 2> /dev/null || true)"
fi
[ -n "$TAG" ] || fail "no version given and no .deploy/previous"
docker image inspect "dentissa-app:$TAG" "dentissa-web:$TAG" > /dev/null 2>&1 ||
    fail "the images of $TAG are not on this machine"

if [ -n "$DUMP" ]; then
    printf 'Rolling back to %s and restoring %s. Everything written after that dump is lost.\n' "$TAG" "$(basename "$DUMP")"
    read -r -p "Type 'rollback' to continue: " answer
    [ "$answer" = "rollback" ] || fail "cancelled"
fi

trap 'log_operation rollback "$TAG" failed' ERR

if [ -n "$DUMP" ]; then
    echo "==> Stopping the app and restoring the database"
    "$SCRIPT_DIR/compose.sh" stop nginx app queue
    "$SCRIPT_DIR/restore.sh" --yes "$DUMP"
fi

echo "==> Starting $TAG"
export APP_VERSION="$TAG"
"$SCRIPT_DIR/compose.sh" up -d --remove-orphans
wait_for_app "$SCRIPT_DIR/compose.sh" exec -T app

mkdir -p .deploy
printf '%s\n' "$TAG" > .deploy/current
if [ -n "$CURRENT" ] && [ "$CURRENT" != "$TAG" ]; then
    printf '%s\n' "$CURRENT" > .deploy/previous
fi

"$SCRIPT_DIR/verify.sh" --local --in-operation
# The next deploy takes its rollback target from here (deploy.sh).
printf '%s\n' "$TAG" > .deploy/verified

trap - ERR
log_operation rollback "$TAG" ok
assert_logged rollback "$TAG"
echo "==> Rolled back to $TAG"
