# Pipeline y flujo de ramas

Este repo es el **producto open source** (código + imagen Docker). El **deploy a producción vive en
otro repo, privado**, que consume lo que este publica. Este documento describe el flujo tal como está
implementado hoy, con el archivo que lo implementa al lado — no como debería ser.

## Ramas

| Rama | Rol |
|---|---|
| `develop` | **Integración y rama por defecto.** Todo entra acá primero. |
| `main` | **Producción.** Sólo recibe merges de release/hotfix. Cada push dispara el release. |
| `feature/*`, `fix/*`, `chore/*` | Trabajo diario. Salen de `develop` y vuelven por PR. |
| `hotfix/*` | Fix urgente de producción. Sale de `main`, mergea a `main` **y** a `develop`. |

`release/*` está **documentado como opcional y hoy no se usa**: el release se hace mergeando
`develop` → `main` directo, con el bump de versión en una rama `chore/release-vX.Y.Z` que va a
`develop` antes del merge. Si en algún momento se quiere estabilizar sin frenar `develop`, `release/*`
es el camino — pero hay que elegir una sola convención (hoy conviven las dos).

## Qué corre y cuándo

| Evento | Workflow | Jobs | Qué hace |
|---|---|---|---|
| PR a `develop` o `main` | `ci.yml` | `phpunit`, `phpstan`, `trivy`, `fugas` | Gate de calidad. Los 4 corren en paralelo. |
| Push a `develop` | `ci.yml` | (los mismos 4) | Mismo gate, sin PR de por medio. |
| Push a `main` | `release.yml` | `release` | Lee `composer.json` → tag `vX.Y.Z` → build → publica la imagen en GHCR (`:vX.Y.Z` + `:latest`). |
| Push a `main` | `downmerge.yml` | `downmerge` | Abre (idempotente) el PR `main` → `develop` si `develop` no contiene a `main`. |
| Manual | `deploy-prod.yml` **del repo de operación** | `validar`, `deploy` | Valida tag + imagen publicada y deploya al servidor. Doble confirmación. |

### Por qué el release lee `composer.json` y no el mensaje del commit
El tag se calcula **después** del merge, leyendo la versión de la rama destino. No depende de cómo se
mergeó (merge commit, squash, rebase) ni de un prefijo en el mensaje. Si el tag ya existe apuntando a
**otro** commit, `release.yml` **falla ruidoso** en vez de publicar la imagen de un tag viejo: eso
significa que `composer.json` quedó con una versión stale y hay que bumpearla antes de mergear.

## Ciclo de release

```bash
# 1. Bump de versión en develop (rama chica, PR normal)
git switch -c chore/release-v0.22.0 develop
#   editar "version" en composer.json
git commit -am "chore(release): bump a 0.22.0" && git push -u origin chore/release-v0.22.0
gh pr create --base develop --fill

# 2. Release: develop -> main (el merge dispara release.yml)
gh pr create --base main --head develop --title "Release v0.22.0" --fill
#   al mergear: tag v0.22.0 + imagen ghcr.io/<org>/<repo>:v0.22.0 + :latest

# 3. Downmerge automático: downmerge.yml abre el PR main -> develop
#    (si el auto-merge está habilitado, se mergea solo cuando el CI da verde)

# 4. Deploy: en el repo de operación, workflow_dispatch con version=v0.22.0
#    El rollback es el mismo workflow con la versión anterior.
```

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

- **`scripts/guard-fugas.sh`** — infraestructura de una instalación concreta (direcciones IP, puertos
  de administración, hosts internos, rutas del servidor) e identidad personal (usuario, emails).
  **Bloquea.** Las menciones de la *marca* sólo se reportan como aviso: el repo se limpia de
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

- `release/*` documentado pero no usado (elegir una convención).
- El downmerge depende de que `allow_auto_merge` esté habilitado en el repo para cerrarse solo.
- La rotación del token histórico allowlisteado en `.gitleaks.toml` (ver el motivo ahí).
