FROM php:8.4-fpm

# Установка зависимостей
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libzip-dev \
    git \
    curl \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql zip \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

# Установка Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html