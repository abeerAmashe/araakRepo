<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LikedItem extends Model
{
    use HasFactory;

    protected $fillable=[
        'id',
        'item_id',
        'customer_id'
    ];

    public function item(){
        return $this->hasone(Item::class);
    }
    public function customer(){
        return $this->belongsTo(Customer::class);
    }
    
}