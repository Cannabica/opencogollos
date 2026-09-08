# Cannabica OpenIndoor SaaS

Sistema de gestión para cultivos indoor basado en Laravel y Filament.

## 🚀 Características Principales

- **Dashboard:** Onboarding rápido, configuración de bot de Telegram y acciones rápidas.
- **Indoors:** Define el hardware y los entornos (Watts, tipo de LEDs, Sensores) además de establecer parámetros basales.
- **Semillas:** Base de datos genética detallada de variedades cultivadas, perfil de cannabinoides (THC/CBD), procedencia.
- **Ciclo de Vida de Plantas:** Seguimiento individual continuo trazando el progreso biológico de las plantas (Germinación -> Plántula -> Vegetativo -> Floración).
- **Planes de Cultivo:** Plantillas maestras de cultivo (fotoperiodo óptimo, rangos de temperatura, humedad, VPD y recuperación).
- **Sistema de Acciones y Bitácora:** Registro de riegos o podas que disparan transiciones en el estado lógico de cada planta acorde a su plan.

## ⚙️ Configuración Inicial

Para arrancar necesitas configurar el archivo de variables de entorno:

```bash
cp .env.local.example .env
```

**Variables importantes a verificar:**
- Configuración de conexión de base de datos (`DB_CONNECTION`, `DB_HOST`, etc.).
- **TELEGRAM_BOT_TOKEN** para habilitar y probar envío de alertas locales.

## 💻 Instalación Local (Entorno nativo sin Docker)

1. Clonar el repositorio e ingresar:
```bash
git clone <repository-url>
cd OpenIndoor
```

2. Instalar todas las dependencias del proyecto:
```bash
composer install
npm install
```

3. Preparar Laravel y la base SQLite por defecto (rápido):
```bash
php artisan key:generate
touch database/database.sqlite
```
*(Asegúrate de cambiar `DB_CONNECTION=sqlite` en tu `.env` y eliminar las variables `DB_HOST`/`DB_PORT` si no las usas)*.

4. Ejecutar las migraciones y sembrar datos de prueba (seeders):
```bash
php artisan migrate --seed
```

5. Inicializar la app:
```bash
npm run build
php artisan serve
```

## 🐳 Instalación con Docker (Desarrollo Local)

El proyecto incluye un entorno preconfigurado que facilita levantar todos los servicios juntos en 2 pasos usando `docker-compose.local.yml`; esto incluye la App, Base de Datos, Caddy (Servidor) y Mailpit para mock de emails.

1. Otorgar permisos y ejecutar el script de construcción de la imagen inicial:
```bash
chmod +x build.sh
./build.sh
```
*(Este script crea contenedores temporales para instalar el bloque de dependencias de Composer y NPM limpiamente).*

2. Levantar el entorno local:
```bash
docker-compose -f docker-compose.local.yml up -d
```

> **Nota:** La automatización `entrypoint.sh` dentro del contenedor se encarga de correr `php artisan migrate --seed` automáticamente al arranque, luego de asegurar que la DB esté receptiva.

**Puertos y Servicios Excluidos Localmente:**
- **APP (Caddy):** `localhost:8090`
- **Adminer (DB UI):** `localhost:8080`
- **Mailpit:** `localhost:8025` (UI web) y `1025` (SMTP interno).

## 🔑 Credenciales de Prueba por Defecto

Después de correr los seeders, el sistema queda con la siguiente información poblada para acceder a los paneles:

- **Panel Superadmin**
  - URL: `/superadmin`
  - Email: `test@example.com`
  - Pass: `password`

- **Panel Tenant / Cultivador**
  - URL: `/tenant` (o `/` directo dependiendo rutas)
  - Email: `user@tenant.com`
  - Pass: `password`

## 🛠 Comandos Útiles en Desarrollo

```bash
# Limpiar toda la base de datos y recrear registros desde los comandos seeder:
php artisan migrate:fresh --seed

# Limpiar cacheos completos frente a errores visuales o lógicos de vistas
php artisan optimize:clear
```

## 🌐 Enlaces Oficiales
- [Cafecito (Apoyo al proyecto)](https://cafecito.app/cannabica_app)
- [Página central: Cannabica.ar](https://cannabica.ar)
- [Unirse a Discord](https://discord.gg/jN9Tje3eJe)
- [Instagram @cannabica.app3](https://www.instagram.com/cannabica.app3/)
- [Twitter/X](https://x.com/CannabicaApp)
- [Facebook Oficial](https://www.facebook.com/profile.php?id=61574070621986)