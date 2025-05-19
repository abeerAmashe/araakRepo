<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomizationsTable extends Migration
{
    public function up()
    {
        Schema::create('customizations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id')->nullable(); 
            $table->unsignedBigInteger('wood_id')->nullable(); 
            $table->unsignedBigInteger('fabric_id')->nullable(); 
            $table->float('extra_length')->nullable(); 
            $table->float('extra_width')->nullable(); 
            $table->float('extra_height')->nullable(); 
            $table->float('old_price')->nullable(); 
            $table->string('wood_color')->nullable();
            $table->string('fabric_color')->nullable();
            $table->float('final_price')->nullable(); 
            $table->unsignedBigInteger('customer_id')->nullable(); 
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->timestamps();

            // الربط مع الجداول الأخرى
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
            $table->foreign('wood_id')->references('id')->on('woods')->onDelete('set null');
            $table->foreign('fabric_id')->references('id')->on('fabrics')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('customizations');
    }
}