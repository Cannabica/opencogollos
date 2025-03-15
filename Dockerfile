FROM php:8.3-fpm

# Set Environment Variables
ENV DEBIAN_FRONTEND=noninteractive

# Install system dependencies and extensions
RUN apt-get update && apt-get install -y \
    git \
    curl \
    sqlite3 \
    libsqlite3-dev \
    libicu-dev \
    unzip \
    && docker-php-ext-install pdo_sqlite intl \
    && rm -rf /var/lib/apt/lists/*

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

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
COPY entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

# Create storage directory and set permissions
RUN mkdir -p storage/framework/{sessions,views,cache} \
    && mkdir -p storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Create database directory
RUN mkdir -p database \
    && chown -R www-data:www-data database \
    && chmod -R 775 database

# Health check
HEALTHCHECK --interval=30s --timeout=30s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8088/health || exit 1

# Expose port
EXPOSE 8088

# Start server using entrypoint script
CMD ["/usr/local/bin/entrypoint.sh"]