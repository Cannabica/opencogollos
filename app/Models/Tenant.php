<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'email', 'active'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function indoors()
    {
        return $this->hasMany(Indoor::class);
    }

    public function apiTokens()
    {
        return $this->hasMany(ApiToken::class);
    }
}
