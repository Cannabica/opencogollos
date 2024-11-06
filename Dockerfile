FROM php:8.3-fpm

# Set Environment Variables
ENV DEBIAN_FRONTEND noninteractive
ENV COMPOSER_ALLOW_SUPERUSER=1

# Software’s Installation
# Installing tools and PHP extentions using “apt”, “docker-php”, “pecl”,
# Install “curl”, “libmemcached-dev”, “libpq-dev”, “libjpeg-dev”,
# “libpng-dev”, “libfreetype6-dev”, “libssl-dev”, “libmcrypt-dev”,

RUN set -eux; \
apt-get update; \
apt-get upgrade -y; \
apt-get install -y --no-install-recommends \
curl \
libmemcached-dev \
libz-dev \
libpq-dev \
libjpeg-dev \
libpng-dev \
libfreetype6-dev \
libssl-dev \
libwebp-dev \
libxpm-dev \
libmcrypt-dev \
libonig-dev \
git \
curl \
zip \
zlib1g-dev \
libicu-dev \
g++ \
unzip;
RUN rm -rf /var/lib/apt/lists/*

RUN set -eux; \
# Install the PHP pdo_mysql extention
docker-php-ext-install pdo_mysql; \
# Install the PHP pdo_pgsql extention
docker-php-ext-install pdo_pgsql; \
# Install the PHP gd library
docker-php-ext-configure gd \
--prefix=/usr \
--with-jpeg \
--with-webp \
--with-xpm \
--with-freetype; \
docker-php-ext-install gd;

RUN docker-php-ext-configure intl

RUN docker-php-ext-install intl

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands change cannabica-user for your username
RUN useradd -G www-data,root -u 1000 -d /home/cannabica-user cannabica-user
RUN mkdir -p /home/cannabica-user/.composer && \
chown -R cannabica-user:cannabica-user /home/cannabica-user

WORKDIR /var/www

COPY composer.json composer.lock ./

RUN composer install --no-dev --optimize-autoloader --no-scripts --ignore-platform-req=ext-zip

COPY . .

RUN chown -R 1000:1000 /var/www

CMD php artisan serve --host=0.0.0.0 --port 8088