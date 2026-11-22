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

COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --no-progress --optimize-autoloader

COPY backend/ ./
# Ne jamais embarquer les caches générés sur le poste de développement :
# ils peuvent référencer des packages dev absents de l'image de déploiement.
RUN rm -f bootstrap/cache/*.php \
    && rm -rf storage/framework/cache/* storage/framework/sessions/* storage/framework/views/* storage/logs/* \
    && composer dump-autoload --no-dev --optimize --no-scripts

COPY docker/php/local.ini /usr/local/etc/php/conf.d/99-accommity.ini
COPY docker/backend-entrypoint.prod.sh /usr/local/bin/backend-entrypoint
RUN chmod +x /usr/local/bin/backend-entrypoint \
    && mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

ENTRYPOINT ["backend-entrypoint"]
CMD ["php-fpm"]
