<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Elimina la tabla del feature de "categorías de preguntas" (FAQ), removido del sistema.
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('question_categories');
    }

    public function down(): void
    {
        Schema::create('question_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color')->default('#00C4FF');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }
};
