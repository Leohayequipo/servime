<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cuántos platos del menú recomendó el bot en cada respuesta.
 *
 * Habilita la métrica de DEMANDA INSATISFECHA: si el cliente preguntó algo y el bot no
 * pudo recomendar ni un plato, es algo que le piden al comercio y no tiene (o no está
 * en la carta). Es el dato más accionable que hay en los logs del chat.
 *
 * Sale gratis del marcador [[PLATOS: ...]] que el modelo ya declara en cada respuesta,
 * y por eso es a prueba de idiomas: no depende de detectar frases como "no está
 * disponible", que ahora salen en inglés, portugués o italiano.
 *
 * NULLABLE a propósito: las filas viejas son "no sabemos", no "cero". Si fueran 0 las
 * conversaciones anteriores a esta migración aparecerían todas como demanda insatisfecha.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->unsignedSmallInteger('platos_sugeridos')->nullable()->after('bot_response');
        });
    }

    public function down(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->dropColumn('platos_sugeridos');
        });
    }
};
