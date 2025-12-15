# docker/Dockerfile
FROM php:8.4-fpm-alpine

# 1. Instalar dependencias DE EJECUCIÓN (Runtime)
# Estas librerías se deben quedar en la imagen final para que PHP funcione.
RUN apk update && apk add --no-cache \
    # Herramientas básicas
    git \
    curl \
    zip \
    unzip \
    nodejs \
    npm \
    # Librerías requeridas por las extensiones de PHP (versiones runtime)
    libpng \
    libjpeg-turbo \
    freetype \
    libzip \
    libpq \
    oniguruma

# 2. Instalar dependencias de COMPILACIÓN (Build deps)
# Usamos --virtual .build-deps para agruparlas y borrarlas fácilmente después
RUN apk add --no-cache --virtual .build-deps \
    postgresql-dev \
    libxml2-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libexif-dev \
    oniguruma-dev \
    libzip-dev

# 3. Configurar extensiones (GD)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg

# 4. Instalar extensiones de PHP
RUN docker-php-ext-install -j$(nproc) pdo_pgsql mbstring exif pcntl bcmath gd zip

# 5. Limpieza INTELIGENTE
# Esto borra solo el grupo virtual .build-deps, pero MANTIENE las librerías del paso 1
RUN apk del .build-deps \
    && rm -rf /var/cache/apk/*

# 6. Obtener Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 7. Establecer directorio de trabajo
WORKDIR /var/www/html

# 8. Configurar permisos
RUN mkdir -p /var/www/html/.composer && \
    chown -R www-data:www-data /var/www/html/.composer

# 9. Ejecución Final
USER www-data