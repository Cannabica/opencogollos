#!/bin/sh

# Configurar variables por defecto si no están definidas
: ${APP_USER_ID:=1000}
: ${APP_GROUP_ID:=1000}

# Crear directorios necesarios con permisos correctos
mkdir -p /var/www/html/storage/{app,framework/{sessions,views,cache},logs}
chown -R ${APP_USER_ID}:${APP_GROUP_ID} /var/www/html/storage
chmod -R 775 /var/www/html/storage
find /var/www/html/storage -type d -exec chmod 775 {} \;
find /var/www/html/storage -type f -exec chmod 664 {} \;

# Configurar logs de PHP-FPM (solo si no existen)
if [ ! -d "/var/log/php-fpm" ]; then
    mkdir -p /var/log/php-fpm
    touch /var/log/php-fpm/error.log
    chown -R ${APP_USER_ID}:${APP_GROUP_ID} /var/log/php-fpm
    chmod -R 755 /var/log/php-fpm
    chmod 666 /var/log/php-fpm/error.log
fi

# Limpiar caché de Laravel (con manejo de errores)
if [ -f "artisan" ]; then
    php artisan config:clear || true
    php artisan cache:clear || true
    php artisan route:clear || true
    php artisan view:clear || true
fi

# Ejecutar migraciones
echo "Ejecutando migraciones..."
php artisan migrate --force

# Crear enlace simbólico de storage si no existe
if [ ! -L "public/storage" ]; then
    php artisan storage:link
fi

# Publicar assets de Livewire
php artisan vendor:publish --force --tag=livewire:assets

# Función para verificar si una tabla existe
check_table_exists() {
    local table=$1
    php artisan tinker --execute="echo Schema::hasTable('$table') ? 'true' : 'false';"
}

# Función para verificar si una tabla tiene registros
check_table_has_records() {
    local table=$1
    if [ "$(check_table_exists $table)" = "true" ]; then
        php artisan tinker --execute="echo DB::table('$table')->count();"
    else
        echo "0"
    fi
}

# Ejecutar seeders solo si las tablas están vacías
echo "Verificando y ejecutando seeders..."

if [ "$(check_table_has_records users)" = "0" ]; then
    echo "Ejecutando seeder del superadmin..."
    php artisan db:seed --class=SuperAdminSeeder --force
fi

if [ "$(check_table_has_records crop_plans)" = "0" ]; then
    echo "Ejecutando seeder de planes de cultivo..."
    php artisan db:seed --class=CropPlanSeeder --force
fi

if [ "$(check_table_has_records seeds)" = "0" ]; then
    echo "Ejecutando seeder de semillas..."
    php artisan db:seed --class=SeedsSeeder --force
fi

if [ "$(check_table_has_records action_types)" = "0" ]; then
    echo "Ejecutando seeder de tipos de acción..."
    php artisan db:seed --class=ActionTypesSeeder --force
fi

# Verificar si se debe ejecutar el seeder de datos de ejemplo
if [ "${SEED_EXAMPLE_DATA}" = "true" ] && [ "$(check_table_has_records crops)" = "0" ]; then
    echo "Ejecutando seeder de datos de ejemplo..."
    php artisan db:seed --class=ExampleDataSeeder --force
fi

# Iniciar queue worker en segundo plano
php artisan queue:work --daemon --sleep=3 --tries=3 &

# Iniciar PHP-FPM en modo daemon
php-fpm -D

# Esperar un momento para que PHP-FPM inicie completamente
sleep 2

# Verificar que PHP-FPM está corriendo
ps aux | grep php-fpm

# Mantener el contenedor vivo
tail -f /var/log/php-fpm/error.log