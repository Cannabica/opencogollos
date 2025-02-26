<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlantState extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'days_since', 'days_until', 'min_daylight_hours', 'max_daylight_hours', 'min_humidity', 'max_humidity', 'actions'];

    protected $casts = [
        'actions' => 'array',  // Para manejar acciones como un array de datos
    ];
    
    public function actions()
    {
        return $this->belongsToMany(ActionType::class);
    }
}
