#!/usr/bin/env python3
"""Guard de fugas del árbol de un repo PÚBLICO.

POR QUÉ EXISTE
    Todo lo que se commitea queda indexado para siempre, incluso si se borra en el commit
    siguiente. Este guard corre en cada PR y push (job `fugas` de ci.yml) para que la fuga no
    llegue a `develop` ni a `main`.

    ⚠️ EL PROPIO SCRIPT ES PÚBLICO: acá NO se escriben los valores que se protegen, y tampoco
    hasheados. Los valores de la instancia se cargan de un archivo que no está en el repo (ver
    `cargar_denylist`); este archivo se queda con las reglas GENÉRICAS (formas, no valores).
    Dos versiones anteriores fallaron acá: una listaba los valores en texto plano y otra los
    guardaba como SHA-256 con su descripción, que es un inventario de la infraestructura y un
    hash reversible. No repetir ninguna de las dos.

QUÉ CUBRE Y QUÉ NO
    · CREDENCIALES: las escanea `gitleaks` (reglas + entropía), no este script. Ver `.gitleaks.toml`.
    · BLOQUEANTE (exit 1): infraestructura de una instalación concreta (IPs privadas, rutas home,
      emails de proveedores personales, claves privadas) + los valores exactos de la DENYLIST.
    · AVISO (no falla): IPs públicas (se revisan a mano), marca de la instancia y referencias al
      repo de operación. La regla del proyecto es "el repo se limpia de INFRAESTRUCTURA, no de
      marca": la marca puede aparecer, pero se reporta para que nadie meta un dominio de una
      instalación concreta donde sirve igual un dominio neutro.

USO
    python3 scripts/guard-fugas.py                 # árbol de trabajo (archivos trackeados)
    python3 scripts/guard-fugas.py --ref origin/main
    exit 0 = sin bloqueantes · 1 = hay bloqueantes
"""

from __future__ import annotations

import hashlib
import re
import subprocess
import sys
from pathlib import Path

# --------------------------------------------------------------------------------------
# Denylist de valores EXACTOS de la instancia.
#
# NO VIVE EN EL REPO, ni siquiera hasheada. Un SHA-256 sin sal no protege un valor de espacio chico
# (medido: un puerto de 5 dígitos se revierte en 46.312 intentos / 0,08 s) y el label al lado le dice
# al atacante qué buscar: los labels que este archivo tenía eran, en conjunto, el inventario de la
# infraestructura de la instalación.
#
# Se carga de afuera, si está:
#   · local  → `.instance-values` en la raíz del repo (gitignored): una línea por valor,
#              con el formato `<descripción corta>=<valor>`.
#   · CI de este repo → no está: se corre con las reglas genéricas y el reporte lo dice.
#   · CI del repo de operación → lo escribe desde el secret de la instancia antes de correr.
# --------------------------------------------------------------------------------------
ARCHIVO_VALORES = Path(".instance-values")


def cargar_denylist(ruta: Path = ARCHIVO_VALORES) -> dict[str, str]:
    """{sha256(valor): descripción}. Vacío si el archivo no está (el repo público no lo tiene)."""
    if not ruta.exists():
        return {}
    vals: dict[str, str] = {}
    for linea in ruta.read_text(encoding="utf-8").splitlines():
        linea = linea.strip()
        if not linea or linea.startswith("#") or "=" not in linea:
            continue
        desc, _, valor = linea.partition("=")
        valor = valor.strip()
        if valor:
            vals[hashlib.sha256(valor.encode()).hexdigest()] = desc.strip() or "valor de la instancia"
    return vals


DENYLIST = cargar_denylist()

# --------------------------------------------------------------------------------------
# Reglas GENÉRICAS: formas sospechosas, sin nombrar nada de ninguna instalación.
# --------------------------------------------------------------------------------------
IPV4 = re.compile(r"(?<![\d.])(?:\d{1,3}\.){3}\d{1,3}(?![\d.])")

