<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomCustomization extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'customer_id',
        'final_price',
        'final_time'
    ];

    public function customizationItems()
    {
        return $this->hasMany(CustomizationItem::class);
    }
    

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}