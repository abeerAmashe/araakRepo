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
        Schema::create('customization_items', function (Blueprint $table) {
            $table->id(); // المعرف الأساسي
            $table->foreignId('room_customization_id')->constrained('room_customizations')->onDelete('cascade'); // معرف تخصيص الغرفة
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade'); // معرف العنصر
            $table->foreignId('wood_id')->nullable()->constrained('woods')->onDelete('set null'); // معرف الخشب
            $table->foreignId('fabric_id')->nullable()->constrained('fabrics')->onDelete('set null'); // معرف القماش
            $table->string('wood_color')->nullable(); // لون الخشب
            $table->string('fabric_color')->nullable(); // لون القماش
            $table->decimal('add_to_length', 8, 2)->default(0); // الزيادة في الطول
            $table->decimal('add_to_width', 8, 2)->default(0); // الزيادة في العرض
            $table->decimal('add_to_height', 8, 2)->default(0); // الزيادة في الارتفاع
            $table->decimal('final_price', 10, 2)->default(0); // السعر النهائي
            $table->decimal('final_time', 10, 2)->default(0); // السعر النهائي

            $table->timestamps(); // الحقول الافتراضية للتوقيت
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customization_items');
    }
};