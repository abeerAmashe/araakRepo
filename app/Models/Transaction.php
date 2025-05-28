<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'wallet_id',
        'amount',
        'type',
        'status',
        'stripe_payment_id',
        'related_transaction_id',
    ];

    // علاقة: Transaction تعود لمستخدم
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // علاقة: Transaction تتبع لمحفظة
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    // علاقة: transaction لها سجل دفع Stripe (إن وجد)
    public function stripePayment()
    {
        return $this->hasOne(StripePayment::class);
    }

    // علاقة: transaction مرتبطة بتحويل (عكسي أو مكمل)
    public function relatedTransaction()
    {
        return $this->belongsTo(Transaction::class, 'related_transaction_id');
    }
}