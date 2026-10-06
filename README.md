# OpenCogollos

**Registro, trazabilidad y compliance de cultivos** (indoor y exterior): plantas, acciones de
cuidado, tareas, planes de cultivo y un bot de Telegram para cargar todo desde el celular.

Laravel 10 + Filament 3, PHP 8.3, PostgreSQL. Multi-tenant (panel de superadministración +
panel de cultivador). Sin marca propia: es **marca blanca**, cada instalación le pone la suya
(ver [`docs/BRANDING.md`](docs/BRANDING.md)).

- Licencia: **AGPL-3.0-only** (ver [`LICENSE`](LICENSE))
- Mantenedor: **Cannabica** · `github.com/Cannabica/opencogollos`
- Imagen Docker: `ghcr.io/cannabica/opencogollos`

---

## Qué trae

- **Entornos de cultivo (indoors):** hardware, luces, sensores y parámetros basales.
- **Plantas y ciclo de vida:** seguimiento individual (germinación → plántula → vegetativo →
  floración), con calculadora de VPD y alertas.
- **Acciones y bitácora:** riegos, podas, transplantes, observaciones con foto; cada acción puede
  disparar transiciones de estado según el plan de la planta.
- **Semillas / genéticas:** variedades, perfil de cannabinoides, procedencia.
- **Planes de cultivo:** plantillas con fotoperiodo, rangos de temperatura, humedad y VPD.
- **Bot de Telegram:** consultar plantas/acciones/indoors, repetir el último riego y registrar
  observaciones **mandando una foto** ([`docs/TELEGRAM.md`](docs/TELEGRAM.md)).
- **Multi-tenant:** un panel de superadmin y un panel por cultivador, con datos aislados.

---

## Quickstart (Docker, ~2 minutos)

Necesitás **Docker** con Compose. No hace falta PHP, Composer ni Node en tu máquina: la imagen se
construye con todo adentro (`composer install` + `npm run build`).

```bash
git clone https://github.com/Cannabica/opencogollos.git
cd opencogollos
cp .env.local.example .env.local

# 1) generar la APP_KEY (indispensable: sin esto la app no arranca).
#    `key:generate` escribe .env, NO .env.local: acá se pide el valor con --show y va a tu .env.local.
#    El `--entrypoint php --no-deps` evita correr el entrypoint del contenedor (que migra la base)
#    sólo para imprimir una clave. OJO: este comando es el que CONSTRUYE la imagen la primera vez
#    (`composer install` + build de assets adentro): ahí está la espera larga del quickstart, no en el `up`.
docker compose -f docker-compose.local.yml --env-file .env.local run --rm --no-deps \
    --entrypoint php php artisan key:generate --show      # copiá esa salida a APP_KEY= en .env.local

# ...o hacé el pegado automático:
#   KEY=$(docker compose -f docker-compose.local.yml --env-file .env.local run --rm --no-deps \
#         --entrypoint php php artisan key:generate --show | tail -1)
#   sed -i "s|^APP_KEY=.*|APP_KEY=$KEY|" .env.local

# 2) levantar el stack
docker compose -f docker-compose.local.yml --env-file .env.local up -d --build

# 3) verificar que quedó arriba (tiene que dar 302 hacia /tenant/login)
curl -s -o /dev/null -w '%{http_code} %{redirect_url}\n' http://localhost:8090/
```

Listo. La app queda en:

| Servicio | URL |
|---|---|
| App | http://localhost:8090 |
| Mailpit (los mails no salen a internet, se ven acá) | http://localhost:8025 |
| Adminer (cliente de base de datos) | http://localhost:8080 |

El stack que levanta este compose es **liviano**: `caddy` (web), `php` (app), `db` (PostgreSQL) y
`mailpit` (mails de prueba). No incluye el stack de observabilidad.

### Si algo no anda

