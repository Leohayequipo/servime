<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comercio_id')->constrained('comercios')->cascadeOnDelete();
            $table->string('session_id')->nullable();
            $table->string('event')->index();           // page_view, menu_choice, promo_click, dish_want...
            $table->json('data')->nullable();           // payload del evento (choice, promo, plato, etc.)
            $table->string('user_ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['comercio_id', 'event', 'created_at']);
            $table->index(['comercio_id', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_events');
    }
};
