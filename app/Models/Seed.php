<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\TenantScope;

class Seed extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'tenant_id', 
        'seed_type', 
        'flowering_time', 
        'ratio_thc', 
        'ratio_cbd',
        'aprobado_inase',
        'provider'
    ];

    protected $casts = [
        'aprobado_inase' => 'boolean',
        'ratio_thc' => 'float',
        'ratio_cbd' => 'float',
        'flowering_time' => 'float',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isGlobal()
    {
        return is_null($this->tenant_id);
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('tenant_id');
    }

    public function scopeLocal($query)
    {
        return $query->whereNotNull('tenant_id');
    }

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);

        static::deleting(function ($seed) {
            if ($seed->isGlobal() && auth()->check() && auth()->user()->tenant_id !== null) {
                throw new \Illuminate\Validation\ValidationException(
                    validator([], []),
                    response()->json([
                        'message' => 'No se pueden eliminar semillas globales'
                    ])
                );
            }
        });

        static::updating(function ($seed) {
            if ($seed->isGlobal() && auth()->check() && auth()->user()->tenant_id !== null) {
                throw new \Illuminate\Validation\ValidationException(
                    validator([], []),
                    response()->json([
                        'message' => 'No se pueden modificar semillas globales'
                    ])
                );
            }
        });
    }
}
