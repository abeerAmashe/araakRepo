<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservedItemsTable extends Migration
{
    public function up()
    {
        Schema::create('reserved_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('item_id')->constrained()->onDelete('cascade');

            $table->unsignedInteger('count');
            $table->timestamp('expires_at');
            $table->integer('quantity');
            $table->timestamps();
            $table->unique(['customer_id', 'item_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('reserved_items');
    }
}