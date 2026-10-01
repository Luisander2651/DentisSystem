#!/bin/sh
# Entrypoint of the production app and queue containers (spec 015).
# Caches config, routes, views and events with this container's environment, then runs
# the given command (php-fpm or queue:work).
set -e

php artisan optimize

exec "$@"
