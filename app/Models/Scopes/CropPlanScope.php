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
        $user = Auth::user();
        
        if ($user && $user->tenant_id !== null) {
            // Usuarios tenant ven sus planes y los del superadmin
            $builder->where(function ($query) use ($user) {
                $query->where('tenant_id', $user->tenant_id)
                      ->orWhereNull('tenant_id');
            });
        }
    }
}
