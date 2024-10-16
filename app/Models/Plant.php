<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plant extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'seed_id', 'indoor_id', 'germination_date', 'planting_date', 'pot_type', 'etapa', 'batches'];

    public function seedType()
    {
        return $this->belongsTo(Seed::class, 'seed_id');
    }

    public function indoor()
    {
        return $this->belongsTo(Indoor::class, 'indoor_id');
    }

    public function actions()
    {
        return $this->belongsToMany(Action::class, 'action_plant');
    }

    public function batches()
    {
        return $this->hasMany(Batch::class, 'batches');
    }
}
