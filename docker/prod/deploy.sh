#!/usr/bin/env bash
# Deploys a tagged version of Dentissa from the production clone (spec 015, ADR 0004).
#
#   deploy.sh <tag>            normal deploy over the running production stack
#   deploy.sh --first <tag>    from the manual stack in MANUAL_DIR: backs it up, stops it
#                              (never removes it) and copies its public storage. Also used
#                              to come back after rollback.sh --to-manual.
#
# Order: build, backup, migrate with the new image, write .deploy/, start, verify. Until
# the migration succeeds the previous version keeps serving and the state does not change.
#
# Run it from the clone, with the tag checked out. Any failure is recorded in DEPLOY_LOG
# with result=failed and stops the deploy; nothing is rolled back automatically.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=docker/prod/lib.sh
source "$SCRIPT_DIR/lib.sh"

FIRST=false
TAG=""
while [ $# -gt 0 ]; do
    case "$1" in
        --first) FIRST=true ;;
        -*) fail "unknown option: $1" ;;
        *) TAG="$1" ;;
    esac
    shift
done
[ -n "$TAG" ] || fail "usage: deploy.sh [--first] <tag>"

cd "$DENTISSA_DIR"

# The image must be built from exactly the tagged commit.
[ -z "$(git status --porcelain)" ] || fail "the clone has uncommitted changes"
git rev-parse -q --verify "refs/tags/$TAG" > /dev/null || fail "tag $TAG does not exist"
[ "$(git rev-parse HEAD)" = "$(git rev-list -n 1 "$TAG")" ] || fail "HEAD is not $TAG; run: git checkout $TAG"

ACTION="deploy"
[ "$FIRST" = true ] && ACTION="deploy-first"

on_error() {
    log_operation "$ACTION" "$TAG" failed
}
trap on_error ERR

echo "==> Building dentissa-app:$TAG and dentissa-web:$TAG"
docker build -f docker/Dockerfile --target prod -t "dentissa-app:$TAG" .
docker build -f docker/Dockerfile --target web -t "dentissa-web:$TAG" .

echo "==> Checking the images carry no secrets or local artefacts"
for image in "dentissa-app:$TAG" "dentissa-web:$TAG"; do
    docker run --rm --entrypoint sh "$image" -c \
        'for path in /var/www/html/.env /var/www/html/.git /var/www/html/public/hot; do [ ! -e "$path" ] || { echo "found $path" >&2; exit 1; }; done'
done

# External in docker-compose.prod.yml, so `down -v` never deletes the uploaded files.
docker volume create dentissa_storage-public > /dev/null

if [ "$FIRST" = true ]; then
    echo "==> Backing up and stopping the manual stack in $MANUAL_DIR"
    "$SCRIPT_DIR/backup.sh" --from-manual "pre-$TAG"
    (cd "$MANUAL_DIR" && docker compose stop)

    echo "==> Copying the manual stack's public storage (only into an empty volume)"
    docker run --rm --user root --entrypoint sh \
        -v dentissa_storage-public:/dst \
        -v "$MANUAL_DIR/storage/app/public:/src:ro" \
        "dentissa-app:$TAG" -c \
        'if [ -z "$(ls -A /dst)" ]; then cp -a /src/. /dst/ && chown -R www-data:www-data /dst; fi'
else
    echo "==> Backing up the production database"
    "$SCRIPT_DIR/backup.sh" "pre-$TAG"
fi

PREVIOUS="$(cat .deploy/current 2> /dev/null || true)"
export APP_VERSION="$TAG"

# Before switching versions: migrations are compatible with the code still in service (P9),
# and a migration that fails leaves that version running and the deploy state untouched.
echo "==> Running migrations with the image of $TAG"
"$SCRIPT_DIR/compose.sh" run --rm app php artisan migrate --force

# Written before `up`, so that if the start fails rollback.sh still knows where to go back.
mkdir -p .deploy
if [ -n "$PREVIOUS" ] && [ "$PREVIOUS" != "$TAG" ]; then
    printf '%s
' "$PREVIOUS" > .deploy/previous
fi
printf '%s
' "$TAG" > .deploy/current

echo "==> Starting $TAG"
"$SCRIPT_DIR/compose.sh" up -d --remove-orphans

for _ in $(seq 1 30); do
    "$SCRIPT_DIR/compose.sh" exec -T app php artisan --version > /dev/null 2>&1 && break
    sleep 2
done

echo "==> Verifying"
"$SCRIPT_DIR/verify.sh" --local --in-operation

# The version to go back to is the one in .deploy/previous, also when the same tag is
# deployed again. A removal that fails is only reported: the deploy is already verified.
KEPT="$(cat .deploy/previous 2> /dev/null || true)"
echo "==> Keeping only the images of $TAG and ${KEPT:-no previous version}"
for repository in dentissa-app dentissa-web; do
    while read -r image_tag; do
        if [ "$image_tag" != "$TAG" ] && [ "$image_tag" != "$KEPT" ]; then
            docker image rm "$repository:$image_tag" > /dev/null 2>&1 ||
                printf 'WARNING: could not remove %s:%s
' "$repository" "$image_tag" >&2
        fi
    done < <(docker image ls "$repository" --format '{{.Tag}}')
done

trap - ERR
log_operation "$ACTION" "$TAG" ok
assert_logged "$ACTION" "$TAG"
echo "==> $TAG deployed"
