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

chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/.config

chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/.config

if [ -f "artisan" ]; then
    php artisan config:clear || true
    php artisan cache:clear || true
    php artisan route:clear || true
    php artisan view:clear || true
fi

# Esperar a que la base de datos esté lista y el usuario pueda conectarse
echo "Esperando a que la base de datos esté lista ($DB_HOST:$DB_PORT)..."
max_tries=30
count=0
until php artisan tinker --execute="DB::connection()->getPdo();" > /dev/null 2>&1 || [ $count -eq $max_tries ]; do
    echo "Base de datos no disponible todavía... esperando (intento $count/$max_tries)"
    sleep 2
    count=$((count + 1))
done

if [ $count -eq $max_tries ]; then
    echo "Error: No se pudo conectar a la base de datos después de $max_tries intentos."
    exit 1
fi

echo "¡Base de datos lista! Procediendo con migraciones..."

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
        >&2 echo "Tabla $1 existe"
        record_count=$(php artisan tinker --execute='echo DB::table("'$1'")->count();')
        >&2 echo "Registros en $1: $record_count"
        echo "$record_count"
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

echo "Sincronizando tipos de acción..."
php artisan db:seed --class=ActionTypesSeeder --force

# Verificar si se debe ejecutar el seeder de datos de ejemplo
if [ "${SEED_EXAMPLE_DATA}" = "true" ]; then
    echo "Ejecutando seeder de datos de ejemplo..."
    php artisan db:seed --class=ExampleDataSeeder --force
fi

# TODO queue worker in a separate container
php artisan queue:work --daemon --sleep=3 --tries=3 &

echo "Registrando webhook de Telegram..."
php artisan telegram:webhook --setup

echo "Contenedor PHP-FPM listo. Iniciando PHP-FPM..."

# Ejecutar el comando principal (PHP-FPM)
exec "$@"