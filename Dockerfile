# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# 1. Dépendances PHP (sans les outils de développement)
# ---------------------------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --ignore-platform-reqs

# ---------------------------------------------------------------------------
# 2. Assets front (Vite + Tailwind)
# ---------------------------------------------------------------------------
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json .npmrc ./
RUN npm ci

COPY . .
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# ---------------------------------------------------------------------------
# 3. Image finale : PHP 8.5 + Apache
# ---------------------------------------------------------------------------
FROM php:8.5-apache AS app

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions bcmath gd intl opcache

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && a2enmod rewrite headers deflate filter \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY <<'EOF' /usr/local/etc/php/conf.d/zz-app.ini
expose_php = Off
memory_limit = 256M
opcache.enable = 1
opcache.validate_timestamps = 0
opcache.memory_consumption = 128
opcache.max_accelerated_files = 20000
opcache.interned_strings_buffer = 16
; Les fichiers ne changent pas dans l'image : leurs chemins résolus sont gardés dix minutes.
realpath_cache_size = 4096K
realpath_cache_ttl = 600
EOF

WORKDIR /var/www/html

COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=vendor /app/vendor ./vendor
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build

RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-interaction \
    && php artisan package:discover --ansi \
    && rm /usr/local/bin/composer

# Démarrage : base SQLite, clé d'application, migrations et caches.
COPY <<'EOF' /usr/local/bin/docker-entrypoint-app
#!/bin/sh
set -e

DATA_DIR="$(dirname "${DB_DATABASE:-/data/database.sqlite}")"
mkdir -p "$DATA_DIR" \
    storage/app/public storage/framework/cache/data storage/framework/sessions \
    storage/framework/views storage/logs

if [ -z "$APP_KEY" ]; then
    if [ ! -s "$DATA_DIR/app.key" ]; then
        php artisan key:generate --show --no-interaction > "$DATA_DIR/app.key"
        echo "Clé d'application générée dans $DATA_DIR/app.key"
    fi
    APP_KEY="$(cat "$DATA_DIR/app.key")"
    export APP_KEY
fi

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ] && [ ! -f "${DB_DATABASE:-/data/database.sqlite}" ]; then
    touch "${DB_DATABASE:-/data/database.sqlite}"
fi

php artisan migrate --force --no-interaction
php artisan optimize --no-interaction

chown -R www-data:www-data storage bootstrap/cache "$DATA_DIR"

exec "$@"
EOF
RUN chmod +x /usr/local/bin/docker-entrypoint-app

EXPOSE 80

ENTRYPOINT ["docker-entrypoint-app"]
CMD ["apache2-foreground"]
