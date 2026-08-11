<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Qué platos ofreció el asistente en cada respuesta.
 *
 * Hasta ahora se guardaba sólo CUÁNTOS (`platos_sugeridos`), y el marcador
 * `[[PLATOS: ...]]` —de donde salen los nombres— se borra de la respuesta antes de
 * guardarla. Así, en el historial de conversaciones del panel se leía "te recomiendo la
 * Pizza Azopardo…" y había que confiar: no se podía ver qué se ofreció realmente ni si el
 * plato existía.
 *
 * Con los nombres, el comercio (y nosotros) ve de un vistazo cuándo el asistente recomendó
 * cualquier cosa. Es el mismo dato con el que se arman las tarjetas del chat, así que no
 * hay que calcular nada nuevo: sólo dejar de tirarlo.
 *
 * Las conversaciones viejas quedan en NULL: ese dato ya se perdió y no se puede reconstruir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->json('platos_ofrecidos')->nullable()->after('platos_sugeridos');
        });
    }

    public function down(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->dropColumn('platos_ofrecidos');
        });
    }
};
