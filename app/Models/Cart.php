<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'customer_id',
        'item_id',
        'room_id',
        'customization_id',
        'room_customization_id',
        'count',
        'time_per_item',
        'price_per_item',
        'time',
        'price',
        'available_count_at_addition',
        'reserved_at'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function customization()
    {
        return $this->belongsTo(Customization::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
    public function roomCustomization()
{
    return $this->hasMany(RoomCustomization::class);
}


}