FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    git \
    curl \
    icu-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libzip-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    postgresql-client \
    postgresql-dev

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd intl zip opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/backend

COPY docker/php/local.ini /usr/local/etc/php/conf.d/99-accommity.ini
COPY docker/backend-entrypoint.dev.sh /usr/local/bin/backend-entrypoint
RUN chmod +x /usr/local/bin/backend-entrypoint

EXPOSE 9000

ENTRYPOINT ["backend-entrypoint"]
CMD ["php-fpm"]
