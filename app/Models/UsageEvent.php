<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageEvent extends Model
{
    use HasFactory;

    /**
     * Solo created_at: los eventos de uso son inmutables por diseño.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'panel',
        'route_name',
        'module',
        'action',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
