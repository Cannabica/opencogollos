<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Action extends Model
{
    use HasFactory;
    protected $fillable = ['plant_id', 'action_type_id', 'data'];

    protected $casts = [
        'data' => 'array',
    ];

    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }

    public function action_type()
    {
        return $this->belongsTo(ActionType::class);
    }

}
