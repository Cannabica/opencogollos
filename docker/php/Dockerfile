FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    nginx \
    supervisor \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libxml2-dev \
    oniguruma-dev \
    postgresql-dev \
    curl \
    git \
    unzip

RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    tokenizer \
    xml \
    ctype \
    openssl \
    gd \
    zip \
    bcmath

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY ./docker/php/nginx.conf /etc/nginx/nginx.conf
COPY ./docker/php/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY ./docker/php/php.ini /usr/local/etc/php/conf.d/php.ini

WORKDIR /var/www/html

EXPOSE 9000
EXPOSE 8000

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]