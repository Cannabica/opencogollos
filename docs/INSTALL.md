# Instalación

Guía de instalación de OpenCogollos para self-hosters. Si sólo querés ver la app corriendo en tu
máquina, el quickstart del [README](../README.md) alcanza; acá está el detalle.

---

## 1. Requisitos

| Camino | Qué necesitás |
|---|---|
| **Docker** (recomendado) | Docker 24+ con el plugin `compose`. **Nada más**: PHP, Composer y Node van dentro de la imagen. |
| **Nativo** | PHP **8.3** + Composer 2 + Node 20+ + PostgreSQL 13+. |

Extensiones de PHP para el camino nativo: `pdo_pgsql`, `pgsql`, `mbstring`, `xml`, `gd`, `zip`,
`bcmath`, `intl`.

Las dependencias se instalan **desde packagist** (el `composer.json` no usa repositorios privados),
así que no necesitás credenciales de GitHub para `composer install`.

---

## 2. Camino A — Docker (compose liviano)

El repo trae `docker-compose.local.yml`: `caddy` (web), `php` (app), `db` (PostgreSQL 15) y
`mailpit` (mails de prueba). Sin stack de observabilidad.

```bash
git clone https://github.com/Cannabica/opencogollos.git
cd opencogollos
cp .env.local.example .env.local
```

**Generá la `APP_KEY`** (sin esto la app no arranca: `No application encryption key has been
specified`). Ojo: `php artisan key:generate` escribe en `.env`, y en el camino Docker la config
del contenedor sale de `.env.local` — así que pedile la clave con `--show` y pegala:

```bash
docker compose -f docker-compose.local.yml --env-file .env.local run --rm --no-deps \
    --entrypoint php php artisan key:generate --show
# copiá la salida (base64:...) a APP_KEY= en .env.local
```

El `--entrypoint php --no-deps` evita correr el entrypoint del contenedor (que migra y siembra) sólo
para imprimir una clave.

O en un solo paso:

```bash
KEY=$(docker compose -f docker-compose.local.yml --env-file .env.local run --rm --no-deps \
      --entrypoint php php artisan key:generate --show | tail -1)
sed -i "s|^APP_KEY=.*|APP_KEY=$KEY|" .env.local
grep -q '^APP_KEY=base64:' .env.local && echo "APP_KEY OK"
```

Después:

```bash
docker compose -f docker-compose.local.yml --env-file .env.local up -d --build
```

La imagen **ya se compiló en el paso de la `APP_KEY`** (ese `docker compose run` es el que instala
Composer y compila los assets), por eso este `up` es rápido. El `entrypoint` del contenedor espera a
que PostgreSQL responda y corre las migraciones solo — **migra, no siembra**: la base queda con las
tablas y **cero usuarios**, así que para entrar tenés que crear tu admin (`ADMIN_EMAIL`/`ADMIN_PASSWORD`
+ `db:seed --class=SuperAdminSeeder`) o cargar la demo con `migrate:fresh --seed`. Cuando termina:

| Servicio | URL |
|---|---|
| App | http://localhost:8090 |
| Mailpit | http://localhost:8025 |
| Adminer | http://localhost:8080 |

Verificación rápida:

```bash
docker compose -f docker-compose.local.yml ps
docker compose -f docker-compose.local.yml logs php | tail -20
curl -I http://localhost:8090/superadmin/login
```

Comandos dentro del contenedor: anteponé
`docker compose -f docker-compose.local.yml --env-file .env.local exec php`.

---

## 3. Camino B — Nativo (sin Docker)

```bash
git clone https://github.com/Cannabica/opencogollos.git
cd opencogollos

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Editá el `.env` con **tu** PostgreSQL y tu URL:

```dotenv
APP_NAME=OpenCogollos
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=opencogollos
DB_USERNAME=opencogollos
DB_PASSWORD=<tu-password>
```

> Ojo con `DB_HOST`: `db` es el nombre del servicio **dentro** de Docker. Si corrés la app en tu
> máquina contra un PostgreSQL local, va `127.0.0.1`.

Ahora la base, los assets y el servidor:

```bash
php artisan migrate --seed    # crea las tablas y carga la demo (opcional el --seed)
npm run build                 # compila CSS/JS (Vite) — sin esto el panel se ve sin estilos
php artisan serve
```

La app queda en `http://127.0.0.1:8000`.

---

