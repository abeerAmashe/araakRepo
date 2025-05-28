<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StripePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'payment_intent_id',
        'amount',
        'currency',
        'payment_method',
        'status',
        'receipt_url',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}