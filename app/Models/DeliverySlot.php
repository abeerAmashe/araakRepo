<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliverySlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'purchase_order_id',
        'time_slot',
    ];

    public function order()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }
}