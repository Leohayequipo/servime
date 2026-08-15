<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Los textos del pedido de reseña, editables por el comercio.
 *
 * Quedan NULL cuando no los tocaron, en vez de sembrar el texto por defecto en la columna:
 * así, si mañana se mejora la redacción que viene de fábrica, la reciben todos los que
 * nunca la editaron. Sembrándola, cada comercio se quedaría congelado con la versión del
 * día que se dio de alta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comercios', function (Blueprint $table) {
            $table->string('resena_titulo', 80)->nullable()->after('resenas_activo');
            $table->string('resena_texto', 200)->nullable()->after('resena_titulo');
        });
    }

    public function down(): void
    {
        Schema::table('comercios', function (Blueprint $table) {
            $table->dropColumn(['resena_titulo', 'resena_texto']);
        });
    }
};
