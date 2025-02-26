<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{

    public function __construct(public bool $byIndoor = false)
    {}

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
         // Obtener el tenant_id del usuario logueado
         $user = Auth::user();

         if ($user) {
            $tenantId = $user->tenant_id;
            // Aplicar el filtro: solo mostrar seeds con el tenant_id del usuario o null
            if ($this->byIndoor) {
                $builder->whereIn('indoor_id', $user->tenant->indoors->pluck('id'));
            } else {
                $builder->where('tenant_id', $tenantId)
                    ->orWhereNull('tenant_id');
            }

         } else {
            // Si no hay usuario logueado, no mostrar nada
            $builder->where('tenant_id', null);
         }
    }
}