# IPs que NO son un dato de infraestructura: loopback, wildcard y los rangos que la RFC 5737
# reserva para documentación (los usan los ejemplos y los tests).
IPS_IGNORADAS = (
    "127.", "0.0.0.0", "255.255.255.255",
    "192.0.2.", "198.51.100.", "203.0.113.",
    "1.2.3.4", "8.8.8.8", "1.1.1.1",
)

BLOQUEANTES = [
    (
        "IP privada (RFC 1918)",
        re.compile(r"(?<![\d.])(?:10\.\d{1,3}\.\d{1,3}\.\d{1,3}|192\.168\.\d{1,3}\.\d{1,3}"
                   r"|172\.(?:1[6-9]|2\d|3[01])\.\d{1,3}\.\d{1,3})(?![\d.])"),
    ),
    (
        "email de proveedor personal",
        re.compile(r"[\w.+-]+@(?:gmail|googlemail|hotmail|outlook|live|yahoo|protonmail|proton"
                   r"|icloud|me|aol)\.\w{2,}\b", re.IGNORECASE),
    ),
    (
        "ruta home de un usuario",
        re.compile(r"/home/(?!runner\b|user\b|www-data\b|vscode\b)[a-z_][a-z0-9_-]{2,}"),
    ),
    (
        "clave privada",
        re.compile(r"-----BEGIN [A-Z ]*PRIVATE KEY-----"),
    ),
]

AVISOS = [
    ("marca de la instancia", re.compile(r"cannabica\.(?:ar|app)", re.IGNORECASE)),
    ("referencia a un repo que no es este", re.compile(r"cannabica-deploy", re.IGNORECASE)),
    ("nombre de un servicio de la instalación", re.compile(r"public-php")),
]

# Tokens candidatos para cruzar contra la DENYLIST: palabras, paths, emails, IPs y puertos.
TOKEN = re.compile(r"[A-Za-z0-9_.@:/-]{4,80}")
# Archivos exentos: contiene los patrones POR DEFINICIÓN (es la regla que se verifica).
EXENTOS = {"scripts/guard-fugas.py"}

# Lockfiles: llevan METADATA de terceros (emails de autores de paquetes, números con forma de IP
# tipo "1.54.0.1" en una versión). No son datos de esta instalación y generarían ruido permanente,
# así que ahí no se aplican las reglas de email ni de IP. El resto de las reglas SÍ corre.
METADATA = {"composer.lock", "package-lock.json"}

# Headers de autor/licencia (los traen los paquetes vendorizados y los assets de Filament):
# un email ahí es metadata de un tercero, no un dato de esta instalación. La DENYLIST sigue
# aplicándose igual sobre esas líneas, así que un email propio en un comentario se sigue cazando.
AUTOR = re.compile(r"@author|@copyright|copyright|@link|\(c\)\s", re.IGNORECASE)


def archivos(ref: str | None) -> list[tuple[str, str]]:
    """Devuelve [(ruta, texto)] de los archivos TRACKEADOS de la ref (o del working tree)."""
    if ref:
        listado = subprocess.run(
            ["git", "ls-tree", "-r", "--name-only", "-z", ref],
            capture_output=True, check=True,
        ).stdout.decode().split("\0")
        rutas = [r for r in listado if r]
        # `git cat-file --batch` = un solo proceso para todos los blobs (rápido).
        pedido = subprocess.run(
            ["git", "cat-file", "--batch"],
            input="\n".join(f"{ref}:{r}" for r in rutas).encode(),
            capture_output=True, check=True,
        ).stdout
        salida, i = [], 0
        for ruta in rutas:
            fin = pedido.find(b"\n", i)
            if fin == -1:
                break
            cabecera = pedido[i:fin].split()
            i = fin + 1
            if len(cabecera) < 3:      # "missing" (no debería pasar: viene de ls-tree)
                continue
            tam = int(cabecera[2])
            crudo = pedido[i:i + tam]
            i += tam + 1               # +1 = el \n que sigue al contenido
            salida.append((ruta, crudo.decode("utf-8", errors="replace")))
        return salida

    rutas = subprocess.run(
        ["git", "ls-files", "-z"], capture_output=True, check=True,
    ).stdout.decode().split("\0")
    salida = []
    for ruta in rutas:
        if not ruta:
            continue
        try:
            with open(ruta, "rb") as fh:
                salida.append((ruta, fh.read().decode("utf-8", errors="replace")))
        except (OSError, UnicodeDecodeError):
            continue
    return salida


