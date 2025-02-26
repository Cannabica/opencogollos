<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class CropPlanScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
         // Obtener el tenant_id del usuario logueado
         $user = Auth::user();
         $tenantId = $user->tenant_id;
 
         // Aplicar el filtro: solo mostrar seeds con el tenant_id del usuario o null
         $builder->where('tenant_id', $tenantId)
                 ->orWhereNull('tenant_id');
    
    }
}
