# Emulador local del Bot API de Telegram — OpenIndoor

Emulador HTTP del Bot API de Telegram para desarrollar y probar **los bots de
OpenIndoor y de OpenCogollos sin tocar los servidores de Telegram**: sin
internet, sin bot real, sin `api_id`/`api_hash` y sin abrir el chat desde el
celular.

## Por qué no alcanza con lo que ya existe

| Opción | Por qué no sirve para esto |
|---|---|
| `tdlib/telegram-bot-api` (server oficial, imagen `aiogram/telegram-bot-api`) | NO emula: es un cliente de Telegram. Exige `api_id`/`api_hash` de my.telegram.org y tokens de bots reales, y proxya contra los servidores de Telegram. Medido: con credenciales falsas, `GET /bot<TOKEN>/getMe` → `{"ok":false,"error_code":401,"description":"Unauthorized"}`. |
| `telegram-test-api`, `telegram-api-mock-server` (npm) | Pollean por `getUpdates` y se enchufan interceptando `api.telegram.org` (hosts/nftables). OpenIndoor usa **webhooks**: necesita que alguien entregue el update al endpoint de la app. |
| Mockear `Telegram\Bot\BotsManager` | Es `final`: Mockery tira. Y mockear `Api` sólo prueba que algo se llamó, no **qué** payload salió. |

Este emulador cubre las dos direcciones del ciclo:

- **Entrante**: entrega updates al webhook de la app (con el header
  `X-Telegram-Bot-Api-Secret-Token`), igual que Telegram.
- **Saliente**: registra cada llamado de la app (`sendMessage`, `sendPhoto`, …)
  para poder **verificar el mensaje**, que es lo que hoy no cubre ningún test.

## Requisitos

Ninguno más que PHP 8.3 (usa sólo stdlib). No hay dependencias, ni composer, ni npm.

## Levantar

Docker (recomendado, no ensucia el host):

```bash
docker compose -f docker-compose.telegram-emulator.yml -p tg-emulator up -d
curl -s http://127.0.0.1:8082/_emulator/health
```

Nativo (más rápido para iterar; **siempre con workers**, ver Pitfalls):

```bash
PHP_CLI_SERVER_WORKERS=4 php -S 127.0.0.1:8082 tools/telegram-emulator/server.php
```

## Bajar

```bash
# Docker (borra también el estado guardado en el volumen)
docker compose -f docker-compose.telegram-emulator.yml -p tg-emulator down -v

# Nativo: Ctrl-C, o
fuser -k 8082/tcp
```

## Plano de control

| Método | Path | Para qué |
|---|---|---|
| GET | `/_emulator/health` | ¿Está vivo? ¿Qué webhooks tiene registrados? |
| POST | `/_emulator/webhook` | Registra el webhook de un bot: `{"token","url","secret_token","allowed_updates"}` |
| GET | `/_emulator/webhooks` | Webhooks registrados (token → url, secret, allowed_updates) |
| POST | `/_emulator/inject` | Inyecta un update y lo entrega al webhook registrado |
| GET | `/_emulator/outbound?token=&method=` | Llamados SALIENTES capturados (con sus params) |
| GET | `/_emulator/state` | Todo el estado |
| POST | `/_emulator/reset` | Borra webhooks + salientes |

`inject` acepta el update completo o un atajo cómodo:

```bash
# atajo: arma el update de mensaje (y la entidad bot_command con el length correcto)
curl -s -X POST http://127.0.0.1:8082/_emulator/inject \
  -H 'Content-Type: application/json' \
  -d '{"token":"emulador-admin","text":"/estado","chat_id":555000111,"from_id":555000111}'

# update crudo
curl -s -X POST http://127.0.0.1:8082/_emulator/inject \
  -H 'Content-Type: application/json' \
  -d '{"token":"emulador-cultivador","update":{"update_id":1,"callback_query":{"id":"cb1","from":{"id":555000111,"is_bot":false,"first_name":"Tester"},"data":"actiondetails:1"}}}'
```

La respuesta de `inject` trae `status` y `response` del webhook de la app: si la
app devuelve 500, se ve ahí (no hace falta adivinar).

## Enchufar la app

1. `base_bot_url` apunta TODOS los bots al emulador (el SDK arma la URL como
   `base_bot_url . TOKEN . '/' . metodo`):

   ```
   TELEGRAM_BASE_BOT_URL=http://127.0.0.1:8082/bot
   ```

   Vacío ⇒ API real (producción no cambia).

2. Tokens de prueba: el emulador no valida tokens, sólo tienen que ser no vacíos
   (`TELEGRAM_BOT_TOKEN=emulador-cultivador`, `TELEGRAM_ADMIN_BOT_TOKEN=emulador-admin`).

3. Registrar el webhook **en el emulador** (no con `telegram:webhook:setup`, ver
   Pitfall 1):

   ```bash
   curl -s -X POST http://127.0.0.1:8082/_emulator/webhook -H 'Content-Type: application/json' -d '{
     "token": "emulador-cultivador",
     "url": "http://127.0.0.1:8095/api/telegram/webhook",
     "secret_token": "clave-tenant-local",
     "allowed_updates": ["message", "callback_query"]
   }'
   ```

