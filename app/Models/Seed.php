<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\TenantScope;

class Seed extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'tenant_id', 'seed_type', 'flowering_time', 'ratio_ths', 'ratio_cbd'];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
}
