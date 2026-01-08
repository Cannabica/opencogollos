FROM php:8.3-fpm-alpine AS builder

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
    unzip \
    nodejs \
    npm \

    
    zlib \
    zlib-dev \
    libjpeg \
    freetype \
    pkgconfig

RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-configure intl && \
    docker-php-ext-install -j$(nproc) \
        pdo pdo_pgsql pgsql mbstring xml gd zip bcmath intl

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-autoloader --no-scripts --prefer-dist

COPY package.json package-lock.json ./
RUN npm ci --only=production

COPY . .
RUN composer install --no-dev --optimize-autoloader && \
    npm run build && \
    rm -rf node_modules





FROM php:8.3-fpm-alpine

RUN apk update && apk add --no-cache \
    libzip \
    libpng \
    libjpeg-turbo \
    freetype \
    libxml2 \
    oniguruma \
    postgresql-libs \
    icu \
    unzip \
    zlib \
    libjpeg \
    pkgconfig

RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-configure intl && \
    docker-php-ext-install -j$(nproc) \
        pdo pdo_pgsql pgsql mbstring xml gd zip bcmath intl

COPY ./docker/php/php.ini /usr/local/etc/php/conf.d/php.ini

WORKDIR /var/www/html

COPY --from=builder /var/www/html/vendor ./vendor
COPY --from=builder /var/www/html/public/build ./public/build
COPY --from=builder /var/www/html/bootstrap ./bootstrap
COPY --from=builder /var/www/html/config ./config
COPY --from=builder /var/www/html/database ./database
COPY --from=builder /var/www/html/resources ./resources
COPY --from=builder /var/www/html/routes ./routes
COPY --from=builder /var/www/html/app ./app
COPY --from=builder /var/www/html/public ./public
COPY --from=builder /var/www/html/storage ./storage
COPY --from=builder /var/www/html/composer.json ./
COPY --from=builder /var/www/html/artisan ./

COPY ./docker/scripts/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm", "-F"]