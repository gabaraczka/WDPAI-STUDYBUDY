FROM php:8.3-fpm

ENV POSTGRES_PASSWORD=docker
ENV POSTGRES_DB=db
ENV POSTGRES_USER=docker

# Install PostgreSQL development files and other dependencies
RUN apt-get update \
    && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Install additional PHP extensions if needed
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

