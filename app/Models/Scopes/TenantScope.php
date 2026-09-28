<?php

namespace App\Models\Scopes;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Aísla las consultas por tenant.
 *
 * Fuente del tenant: `TenantContext::current()` (contexto explícito → usuario logueado → consola).
 * Sin contexto y sin usuario **no devuelve nada** (falla cerrado): antes, ese caso era un `return`
 * silencioso (sin filtro) y por ahí se filtraban datos entre grupos — el webhook de Telegram no tiene
 * sesión, así que `Plant::find($id)` de un comando devolvía la planta de cualquier tenant.
 *
 * Variantes:
 * - normal (`tenant_id` en la propia tabla): `Indoor`, `Action`.
 * - `byIndoor: true` (modelos SIN `tenant_id`, como `Plant`): filtra por el tenant del indoor.
 * - `Seed`: además de las semillas del tenant, ve las GLOBALES (`tenant_id` null), que son compartidas.
 *
 * Para saltearlo en un contexto que necesita todo (seeders, backfills):
 * `Model::withoutGlobalScope(TenantScope::class)`.
 */
class TenantScope implements Scope
{
    /**
     * @param  bool  $byIndoor     El modelo NO tiene `tenant_id`: el tenant sale del indoor (`Plant`).
     * @param  bool  $allowGlobal  Además de lo del tenant, ve lo global (`tenant_id` null): catálogos
     *                             compartidos que cada grupo puede extender (`Seed`, `ActionType`).
     */
    public function __construct(public bool $byIndoor = false, public bool $allowGlobal = false) {}

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = TenantContext::current();

        if ($tenantId === false) {
            // Sin contexto ni usuario: no devolvemos nada (falla cerrado).
            $builder->whereRaw('1 = 0');

            return;
        }

        if ($tenantId === null) {
            // Contexto de servicio (superadmin, panel admin del bot, consola): sin filtro.
            return;
        }

        if ($this->byIndoor) {
            // Modelos sin `tenant_id` (ej. `plants`): el tenant sale del indoor.
            $builder->whereHas('indoor', fn (Builder $query) => $query->where('tenant_id', $tenantId));

            return;
        }

        if ($this->allowGlobal) {
            // Lo propio + lo global (sin tenant), que es de todos: catálogos compartidos.
            $builder->where(function (Builder $query) use ($tenantId) {
                $query->where('tenant_id', $tenantId)
                    ->orWhereNull('tenant_id');
            });

            return;
        }

        $builder->where($model->getTable().'.tenant_id', $tenantId);
    }
}
