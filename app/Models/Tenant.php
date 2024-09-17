<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'active'];

    public function users()
    {
        return $this->hasMany(User::class);
    }
  
    public function indoors()
    {
        return $this->hasMany(Indoor::class);
    }
}
