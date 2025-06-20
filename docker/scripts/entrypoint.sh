#!/bin/sh
if $APP_DEBUG; then
    echo "Debug mode is ON. Displaying all commands."
    set -x
fi

: ${APP_USER_ID:=1000}
: ${APP_GROUP_ID:=1000}

mkdir -p /var/www/html/storage/{app,framework/{sessions,views,cache},logs}
mkdir -p /var/www/html/.config/psysh
chown -R ${APP_USER_ID}:${APP_GROUP_ID} /var/www/html/storage /var/www/html/.config
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/.config

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
    php artisan tinker --execute='echo Schema::hasTable("'$1'") ? "true" : "false";'
}

# Función para verificar si una tabla tiene registros
check_table_has_records() {
    if [ "$(check_table_exists $1)" = "true" ]; then
        record_count=$(php artisan tinker --execute='echo DB::table("'$1'")->count();')
        return $record_count
    else
        echo '0'
    fi
}

# Ejecutar seeders solo si las tablas están vacías
echo "Verificando y ejecutando seeders..."


if [ "$(check_table_has_records users)" = "0" ]; then
    echo "Ejecutando seeder del superadmin..."
    php artisan db:seed --class=SuperAdminSeeder --force
else
    echo "La tabla 'users' ya tiene registros, no se ejecuta el seeder de SuperAdmin."
fi

if [ "$(check_table_has_records crop_plans)" = "0" ]; then
    echo "Ejecutando seeder de planes de cultivo..."
    php artisan db:seed --class=CropPlanSeeder --force
else
    echo "La tabla 'crop_plans' ya tiene registros, no se ejecuta el seeder de CropPlan."
fi

if [ "$(check_table_has_records seeds)" = "0" ]; then
    echo "Ejecutando seeder de semillas..."
    php artisan db:seed --class=SeedsSeeder --force
else
    echo "La tabla 'seeds' ya tiene registros, no se ejecuta el seeder de Seeds."
fi

if [ "$(check_table_has_records action_types)" = "0" ]; then
    echo "Ejecutando seeder de tipos de acción..."
    php artisan db:seed --class=ActionTypesSeeder --force
else
    echo "La tabla 'action_types' ya tiene registros, no se ejecuta el seeder de ActionTypes."
fi

# Verificar si se debe ejecutar el seeder de datos de ejemplo
if [ "${SEED_EXAMPLE_DATA}" = "true" ]; then
    echo "Ejecutando seeder de datos de ejemplo..."
    php artisan db:seed --class=ExampleDataSeeder --force
fi

# Iniciar queue worker en segundo plano
php artisan queue:work --daemon --sleep=3 --tries=3 &

echo "Registrando webhook de Telegram..."
php artisan telegram:webhook --setup

echo "Contenedor PHP-FPM listo. Iniciando PHP-FPM..."

# Ejecutar el comando principal (PHP-FPM)
exec "$@"