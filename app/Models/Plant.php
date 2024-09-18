<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plant extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'plant_type_id', 'indoor_id'];

    public function plantType()
    {
        return $this->belongsTo(PlantsType::class, 'plant_type_id');
    }

    public function indoor()
    {
        return $this->belongsTo(Indoor::class, 'indoor_id');
    }

    public function attentions()
    {
        return $this->hasMany(Attention::class);
    }
}
