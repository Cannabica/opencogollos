#!/usr/bin/env python3
"""Guard de COMUNICACIÓN del repo PÚBLICO: las superficies de GitHub que no son archivos.

POR QUÉ EXISTE
    `guard-fugas.py` escanea el ÁRBOL: lo que se commitea. Pero la fuga más grande que tuvo este
    repo no estuvo en ningún archivo — estuvo en el **título y el cuerpo de un PR** y en el
    **mensaje de un commit**, que son texto libre, quedan indexados igual que un archivo y
    (a diferencia de un archivo) no los mira ningún gate. Medido 2026-09-28:
      · el cuerpo de un PR publicó los valores de infraestructura en texto plano;
      · el cuerpo de otro PR apuntó al SHA exacto del commit que los contiene;
      · el MENSAJE de ese commit contiene 2 de los 8 valores de la DENYLIST — y un mensaje de
        commit no se puede editar: es inmutable.
    Y el caso que abre esta política: el cuerpo de un PR de hotfix explicaba el vector de una
    vulnerabilidad de autorización con sus rutas concretas. El repo es público: eso es una guía.

QUÉ ESCANEA (las 3 superficies del evento; nunca los archivos)
    1. título del PR      2. cuerpo del PR      3. mensajes de commit del rango del PR

REGLAS
    · BLOQUEANTE — datos: los mismos valores (DENYLIST por SHA-256, ver guard-fugas.py) y las
      mismas formas (IP privada, ruta home, email) + cualquier email de tercero. Se reusan las
      reglas de `guard-fugas.py`: una sola fuente de verdad, cero listas duplicadas.
    · BLOQUEANTE — el PAR EXPLOTABLE: un mecanismo de ataque (idor/bypass/fuga entre grupos/
      escalada…) **junto con** el vector concreto (ruta de la app o callback con id) en el mismo
      texto. Una cosa sin la otra es lo que la política permite: se describe el EFECTO, no el CÓMO.
    · AVISO (no falla) — mecanismo de ataque sin vector, y el nombre de la rama de trabajo
      (ya está publicado: borrarla es limpieza, no se puede des-indexar).

    ⚠️ EL PROPIO SCRIPT ES PÚBLICO: acá NO se escriben ni los valores que se protegen ni las rutas
    de los vectores. Las categorías se nombran, los valores nunca.

DISEÑO DELIBERADO: BLOQUEA SÓLO LO QUE PODÉS ARREGLAR ANTES DE MERGEAR
    Un título, un cuerpo y un mensaje de commit se editan (el mensaje, con un commit nuevo de
    corrección). Un SHA, un nombre de rama que ya se pusheó y un mensaje de commit ya indexado NO.
    Lo segundo se reporta como deuda con su remediación; no se bloquea el build por algo que el
    autor no puede resolver desde la rama.

USO
    python3 scripts/guard-comunicacion.py                            # CI: lee $GITHUB_EVENT_PATH
    python3 scripts/guard-comunicacion.py --body-file /tmp/pr.md     # revisar ANTES de abrir el PR
    python3 scripts/guard-comunicacion.py --title "..." --body-file … --range origin/develop..HEAD
    exit 0 = sin bloqueantes · 1 = hay bloqueantes
"""

from __future__ import annotations

import hashlib
import importlib.util
import json
import os
import re
import subprocess
import sys
from pathlib import Path

# --------------------------------------------------------------------------------------
# Reuso de las reglas de guard-fugas.py (mismo directorio). NO se duplican patrones ni
# valores: si mañana se agrega un valor a la DENYLIST, este guard lo cubre solo.
# El nombre del archivo lleva guion, así que no es importable por `import`: se carga por ruta.
# --------------------------------------------------------------------------------------
_aqui = Path(__file__).resolve().parent
_spec = importlib.util.spec_from_file_location("guard_fugas", _aqui / "guard-fugas.py")
assert _spec is not None and _spec.loader is not None, "no se pudo cargar guard-fugas.py"
gf = importlib.util.module_from_spec(_spec)
_spec.loader.exec_module(gf)

# Candidatos para cruzar contra la DENYLIST: palabras, caminos y valores pegados.
TOKEN = re.compile(r"[A-Za-z0-9_.:@/-]{3,}")
# Segunda segmentación, SIN los separadores de path/namespace. Hace falta porque `TOKEN` se come
# un nombre de rama con el valor pegado y una ruta de servidor como UNA sola pieza: con la primera
# segmentación el valor no llega al hash; con esta, se rescata el valor embebido en un nombre de
# rama o en una URL. Se prueban las dos y gana cualquiera que matchee la DENYLIST (medido: una rama
# con el puerto del server pegada al nombre pasaba limpia antes de esto).
TOKEN_CORTO = re.compile(r"[A-Za-z0-9_.@]{3,}")

