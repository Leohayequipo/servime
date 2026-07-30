<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Distingue lo que ESCRIBIÓ el cliente de lo que generó la interfaz.
 *
 * Al tocar "Ver más opciones" o un chip de categoría, el front manda un mensaje armado
 * ("Mostrame todas las opciones que coincidan...") que se guardaba como si lo hubiera
 * tipeado una persona. En el comercio de prueba eso era el 25% de las conversaciones, y
 * arruinaba los tres widgets que analizan texto: era el término #1 en "palabras más
 * buscadas" (17 de 18) y la pregunta #1 en "preguntas frecuentes".
 *
 * El dato ya venía en el request (`tipo`), solo no se guardaba.
 */
return new class extends Migration
{
    /** Mensajes que arma el front, para poder marcar lo que ya está guardado. */
    private const PATRONES = [
        'ver_mas' => 'Mostrame todas las opciones que coincidan%',
        'categoria' => 'Mostrame TODAS las opciones de la categoría%',
    ];

    public function up(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            // 'escrito' | 'categoria' | 'ver_mas'
            $table->string('origen', 20)->default('escrito')->after('user_message')->index();
        });

        foreach (self::PATRONES as $origen => $patron) {
            DB::table('chat_conversations')
                ->where('user_message', 'like', $patron)
                ->update(['origen' => $origen]);
        }
    }

    public function down(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->dropIndex(['origen']);
            $table->dropColumn('origen');
        });
    }
};
