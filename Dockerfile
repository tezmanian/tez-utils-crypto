FROM php:8.3-cli-alpine

RUN apk add --no-cache git unzip openssl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN mkdir -p /tmp/composer && chown 1000:1000 /tmp/composer

WORKDIR /app