| Síntoma | Causa más probable | Qué hacer |
|---|---|---|
| No responde o **502** | El contenedor `php` no terminó de arrancar | `docker compose -f docker-compose.local.yml --env-file .env.local logs php` — tiene que llegar a `ready to handle connections`. Si dice `Migración fallida`, la base no está lista: mirá `logs db` |
| **500** `No application encryption key has been specified` | La `APP_KEY` de `.env.local` quedó vacía | repetí el paso 1 |
| **Puertos ocupados** | Ya tenés algo en 8090/8080/8025 | en `.env.local`: `APP_HTTP_PORT` y `APP_HTTPS_PORT` (el HTTPS tiene que ser el HTTP + 10000); y los puertos de `adminer`/`mailpit` en el compose |
| La app se ve **sin estilos** | Se montó el `public/` de tu host (un clone no tiene `npm run build`) | no uses el override de desarrollo (abajo) para una instalación normal |

> **¿Por qué anda sin PHP ni Node en tu máquina?** El compose local monta **sólo el código** del repo:
> las dependencias (`vendor/`, `node_modules/`) y los assets compilados (`public/build/`) los toma de
> la **imagen**, por volúmenes que Docker inicializa con su contenido. Montarlos desde tu host
> (`- ./vendor:/...`) sobre un clone limpio deja carpetas vacías encima de las deps de la imagen:
> `php artisan` devuelve 255, el contenedor muere en las migraciones y el proxy devuelve 502. Fue un
> bug real, corregido el 2026-09-27.

### Creá tu usuario (la base arranca vacía)

El contenedor **migra pero no siembra**: después del quickstart vas a tener las tablas creadas y
**cero usuarios**, así que todavía no podés entrar. Poné tu email y una clave en `.env.local`
(`ADMIN_EMAIL` y `ADMIN_PASSWORD`), volvé a levantar el stack para que el contenedor lea el cambio, y
corré el seeder:

```bash
docker compose -f docker-compose.local.yml --env-file .env.local up -d
docker compose -f docker-compose.local.yml --env-file .env.local exec php \
    php artisan db:seed --class=SuperAdminSeeder
```

¿Preferís arrancar con **datos de ejemplo** en vez de una base vacía? El default trae una demo
completa (5 perfiles de cultivador, con sus entornos, plantas y acciones):

```bash
docker compose -f docker-compose.local.yml --env-file .env.local exec php \
    php artisan migrate:fresh --seed
```

- Panel de administración: http://localhost:8090/superadmin
- Panel de cultivador: http://localhost:8090/tenant

Para frenar todo: `docker compose -f docker-compose.local.yml down` (agregá `-v` si además querés
borrar la base de datos).

### Desarrollo (hot-reload)

Si vas a **modificar el código**, montá el repo desde tu host con el override:

```bash
composer install && npm install && npm run build     # tu host necesita las deps y los assets
docker compose -f docker-compose.local.yml -f docker-compose.dev.yml --env-file .env.local up -d
```

El override agrega los bind mounts de `.:/var/www/html`, `vendor/`, `node_modules/`, `public/` y
`storage/`. Si te olvidás del `composer install`/`npm run build`, vas a ver el mismo 502 de arriba:
ese camino **sí** exige las dependencias en el host.

---

## Quickstart (nativo, sin Docker)

Necesitás **PHP 8.3** (con `pdo_pgsql`, `intl`, `gd`, `zip`, `bcmath`, `xml`, `mbstring`),
**Composer 2**, **Node 20+** y una **PostgreSQL** accesible.

```bash
git clone https://github.com/Cannabica/opencogollos.git
cd opencogollos

composer install
npm install

cp .env.example .env
php artisan key:generate

# en .env: DB_CONNECTION=pgsql, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
php artisan migrate --seed

npm run build
php artisan serve
```

La app queda en `http://127.0.0.1:8000`.

### Variante sin base de datos (SQLite)

Si no querés levantar PostgreSQL, en teoría alcanza con:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=/ruta/absoluta/a/database/database.sqlite
```

```bash
touch database/database.sqlite
php artisan migrate --seed
```

> ⚠️ **Pendiente.** En el estado actual del repo esta ruta **falla**: la migración
> `2025_04_26_084932_remove_owner_id_from_tenants_table` usa `dropForeign()`, que SQLite no
> soporta (`SQLite doesn't support dropping foreign keys`). El fix está en camino; hasta que
> entre, usá PostgreSQL (las dos rutas de arriba). No la documentamos como verificada a
> propósito.

