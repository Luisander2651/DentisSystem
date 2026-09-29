#!/usr/bin/env bash
# `docker compose` for the production stack, pinned to the version in service (spec 015).
# Use it for anything outside deploy.sh and rollback.sh: the CSP switch, the backup cron,
# certbot's deploy hook, or looking at logs. Examples:
#   docker/prod/compose.sh ps
#   docker/prod/compose.sh up -d app
#   docker/prod/compose.sh exec nginx nginx -s reload
#
# APP_VERSION comes from .deploy/current (written by deploy.sh and rollback.sh), so the
# .env of the clone can never switch the running image by accident. Before the first
# deploy there is no image yet; a neutral value keeps commands that do not use it working.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=docker/prod/lib.sh
source "$SCRIPT_DIR/lib.sh"

if [ -z "${APP_VERSION:-}" ]; then
    if [ -f "$DENTISSA_DIR/.deploy/current" ]; then
        APP_VERSION="$(cat "$DENTISSA_DIR/.deploy/current")"
    else
        APP_VERSION="none"
    fi
fi
export APP_VERSION

cd "$DENTISSA_DIR"
exec docker compose -f docker-compose.prod.yml "$@"
