FROM php:8.3-fpm

ENV POSTGRES_PASSWORD=docker
ENV POSTGRES_DB=db
ENV POSTGRES_USER=docker


RUN apt-get update \
    && apt-get install -y \
        libpq-dev \
        git \
        unzip \
        libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql zip

RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Create uploads directory and set permissions
RUN mkdir -p /app/uploads && \
    chown -R www-data:www-data /app && \
    chmod -R 777 /app/uploads

COPY composer.json composer.lock ./

RUN composer install --no-scripts --no-autoloader

COPY . .

RUN composer dump-autoload --optimize