Detalle completo de las dos rutas, variables de entorno y troubleshooting:
**[`docs/INSTALL.md`](docs/INSTALL.md)**.

---

## Crear tu propio bot de Telegram

El bot no viene incluido: cada instalación usa el suyo.

1. Hablale a **@BotFather** en Telegram → `/newbot` → te da un **token**.
2. Poné el token en tu `.env`:

   ```dotenv
   TELEGRAM_BOT_TOKEN=<el token que te dio BotFather>
   ```

3. Registrá el webhook (necesita una URL pública con **HTTPS**):

   ```bash
   php artisan telegram:webhook:setup          # registrar
   php artisan telegram:webhook:setup --info   # ver estado, errores y updates pendientes
   ```

4. Los usuarios se autentican con `/auth <token>`, que generan desde **Mi grupo** en el panel.

Guía completa — comandos, bot de administración, túnel para desarrollo local y troubleshooting:
**[`docs/TELEGRAM.md`](docs/TELEGRAM.md)**.

---

## Poner tu marca

OpenCogollos no impone marca. `APP_NAME` define el nombre de tu instalación y las 9 claves
`PLATFORM_*` (opcionales) las superficies: tu web, tu página de estado, tu Discord, tu logo del
header de los mails. Vacías, la app funciona igual pero sin esos bloques.

```dotenv
APP_NAME=Mi Cultivo
PLATFORM_SITE_URL=https://micultivo.example
PLATFORM_STATUS_PAGE_URL=https://status.micultivo.example
PLATFORM_ADMIN_EMAIL=admin@micultivo.example
PLATFORM_TELEGRAM_BOT_USERNAME=micultivo_bot
```

Tabla completa, ejemplo neutro vs. con marca y cómo aplicar los cambios:
**[`docs/BRANDING.md`](docs/BRANDING.md)**.

---

## Documentación

| Documento | Contenido |
|---|---|
| [`docs/INSTALL.md`](docs/INSTALL.md) | Docker o nativo, paso a paso: requisitos, variables de entorno, tus primeros usuarios y troubleshooting |
| [`docs/BRANDING.md`](docs/BRANDING.md) | Cómo hacer tuya la instalación: `APP_NAME` y las 9 claves `PLATFORM_*`. Vacías, la app funciona igual, en neutro |
| [`docs/TELEGRAM.md`](docs/TELEGRAM.md) | Tu bot con BotFather: token, webhook, comandos y cómo se autentica cada usuario |

## Comandos útiles

```bash
php artisan migrate:fresh --seed   # recrear la base y cargar datos de demo
php artisan optimize:clear         # limpiar todas las cachés (config, rutas, vistas)
php artisan test --testdox         # correr la suite de tests
php artisan telegram:commands:list # ver los comandos del bot registrados
```

---

## Mantenedor y comunidad

OpenCogollos es software libre bajo **AGPL-3.0-only**, mantenido por **Cannabica**.

- **Issues y propuestas:** abrí un issue en este repositorio. Es el canal para reportar bugs,
  pedir funcionalidad y preguntar.
- **Comunidad:** el Discord de **Cannabica**, el mantenedor — <https://discord.com/invite/jN9Tje3eJe>.
  (Es la comunidad del proyecto, no un bloque de la app: si montás tu propia instancia y querés tu
  propio Discord, se configura con `PLATFORM_DISCORD_URL`; vacío, la app no muestra el link.)
- **Contribuciones:** rama desde `develop` → cambios con tests → pull request. El CI corre la
  suite y el análisis estático en cada PR; se mergea con CI verde.
- **Verificación del repo:** `scripts/verificacion-integral.sh` corre el checklist (clone fresco,
  fugas de infraestructura o de marca en el historial y en el árbol, app levantada sin ninguna marca
  configurada).
- **Qué implica la AGPL:** si corrés una versión modificada de OpenCogollos como servicio en red,
  tenés que ofrecer a tus usuarios el código fuente de esa versión modificada.

¿Sos una institución, cooperativa o proyecto que quiere su propia instancia? Abrí un issue y
contanos el caso.