## E2E completo (lo que hace `run-e2e-app.sh`)

```bash
# 1. Postgres de prueba aislado (NUNCA la DB de desarrollo ni producción)
docker run -d --name tg-e2e-db \
  -e POSTGRES_HOST_AUTH_METHOD=trust \
  -e POSTGRES_DB=cannibica_telegram_e2e -e POSTGRES_USER=cannabica_e2e \
  -p 127.0.0.1:5436:5432 postgres:16-alpine

# 2. Migrar + fixtures (tenant + chat de prueba)
bash tools/telegram-emulator/run-e2e-app.sh php artisan migrate --force

# 3. Levantar la app contra el emulador
bash tools/telegram-emulator/run-e2e-app.sh

# 4. Que el bot reciba un comando y conteste
curl -s -X POST http://127.0.0.1:8082/_emulator/inject -H 'Content-Type: application/json' \
  -d '{"token":"emulador-admin","text":"/estado","chat_id":555000111,"from_id":555000111}'

# 5. Ver la respuesta que la app mandó
curl -s 'http://127.0.0.1:8082/_emulator/outbound?method=sendMessage'

# 6. Probar un flujo SALIENTE de la app
bash tools/telegram-emulator/run-e2e-app.sh php artisan tinker --execute='dispatch(new App\Jobs\SendDelayedProductNotification("flora", 3, 1));'
```

## HTTPS con CA propia (para que el SDK no rechace la URL)

El SDK valida que la URL del **webhook** sea HTTPS (`Methods/Update.php:184`,
`Invalid URL, should be a HTTPS url`). O sea: el TLS va delante de **la app**, no
del emulador (`base_bot_url` no se valida).

Receta verificada con Caddy (CA interna, sin instalar nada en el sistema):

```caddyfile
# Caddyfile
{
    local_certs
}
localhost:8443 {
    tls internal
    reverse_proxy host.docker.internal:8095   # la app
}
```

```bash
docker run -d --name tg-caddy-tls --add-host=host.docker.internal:host-gateway \
  -p 8443:8443 -v $PWD/Caddyfile:/etc/caddy/Caddyfile:ro -v tg-caddy-data:/data caddy:alpine

# exportar la CA interna (10 años de validez)
docker exec tg-caddy-tls cat /data/caddy/pki/authorities/local/root.crt > /tmp/caddy-root.crt

# el emulador tiene que confiar en esa CA para entregar el update
# (o TELEGRAM_EMULATOR_TLS_INSECURE=1 para desactivar la verificación SOLO en local)
TELEGRAM_EMULATOR_CA_FILE=/tmp/caddy-root.crt php -S 127.0.0.1:8082 tools/telegram-emulator/server.php
```

Con eso `php artisan telegram:webhook:setup --bot=admin` **funciona** apuntando a
`https://localhost:8443/api/telegram/admin/webhook` (verificado), y el ciclo
update → webhook → comando → respuesta corre entero sobre TLS.

## Pitfalls (los que costaron tiempo)

1. **El SDK rechaza webhooks HTTP.** `setWebhook()` valida `https` antes de
   mandar nada: contra el emulador, `php artisan telegram:webhook:setup` falla con
   `Invalid URL, should be a HTTPS url` salvo que uses el front TLS de arriba. Por
   eso el emulador tiene `POST /_emulator/webhook` para registrar el webhook
   directo.
2. **`php -S` es single-thread.** El camino `inject` → webhook → `sendMessage` →
   emulador vuelve a entrar en el mismo proceso: sin `PHP_CLI_SERVER_WORKERS>1`
   el inject deadlockea (parece que "no llega nada"). En el compose ya está en 4.
3. **`APP_ENV=local` hace que Laravel cargue `.env.local`**, no `.env` (que puede
   tener otro `DB_HOST` — típicamente `db`, el nombre del servicio de Docker — y
   otro secret). Si el server y el comando artisan no cargan el mismo archivo, los
   secrets no coinciden y el webhook responde 401. `run-e2e-app.sh` fija el
   entorno en un solo lugar.
4. **No pasar tokens/secrets en la línea de comandos.** El runtime del agente
   reescribe esos literales y corrompe el valor. Por eso el entorno vive en
   `run-e2e-app.sh`.
5. **`psysh` (tinker) necesita escribir en `$HOME/.config`**, que en este entorno
   puede apuntar a `/var/www/html` (no escribible): el script exporta
   `XDG_CONFIG_HOME` a un tmp.
6. **La entidad `bot_command` debe cubrir sólo el comando** (`length` = largo de
   `/estado`, no del texto completo). El atajo de `inject` ya lo hace bien; el
   sintoma de hacerlo mal es que el update llega, la app responde 200 y no pasa
   nada (cae en `HelpCommand`).

## Qué NO cubre

- Fidelidad byte a byte con el Bot API real (implementa el subconjunto que usa la
  app; un método desconocido devuelve 404).
- Uploads de archivos grandes / `getFile` con contenido real.
- Updates entre bots: cada token tiene su webhook y su cola.
- Nada de esto va a producción: es una herramienta de desarrollo.
