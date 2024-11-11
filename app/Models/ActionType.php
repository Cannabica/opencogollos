<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActionType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'tenant_id', 'action_class'];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
