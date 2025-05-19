<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerAvailableTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'purchase_order_id',
        'available_at',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}