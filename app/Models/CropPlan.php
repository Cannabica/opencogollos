<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CropPlanScope;
use Illuminate\Database\Eloquent\SoftDeletes;

class CropPlan extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'name',
        'tenant_id',
        'rest_pruning',
        'rest_fert',
        'stop_fert',
        'irrigation',
        'germination_since',
        'germination_until',
        'germination_light',
        'germination_darkness',
        'germination_humidity_since',
        'germination_humidity_until',
        'germination_temp_since',
        'germination_temp_until',
        'plantula_since',  // Cambiado de 'seedling_' a 'plantula_'
        'plantula_until',
        'plantula_light',
        'plantula_darkness',
        'plantula_humidity_since',
        'plantula_humidity_until',
        'plantula_temp_since',
        'plantula_temp_until',
        'vegetative_since',
        'vegetative_until',
        'vegetative_light',
        'vegetative_darkness',
        'vegetative_humidity_since',
        'vegetative_humidity_until',
        'vegetative_temp_since',
        'vegetative_temp_until',
        'flowering_since',
        'flowering_until',
        'flowering_light',
        'flowering_darkness',
        'flowering_humidity_since',
        'flowering_humidity_until',
        'flowering_temp_since',
        'flowering_temp_until',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function indoors()
    {
        return $this->hasMany(Indoor::class);
    }

    public function isGlobal()
    {
        return is_null($this->tenant_id);
    }

    protected static function booted()
    {
        static::creating(function ($cropPlan) {
            if (auth()->check()) {
                // Si es superadmin, tenant_id será null
                $cropPlan->tenant_id = auth()->user()->tenant_id;
            }
        });

        static::deleting(function ($cropPlan) {
            if (is_null($cropPlan->tenant_id) && auth()->check() && auth()->user()->tenant_id !== null) {
                throw new \Illuminate\Validation\ValidationException(
                    validator([], []),
                    response()->json([
                        'message' => 'No se pueden eliminar planes de cultivo globales'
                    ])
                );
            }
        });

        static::updating(function ($cropPlan) {
            if (is_null($cropPlan->tenant_id) && auth()->check() && auth()->user()->tenant_id !== null) {
                throw new \Illuminate\Validation\ValidationException(
                    validator([], []),
                    response()->json([
                        'message' => 'No se pueden modificar planes de cultivo globales'
                    ])
                );
            }
        });

        static::addGlobalScope(new CropPlanScope);
    }
}
