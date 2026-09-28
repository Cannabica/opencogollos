# Pipeline y flujo de ramas

Este repo es el **producto open source** (código + imagen Docker). El **deploy a producción vive en
otro repo, privado**, que consume lo que este publica. Este documento describe el flujo tal como está
implementado hoy, con el archivo que lo implementa al lado — no como debería ser.

## Ramas

| Rama | Rol |
|---|---|
| `develop` | **Integración y rama por defecto.** Todo entra acá primero. |
| `main` | **Producción.** Sólo recibe merges de `release/*` y `hotfix/*`. Cada push dispara el release. |
| `feature/*`, `fix/*`, `chore/*` | Trabajo diario. Salen de `develop` y vuelven por PR. |
| `release/x.y.z` | **Estabilización (el camino oficial).** Sale de `develop` cuando se congela una versión, recibe sólo fixes, y mergea a `main` **y** a `develop`. El bump de versión vive acá. |
| `hotfix/x` | Fix urgente de producción. Sale de `main`, mergea a `main` **y** a `develop`. |

Regla: **`main` nunca recibe commits directos** — sólo merges de `release/*` o `hotfix/*` (con el tag
calculado del `composer.json` de la rama que llega). Nada de ramas de release con otros nombres
(`chore/release-*`, `release-v*`): la convención es `release/x.y.z` y el CI corre en `release/**`.

## Qué corre y cuándo

| Evento | Workflow | Jobs | Qué hace |
|---|---|---|---|
| PR a `develop`, `main` o `release/**` | `ci.yml` | `phpunit`, `phpstan`, `trivy`, `fugas` | Gate de calidad. Los 4 corren en paralelo. |
| Push a `develop` o a `release/**` | `ci.yml` | (los mismos 4) | Mismo gate, sin PR de medio. |
| Push a `main` | `release.yml` | `release` | Lee `composer.json` → tag `vX.Y.Z` → build → publica la imagen en GHCR (`:vX.Y.Z` + `:latest`). |
| Push a `main` | `downmerge.yml` | `downmerge` | Abre (idempotente) el PR `main` → `develop` si `develop` no contiene a `main`. |
| Manual | `deploy-prod.yml` **del repo de operación** | `validar`, `deploy` | Valida tag + imagen publicada y deploya al servidor. Doble confirmación. |

### Por qué el release lee `composer.json` y no el mensaje del commit
El tag se calcula **después** del merge, leyendo la versión de la rama destino. No depende de cómo se
mergeó (merge commit, squash, rebase) ni de un prefijo en el mensaje. Si el tag ya existe apuntando a
**otro** commit, `release.yml` **falla ruidoso** en vez de publicar la imagen de un tag viejo: eso
significa que `composer.json` quedó con una versión stale y hay que bumpearla antes de mergear.

## Ciclo de release (con `release/x.y.z`)

```bash
# 1. Cortar la rama de estabilización desde develop
git switch -c release/0.22.0 develop
#   editar "version" en composer.json -> 0.22.0  (el bump vive en la RAMA, no en develop ni en main)
git commit -am "chore(release): bump a 0.22.0" && git push -u origin release/0.22.0
#   el CI corre en release/** igual que en develop: puerta abierta para estabilizar

# 2. Estabilización (opcional): los fixes que aparezcan van a release/0.22.0 como PR
#    (con base release/0.22.0). develop sigue libre para la próxima versión.

# 3. Release a producción: release/0.22.0 -> main (el merge dispara release.yml)
gh pr create --base main --head release/0.22.0 --title "Release v0.22.0" --fill
#   al mergear: tag v0.22.0 + imagen ghcr.io/<org>/<repo>:v0.22.0 + :latest

# 4. Bajar release/0.22.0 a develop (el fix que se hizo estabilizando tiene que vivir en develop)
gh pr create --base develop --head release/0.22.0 --title "release/0.22.0 -> develop" --fill

# 5. downmerge.yml baja `main` a `develop` automáticamente (PR + auto-merge si está habilitado).

# 6. Deploy: en el repo de operación, workflow_dispatch con version=v0.22.0
#    El rollback es el mismo workflow con la versión anterior.
```

Una versión **patch** que no necesita estabilización puede ir directo `develop` → `main` (mismo
resultado: tag + imagen). Lo que no se hace es mergear a `main` desde una rama con otro nombre.

## Hotfix

```bash
git switch -c hotfix/fix-x main
#   fix + bump de patch en composer.json (ej. 0.21.1 -> 0.21.2)
gh pr create --base main --title "Hotfix v0.21.2" --fill   # merge -> tag + imagen
# El retorno a develop lo cubre downmerge.yml automáticamente (mismo camino que el release).
```

## Guard de fugas (repo público)

Todo lo que se commitea acá queda indexado para siempre, incluso si se borra en el commit siguiente.
El job `fugas` de `ci.yml` corre dos capas:

- **`scripts/guard-fugas.py`** — infraestructura de una instalación concreta (direcciones IP, rutas
  home, emails de proveedores personales) e identidad personal. **Bloquea.** Los valores exactos de
  la instancia **no están en el script** (es público): van como SHA-256 en la `DENYLIST`. Las
  menciones de la *marca* y las IPs públicas sólo se reportan como aviso: el repo se limpia de
  infraestructura, no de marca.
- **`gitleaks`** — credenciales, sobre la **historia completa**, con el binario oficial pineado por
  versión y sha256. Las excepciones aprobadas viven en `.gitleaks.toml`, scoped por path + patrón.

Correrlo localmente antes de pushear:

```bash
bash scripts/guard-fugas.sh
docker run --rm -v "$PWD:/repo" zricethezav/gitleaks:v8.28.0 \
  detect --source=/repo --config=/repo/.gitleaks.toml --redact --no-banner
```

## Deuda conocida

- El downmerge depende de que la organización permita a Actions crear PRs (`allow_auto_merge` +
  *"Allow GitHub Actions to create and approve pull requests"*); sin eso deja un issue de recordatorio.
- La rotación del token histórico allowlisteado en `.gitleaks.toml` (ver el motivo ahí).

## Protección de ramas

| Rama(s) | Cómo | Qué exige |
|---|---|---|
| `develop`, `main` | branch protection clásica | `phpunit`, `phpstan`, `trivy`, `fugas`. Sin force-push, sin borrado. |
| `release/**` | **ruleset** (`release-branches`) | los mismos 4 checks + sin force-push ni borrado. Los rulesets SÍ aceptan patrones; la protección clásica no. |

Sin reviews obligatorias en ninguna: el repo lo mantiene una sola persona y exigir una aprobación
propia trabaría el flujo sin agregar control real.
