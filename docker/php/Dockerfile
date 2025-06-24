FROM php:8.3-fpm-alpine

RUN apk update && apk add --no-cache \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libxml2-dev \
    oniguruma-dev \
    postgresql-dev \
    curl \
    git \
    icu-dev \
    unzip

RUN docker-php-ext-configure gd --with-freetype --with-jpeg

RUN docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    xml \
    gd \
    zip \
    bcmath

RUN docker-php-ext-configure intl
RUN docker-php-ext-install intl

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ARG ENV=development

# Copy appropriate php.ini based on environment
COPY ./docker/php/php.${ENV}.ini /usr/local/etc/php/conf.d/php.ini

# Copy appropriate entrypoint based on environment
COPY ./docker/scripts/entrypoint.${ENV}.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

WORKDIR /var/www/html

EXPOSE 9000

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm", "-F"]