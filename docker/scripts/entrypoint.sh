#!/bin/sh
# Set default values for critical environment variables
: ${APP_DEBUG:=false}
: ${SEED_EXAMPLE_DATA:=false}

if [ "${APP_DEBUG}" = "true" ]; then
    echo "Debug mode is ON. Displaying all commands."
    set -x
fi

mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/app/public
mkdir -p /var/www/html/storage/logs

mkdir -p /var/www/html/bootstrap/cache
mkdir -p /var/www/html/.config/psysh

if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data /var/www/html/storage
    chown -R www-data:www-data /var/www/html/bootstrap/cache
    chown -R www-data:www-data /var/www/html/.config
fi

chmod -R 775 /var/www/html/storage 2>/dev/null || true
chmod -R 775 /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/.config 2>/dev/null || true

if [ -f "artisan" ]; then
    php artisan package:discover || true

    # Limpiar caches viejos por si cambiaron env vars
    php artisan config:clear || true
    php artisan route:clear || true
    php artisan event:clear || true

    # Esperar a que la DB esté disponible
    max_tries=30
    count=0
    while ! php artisan migrate --force; do
        count=$((count + 1))
        if [ $count -ge $max_tries ]; then
            echo "ERROR: php artisan migrate falló después de $max_tries intentos"
            exit 1
        fi
        echo "Migración fallida, reintentando ($count/$max_tries)..."
        sleep 3
    done

    # Cachear config para rendimiento en producción (las env vars ya están disponibles del docker-compose)
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan event:cache || true

    if [ "${SEED_EXAMPLE_DATA}" = "true" ]; then
        php artisan db:seed --class=ExampleDataSeeder || true
    fi

    # Forzar carga de config en OPcache
    php artisan optimize || true
fi

exec "$@"
