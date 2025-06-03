FROM php:8.3-fpm

ENV POSTGRES_PASSWORD=docker
ENV POSTGRES_DB=db
ENV POSTGRES_USER=docker

RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

