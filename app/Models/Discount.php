<?php

// app/Models/Discount.php
// app/Models/Discount.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'item_id',
        'discount_percentage',
        'start_date',
        'end_date',
    ];

   
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

   
    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}