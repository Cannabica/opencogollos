FROM php:8.3-fpm

# Set Environment Variables
ARG HOST_UID=1000
ARG HOST_GID=1000
ENV DEBIAN_FRONTEND=noninteractive
ENV APP_USER_ID=${HOST_UID}
ENV APP_GROUP_ID=${HOST_GID}

# Install system dependencies and extensions
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpq-dev \
    libicu-dev \
    libzip-dev \
    unzip \
    nginx \
    procps \
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

# Create storage directories and set permissions
RUN groupadd -g ${HOST_GID} appuser || true && \
    useradd -u ${HOST_UID} -g ${HOST_GID} -d /home/appuser -m appuser || true && \
    mkdir -p storage/framework/{sessions,views,cache} \
    && mkdir -p storage/logs \
    && mkdir -p /var/log/php-fpm \
    && touch /var/log/php-fpm/error.log \
    && chown -R ${HOST_UID}:${HOST_GID} storage /var/log/php-fpm \
    && find storage -type d -exec chmod 775 {} \; \
    && find storage -type f -exec chmod 664 {} \; \
    && chmod -R 755 /var/log/php-fpm


# Health check
HEALTHCHECK --interval=30s --timeout=30s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# Expose port
EXPOSE 80

# Start server using entrypoint script
CMD ["/usr/local/bin/entrypoint.sh"]