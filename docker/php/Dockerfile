FROM php:8.3-fpm-alpine

RUN apk add --no-cache git unzip libpng-dev oniguruma-dev icu-dev $PHPIZE_DEPS \
    && docker-php-ext-install pdo_mysql bcmath intl gd \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

EXPOSE 9000
CMD ["php-fpm"]
