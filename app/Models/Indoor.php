<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Indoor extends Model
{
    use HasFactory;

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
    return $this->hasMany(Plant::class);
}


}
