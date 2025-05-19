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
        Schema::create('room_customizations', function (Blueprint $table) {
            $table->id(); // المعرف الأساسي
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade'); // معرف الغرفة
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade'); // معرف العميل
            $table->integer('final_price');
            $table->integer('final_time');            
            $table->timestamps(); // الحقول الافتراضية للتوقيت
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_customizations');
    }
};