## 4. Camino C — Nativo con SQLite (sin PostgreSQL)

> ⚠️ **Pendiente de verificación.** En el estado actual del repo esta ruta **falla** en la
> migración `2025_04_26_084932_remove_owner_id_from_tenants_table`
> (`SQLite doesn't support dropping foreign keys`). Está identificada y con fix en camino; hasta
> que entre en el repo, **usá el camino A o B (PostgreSQL)**.

Cuando el fix esté, el camino es:

```bash
touch database/database.sqlite
```

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=/ruta/absoluta/al/repo/database/database.sqlite
# borrá o comentá DB_HOST / DB_PORT / DB_USERNAME / DB_PASSWORD
```

```bash
php artisan migrate --seed
```

`DB_DATABASE` tiene que ser una **ruta absoluta** (o relativa a `database/`).

---

## 5. Variables de entorno

Las más importantes (el resto está comentado en `.env.example` / `.env.local.example`).

### Aplicación

| Variable | Para qué |
|---|---|
| `APP_NAME` | Nombre de **tu instalación** (logos del panel, PWA, mails). Ver `docs/BRANDING.md`. |
| `APP_ENV` | `local` / `production` |
| `APP_KEY` | Clave de cifrado. **Obligatoria.** `php artisan key:generate` |
| `APP_DEBUG` | `false` en producción (si está en `true` exponés stack traces) |
| `APP_URL` | URL base. Define la URL del webhook de Telegram si no seteás `TELEGRAM_WEBHOOK_URL` |
| `APP_HTTP_PORT` / `APP_HTTPS_PORT` | Puertos publicados por Caddy en el compose local (default 8090/8040) |

### Base de datos

| Variable | Para qué |
|---|---|
| `DB_CONNECTION` | `pgsql` (o `sqlite`, ver §4) |
| `DB_HOST` | `db` en Docker, `127.0.0.1` en nativo |
| `DB_PORT` | 5432 dentro de Docker; el compose publica 5434 en tu máquina |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Base y credenciales. En Docker se usan para **inicializar** PostgreSQL (`POSTGRES_DB`/`POSTGRES_USER`/`POSTGRES_PASSWORD`) |

### Colas, sesión y caché

```dotenv
QUEUE_CONNECTION=sync     # o database, si querés colas persistidas
SESSION_DRIVER=file
CACHE_DRIVER=file
```

### Mail

Los mails (activaciones, avisos) salen por SMTP:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=mailpit         # en Docker (127.0.0.1 si tu Mailpit corre en el host)
MAIL_PORT=1025
MAIL_FROM_ADDRESS=noreply@tu-dominio.example
# MAIL_FROM_NAME: vacío => usa APP_NAME
```

En Docker podés ver todos los mails sin configurar nada en **http://localhost:8025**.

### Admin inicial

```dotenv
ADMIN_EMAIL=admin@tu-dominio.example
ADMIN_PASSWORD=<password-fuerte>
SEED_EXAMPLE_DATA=false
```

### Telegram

`TELEGRAM_BOT_TOKEN`, `TELEGRAM_WEBHOOK_URL` y las del bot de admin: ver
[`docs/TELEGRAM.md`](TELEGRAM.md).

### Marca

Las 9 claves `PLATFORM_*` (opcionales): ver [`docs/BRANDING.md`](BRANDING.md).

---

## 6. Crear el usuario de administración

**Sin esto no podés entrar al panel de superadmin.**

1. Definí en el entorno (`ADMIN_EMAIL` y `ADMIN_PASSWORD`):

   ```dotenv
   ADMIN_EMAIL=admin@tu-dominio.example
   ADMIN_PASSWORD=<password-fuerte>
   ```

2. Corré el seeder:

   ```bash
   php artisan db:seed --class=SuperAdminSeeder
   # Docker:
   # docker compose -f docker-compose.local.yml --env-file .env.local exec php php artisan db:seed --class=SuperAdminSeeder
   ```

3. Entrá a `/superadmin` con ese email y password.

> En Docker estas dos variables van en **`.env.local`** (el contenedor recibe la config por
> `env_file`, y el `.env` no entra a la imagen). Después de editarlas, `docker compose ... up -d`
> para recrear el contenedor; si no, el contenedor sigue con el entorno viejo.

