<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de procesamientos con IA de cada carta.
 *
 * Es una tabla y no un contador porque sirve para dos cosas:
 *
 * 1. El TOPE de cartas del plan. Se cuentan las filas con `cuenta_para_limite`: la carta
 *    nueva que sube el comercio. Lo que hace el super admin no cuenta, ni reintentar una
 *    carta que falló (la falla fue nuestra o del archivo).
 * 2. Que el super admin pueda ver cuánto consumió cada corrida (modelo, tokens, tiempo).
 *    Las columnas están; todavía nada las llena.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procesamientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comercio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tipo', 20);          // carta
            $table->string('origen', 20);        // comercio | admin
            $table->boolean('cuenta_para_limite')->default(false);
            $table->string('modelo', 60)->nullable();
            $table->unsignedInteger('tokens_entrada')->default(0);
            $table->unsignedInteger('tokens_salida')->default(0);
            $table->unsignedInteger('tokens_razonamiento')->default(0);
            $table->unsignedInteger('segundos')->nullable();
            $table->timestamps();

            $table->index(['comercio_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procesamientos');
    }
};
