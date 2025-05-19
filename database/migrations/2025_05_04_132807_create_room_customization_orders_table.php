<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('room_customization_orders', function (Blueprint $table) {
        $table->id();
        $table->foreignId('purchase_order_id')->constrained()->onDelete('cascade');
        $table->foreignId('room_customization_id')->constrained()->onDelete('cascade');
        $table->integer('count');
        $table->float('deposite_price')->default(0);
        $table->integer('deposite_time')->default(0);
        $table->integer('delivery_time')->default(0);
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_customization_orders');
    }
};