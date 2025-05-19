<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'item_id',
        'purchase_order_id',
        'count',
        'deposite_price',
        'deposite_time'
    ];

    
}