<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'customer_id',
        'status',
        'delivery_status',
        'want_delivery',
        'is_paid',
        'is_recived',
        'total_price',
        'recive_date',
        'latitude',
        'longitude',
        'delivery_time',
        'address',
        'delivery_price',
        'rabbon',
        'price_after_rabbon',
        'price_after_rabbon_with_delivery',
        'remaining_amount',//المبلغ المتبقي بدون التوصيل
        'remaining_amount_with_delivery',// المبلغ المتبقي مع التوصيل
        'branch_id'
    ];



    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function report()
    {
        return $this->belongsTo(Report::class);
    }



    public function item()
    {
        return $this->belongsToMany(Item::class, 'item_orders', 'purchase_order_id', 'item_id')->withPivot(
            'count',
            'deposite_price',
            'deposite_time',
            'delivery_time',

        );
    }



    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function roomOrders()
    {
        return $this->hasMany(RoomOrder::class);
    }
}