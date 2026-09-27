#!/usr/bin/env bash
#
# verificacion-integral.sh — checklist ejecutable de T6.3 (verificación integral).
#
# Qué hace: corre, sobre un CLONE FRESCO, los 4 criterios de aceptación de T6.3 y reporta
# PASS/FAIL por criterio. Los criterios que necesitan acciones humanas o de otro sistema
# (verificación por un tercero, CI en un PR de prueba, deploy) los deja marcados como MANUAL
# con el comando exacto, para que no se confundan con un verde automático.
#
# Uso:
#   ./scripts/verificacion-integral.sh                     # todo lo automático
#   REPO_URL=git@github.com:Cannabica/opencogollos.git REF=main ./scripts/verificacion-integral.sh
#   SKIP_DOCKER=1 ./scripts/verificacion-integral.sh       # solo el grep (rápido)
#
# Salida: un reporte en stdout + exit code 0 si TODO lo automático pasó, 1 si algo falló.
#
set -uo pipefail

REPO_URL="${REPO_URL:-https://github.com/Cannabica/opencogollos.git}"
REF="${REF:-develop}"
WORKDIR="${WORKDIR:-$(mktemp -d /tmp/opencogollos-verif-XXXXXX)}"
APP_PORT="${APP_PORT:-18090}"          # ojo: 8090 suele estar ocupado por el dev local
DB_PORT="${DB_PORT:-15434}"
MAIL_PORT="${MAIL_PORT:-18025}"
SKIP_DOCKER="${SKIP_DOCKER:-0}"

# Patrón EXACTO del criterio de aceptación de T6.3.
LEAK_RE='cannabica\.(ar|app)|192\.168\.|frankie|trantor|60022'

# Archivos exentos: documentación de mantenedor aprobada / artefactos internos.
# (vacío por ahora: si se aprueba una excepción, va acá y el script excluye ESE path nomás)
LEAK_ALLOWLIST_FILE="${LEAK_ALLOWLIST_FILE:-}"

FAILS=0
pass() { printf '  \033[32mPASS\033[0m  %s\n' "$1"; }
fail() { printf '  \033[31mFAIL\033[0m  %s\n' "$1"; FAILS=$((FAILS+1)); }
mani() { printf '  \033[33mMANUAL\033[0m %s\n' "$1"; }
head1() { printf '\n\033[1m== %s\033[0m\n' "$1"; }

echo "Verificación integral T6.3"
echo "  repo:    $REPO_URL"
echo "  ref:     $REF"
echo "  clone:   $WORKDIR"
echo "  puertos: app=$APP_PORT db=$DB_PORT mail=$MAIL_PORT"

# ---------------------------------------------------------------------------
head1 "0. Clone fresco"
# ---------------------------------------------------------------------------
if [ -n "${LOCAL_DIR:-}" ]; then
    # Modo pre-apertura: el repo todavía no está publicado (o querés verificar un checkout local).
    # OJO: en este modo NO se hace clone fresco, así que el criterio 2 no queda 100% cubierto.
    echo "  LOCAL_DIR=$LOCAL_DIR (sin clone fresco: criterio 0 y 2 sólo parcialmente cubiertos)"
    WORKDIR="$LOCAL_DIR"
fi
if [ -e "$WORKDIR/.git" ]; then    # -e y no -d: en un worktree .git es un ARCHIVO, no un directorio
    echo "  (reusando el checkout existente en $WORKDIR — sin clone fresco)"
else
    git clone --depth 1 --branch "$REF" "$REPO_URL" "$WORKDIR" || { fail "git clone falló"; exit 1; }
fi
cd "$WORKDIR" || exit 1
echo "  HEAD: $(git rev-parse --short HEAD) $(git log -1 --pretty=%s)"
# Un clone fresco no debe traer basura trackeada (T5.1).
for junk in .config/psysh/psysh_history tmp routes/test_403.php; do
    if git ls-files --error-unmatch "$junk" >/dev/null 2>&1; then
        fail "basura trackeada presente: $junk"
    else
        pass "sin basura trackeada: $junk"
    fi
done
# La referencia a build.sh (inexistente) no debe reaparecer.
if git grep -n "build\.sh" -- '*.md' >/dev/null 2>&1; then
    fail "algún .md sigue mencionando build.sh"
else
    pass "ningún .md referencia build.sh"
fi

# ---------------------------------------------------------------------------
head1 "1. grep de fugas sobre HEAD = 0"
# ---------------------------------------------------------------------------
GREP_CMD="grep -rniE \"$LEAK_RE\""
echo "  (comando del criterio: $GREP_CMD .  — acá con git grep = sólo archivos trackeados)"
LEAKS="$(git grep -niE "$LEAK_RE" -- . || true)"
LEAK_COUNT="$(printf '%s' "$LEAKS" | grep -c . || true)"
if [ -n "$LEAK_ALLOWLIST_FILE" ] && [ -f "$LEAK_ALLOWLIST_FILE" ]; then
    LEAKS="$(printf '%s\n' "$LEAKS" | grep -vFf "$LEAK_ALLOWLIST_FILE" || true)"
    LEAK_COUNT="$(printf '%s' "$LEAKS" | grep -c . || true)"
    echo "  (aplicada allowlist: $LEAK_ALLOWLIST_FILE)"