# Emails de EJEMPLO (los que la propia documentación usa): no son un dato de nadie.
EMAIL = re.compile(r"[\w.+-]+@[\w-]+\.[\w.]{2,}")
EMAIL_EJEMPLO = re.compile(r"@(example\.(com|org|net)|test|localhost|invalid)$|^(user|usuario|test|alguien)@",
                           re.IGNORECASE)
# El alias noreply de GitHub NO es un buzón: es exactamente el reemplazo que este guard recomienda.
# Tratarlo como dato personal hacía que el propio arreglo recomendado disparara el aviso.
EMAIL_ALIAS = re.compile(r"@users\.noreply\.github\.com$", re.IGNORECASE)

# Cualquier email de una persona es dato personal: en un repo público no va. (Más amplio que
# guard-fugas.py, que en ARCHIVOS sólo bloquea los proveedores personales para no romper con
# la metadata de autor de los paquetes vendorizados — acá no hay metadata de terceros.)
def es_email_de_persona(texto: str) -> bool:
    for m in EMAIL.finditer(texto):
        if not (EMAIL_EJEMPLO.search(m.group(0)) or EMAIL_ALIAS.search(m.group(0))):
            return True
    return False

# --- El PAR EXPLOTABLE -------------------------------------------------------------------
# Un mecanismo de ataque NOMBRADO. Solo, es un aviso (la política permite describir el efecto).
MECANISMOS = re.compile(
    r"\b(idor|bypass|eludi\w+|escalad\w+\s+de\s+privilegios|"
    r"fuga\s+(entre|de)\s+(grupos?|tenants?|cuentas?|datos?)|"
    r"acceso\s+no\s+autorizado|sin\s+autorizaci[oó]n|"
    r"robo\s+de\s+token|token\s+en\s+claro|"
    r"enumeraci[oó]n\s+de\s+(ids?|recursos?))\b",
    re.IGNORECASE,
)
# El vector CONCRETO: una ruta de la app o un callback con id. Solo, no significa nada.
VECTOR = re.compile(
    r"(?<![\w/])/(?:api|action|plant|indoor|livewire|telegram|tenant|superadmin)[\w/{}\-.]*"
    r"|\bcallback_data\b"
    r"|\b[a-z][a-z0-9_]{3,}:\d+\b"      # callback con id, ej. nombre_accion:2
)

# Nombres de rama que anuncian el vector. La rama ya se pusheó: es aviso + deuda de limpieza.
RAMA_VECTOR = re.compile(r"(idor|vuln|cve-\d|bypass|exploit|fuga|hack|crack|injection|sqli|xss)",
                         re.IGNORECASE)

ROJO, AMARILLO, VERDE, GRIS, FIN = "\033[31m", "\033[33m", "\033[32m", "\033[90m", "\033[0m"
if not sys.stdout.isatty():
    ROJO = AMARILLO = VERDE = GRIS = FIN = ""


def ocultar(texto: str, patron: re.Pattern[str]) -> str:
    """Reemplaza el match por su categoría: el guard es público, no publica lo que protege."""
    return patron.sub(lambda m: f"«oculto: {len(m.group(0))} caracteres»", texto)


def limpiar(linea: str) -> str:
    return ocultar(linea.strip()[:160], EMAIL)


# Ramas que representan "ya publicado": si un commit es ancestro de alguna, está en el remoto y no se
# puede reescribir sin force-push (que en un repo público con PRs mergeados no se hace).
REF_PUBLICAS = ("origin/main", "origin/develop")


def ya_publicado(sha: str) -> bool:
    """¿El commit ya viaja en el remoto por una rama pública?

    Caso real que lo motivó (medido 2026-09-29): un PR de downmerge `main → develop` arrastra en su
    rango commits que ya estaban en `main` (el hotfix). Frenar el build por eso es inútil —el autor no
    puede reescribirlos— y sería un gate que se desactiva. Los del PR propio (que sólo viven en su
    rama) sí se pueden corregir: ésos bloquean.
    """
    for ref in REF_PUBLICAS:
        r = subprocess.run(["git", "merge-base", "--is-ancestor", sha, ref],
                           capture_output=True, text=True)
        if r.returncode == 0:
            return True
    return False


