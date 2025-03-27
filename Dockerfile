FROM php:8.3-fpm

# Set Environment Variables
ENV DEBIAN_FRONTEND=noninteractive

# Install system dependencies and extensions
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpq-dev \
    libicu-dev \
    libzip-dev \
    unzip \
    nginx \
    && docker-php-ext-install pdo_pgsql intl \
    && docker-php-ext-configure zip \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --no-scripts --no-autoloader

# Copy application files
COPY . .

# Generate optimized autoload files and cache configuration
RUN composer dump-autoload --optimize \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Set up entrypoint script
COPY docker/scripts/entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

# Create storage directory and set permissions
RUN mkdir -p storage/framework/{sessions,views,cache} \
    && mkdir -p storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Configure PHP-FPM
RUN echo "pm.max_children = 50" >> /usr/local/etc/php-fpm.d/zz-docker.conf \
    && echo "pm.start_servers = 5" >> /usr/local/etc/php-fpm.d/zz-docker.conf \
    && echo "pm.min_spare_servers = 5" >> /usr/local/etc/php-fpm.d/zz-docker.conf \
    && echo "pm.max_spare_servers = 35" >> /usr/local/etc/php-fpm.d/zz-docker.conf \
    && echo "listen = /var/run/php-fpm.sock" >> /usr/local/etc/php-fpm.d/zz-docker.conf \
    && echo "listen.owner = www-data" >> /usr/local/etc/php-fpm.d/zz-docker.conf \
    && echo "listen.group = www-data" >> /usr/local/etc/php-fpm.d/zz-docker.conf \
    && echo "listen.mode = 0660" >> /usr/local/etc/php-fpm.d/zz-docker.conf

# Health check
HEALTHCHECK --interval=30s --timeout=30s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# Expose port
EXPOSE 80

# Start server using entrypoint script
CMD ["/usr/local/bin/entrypoint.sh"]