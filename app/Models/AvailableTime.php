<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvailableTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'available_at',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}