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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->string('name');
            $table->integer('time');
            $table->float('price');
            $table->string('image_url')->nullable();
            $table->string('description')->nullable();
            $table->string('wood_color')->nullable();
            $table->string('wood_type')->nullable();
            $table->string('fabric_type')->nullable();
            $table->string('fabric_color')->nullable();

            $table->integer('count');
            $table->integer('count_reserved')->default(0);
            $table->unsignedBigInteger('item_type_id'); // عمود المفتاح الخارجي
            $table->foreign('item_type_id')->references('id')->on('item_types')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
