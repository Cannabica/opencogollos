<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActionType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'tenant_id', 'action_class'];

    /**
     * El catálogo es global (`tenant_id` null) y cada grupo puede agregar los suyos: sin scope, el
     * panel tenant (`ActionType::all()` / `pluck`) mostraba los tipos personalizados de OTROS grupos
     * y permitía usarlos. Con `allowGlobal` ve los globales + los propios, y nada más.
     */
    protected static function booted()
    {
        static::addGlobalScope(new TenantScope(allowGlobal: true));
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
