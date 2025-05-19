<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->foreign('room_id')->references('id')->on('rooms')->onDelete('cascade');
            $table->unsignedBigInteger('item_id')->nullable();
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
            $table->foreignId('customization_id')->nullable()->constrained('customizations')->cascadeOnDelete();
            $table->foreignId('room_customization_id')->nullable()->constrained('room_customizations')->cascadeOnDelete();
            $table->integer('count');
            $table->integer('available_count_at_addition')->default(0); // لحفظ الكمية المتاحة وقت الإضافة
            $table->decimal('time_per_item', 8, 2)->nullable();
            $table->decimal('price_per_item', 10, 2)->nullable();
            $table->decimal('time', 10, 2)->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->timestamp('reserved_at')->default(DB::raw('CURRENT_TIMESTAMP')); // Add 'reserved_at' column

            $table->timestamps();
        });
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};