Notas:
- El `DatabaseSeeder` **no** crea superadmin: exige `ADMIN_PASSWORD`. Si no está, avisa y sigue.
- El superadmin es el usuario **sin tenant** (`tenant_id` null). El email se guarda cifrado, así
  que si más adelante cambiás `ADMIN_EMAIL`, el `updateOrCreate` no lo matchea y podés terminar
  con **dos** admins. Cambiá el email desde el panel, no a mano.

### Datos de demo (opcional)

`php artisan migrate --seed` (o `php artisan db:seed`) carga una demo coherente: 5 organizaciones,
plantas, acciones, semillas y planes de cultivo. Los usuarios de demo usan la password
`password` — por ejemplo `juan@cultivo.com.ar` (panel tenant en `/tenant`). **No la uses en
producción.**

---

## 7. Primeros pasos en la app

1. Entrá a `/superadmin` → creá una **organización (tenant)** y su usuario.
2. El dueño entra a `/tenant` → crea su **entorno (indoor)** con luces y sensores.
3. Carga **semillas** y un **plan de cultivo** (o usa los planes globales).
4. Crea **plantas** y empezá a registrar **acciones** (riego, poda, transplante, observaciones).
5. Activá el **bot de Telegram** para cargar observaciones desde el celular
   ([`docs/TELEGRAM.md`](TELEGRAM.md)).

---

## 8. Troubleshooting

| Síntoma | Causa / solución |
|---|---|
| `No application encryption key has been specified` | Falta `APP_KEY`. Corré `php artisan key:generate` (§2 camino Docker, §3 nativo). |
| El panel se ve **sin estilos** | Faltan los assets de Vite: `npm run build` (en Docker los compila la imagen). |
| `SQLSTATE[08006] could not translate host name "db"` | `DB_HOST=db` es sólo para Docker. En nativo va `127.0.0.1`. |
| `SQLSTATE[08006] connection refused` en Docker | PostgreSQL todavía no está listo; el entrypoint reintenta solo. Mirá `docker compose ... logs db`. |
| La app devuelve 500 con `APP_DEBUG=false` | Mirá `storage/logs/laravel.log`. |
| Cambié el `.env` y no toma los cambios | La config está cacheada: `php artisan config:clear` (nativo) o `docker compose ... restart php`. |
| Los puertos 8090/8080/8025/5434 están ocupados | Cambiá `APP_HTTP_PORT`/`APP_HTTPS_PORT` en `.env.local` o pará el otro stack. |
| No llegan mails | En Docker el `MAIL_HOST` correcto es `mailpit` (no `127.0.0.1`). Verificá en http://localhost:8025. |
| Las fotos dan 404 | El archivo no existe en `storage/app/public` o no es de tu organización. **No** corras `php artisan storage:link`: las fotos se sirven por la ruta protegida `/storage/{path}` (ver §10). |
| El bot de Telegram no responde | Ver el troubleshooting de [`docs/TELEGRAM.md`](TELEGRAM.md). |

### Comandos de diagnóstico

```bash
php artisan about                  # versión, entorno, drivers, cachés
php artisan migrate:status         # estado de las migraciones
php artisan optimize:clear         # limpiar todas las cachés
php artisan telegram:webhook:setup --info   # estado del webhook de Telegram
```

---

## 9. Producción (notas generales)

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` propia y **fuera del repo**.
- Corré `php artisan config:cache` y `php artisan route:cache` (el entrypoint del contenedor ya lo
  hace): son la diferencia entre una app rápida y una lenta.
- Serví **siempre por HTTPS**: la PWA, el webhook de Telegram y las cookies seguras lo necesitan.
- Apuntá un backup de la base de datos y del `storage/app/public` (fotos) desde el día uno.

---

## 10. Cómo se sirven las fotos (no corras `storage:link`)

Las fotos de las acciones se guardan en `storage/app/public` y se sirven **a través de la app**,
por la ruta `GET /storage/{path}`:

- usuario anónimo → redirige al login;
- usuario autenticado → sólo ve los archivos de **su** organización;
- acceso cruzado entre organizaciones → `403`.

Por eso **no** hay que correr `php artisan storage:link`: si creás el symlink `public/storage`,
el servidor web (Caddy/nginx) entrega el archivo **directo, sin pasar por la app**, y se saltea
ese control de acceso. `public/storage` está en el `.gitignore` a propósito.

Si usás un proxy o CDN adelante, cuidá que `/storage/` vaya a la app (PHP-FPM) y no a un
directorio estático.
