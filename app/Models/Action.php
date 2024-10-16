<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Action extends Model
{
    use HasFactory;
    protected $fillable = ['action_date', 'indoor_id', 'action_type_id', 'data'];

    protected $casts = [
        'data' => 'array',
    ];

    public function indoor()
    {
        return $this->belongsTo(Indoor::class);
    }

    public function plants()
    {
        return $this->belongsToMany(Plant::class, 'action_plant');
    }

    public function action_type()
    {
        return $this->belongsTo(ActionType::class);
    }

}
