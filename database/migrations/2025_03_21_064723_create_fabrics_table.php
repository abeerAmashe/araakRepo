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
        Schema::create('fabrics', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->float('price_per_meter');
            
            $table->unsignedBigInteger('room_detail_id')->nullable();

            $table->foreign('room_detail_id')
                  ->references('id')
                  ->on('room_details')
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /** 'id',
        
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fabrics');
    }
};