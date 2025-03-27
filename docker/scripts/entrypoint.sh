#!/bin/bash

# Crear directorios de logs y establecer permisos
mkdir -p /var/log/nginx
mkdir -p /var/log/php-fpm
chown -R www-data:www-data /var/log/nginx /var/log/php-fpm
chmod -R 755 /var/log/nginx /var/log/php-fpm

# Iniciar PHP-FPM en segundo plano
php-fpm -D

# Esperar un momento para asegurarse de que PHP-FPM esté listo
sleep 2

# Ejecutar solo las migraciones pendientes sin revertir las existentes
php artisan migrate --force

php artisan vendor:publish --force --tag=livewire:assets
php artisan storage:link
# Ejecutar el seeder del superadmin
php artisan db:seed --class=SuperAdminSeeder --force
php artisan db:seed --class=CropPlanSeeder --force
php artisan db:seed --class=SeedsSeeder --force

# Verificar si se debe ejecutar el seeder de datos de ejemplo
if [ "${SEED_EXAMPLE_DATA}" = "true" ]; then
    echo "Ejecutando seeder de datos de ejemplo..."
    php artisan db:seed --class=ExampleDataSeeder --force
fi

# Iniciar nginx en primer plano
exec nginx -g "daemon off;" 