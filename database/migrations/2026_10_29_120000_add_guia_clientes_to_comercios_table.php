<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La guía de bienvenida del menú público (tour que señala dónde tocar). La prende o
 * apaga cada comercio desde el editor; arranca prendida para todos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comercios', function (Blueprint $table) {
            $table->boolean('guia_clientes')->default(true)->after('pedido_habilitado');
        });
    }

    public function down(): void
    {
        Schema::table('comercios', function (Blueprint $table) {
            $table->dropColumn('guia_clientes');
        });
    }
};
