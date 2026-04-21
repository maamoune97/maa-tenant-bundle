FROM php:8.5-cli-alpine

RUN apk add --no-cache \
    $PHPIZE_DEPS \
    libpq-dev \
    icu-dev \
    libzip-dev \
    libxml2-dev \
    git \
    unzip \
 && docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    intl \
    zip \
    xml \
    pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

CMD ["tail", "-f", "/dev/null"]