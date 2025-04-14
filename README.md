# Cannabica Indoor SaaS

Sistema de gestión para cultivos indoor basado en Laravel y Filament.

## Requisitos

## Configuración del Entorno

El proyecto incluye archivos de ejemplo para configuración:

### Archivos .env
- `.env.example` - Configuración base para producción
- `.env.local.example` - Configuración para desarrollo local

Para comenzar:
```bash
cp .env.example .env       # Para producción
cp .env.local.example .env.local  # Para desarrollo local
```

### Configuración Nginx
- `default.conf.example` - Configuración para producción
- `default.local.conf.example` - Configuración para desarrollo local

Reemplazar los siguientes placeholders:
- `DOMINIO_PRODUCCION` - Tu dominio real en producción
- Rutas de certificados SSL en producción

### Variables Importantes
Asegúrate de configurar:
- Credenciales de base de datos
- Configuración de email
- Variables específicas de la aplicación
- Configuración de Docker (si aplica)


- PHP 8.3 o superior
- Composer
- Node.js 18 o superior
- SQLite3
- Docker (opcional)

## Instalación Local

> **Nota**: Para desarrollo local, usa `.env.local` y `default.local.conf` como base para tu configuración.

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

### Configuraciones Docker

El proyecto incluye dos archivos de configuración Docker:

1. `docker-compose.yml` - Para entornos de producción:
   - Configuración con SSL (Certbot)
   - PostgreSQL como base de datos
   - Uptime Kuma para monitoreo
   - Adminer para gestión de base de datos
   - Volúmenes persistentes para datos

2. `docker-compose.local.yml` - Para desarrollo local:
   - Configuración simplificada sin SSL
   - PostgreSQL con credenciales de desarrollo
   - Mailpit para testing de emails
   - Montaje de volumenes locales para desarrollo rápido

#### Comandos para Producción:
```bash
docker-compose -f docker-compose.yml up -d
```

#### Comandos para Desarrollo Local:
```bash
docker-compose -f docker-compose.local.yml up -d
```

#### Detalles Comunes:
- Ambos entornos usan el script `entrypoint.sh` que:
  1. Espera 2 segundos para asegurar que los servicios estén listos
  2. Ejecuta migraciones y seeders de la base de datos
  3. Inicia el servidor Laravel

#### Puertos Exposición:
- Producción:
  - APP: 8090 (HTTP), 8040 (HTTPS)
  - Adminer: 8080
  - Uptime Kuma: 3001

- Desarrollo Local:
  - APP: 8090
  - Adminer: 8080
  - Mailpit: 8025 (UI), 1025 (SMTP)

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
```


```
'name' => 'Tenant Example',
'email' => 'tenant@example.com',
'user_name' => 'User Tenant',
'user_email' => 'user@tenant.com',
'name' => 'Green Gardens Co.',
'email' => 'admin@greengardens.com',
'user_name' => 'Green Gardens Manager',
'user_email' => 'manager@greengardens.com',
'name' => 'Urban Cultivators',
'email' => 'contact@urbancultivators.com',
'user_name' => 'Urban Cultivator Admin',
'user_email' => 'admin@urbancultivators.com',
```            


- [Cafecito](https://cafecito.app/cannabica_app)

- [Cannabica.ar](https://cannabica.ar)	

- [Twitter](https://x.com/CannabicaApp)	

- [Facebook](https://www.facebook.com/profile.php?id=61574070621986)	

- [Instagram](https://www.instagram.com/cannabica.app3/)	

- [Servidor de discord](https://discord.gg/jN9Tje3eJe)