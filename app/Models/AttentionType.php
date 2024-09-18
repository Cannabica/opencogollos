<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttentionType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'tenant_id'];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
