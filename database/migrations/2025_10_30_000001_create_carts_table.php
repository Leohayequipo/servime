<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('comercio_id');
            $table->string('session_id', 120);
            $table->bigInteger('total_centavos')->default(0);
            $table->json('meta_json')->nullable();
            $table->timestamps();

            $table->index(['comercio_id', 'session_id']);
            $table->unique(['comercio_id', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};


