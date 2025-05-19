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
        Schema::create('types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // ✅ أضفنا هذا السطر
            $table->unsignedBigInteger('fabric_id')->nullable();
            $table->foreign('fabric_id')->references('id')->on('fabrics')->onDelete('cascade');
            $table->unsignedBigInteger('wood_id')->nullable();
            $table->foreign('wood_id')->references('id')->on('woods')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('types');
    }
};