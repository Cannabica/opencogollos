# Telegram

OpenCogollos trae un bot de Telegram que te deja consultar y cargar cosas de tu cultivo desde el
celular: plantas, acciones, indoors, semillas, y registrar observaciones **mandando una foto** al
chat.

El bot **no** viene con un token propio: cada instalación usa su propio bot. Tenés que crear el
tuyo con **@BotFather** (2 minutos) y pegar el token en tu `.env`. Podés tener, además, un
**bot de administración** separado para el superadmin (opcional).

---

## 1. Crear tu bot con BotFather

1. Abrí Telegram y buscá **@BotFather** (`https://t.me/BotFather`).
2. Mandale `/newbot`.
3. Te pide un **nombre** (es el que se ve en el chat, ej. `Mi Cultivo`) y después un
   **username**, que tiene que terminar en `bot` y ser único (ej. `micultivo_bot`).
4. BotFather te responde con el **token**, con esta forma: `<números>:<letras>`. Ese token es la
   credencial del bot: **no lo commitees**. Si se filtra, se revoca con `/revoke` en BotFather.
5. Opcional, en BotFather:
   - `/setdescription` y `/setabouttext` — los textos que ve el usuario al abrir el chat.
   - `/setcommands` — **no hace falta**: la app registra los comandos sola (ver §3).

---

## 2. Configurar el token

En tu `.env` (o `.env.local`, según cómo hayas instalado — ver `docs/INSTALL.md`):

```dotenv
TELEGRAM_BOT_TOKEN=<el token de BotFather>
# Obligatorio: Telegram manda este valor en el header X-Telegram-Bot-Api-Secret-Token.
# Sin esto el webhook del bot rechaza TODO (503, fail closed).
TELEGRAM_SECRET_TOKEN=<un string aleatorio tuyo>
```

Y la URL del webhook. Si la dejás vacía, la app usa `APP_URL` + `/api/telegram/webhook/`:

```dotenv
APP_URL=https://tu-dominio.example
# opcional, para forzar otra URL de webhook:
# TELEGRAM_WEBHOOK_URL=https://tu-dominio.example/api/telegram/webhook/
```

Después de cambiar el `.env` en una instalación que cachea config, recargá la config:

```bash
php artisan config:clear
# en Docker:
docker compose -f docker-compose.local.yml --env-file .env.local restart php
```

---

## 3. Registrar el webhook

La app incluye un comando para esto (lee la URL de `config/telegram.php`):

```bash
# registrar el webhook
php artisan telegram:webhook:setup

# ver el estado actual (URL, updates pendientes, último error)
php artisan telegram:webhook:setup --info

# borrar el webhook (pasar a modo polling, o desactivar el bot)
php artisan telegram:webhook:setup --remove

# listar los comandos registrados y con qué clase los maneja cada uno
php artisan telegram:commands:list
```

En Docker, anteponé el prefijo del servicio PHP:

```bash
docker compose -f docker-compose.local.yml --env-file .env.local exec php php artisan telegram:webhook:setup --info
```

**Requisitos del webhook:** Telegram sólo entrega updates a una URL **pública y con HTTPS**. En
desarrollo local usá un túnel (ngrok, cloudflared, etc.) apuntando al puerto de la app y poné esa
URL en `APP_URL`/`TELEGRAM_WEBHOOK_URL`. Sin HTTPS público, Telegram rechaza el registro del
webhook y el bot queda mudo (aunque `/start` responda si usás polling).

---

## 4. Comandos del bot

Lista real de comandos registrados (salida de `php artisan telegram:commands:list`):

| Comando | Para qué sirve |
|---|---|
| `/start` | Bienvenida + cómo autenticarse |
| `/auth <token>` | Vincula tu chat de Telegram con tu cuenta (ver §5) |
| `/tenantinfo` | Datos de tu organización/tenant |
| `/indoordetails` | Detalle de tus espacios de cultivo |
| `/plantslist` | Listado de plantas (paginado) |
| `/plantdetails` | Detalle de una planta |
| `/acciones` | Listado de acciones registradas |
| `/actiondetails` | Detalle de una acción |
| `/seedslist` | Listado de semillas/genéticas |
| `/repetirriego` | Repite el último riego cargado |

Además de los comandos, dos flujos que no son `/comandos`:

- **Mandar una foto** al chat → se registra como una **observación** con esa foto.
- **Botones inline** (callback) en los listados → abren el detalle de plantas/acciones.

Variables opcionales que afectan al bot:

```dotenv
TELEGRAM_MAX_PLANTS_PER_PAGE=101
TELEGRAM_PHOTO_MAX_SIZE=5120   # KB
```

---

## 5. Cómo se autentica un usuario

El bot necesita saber a qué cuenta pertenece cada chat. El flujo es:

