<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chips configurables por comercio, para la portada y para el chat.
 *
 * Hasta ahora los de la portada salían 100% automáticos del menú (`getChipsSugeridos()`) y
 * los del chat eran cuatro frases fijas iguales para todos. Ninguno de los dos se podía
 * tocar, y la regla automática ordena las categorías como venían en el PDF: aparecían
 * "Adicionales" o "Cambiar por otras guarniciones" antes que las hamburguesas.
 *
 * Forma: { "portada": [ {id, emoji, label, q}, ... ], "chat": [ ... ] }
 * Null = el comercio no configuró nada y siguen los automáticos de siempre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comercios', function (Blueprint $table) {
            $table->json('chips')->nullable()->after('chatbot_config');
        });
    }

    public function down(): void
    {
        Schema::table('comercios', function (Blueprint $table) {
            $table->dropColumn('chips');
        });
    }
};
