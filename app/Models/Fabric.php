<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fabric extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price_per_meter',
        'room_detail_id',
        'item_fabric_id'

    ];
    protected $casts = [
        'price_per_meter' => 'float',
    ];


    public function fabricType()
    {
        return $this->belongsTo(Type::class);
    }

    public function colors()
    {
        return $this->hasMany(Color::class);
    }

    public function types()
    {
        return $this->hasMany(Type::class);
    }

    public function rooms()
    {
        return $this->belongsToMany(Room::class, 'room_fabric');
    }

    public function itemDetails()
    {
        return $this->hasMany(ItemDetail::class);
    }
    public function roomDetail()
    {
        return $this->belongsTo(RoomDetail::class);
    }


    public function items()
    {
        return $this->belongsToMany(Item::class, 'item_fabric');
    }
}
