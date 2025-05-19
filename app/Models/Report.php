<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'customer_id',
        'purchase_order_id',
        'date'
    ];

    public function purchaseOrder()
    {
        return $this->hasMany(PurchaseOrder::class);
    }
    public function repairRequest()
    {
        return $this->hasMany(RepairRequest::class);
    }
}