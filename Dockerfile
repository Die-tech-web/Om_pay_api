# Étape 1 : Build des dépendances PHP avec Composer
FROM composer:2.6 AS composer-build

WORKDIR /app

# Copier le code source
COPY . .

# Installer les dépendances sans exécuter de scripts artisan
RUN composer install --no-scripts --optimize-autoloader --no-interaction --prefer-dist

# Étape 2 : Image finale
FROM php:8.3-fpm-alpine

# Installer les dépendances système nécessaires pour GD
RUN apk add --no-cache \
    postgresql-dev \
    bash \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev

# Configurer et installer les extensions PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql gd

# Créer un utilisateur non-root
RUN addgroup -g 1000 laravel && adduser -G laravel -g laravel -s /bin/sh -D laravel

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers depuis le build précédent
COPY --from=composer-build /app /var/www/html

# Créer les répertoires nécessaires et donner les bons droits
RUN mkdir -p storage/framework/{cache,data,sessions,testing,views} \
    && mkdir -p storage/logs bootstrap/cache \
    && chown -R laravel:laravel /var/www/html \
    && chmod -R 775 storage bootstrap/cache

USER laravel

EXPOSE 8000

# Commande par défaut (sera remplacée par docker-compose)
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]