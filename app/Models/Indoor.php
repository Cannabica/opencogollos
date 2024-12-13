<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Indoor extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $fillable = ['name', 'large', 'width', 'fans', 'hygometer', 'humidifier', 'peak_quantity', 'scheduled_time', 'times_a_day', 'scheduled_days', 'height', 'tenant_id', 'fan_number','lamps'];

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

}
