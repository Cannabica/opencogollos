<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Plant extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'indoor_id', 'seed_id', 'state', 'germination_date', 'flowerpot', 'capacity', 'base_floor', 'soil_enrichment'];

    protected $casts = [
        'base_floor' => 'json',
        'soil_enrichment' => 'json',
    ];

    public function seedType()
    {
        return $this->belongsTo(Seed::class, 'seed_id');
    }

    public function seed()
    {
        return $this->belongsTo(Seed::class);
    }
    public function indoor()
    {
        return $this->belongsTo(Indoor::class);
    }

    // public function plant_state()
    // {
    //     return $this->belongsTo(PlantState::class, 'plant_state_id');
    // }

    public function actions()
    {
        return $this->belongsToMany(Action::class, 'action_plant')
                    ->withTimestamps()
                    ->withPivot(['id']);
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($plant) {
            Log::info('Creando planta con datos:', [
                'datos' => $plant->toArray(),
                'tenant_id' => auth()->user()->tenant_id ?? 'no_auth'
            ]);
        });

        static::updating(function ($plant) {
            Log::info('Actualizando planta con datos:', [
                'id' => $plant->id,
                'datos_originales' => $plant->getOriginal(),
                'datos_nuevos' => $plant->getDirty(),
                'tenant_id' => auth()->user()->tenant_id ?? 'no_auth'
            ]);
        });

        static::deleting(function ($plant) {
            // Eliminar las relaciones en la tabla pivote
            $plant->actions()->detach();
        });
    }

    protected static function booted()
    {
        // `plants` NO tiene columna `tenant_id`: el tenant sale del indoor (variante byIndoor).
        // Sin esto, cualquier `Plant::find($id)` (los comandos del bot, por ejemplo) veía plantas de
        // otros grupos. Era un `use` importado y el `addGlobalScope` olvidado (ver §5.2 del board).
        static::addGlobalScope(new TenantScope(byIndoor: true));
    }
}
