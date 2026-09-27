#!/usr/bin/env bash
#
# Levanta OpenIndoor apuntando al emulador local del Bot API de Telegram.
#
# NO usa credenciales reales: los tokens son cadenas de prueba y el "webhook"
# apunta a la app local. Sirve para el E2E (update entrante -> respuesta del bot
# y flujo saliente -> mensaje capturado por el emulador) sin tocar Telegram.
#
# Requisitos (una sola vez):
#   1. Emulador levantado:  ver tools/telegram-emulator/README.md
#   2. Postgres de prueba:  docker run -d --name tg-e2e-db \
#        -e POSTGRES_HOST_AUTH_METHOD=trust \
#        -e POSTGRES_DB=cannibica_telegram_e2e -e POSTGRES_USER=cannabica_e2e \
#        -p 127.0.0.1:5436:5432 postgres:16-alpine
#   3. Migrar + fixtures:   ver README.md (sección E2E)
#
# Uso:
#   bash tools/telegram-emulator/run-e2e-app.sh
#
# Por qué un script y no env inline: además de ser reproducible, varias de estas
# variables (nombres *_TOKEN / *_SECRET) hacen que el runtime reescriba el
# comando y corrompa los valores cuando se pasan en la línea de comandos.
# Además el SAPI cli-server NO hereda el env del padre `artisan serve`, así que
# la app se sirve con `php -S` directo, con el entorno ya exportado acá.

set -euo pipefail

cd "$(dirname "$0")/../.."

APP_PORT="${APP_PORT:-8095}"
# 0.0.0.0 hace falta cuando el TLS lo termina un contenedor (Caddy en Docker
# proxya al host) o cuando se prueba desde otro contenedor/dispositivo.
APP_HOST="${APP_HOST:-127.0.0.1}"
EMULATOR_BASE_URL="${EMULATOR_BASE_URL:-http://127.0.0.1:8082/bot}"
DB_PORT_E2E="${DB_PORT_E2E:-5436}"

# APP_ENV=local hace que Laravel cargue .env.local (donde vive APP_KEY y el
# resto de la config base); lo de acá abajo pisa esos valores porque son
# variables de entorno reales y Dotenv no sobreescribe el entorno.
export APP_ENV=local
export APP_URL="http://127.0.0.1:${APP_PORT}"

# Postgres de prueba aislado (nunca la DB de desarrollo ni la de producción).
export DB_CONNECTION=pgsql
export DB_HOST=127.0.0.1
export DB_PORT="${DB_PORT_E2E}"
export DB_DATABASE=cannibica_telegram_e2e
export DB_USERNAME=cannabica_e2e
export DB_PASSWORD=e2e

export QUEUE_CONNECTION=sync

# Todos los bots de la app hablan contra el emulador.
export TELEGRAM_BASE_BOT_URL="${EMULATOR_BASE_URL}"

# Tokens de prueba (el emulador no valida tokens; sólo tienen que ser no vacíos).
export TELEGRAM_BOT_TOKEN="emulador-cultivador"
export TELEGRAM_ADMIN_BOT_TOKEN="emulador-admin"

# Secrets de webhook: el emulador los manda en X-Telegram-Bot-Api-Secret-Token
# y los dos webhooks de la app los exigen (fail closed).
export TELEGRAM_SECRET_TOKEN="clave-tenant-local"
export TELEGRAM_ADMIN_SECRET_TOKEN="clave-admin-local"
export TELEGRAM_ADMIN_ALLOWED_USER_IDS="555000111"

export TELEGRAM_WEBHOOK_URL="${TELEGRAM_WEBHOOK_URL:-http://127.0.0.1:${APP_PORT}/api/telegram/webhook}"
export TELEGRAM_ADMIN_WEBHOOK_URL="${TELEGRAM_ADMIN_WEBHOOK_URL:-http://127.0.0.1:${APP_PORT}/api/telegram/admin/webhook}"

# El emulador entrega el update al webhook DENTRO del request de inject: hace
# falta más de un worker o el inject deadlockea (php -S es single-thread y el
# camino inject -> webhook -> sendMessage -> emulador vuelve a entrar acá).
export PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-3}"

# psysh (tinker) quiere escribir su config en $HOME/.config y en este entorno HOME
# puede apuntar a /var/www/html (no escribible) -> "Writing to directory ... is
# not allowed". Con XDG_CONFIG_HOME a un tmp escribible, tinker corre.
export XDG_CONFIG_HOME="${XDG_CONFIG_HOME:-/tmp/tg-e2e-xdg}"

echo "OpenIndoor E2E en http://127.0.0.1:${APP_PORT} -> emulador ${TELEGRAM_BASE_BOT_URL}"

# Con argumentos, corre ese comando con este mismo entorno (así se migra, se
# siembran fixtures o se dispara un job saliente sin repetir los secretos en la
# línea de comandos). Sin argumentos, levanta el server.
if [ "$#" -gt 0 ]; then
    exec "$@"
fi

exec php -S "${APP_HOST}:${APP_PORT}" -t public public/index.php
