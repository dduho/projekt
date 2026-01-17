FROM php:8.2-fpm-alpine

LABEL maintainer="PRISM Development Team"
LABEL description="PHP-FPM container for PRISM application"

# Arguments pour le build
ARG WWWGROUP=1000
ARG WWWUSER=1000

# Variables d'environnement
ENV PHP_OPCACHE_ENABLE=1
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS=0

# Installation des dependances systeme
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    postgresql-dev \
    icu-dev \
    oniguruma-dev \
    linux-headers \
    $PHPIZE_DEPS

# Configuration et installation des extensions PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# Installation de Redis extension
RUN pecl install redis-6.0.2 \
    && docker-php-ext-enable redis

# Installation de PCOV pour le code coverage (dev)
RUN pecl install pcov \
    && docker-php-ext-enable pcov

# Nettoyage
RUN apk del $PHPIZE_DEPS \
    && rm -rf /var/cache/apk/* /tmp/*

# Installation de Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Creation de l'utilisateur www-data avec le bon UID/GID
RUN addgroup -g ${WWWGROUP} -S www \
    && adduser -u ${WWWUSER} -S www -G www

# Configuration PHP optimisee
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Repertoire de travail
WORKDIR /var/www

# Copie des fichiers de l'application
COPY --chown=www:www . .

# Installation des dependances PHP
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev \
    && composer clear-cache

# Permissions
RUN chown -R www:www /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Utilisateur non-root
USER www

EXPOSE 9000

CMD ["php-fpm"]
