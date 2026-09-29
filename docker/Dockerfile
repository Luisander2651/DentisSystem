# docker/Dockerfile — spec 015, ADR 0004
#
#   dev     local development: code mounted as a volume, Node and Composer inside
#   prod    production app and queue: code, vendor (no dev) and compiled assets baked in
#   web     production nginx: public/ with the compiled assets and docker/nginx/prod.conf
#
# Base images are pinned to at least major.minor and must be in support (never :latest).

# --- base: PHP and the extensions every image needs --------------------------------------
FROM php:8.4-fpm-alpine AS base

# Runtime libraries of the PHP extensions (they stay in the image)
RUN apk add --no-cache \
    libpng \
    libjpeg-turbo \
    freetype \
    libzip \
    libpq \
    oniguruma

# Build dependencies, grouped so they can be removed after compiling the extensions
RUN apk add --no-cache --virtual .build-deps \
    postgresql-dev \
    libxml2-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libexif-dev \
    oniguruma-dev \
    libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql mbstring exif pcntl bcmath gd zip opcache \
    && apk del .build-deps \
    && rm -rf /var/cache/apk/*

WORKDIR /var/www/html

# --- dev: the local development image ----------------------------------------------------
FROM base AS dev

RUN apk add --no-cache git curl zip unzip nodejs npm

COPY --from=composer:2.9 /usr/bin/composer /usr/bin/composer

RUN mkdir -p /var/www/html/.composer \
    && chown -R www-data:www-data /var/www/html/.composer

USER www-data

# --- vendor: production dependencies, without dev packages --------------------------------
FROM composer:2.9 AS vendor

WORKDIR /app
COPY . .
# The extensions live in the runtime image, not in this one.
RUN composer install --no-dev --optimize-autoloader --no-scripts --prefer-dist \
    --no-interaction --no-progress --ignore-platform-reqs

# --- assets: the compiled frontend ---------------------------------------------------------
FROM node:22.20-alpine3.22 AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
# Tailwind also scans the pagination views shipped with the framework.
COPY --from=vendor /app/vendor/laravel/framework/src/Illuminate/Pagination/resources/views \
    ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

# --- prod: app and queue ------------------------------------------------------------------
FROM base AS prod

COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY uploads.ini /usr/local/etc/php/conf.d/uploads.ini
COPY docker/prod/php.ini /usr/local/etc/php/conf.d/zz-production.ini

# Code and vendor stay owned by root (read-only for the app); only the runtime folders
# belong to www-data, so fresh volumes mounted there inherit that owner.
COPY --from=vendor /app /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build
COPY docker/prod/entrypoint.sh /usr/local/bin/dentissa-entrypoint

RUN chmod 755 /usr/local/bin/dentissa-entrypoint \
    && mkdir -p storage/app/public storage/app/private storage/framework/cache/data \
        storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data

ENTRYPOINT ["dentissa-entrypoint"]
CMD ["php-fpm"]

# --- web: production nginx ----------------------------------------------------------------
FROM nginx:1.30-alpine AS web

COPY --from=vendor /app/public /var/www/html/public
COPY --from=assets /app/public/build /var/www/html/public/build
COPY docker/nginx/prod.conf /etc/nginx/conf.d/default.conf
