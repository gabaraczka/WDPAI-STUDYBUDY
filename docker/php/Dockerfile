FROM php:8.3-fpm

ENV POSTGRES_PASSWORD=docker
ENV POSTGRES_DB=db
ENV POSTGRES_USER=docker

# Install system dependencies
RUN apt-get update \
    && apt-get install -y \
        libpq-dev \
        git \
        unzip \
        libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql zip

# Install additional PHP extensions if needed
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --no-scripts --no-autoloader

# Copy the rest of the application
COPY . .

# Generate autoloader
RUN composer dump-autoload --optimize

