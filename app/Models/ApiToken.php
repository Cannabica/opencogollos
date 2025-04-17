<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'token_hash',
        'token',
        'expires_at',
        'renew_count',
        'last_renewed_at',
        'reference'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_renewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
