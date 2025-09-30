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
        Schema::create('question_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre de la categoría (ej: "Menú", "Precios")
            $table->string('slug')->unique(); // Slug único (ej: "menu", "precios")
            $table->string('color')->default('#00C4FF'); // Color de la categoría
            $table->boolean('is_active')->default(true); // Si está activa
            $table->integer('sort_order')->default(0); // Orden de aparición
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_categories');
    }
};
