<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Indoor extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'tenant_id', 'fan_number','lamps'];

    protected $casts = [
        'lamps' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

}