def es_octeto_valido(ip: str) -> bool:
    return all(0 <= int(o) <= 255 for o in ip.split("."))


def ip_ignorada(ip: str) -> bool:
    return any(ip == pref or ip.startswith(pref) for pref in IPS_IGNORADAS)


def main() -> int:
    ref = None
    args = sys.argv[1:]
    while args:
        a = args.pop(0)
        if a == "--ref" and args:
            ref = args.pop(0)
        elif a.startswith("--ref="):
            ref = a.split("=", 1)[1]
        else:
            print(f"uso: {sys.argv[0]} [--ref <ref>]", file=sys.stderr)
            return 2

    alcance = f"ref {ref}" if ref else "árbol de trabajo (archivos trackeados)"
    print(f"Guard de fugas — alcance: {alcance}")
    print("  (los valores de la instancia se cargan de `.instance-values`, fuera del repo)")

    bloqueantes: list[str] = []
    avisos: list[str] = []

    for ruta, texto in archivos(ref):
        if ruta in EXENTOS:
            continue
        for nro, linea in enumerate(texto.splitlines(), 1):
            ubic = f"{ruta}:{nro}: {linea.strip()[:120]}"

            for nombre, patron in BLOQUEANTES:
                if ruta in METADATA and nombre == "email de proveedor personal":
                    continue
                if nombre == "email de proveedor personal" and AUTOR.search(linea):
                    continue
                if patron.search(linea):
                    bloqueantes.append(f"[{nombre}] {ubic}")

            for nombre, patron in AVISOS:
                if patron.search(linea):
                    avisos.append(f"[{nombre}] {ubic}")

            # IPs: privadas ya caen arriba; las públicas se avisan (hay que mirarlas una vez).
            if ruta not in METADATA:
                for m in IPV4.finditer(linea):
                    ip = m.group(0)
                    if es_octeto_valido(ip) and not ip_ignorada(ip):
                        avisos.append(f"[IP pública] {ubic}")

            # Denylist: se hashean los tokens y se comparan contra los hashes conocidos.
            for token in TOKEN.findall(linea):
                h = hashlib.sha256(token.encode()).hexdigest()
                if h in DENYLIST:
                    bloqueantes.append(f"[dato de la instancia: {DENYLIST[h]}] {ubic}")

    print()
    if not DENYLIST:
        print("  (denylist de la instancia NO cargada: corre sólo con las reglas genéricas.")
        print("   El repo público no tiene los valores a propósito — ver scripts/guard-fugas.py)")
    print("== BLOQUEANTE: infraestructura / identidad / datos de la instancia ==")
    if bloqueantes:
        for b in dict.fromkeys(bloqueantes):
            print(f"  ✗ {b}")
    else:
        print("  ✓ sin coincidencias")

    print()
    print("== AVISO: IPs públicas y marca de la instancia (no falla el build) ==")
    if avisos:
        for a in dict.fromkeys(avisos):
            print(f"  · {a}")
    else:
        print("  · sin coincidencias")

    print()
    if bloqueantes:
        print(f"RESULTADO: FAIL — {len(set(bloqueantes))} bloqueante(s), {len(set(avisos))} aviso(s).")
        print("  Si el dato es real: sacarlo del archivo (y rotar lo que corresponda).")
        print("  Si es legítimo y necesario: ajustar una regla genérica, con el motivo escrito al lado,")
        print("  o sumar el valor a `.instance-values` (local, fuera del repo).")
        return 1
    print(f"RESULTADO: PASS — 0 bloqueantes, {len(set(avisos))} aviso(s).")
    print("  (los avisos no rompen el build; se limpian cuando se toca ese archivo)")
    return 0


if __name__ == "__main__":
    sys.exit(main())
