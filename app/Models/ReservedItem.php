<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservedItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'item_id',
        'count',
        'expires_at',
        'quantity'
    ];

    protected $dates = ['expires_at']; 

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}