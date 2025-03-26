# Cannabica Indoor SaaS

Sistema de gestión para cultivos indoor basado en Laravel y Filament.

## Requisitos

- PHP 8.3 o superior
- Composer
- Node.js 18 o superior
- SQLite3
- Docker (opcional)

## Instalación Local

1. Clonar el repositorio:
```bash
git clone <repository-url>
cd indoor-saas
```

2. Instalar dependencias:
```bash
composer install
npm install
```

3. Configurar el entorno:
```bash
cp .env.example .env
php artisan key:generate
```

4. Configurar la base de datos SQLite:
```bash
# En el archivo .env
DB_CONNECTION=sqlite
DB_DATABASE=../database/cannabica_db.sqlite

# Crear el archivo de base de datos
touch database/cannabica_db.sqlite
```

5. Ejecutar migraciones y seeders:
```bash
php artisan migrate --seed
```

6. Compilar assets:
```bash
npm run build
```

7. Iniciar el servidor:
```bash
php artisan serve
```

## Instalación con Docker

### Método Automatizado (Recomendado)

El proyecto incluye un script `build.sh` que automatiza el proceso de construcción:

```bash
# Dar permisos de ejecución al script
chmod +x build.sh

# Ejecutar el script de construcción
./build.sh
```

El script realiza las siguientes acciones:
1. Crea un contenedor temporal para instalar dependencias de Composer
2. Crea un contenedor temporal para instalar dependencias NPM y construir assets
3. Construye la imagen final del proyecto

### Ejecutar el Contenedor (Desarrollo Local)

```bash
docker run --rm -d --name cannabica-app \
    -v $(pwd)/cannabica_db.sqlite:/var/www/database/cannabica_db.sqlite \
    -v $(pwd)/.env:/var/www/.env \
    -p 8000:8088 cannabica-app
```

Este comando:
- Monta la base de datos SQLite local
- Monta el archivo .env para configuración
- Mapea el puerto 8000 local al 8088 del contenedor
- Ejecuta en modo detached (-d)

### Detalles del Contenedor

El contenedor utiliza un script `entrypoint.sh` que:
1. Espera 2 segundos para asegurar que todos los servicios estén listos
2. Ejecuta las migraciones y seeders de la base de datos
3. Inicia el servidor Laravel en el puerto 8088

## CI/CD Pipeline

El proyecto utiliza GitHub Actions para la integración y despliegue continuo. El pipeline se activa en:

- Push a `main`, `develop`, `feature/*`, y `hotfix/*`
- Pull requests a `main` y `develop`

### Etapas del Pipeline

1. **Build**
   - Configuración de PHP 8.3 y extensiones
   - Instalación de dependencias
   - Configuración del entorno
   - Construcción de assets frontend
   - Migraciones de base de datos

2. **Versionado**
   - Genera tags automáticos basados en la rama:
     - `main` → `vX.Y.Z`
     - `develop` → `vX.Y.Z-beta`
     - `feature/*` → `vX.Y.Z-alpha`
     - `hotfix/*` → `vX.Y.Z-hotfix`

3. **Docker**
   - Se ejecuta solo en push a `main` o `develop`
   - Construye y publica la imagen en Docker Hub
   - Tags: `latest` y SHA del commit

## Credenciales de Prueba

### Panel de Superadmin
- URL: `/superadmin`
- Email: test@example.com
- Password: password

### Panel de Tenant
- URL: `/tenant`
- Email: user@tenant.com
- Password: password

## Comandos Útiles

### Base de Datos
```bash
# Recrear base de datos y seedear
php artisan migrate:fresh --seed

# Ejecutar migraciones y seeders por separado
php artisan migrate:fresh
php artisan db:seed

# Ejecutar un seeder específico
php artisan db:seed --class=NombreDelSeeder
```

## Salud del Sistema

El contenedor Docker incluye un health check que verifica el estado del servicio cada 30 segundos en:
```
http://localhost:8088/health
```

### Monitoreo del Contenedor
```bash
# Ver logs del contenedor
docker logs cannabica-app

# Ver estado del contenedor
docker ps -a | grep cannabica-app

# Entrar al contenedor
docker exec -it cannabica-app bash