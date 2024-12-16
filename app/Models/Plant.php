<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plant extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'indoor_id', 'seed_id', 'state', 'germination_date', 'flowerpot', 'capacity', 'base_floor', 'soil_enrichment'];

    protected $casts = [
        'base_floor' => 'array',
        'soil_enrichment' => 'array',
    ];

    public function seedType()
    {
        return $this->belongsTo(Seed::class, 'seed_id');
    }

    public function indoor()
    {
        return $this->belongsTo(Indoor::class, 'indoor_id');
    }

    // public function plant_state()
    // {
    //     return $this->belongsTo(PlantState::class, 'plant_state_id');
    // }
    
public function actions()
    {
        return $this->belongsToMany(Action::class, 'action_plant');
    }
}
