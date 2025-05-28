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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // المستخدم المرتبط
            $table->decimal('balance', 15, 2)->default(0.00);                 // الرصيد
            $table->string('currency', 3)->default('USD');                    // العملة (اختياري)
            $table->enum('wallet_type', ['investment', 'profits', 'platform']); // نوع المحفظة
            $table->boolean('is_active')->default(true);                      // فعّالة أو لا
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
