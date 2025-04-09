FROM php:7.1.8-fpm-alpine AS base

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN docker-php-ext-configure pcntl --enable-pcntl \
  && docker-php-ext-install \
    pcntl \
    mysqli pdo pdo_mysql

RUN docker-php-ext-enable pdo_mysql
RUN docker-php-ext-enable mysqli

FROM base AS setup

COPY . /app

WORKDIR /app

RUN composer install