fi
if [ "$LEAK_COUNT" -eq 0 ]; then
    pass "0 fugas"
else
    fail "$LEAK_COUNT fugas — ver abajo"
    printf '%s\n' "$LEAKS" | sed 's/^/        /'
fi

# ---------------------------------------------------------------------------
head1 "2. README → app levantada sin config de Cannabica"
# ---------------------------------------------------------------------------
if [ "$SKIP_DOCKER" = "1" ]; then
    mani "SKIP_DOCKER=1: no se levantó el stack"
else
    COMPOSE="docker compose -f docker-compose.local.yml --env-file .env.local"
    cp .env.local.example .env.local
    # Puertos propios para no chocar con un dev local ya levantado.
    {
        echo "APP_HTTP_PORT=$APP_PORT"
        echo "APP_HTTPS_PORT=1$APP_PORT"
        echo "APP_URL=http://localhost:$APP_PORT"
    } >> .env.local
    # APP_KEY: el README usa key:generate dentro del contenedor (--env=local escribe .env.local).
    docker compose -f docker-compose.local.yml --env-file .env.local run --rm php \
        php artisan --env=local key:generate || fail "key:generate falló"
    grep -qE '^APP_KEY=base64:.+' .env.local && pass "APP_KEY generada" || fail "APP_KEY vacía"

    $COMPOSE up -d --build || fail "docker compose up falló"
    # El entrypoint migra solo: esperamos a que la app responda.
    ok=0
    for i in $(seq 1 60); do
        code="$(curl -s -o /dev/null -w '%{http_code}' "http://localhost:$APP_PORT/superadmin/login" || true)"
        [ "$code" = "200" ] && { ok=1; break; }
        sleep 5
    done
    [ "$ok" = "1" ] && pass "/superadmin/login responde 200 en $APP_PORT" \
                    || fail "/superadmin/login no respondió 200 (último: ${code:-sin respuesta})"

    # El login no debe traer marca de otra instalación (instalación neutra).
    if [ "$ok" = "1" ]; then
        BODY="$(curl -s "http://localhost:$APP_PORT/superadmin/login")"
        if printf '%s' "$BODY" | grep -qiE "$LEAK_RE"; then
            fail "el HTML servido contiene datos de una instalación concreta"
            printf '%s' "$BODY" | grep -ioE "$LEAK_RE" | sort -u | sed 's/^/        /'
        else
            pass "el HTML servido no contiene datos de una instalación concreta"
        fi
    fi
    $COMPOSE logs php --tail 20 | sed 's/^/        /'
fi

# ---------------------------------------------------------------------------
head1 "3. CI verde en un PR de prueba  (MANUAL)"
# ---------------------------------------------------------------------------
mani "abrir un PR de prueba contra develop (rama descartable, sin cambios reales) y verificar que"
mani "los checks pasan:  gh pr create --base develop --head chore/verificacion-t6.3 --title 'PR de prueba T6.3'"
mani "                    gh pr checks --watch"
mani "revisar que corra: suite de tests + análisis estático. Cerrar el PR sin mergear."
mani "⚠️  si el CI trae el job de deploy armado (T3.3), NO mergear a main/develop durante la prueba."

# ---------------------------------------------------------------------------
head1 "4. Deploy desde cannbica-deploy  (MANUAL)"
# ---------------------------------------------------------------------------
mani "desde el repo privado de operación: disparar el deploy a un entorno de prueba y verificar"
mani "que la app responde con la imagen publicada (ghcr.io/cannabica/opencogollos)."
mani "NO hacerlo sobre producción sin ventana acordada."

# ---------------------------------------------------------------------------
head1 "5. Verificado por un tercero  (MANUAL)"
# ---------------------------------------------------------------------------
mani "una persona que NO sea el mantenedor clona el repo en una máquina limpia y sigue SOLO el"
mani "README. Se registran: pasos donde se trabó, tiempo total, y si llegó a ver la app arriba."
mani "Esto NO lo puede firmar la propia sesión que escribió el README (criterio de T6.1)."

# ---------------------------------------------------------------------------
head1 "Resultado"
# ---------------------------------------------------------------------------
if [ "$FAILS" -eq 0 ]; then
    echo "  Todo lo automático pasó. Faltan los 3 ítems MANUAL (CI, deploy, tercero)."
    exit 0
else
    echo "  $FAILS chequeo(s) automático(s) FALLARON."
    exit 1
fi
