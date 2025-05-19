<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomCustomizationOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'room_customization_id',
        'count',
        'deposite_price',
        'deposite_time',
        'delivery_time'
    ];

    public function roomCustomization()
    {
        return $this->belongsTo(RoomCustomization::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}