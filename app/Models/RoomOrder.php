<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomOrder extends Model
{
    use HasFactory;


    protected $fillable = [
        'id',
        'room_id',
        'count',
        'deposite_time',
        'deposite_price',
        'purchase_order_id'
       
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function customer()
    {
        return $this->purchaseOrder ? $this->purchaseOrder->customer : null;
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}