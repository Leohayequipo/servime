<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intent_classification_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comercio_id')->constrained('comercios')->cascadeOnDelete();
            $table->unsignedInteger('mensajes_procesados')->default(0);
            $table->string('modelo')->nullable();
            $table->timestamps();

            $table->index(['comercio_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intent_classification_runs');
    }
};
