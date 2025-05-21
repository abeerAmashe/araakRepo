<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'room_id',
        'name',
        'time',
        'price',
        'count',
        'image_url',
        'count_reserved',
        'item_type_id',
        'description'
    ];

    public function itemDetail()
    {
        return $this->hasOne(ItemDetail::class);
    }

    public function customer()
    {
        return $this->belongsToMany(Item::class, 'liked_items', 'item_id', 'customer_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function customizations()
    {
        return $this->hasMany(Customization::class);
    }


    public function purchaseOrder()
    {
        return $this->belongsToMany(purchaseOrder::class, 'item_orders', 'item_id', 'purchase_order_id')->withPivot(
            'count',
            'deposite_price',
            'deposite_time'
        );
    }


    public function favorites()
    {
        return $this->hasMany(Favorite::class, 'item_id');
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

    public function itemType()
    {
        return $this->belongsTo(ItemType::class);
    }

    public function woods()
    {
        return $this->belongsToMany(Wood::class, 'item_wood');
    }

    public function fabrics()
    {
        return $this->belongsToMany(Fabric::class, 'item_fabric');
    }
}
