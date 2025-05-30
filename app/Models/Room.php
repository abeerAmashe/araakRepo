<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name',
        'category_id',
        'description',
        'image_url',
        'count_reserved',
        'time',
        'price',
        'count',
        'wood_type',
        'wood_color',
        'fabric_type',
        'fabric_color'
    ];

    protected $casts = [
        'price' => 'float',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function roomOrder()
    {
        return $this->hasMany(RoomOrder::class);
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }


    public function favorites()
    {
        return $this->hasMany(Favorite::class, 'room_id');
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function discounts()
    {
        return $this->hasMany(Discount::class);
    }



    public function roomDetails()
    {
        return $this->hasMany(RoomDetail::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
