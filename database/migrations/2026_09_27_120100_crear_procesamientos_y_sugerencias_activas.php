<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de procesamientos con IA de cada carta, y el interruptor de sugerencias.
 *
 * `procesamientos` sirve para dos cosas, y por eso es una tabla y no un contador:
 *
 * 1. El TOPE de cartas del plan. Se cuentan las filas con `cuenta_para_limite`: la carta
 *    nueva y las sugerencias que el comercio pidió regenerar. Las que se generan solas al
 *    procesar la carta NO cuentan (ya contó la carta), ni lo que hace el super admin, ni
 *    reintentar una carta que falló (la falla fue nuestra o del archivo).
 * 2. Que el super admin vea CUÁNTO CONSUMIÓ cada corrida: tokens, modelo y tiempo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procesamientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comercio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tipo', 20);          // carta | sugerencias
            $table->string('origen', 20);        // comercio | automatico | admin
            $table->boolean('cuenta_para_limite')->default(false);
            $table->string('modelo', 60)->nullable();
            $table->unsignedInteger('tokens_entrada')->default(0);
            $table->unsignedInteger('tokens_salida')->default(0);
            $table->unsignedInteger('tokens_razonamiento')->default(0);
            $table->unsignedInteger('segundos')->nullable();
            $table->timestamps();

            $table->index(['comercio_id', 'created_at']);
        });

        Schema::table('comercios', function (Blueprint $table) {
            // Encendido por defecto: la mayoría las quiere, y el que no, las apaga.
            $table->boolean('sugerencias_activas')->default(true)->after('menu_clasico_vista');
        });
    }

    public function down(): void
    {
        Schema::table('comercios', function (Blueprint $table) {
            $table->dropColumn('sugerencias_activas');
        });

        Schema::dropIfExists('procesamientos');
    }
};
