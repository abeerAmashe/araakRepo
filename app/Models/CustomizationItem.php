<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomizationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_customization_id',
        'item_id',
        'wood_id',
        'fabric_id',
        'wood_color',
        'fabric_color',
        'add_to_length',
        'add_to_width',
        'add_to_height',
        'final_price',
        'final_time'
    ];

    public function roomCustomization()
    {
        return $this->belongsTo(RoomCustomization::class);
    }
    

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function wood()
    {
        return $this->belongsTo(Wood::class);
    }

    public function fabric()
    {
        return $this->belongsTo(Fabric::class);
    }
}