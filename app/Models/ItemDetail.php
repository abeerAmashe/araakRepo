<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemDetail extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'item_id',
        'wood_id',
        'fabric_id',
        'wood_length',
        'wood_width',
        'wood_height',
        'fabric_dimension',
        'wood_color',
        'fabric_color'

    ];

    public function fabric()
    {
        return $this->hasOne(Fabric::class);
    }

    public function wood()
    {
        return $this->hasOne(Wood::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}