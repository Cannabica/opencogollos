<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramUserTenant extends Model
{
    protected $table = 'telegram_user_tenant';

    protected $fillable = [
        'telegram_user_id',
        'tenant_id', 
        'telegram_username',
        'expires_at'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}