def revisar_bloqueantes(superficie: str, contenido: str, pos: list[str],
                        avisos: list[str] | None = None, sha_commit: str | None = None) -> None:
    """Aplica las reglas bloqueantes a un bloque de texto (título, cuerpo o mensaje).

    Si el bloque es el mensaje de un commit que YA está publicado, el hallazgo se degrada a aviso:
    un mensaje de commit pusheado no se edita, así que bloquear no arregla nada (ver `ya_publicado`).
    """
    if not contenido:
        return

    publicado = bool(sha_commit and ya_publicado(sha_commit))
    destino = avisos if (publicado and avisos is not None) else pos
    prefijo = "ya publicado, deuda" if publicado else ""

    def agregar(mensaje: str) -> None:
        destino.append(f"{mensaje} [{prefijo}]" if prefijo else mensaje)

    # 1. Formas genéricas (mismas reglas que el guard de archivos).
    for nombre, patron in gf.BLOQUEANTES:
        for m in patron.finditer(contenido):
            if nombre == "IP privada (RFC 1918)" and gf.ip_ignorada(m.group(0)):
                continue
            agregar(f"{superficie}: [{nombre}] {limpiar(contenido[max(0, m.start() - 40):m.end() + 40])}")

    # 2. IP pública + forma de IP válida (aviso en archivos, acá importa: un PR no lleva la IP).
    for m in gf.IPV4.finditer(contenido):
        if gf.es_octeto_valido(m.group(0)) and not gf.ip_ignorada(m.group(0)):
            agregar(f"{superficie}: [dirección IP] «oculto»")

    # 3. Email de una persona.
    if es_email_de_persona(contenido):
        agregar(f"{superficie}: [email de una persona] {limpiar(contenido)}")

    # 4. Los VALORES de la DENYLIST (por hash: el valor no se escribe ni en el código ni en el log).
    #    Se prueban las dos segmentaciones: la de path/namespace completos y la de piezas sueltas.
    for patron in (TOKEN, TOKEN_CORTO):
        for m in patron.finditer(contenido):
            token = m.group(0)
            h = hashlib.sha256(token.encode()).hexdigest()
            if h in gf.DENYLIST:
                agregar(f"{superficie}: [valor de la instalación — {gf.DENYLIST[h]}] «oculto»")

    # 5. El PAR EXPLOTABLE: mecanismo + vector concreto en el mismo texto.
    mecanismo = MECANISMOS.search(contenido)
    vector = VECTOR.search(contenido)
    if mecanismo and vector:
        agregar(
            f"{superficie}: [par explotable] mecanismo «{mecanismo.group(0)}» + vector concreto "
            f"«{vector.group(0)[:24]}» — describí el EFECTO, no el CÓMO (ver docs/COMUNICACION-REPO-PUBLICO.md)"
        )


def revisar_avisos(superficie: str, contenido: str, avisos: list[str]) -> None:
    if not contenido:
        return
    m = MECANISMOS.search(contenido)
    if m and not (MECANISMOS.search(contenido) and VECTOR.search(contenido)):
        avisos.append(f"{superficie}: [lenguaje] dijo «{m.group(0)}» sin vector: aceptado, "
                      f"revisá que no agregues el CÓMO")
    for nombre, patron in gf.AVISOS:
        if patron.search(contenido):
            avisos.append(f"{superficie}: [{nombre}]")


def commits_del_rango(rango: str | None) -> list[tuple[str, str, str]]:
    """[(sha corto, autor, mensaje)] de los commits del rango; sin rango, el último commit.

    El AUTOR va incluido a propósito: el email personal del mantenedor viaja en la metadata de
    autoría de 246 commits de `opencogollos` y hasta ahora ninguna superficie lo miraba — ni este
    guard ni `guard-fugas.py`, que escanean contenido de archivos/mensajes. Se reporta como
    aviso (ver `main`), no como bloqueante: se corrige en el `git config` del autor, no en el PR.
    """
    sep = "\x1e"  # record separator: el mensaje puede tener \x00 pero no \x1e
    if rango:
        cmd = ["git", "log", f"--format=%h%x00%an <%ae>%x00%B{sep}", rango]
    else:
        cmd = ["git", "log", "-1", f"--format=%h%x00%an <%ae>%x00%B{sep}"]
    try:
        crudo = subprocess.run(cmd, capture_output=True, text=True, check=True,
                               errors="replace").stdout
    except subprocess.CalledProcessError as exc:
        print(f"{AMARILLO}no se pudo leer el rango «{rango}»: {exc.stderr.strip()[:200]}{FIN}",
              file=sys.stderr)
        return []
    salida = []
    for registro in crudo.split(sep):
        if not registro.strip():
            continue
        campos = registro.split("\x00")
        if len(campos) < 3:
            continue
        salida.append((campos[0].strip(), campos[1].strip(), campos[2]))
    return salida


