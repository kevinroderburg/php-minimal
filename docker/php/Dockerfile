FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install \
    pdo_mysql \
    intl \
    mbstring \
    zip \
    opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN pecl install xdebug-3.4.2 \
    && docker-php-ext-enable xdebug

RUN mkdir -p /log \
    && touch /log/xdebug.log \
    && chown www-data:www-data /log/xdebug.log    

COPY docker/php/conf.d/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
COPY docker/php/conf.d/php.ini /usr/local/etc/php/conf.d/php.ini