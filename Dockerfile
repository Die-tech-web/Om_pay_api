# Étape 1 : Build des dépendances PHP avec Composer
FROM composer:2.6 AS composer-build

# Augmenter la limite mémoire de Composer
ENV COMPOSER_MEMORY_LIMIT=-1

WORKDIR /app

# Copier d'abord les fichiers de dépendances
COPY composer.json composer.lock ./

# Télécharger les dépendances (sans scripts)
RUN composer install --no-scripts --no-autoloader --no-interaction --prefer-dist --ignore-platform-reqs

# Copier le reste du code
COPY . .

# Générer l'autoloader optimisé
RUN composer dump-autoload --optimize --no-scripts

# Étape 2 : Image finale
FROM php:8.3-fpm-alpine

# Installer les dépendances système nécessaires
RUN apk add --no-cache \
    postgresql-dev \
    bash \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    libxml2-dev \
    $PHPIZE_DEPS

# Configurer et installer GD avec la nouvelle syntaxe pour PHP 8.3
RUN docker-php-ext-configure gd \
        --enable-gd \
        --with-freetype \
        --with-jpeg

# Installer les extensions PHP une par une pour voir laquelle pose problème
RUN docker-php-ext-install -j$(nproc) pdo pdo_pgsql
RUN docker-php-ext-install -j$(nproc) gd
RUN docker-php-ext-install -j$(nproc) zip
RUN docker-php-ext-install -j$(nproc) intl
RUN docker-php-ext-install -j$(nproc) bcmath

# Créer un utilisateur non-root
RUN addgroup -g 1000 laravel && adduser -G laravel -g laravel -s /bin/sh -D laravel

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers depuis le build précédent avec les bons propriétaires
COPY --from=composer-build --chown=laravel:laravel /app /var/www/html

# Créer les répertoires nécessaires et définir les permissions
RUN mkdir -p storage/framework/{cache,data,sessions,testing,views} \
    && mkdir -p storage/logs bootstrap/cache storage/api-docs \
    && chown -R laravel:laravel /var/www/html \
    && chmod -R 775 storage bootstrap/cache

USER laravel

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]