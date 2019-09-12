FROM php:5.6.40-fpm-alpine

ENV LANG=C.UTF-8

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN apk add --no-cache --update --virtual .phpize-deps $PHPIZE_DEPS \
    && pecl install redis-3.1.6 \
    && docker-php-ext-enable redis \
    && apk del .phpize-deps

# ENV APP_DIR /var/www/src
# WORKDIR ${APP_DIR}
# COPY ./app ${APP_DIR}