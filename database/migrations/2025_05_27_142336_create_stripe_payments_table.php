<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stripe_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->onDelete('cascade'); // العلاقة مع transaction
            $table->string('payment_intent_id')->unique();                           // معرّف الدفع من Stripe
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('usd');
            $table->string('payment_method');                                        // نوع البطاقة أو الوسيلة
            $table->string('status', 50);                                            // succeeded, failed...
            $table->string('receipt_url', 512)->nullable();                          // رابط الفاتورة من Stripe
            $table->json('metadata')->nullable();                                    // بيانات إضافية إن وُجدت
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stripe_payments');
    }
};