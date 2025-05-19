<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'customer_id',
        'item_id',
        'room_id'
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}