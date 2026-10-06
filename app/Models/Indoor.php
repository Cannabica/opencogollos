<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

/**
 * Lugar (carpa, habitación, patio) donde crecen las plantas.
 *
 * @property int|null $plants_count Cantidad de plantas; sólo existe cuando la consulta trae
 *                                  `withCount('plants')` (lo usa el selector de lugar del dashboard).
 */
class Indoor extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $fillable = ['name', 'large', 'width', 'fans', 'hygometer', 'humidifier', 'peak_quantity', 'scheduled_time', 'times_a_day', 'scheduled_days', 'height', 'tenant_id', 'fan_number', 'lamps', 'crop_plan_id'];

    protected $casts = [
        'scheduled_days' => 'array',
        'fans' => 'array',
        'lamps' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plants()
    {
        return $this->hasMany(Plant::class, 'indoor_id');
    }

    public function actions()
    {
        return $this->hasMany(Action::class, 'indoor_id');
    }

    public function cropPlan()
    {
        return $this->belongsTo(CropPlan::class);
    }

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($indoor) {
            // Eliminar todas las plantas asociadas
            foreach ($indoor->plants as $plant) {
                $plant->delete();
            }

            foreach ($indoor->actions as $action) {
                 // Eliminar todas las acciones asociadas
                $action->delete();
            }
        });
    }

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

}
