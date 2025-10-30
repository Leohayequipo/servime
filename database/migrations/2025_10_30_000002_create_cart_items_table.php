<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id');
            $table->string('nombre');
            $table->integer('qty')->default(1);
            $table->bigInteger('price_centavos')->nullable();
            $table->string('price_text')->nullable();
            $table->timestamps();

            $table->index(['cart_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};


