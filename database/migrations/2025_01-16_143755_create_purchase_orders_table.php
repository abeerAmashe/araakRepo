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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->enum('status', ['not_ready', 'ready', 'in_progress']);
            $table->enum('delivery_status', ['pending', 'negotiation', 'confirmed']);
            $table->enum('want_delivery', ['yes', 'no']);
            $table->enum('is_paid', ['pending', 'partial', 'paid'])->default('pending');
            $table->enum('is_recived', ['pending', 'done'])->default('pending');
            $table->decimal('total_price', 15, 2);
            $table->date('recive_date');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->dateTime('delivery_time')->nullable();
            $table->string('address');
            $table->decimal('delivery_price', 8, 2)->default(0);
            $table->timestamps();
        });
    }




    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
