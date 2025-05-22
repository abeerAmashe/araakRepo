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
        Schema::create('woods', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('');
            $table->string('color')->default('');
            $table->float('price_per_meter');
            // أضف العمود أولاً
            $table->unsignedBigInteger('room_detail_id')->nullable();

            // ثم عرّف المفتاح الأجنبي
            $table->foreign('room_detail_id')
                ->references('id')
                ->on('room_details')
                ->onDelete('cascade');

            $table->unsignedBigInteger('item_wood_id')->nullable();
            $table->foreign('item_wood_id')
                ->references('id')
                ->on('item_wood')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('woods');
    }
};