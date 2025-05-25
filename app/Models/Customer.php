<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'user_id',
        'latitude',
        'longitude',
        'phone_number',
        'profile_image',
        'address'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function item()
    {
        return $this->belongsToMany(Item::class, 'liked_items',  'customer_id', 'item_id');
    }

    public function roomOrder()
    {
        return $this->belongsToMany(Room::class, 'purchase_orders', 'customer_id', 'room_order_id')
            ->withPivot(
                'is_ready',
                'is_paid',
                'is_recived',
                'want_delivery',
                'total_price',
                'recive_date',
                'profile_image'
            );
    }



    public function favorites()
    {
        return $this->hasMany(Favorite::class, 'customer_id');
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class, 'customer_id');
    }
}