1. El cultivador entra al panel (`/tenant`), sección **Mi grupo** (`/tenant/tenant-page`).
2. Genera un **token** para Telegram.
3. En el chat del bot manda `/auth <token>`.
4. Listo: el chat queda vinculado a ese tenant (queda una fila en `telegram_user_tenant`).

Notas:
- El usuario **tiene que iniciar el chat primero** (mandarle `/start` al bot). Telegram no permite
  que un bot le escriba a alguien que nunca le habló (`400 chat not found`).
- El vínculo tiene vencimiento y se puede renovar generando otro token.

---

## 6. Bot de administración (opcional, superadmin)

Es un **bot aparte** para tareas de administración de la plataforma. Se configura con sus propias
variables:

```dotenv
TELEGRAM_ADMIN_BOT_TOKEN=<token del bot de admin>
# Obligatorio: Telegram manda este valor en el header X-Telegram-Bot-Api-Secret-Token.
# Sin esto, el webhook de admin rechaza TODO (503, fail closed).
TELEGRAM_ADMIN_SECRET_TOKEN=<un string aleatorio tuyo>
# opcional (default: APP_URL/api/telegram/admin/webhook/)
TELEGRAM_ADMIN_WEBHOOK_URL=
# ids numéricos de Telegram autorizados, separados por coma
TELEGRAM_ADMIN_ALLOWED_USER_IDS=
# resumen proactivo automático de novedades
TELEGRAM_ADMIN_DIGEST_ENABLED=true
```

El digest se puede mandar a mano con:

```bash
php artisan admin:digest
```

Si `TELEGRAM_ADMIN_SECRET_TOKEN` está vacío el webhook de admin **no funciona** (a propósito: es
fail closed). Para registrar el webhook de ese bot:

```bash
php artisan telegram:webhook:setup --bot=admin
```

> **Nota de seguridad.** Los DOS webhooks exigen el header `X-Telegram-Bot-Api-Secret-Token` y
> fallan cerrado: sin `TELEGRAM_SECRET_TOKEN` (bot de tenants) o `TELEGRAM_ADMIN_SECRET_TOKEN`
> (admin) configurado, responden **503**; con el header ausente o distinto, **401**. Las variables
> tienen que estar puestas en el `.env` **y** registradas con `telegram:webhook:setup`, que es quien
> le pasa el `secret_token` a Telegram y hace que Telegram agregue el header.

---

## 7. Probar los bots sin tocar Telegram (devkit local)

Para desarrollar el bot no hace falta internet, ni un bot real, ni abrir el chat desde el celular. El
**devkit local** vive en un repo aparte (`Cannabica/opencogollos-devkit`, hermano de
`cannabica-deploy`) para no meter herramientas de desarrollo dentro de este repo. Trae:

- un **emulador HTTP del Bot API** que emula el subconjunto que usa la app, entrega los updates al
  webhook y **registra los mensajes salientes** para poder verificarlos;
- un **front TLS con CA propia**, porque el SDK rechaza URLs de webhook que no sean https (así el
  `telegram:webhook:setup` real se puede usar en local).

```bash
git clone https://github.com/Cannabica/opencogollos-devkit.git
cd opencogollos-devkit
make up                                    # emulador (8082) + front TLS (8443)
make ca                                    # CA interna, para el webhook https
make app APP=/ruta/a/OpenIndoor            # levanta la app contra el devkit
make smoke SMOKE_TLS=1 SMOKE_TEXT=/estado  # E2E: inyecta un update y muestra la respuesta
```

Se enchufa apuntando `TELEGRAM_BASE_BOT_URL` al emulador (vacío = API real, producción no cambia):

```dotenv
TELEGRAM_BASE_BOT_URL=http://127.0.0.1:8082/bot
```

Guía completa (plano de control, HTTPS, pitfalls): `opencogollos-devkit/telegram-bot-emulator/README.md`.

> Los tests de payload saliente sin red (`tests/Support/FakeTelegramHttpClient.php` + los tests de
> Telegram) viven **acá**, en la suite de la app: no dependen del devkit ni de Docker.

---

## 8. Troubleshooting

| Síntoma | Causa habitual |
|---|---|
| El bot no responde nada | Webhook no registrado, o la URL no es HTTPS pública. Mirá `telegram:webhook:setup --info` (`Pending Update Count`, `Last Error Message`). |
| El bot responde `400 chat not found` | El usuario nunca le escribió al bot: pedile un `/start`. |
| El webhook devuelve 500 | Revisá `storage/logs/laravel.log`. Los updates que fallan quedan en *retry* y se drenan solos cuando el 500 se arregla. |
| `/auth` dice token inválido | Token vencido o de otro tenant: generá uno nuevo desde **Mi grupo**. |
| El webhook de admin devuelve 503 | Falta `TELEGRAM_ADMIN_SECRET_TOKEN` (fail closed). |