def main() -> int:
    titulo = cuerpo = rama = rango = None
    body_file = None
    args = sys.argv[1:]
    while args:
        a = args.pop(0)
        if a == "--title" and args:
            titulo = args.pop(0)
        elif a == "--body-file" and args:
            body_file = args.pop(0)
        elif a == "--range" and args:
            rango = args.pop(0)
        elif a == "--branch" and args:
            rama = args.pop(0)
        else:
            print(f"uso: {sys.argv[0]} [--title T] [--body-file F] [--range A..B] [--branch R]",
                  file=sys.stderr)
            return 2

    if body_file:
        cuerpo = Path(body_file).read_text(encoding="utf-8", errors="replace")
        rama = rama or subprocess.run(["git", "branch", "--show-current"], capture_output=True,
                                      text=True).stdout.strip()
    else:
        # Modo CI: el payload del evento trae todo (título, cuerpo, SHAs). Ni una interpolación
        # de shell con el cuerpo del PR: el JSON se lee del archivo que GitHub deja en disco.
        ruta = os.environ.get("GITHUB_EVENT_PATH")
        if not ruta or not Path(ruta).exists():
            print(f"{AMARILLO}sin --body-file y sin GITHUB_EVENT_PATH: nada que revisar{FIN}")
            return 0
        ev = json.loads(Path(ruta).read_text(encoding="utf-8"))
        if "pull_request" in ev:
            pr = ev["pull_request"]
            titulo = pr.get("title")
            cuerpo = pr.get("body")
            rama = (pr.get("head") or {}).get("ref")
            rango = f"{(pr.get('base') or {}).get('sha')}..{(pr.get('head') or {}).get('sha')}"
        elif "commits" in ev:
            rango = f"{ev.get('before')}..{ev.get('after')}"
            rama = (ev.get("ref") or "").replace("refs/heads/", "") or None
        else:
            print(f"{GRIS}evento sin PR ni commits: nada que revisar{FIN}")
            return 0

    print("Guard de comunicación — superficies del evento (título, cuerpo del PR, mensajes de commit)")
    print(f"{GRIS}(los valores de la instalación se detectan por SHA-256: lo que se protege no se escribe){FIN}")

    bloqueantes: list[str] = []
    avisos: list[str] = []

    if titulo:
        revisar_bloqueantes("título", titulo, bloqueantes)
        revisar_avisos("título", titulo, avisos)
    if cuerpo:
        revisar_bloqueantes("cuerpo", cuerpo, bloqueantes)
        revisar_avisos("cuerpo", cuerpo, avisos)

    commits = commits_del_rango(rango)
    print(f"{GRIS}commits revisados: {len(commits)}{FIN}")
    autores_vistos: set[str] = set()
    for sha, autor, mensaje in commits:
        # La metadata de autoría viaja en el commit y en un repo público no se puede sacar sin
        # reescribirlo todo: se AVISA (una vez por autor) y el fix es del entorno del autor.
        if autor and autor not in autores_vistos:
            autores_vistos.add(autor)
            previos: list[str] = []
            revisar_bloqueantes(f"autor de commit", autor, previos)
            if previos:
                avisos.append(
                    f"autor de commit «{autor}»: la metadata de autoría publica ese dato en CADA "
                    f"commit. Se corrige en tu entorno, no en el PR: "
                    f"`git config user.email '<id>+<usuario>@users.noreply.github.com'` "
                    f"(GitHub → Settings → Emails → Keep my email addresses private)"
                )
        if "Merge" in mensaje.splitlines()[0] and "[skip ci]" not in mensaje:
            # Los merge commits los escribe GitHub: no los controla el autor.
            continue
        revisar_bloqueantes(f"commit {sha}", mensaje, bloqueantes, avisos, sha_commit=sha)
        revisar_avisos(f"commit {sha}", mensaje, avisos)

    if rama:
        # El nombre de rama es texto público indexado igual que el título: se le aplican las MISMAS
        # reglas (un `feat/deploy-<puerto>` publicaba el puerto y sólo se miraba el vector).
        revisar_bloqueantes(f"rama «{rama}»", rama, bloqueantes)
        revisar_avisos(f"rama «{rama}»", rama, avisos)
        if RAMA_VECTOR.search(rama.split("/")[-1]):
            avisos.append(f"rama «{rama}»: el nombre anuncia el vector. Ya está publicada (queda en el "
                          f"remoto): nombrá la próxima por el EFECTO — ver docs/COMUNICACION-REPO-PUBLICO.md")

    for a in avisos:
        print(f"{AMARILLO}Aviso{FIN} {a}")

    if bloqueantes:
        print(f"\n{ROJO}✗ Guard de comunicación: {len(bloqueantes)} bloqueante(s){FIN}")
        for b in bloqueantes:
            print(f"  {b}")
        print(f"\n{ROJO}Qué hacer:{FIN} el título y el cuerpo del PR se editan (GitHub → Edit). Un mensaje de\n"
              f"commit ya indexado se corrige con un commit de corrección y, si el dato tiene que salir\n"
              f"de la historia, con un ticket de sensitive-data removal. El CÓMO de una vulnerabilidad va al\n"
              f"aviso privado de seguridad del repo, no al PR. Ver docs/COMUNICACION-REPO-PUBLICO.md.")
        return 1

    print(f"{VERDE}✓ Guard de comunicación: sin bloqueantes ({len(avisos)} aviso/s){FIN}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
