FROM php:8.3-fpm

# Set Environment Variables
ENV DEBIAN_FRONTEND=noninteractive

# Install system dependencies and extensions
RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    libicu-dev \
    && docker-php-ext-install pdo_sqlite intl \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . .

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