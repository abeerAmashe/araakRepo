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
        Schema::create('item_wood', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            $table->foreignId('wood_id')->constrained('woods'); // ✅ الصحيح لأنه جدول wood اسمه الحقيقي woods
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};