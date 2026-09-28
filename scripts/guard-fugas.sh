#!/usr/bin/env bash
#
# guard-fugas.sh — guard de fugas del árbol de trabajo de un repo PÚBLICO.
#
# POR QUÉ EXISTE
#   El repo es público. Todo lo que se commitea queda indexado para siempre, y lo que se
#   commitea "por error" y se borra en el commit siguiente sigue estando en la historia.
#   Este guard corre en cada PR y push (job `fugas` de ci.yml) para que la fuga no llegue a
#   `develop` ni a `main`. Nació de la verificación T6.3, que midió las fugas de la apertura
#   por única vez y a mano.
#
# QUÉ CUBRE Y QUÉ NO
#   · SECRETOS: los escanea gitleaks (reglas + entropía), no este script. Ver `.gitleaks.toml`
#     y el paso "gitleaks" del job. Acá no se reimplementan reglas de secretos.
#   · BLOQUEANTE (falla el build): infraestructura de la instalación de referencia
#     (IPs, puertos, hosts internos, rutas del server) e identidad personal del mantenedor
#     (usuario, emails).
#   · AVISO (no falla): menciones de la MARCA de la instancia. La regla del proyecto es
#     "el repo se limpia de INFRAESTRUCTURA, no de marca": la marca puede aparecer, pero se
#     reporta para que nadie meta dominios de una instalación concreta en código de producto
#     (p.ej. en un fixture de test, donde sirve igual un dominio neutro).
#
# USO
#   bash scripts/guard-fugas.sh                    # árbol de trabajo actual
#   bash scripts/guard-fugas.sh --ref origin/main  # otra ref (la usa el CI si hace falta)
#   Salida: lista de hallazgos + resumen. Exit 0 si no hay BLOQUEANTES, 1 si hay.
#
set -uo pipefail

REF=""
while [ $# -gt 0 ]; do
    case "$1" in
        --ref) REF="${2:-}"; shift 2 ;;
        --ref=*) REF="${1#--ref=}"; shift ;;
        *) echo "uso: $0 [--ref <ref>]" >&2; exit 2 ;;
    esac
done

# Archivos exentos: contienen los patrones POR DEFINICIÓN (son la regla que se verifica).
EXCLUDES=(
    ':(exclude)scripts/guard-fugas.sh'
    ':(exclude)scripts/verificacion-integral.sh'
)

# --- BLOQUEANTE: infraestructura + identidad personal ------------------------------
# OJO al agregar patrones: grep -E (POSIX) no tiene lookahead. Los números se anclan con
# (^|[^0-9]) / ([^0-9]|$) para no matchear dentro de otro número (p.ej. un SHA o una fecha).
BLOQ=(
    '148\.113\.175\.253'
    '(^|[^0-9])192\.168\.[0-9]{1,3}\.[0-9]{1,3}'
    '(^|[^0-9])10\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}'
    '(^|[^0-9])172\.(1[6-9]|2[0-9]|3[01])\.[0-9]{1,3}\.[0-9]{1,3}'
    '(^|[^0-9])60022([^0-9]|$)'
    'trantor'
    '/home/debian'
    'frankietoledo'
    'alexis\.frank\.toledo'
    'frankie@cannabica\.app'
    'cannabica_deploy'
    'BEGIN [A-Z ]*PRIVATE KEY'
)

# --- AVISO: marca de una instalación concreta --------------------------------------
WARN=(
    'cannabica\.(ar|app)'
    'cannabica-deploy'
    'public-php'
)

# --- helpers -----------------------------------------------------------------------
grep_scope() {   # $1 = patrón
    if [ -n "$REF" ]; then
        git grep -nIE "$1" "$REF" -- . "${EXCLUDES[@]}" 2>/dev/null
    else
        git grep -nIE "$1" -- . "${EXCLUDES[@]}" 2>/dev/null
    fi
}

SCOPE_DESC="árbol de trabajo (archivos trackeados)"
[ -n "$REF" ] && SCOPE_DESC="ref $REF"
echo "Guard de fugas — alcance: $SCOPE_DESC"
[ -n "$REF" ] || echo "  (para el CI sobre una ref: $0 --ref <ref>)"

echo
echo "== BLOQUEANTE: infraestructura / identidad personal =="
BLOQ_HITS=0
for pat in "${BLOQ[@]}"; do
    hits="$(grep_scope "$pat" || true)"
    if [ -n "$hits" ]; then
        n="$(printf '%s\n' "$hits" | grep -c .)"
        BLOQ_HITS=$((BLOQ_HITS + n))
        printf '  ✗ /%s/  (%s)\n' "$pat" "$n"
        printf '%s\n' "$hits" | sed 's/^/      /'
    fi
done
[ "$BLOQ_HITS" -eq 0 ] && echo "  ✓ sin coincidencias"

echo
echo "== AVISO: marca de la instancia (no falla el build) =="
WARN_HITS=0
for pat in "${WARN[@]}"; do
    hits="$(grep_scope "$pat" || true)"
    if [ -n "$hits" ]; then
        n="$(printf '%s\n' "$hits" | grep -c .)"
        WARN_HITS=$((WARN_HITS + n))
        printf '  · /%s/  (%s)\n' "$pat" "$n"
        printf '%s\n' "$hits" | sed 's/^/      /'
    fi
done
[ "$WARN_HITS" -eq 0 ] && echo "  · sin coincidencias"

echo
if [ "$BLOQ_HITS" -gt 0 ]; then
    echo "RESULTADO: FAIL — $BLOQ_HITS coincidencia(s) bloqueante(s) y $WARN_HITS aviso(s)."
    echo "  Qué hacer: si el dato es real, sacarlo del archivo (y rotar lo que corresponda)."
    echo "  Si el dato es legítimo y necesario, agregar una excepción EXPLÍCITA y justificada"
    echo "  (patrón + path) en el allowlist de este script, nunca un comodín."
    exit 1
fi
echo "RESULTADO: PASS — 0 bloqueantes, $WARN_HITS aviso(s) de marca."
echo "  (los avisos no rompen el build; se limpian cuando se toca ese archivo)"
exit 0
