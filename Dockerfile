# Étape 1: Build des dépendances PHP
FROM composer:2.6 AS composer-build

WORKDIR /app

COPY . .

RUN composer require "zircote/swagger-php:^4.0" --no-scripts --no-interaction --prefer-dist \
    && composer install --no-scripts --optimize-autoloader --no-interaction --prefer-dist

# Étape 2: Image finale
FROM php:8.3-fpm-alpine

RUN apk add --no-cache postgresql-dev \
    && docker-php-ext-install pdo pdo_pgsql

RUN addgroup -g 1000 laravel && adduser -G laravel -g laravel -s /bin/sh -D laravel

WORKDIR /var/www/html
COPY --from=composer-build /app /var/www/html

RUN mkdir -p storage/framework/{cache,data,sessions,testing,views} \
    && mkdir -p storage/logs bootstrap/cache storage/api-docs \
    && chown -R laravel:laravel /var/www/html \
    && chmod -R 775 storage bootstrap/cache

USER laravel

EXPOSE 8000

CMD ["sh", "-c", "php artisan passport:install --uuids --force && php artisan l5-swagger:generate && php artisan serve --host=0.0.0.0 --port=8000"]
