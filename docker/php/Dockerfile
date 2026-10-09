FROM php:8.3-fpm AS base

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libonig-dev \
        libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql gd mbstring opcache zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini

WORKDIR /var/www/html

FROM base AS dev

FROM base AS prod

COPY . /var/www/html
RUN mkdir -p /var/www/html/templates_c /var/www/html/public/uploads \
    && chown -R www-data:www-data /var/www/html/templates_c /var/www/html/public/uploads
