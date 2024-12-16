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

    // #FRANKIE 16/12
//     public function getDaysInCurrentStage($plant)
// {
//     $lastAction = $plant->actions()
//         ->orderBy('action_date', 'desc')
//         ->first();

//     if ($lastAction) {
//         $currentStateStartDate = $lastAction->action_date;
//         return \Carbon\Carbon::now()->diffInDays($currentStateStartDate);
//     }

//     return \Carbon\Carbon::now()->diffInDays(date: $plant->germination_date);
// }

// public function getCurrentStateAction()
// {
//     return $this->actions()
//         ->orderBy('action_date', 'desc')
//         ->first();
// }

// public function getDaysInCurrentState()
// {
//     $currentStateAction = $this->getCurrentStateAction();

//     if ($currentStateAction) {
//         return $currentStateAction;
//     }

//     return null;
// }

// public function getDaysSinceGermination()
// {
//     return \Carbon\Carbon::now()->diffInDays($this->germination_date);
// }
    
public function actions()
    {
        return $this->belongsToMany(Action::class, 'action_plant');
    }
}
