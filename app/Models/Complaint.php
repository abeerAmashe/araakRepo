<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'customer_id',
        'message',
        'status'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}