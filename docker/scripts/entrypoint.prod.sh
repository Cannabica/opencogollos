#!/bin/sh

# Configurar permisos básicos
mkdir -p /var/www/html/storage/{app,framework/{sessions,views,cache},logs}
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage

# Limpiar cachés
if [ -f "artisan" ]; then
    php artisan config:clear
    php artisan cache:clear
    php artisan route:clear
    php artisan view:clear
fi

# Ejecutar migraciones (solo en producción)
if [ "$APP_ENV" = "production" ]; then
    php artisan migrate --force
fi

# Crear enlace simbólico de storage si no existe
if [ ! -L "public/storage" ]; then
    php artisan storage:link
fi

# Iniciar queue worker en segundo plano
php artisan queue:work --daemon --sleep=3 --tries=3 &

# Ejecutar el comando principal (PHP-FPM)
exec "$@"