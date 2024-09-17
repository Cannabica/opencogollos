<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attention extends Model
{
    use HasFactory;

    protected $fillable = ['plant_id', 'attention_type_id'];

    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }

    public function attention_type()
    {
        return $this->belongsTo(AttentionType::class);
    }

}
