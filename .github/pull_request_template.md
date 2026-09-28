# Qué cambia

<!-- 1-3 líneas: qué problema resuelve y cómo se nota desde afuera. -->

## Checklist de los riesgos que YA se nos escaparon una vez

### 1. Datos de grupo (multi-tenant)

- [ ] **No aplica** (el cambio no toca datos de grupos) ← tildá esto y seguí
- [ ] Si AGREGA o MODIFICA una consulta sobre `Indoor` / `Plant` / `Action` / `Seed` / `ActionType` /
      `CropPlan`: cruza por el tenant (`->where('tenant_id', …)`, `->whereHas('indoor', …)`) **o** corre
      con el contexto fijado a propósito (`TenantContext::use()` / `useAll()`).
      ⚠️ Sin contexto y sin sesión el scope **no devuelve nada**: si ves contadores en 0 o `first()` null
      en un camino nuevo, falta fijar el contexto.
- [ ] Si agrega un MODELO con datos de grupo: le registró el `TenantScope` (o lo sumó a `EXENTOS` en
      `TenantIsolationGuardTest` con el motivo escrito).
- [ ] Test en las **dos direcciones**: el grupo no ve lo ajeno **y sigue viendo lo propio**
      (`tests/Feature/TenantScopeTest.php`, `tests/Feature/Telegram/AislamientoTenantsBotTest.php`).

### 2. Caminos SIN sesión (webhook del bot, jobs, cron, artisan)

- [ ] Si toca un camino que corre **sin usuario logueado**: verifiqué que **falla cerrado**, no sólo que
      "no rompe". Un `find($id)` / `where('name', …)` con un dato que viene de afuera (argumento del
      comando, `callback_data`, texto de un mensaje) es **input del usuario**: se cruza contra el tenant
      o no se devuelve nada (y se corta con alerta + `return`, sin escribir).

### 3. Secretos y datos personales

- [ ] No se guardan secretos en claro — ni "al lado del hash" (`metadata`, columnas de texto, caches).
- [ ] Los logs no imprimen tokens ni datos de contacto completos.

### 4. Instalación / configuración

- [ ] Si agrega o cambia una variable de entorno: `config/*.php` → mapa de `PlatformConfigTest` →
      `.env.example` → variable del repo de deploy (la instalación falla en silencio, sin esto).

### 5. Gates corridos (pegar la salida REAL, no "ya lo corrí")

- [ ] `DB_CONNECTION=sqlite DB_DATABASE=":memory:" php artisan test` → `Tests: N passed`
- [ ] `vendor/bin/phpstan analyse --memory-limit=1G` → `[OK] No errors`

## Cómo lo probé

<!-- El camino REAL, no la pieza: comando / URL / pantalla / update de Telegram inyectado, y qué
     contestó. Si es el bot: "webhook entregado status 200 → saliente: <texto>". -->
