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
        Schema::create('item_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wood_id')->constrained('woods')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('fabric_id')->constrained('fabrics')->cascadeOnDelete();
            $table->float('wood_length')->nullable();
            $table->float('wood_width')->nullable();
            $table->float('wood_height')->nullable();
            $table->integer('fabric_dimension');
            $table->string('wood_color')->nullable();  
            $table->string('fabric_color')->nullable();  
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_details');
    }
};