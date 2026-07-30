<?php

use App\Models\Comercio;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dos cosas para que "Preguntas más frecuentes" muestre preguntas de verdad.
 *
 * 1. Marca como 'categoria' los mensajes que son EXACTAMENTE el nombre de una categoría
 *    del menú. Al tocar un chip, el front manda el nombre pelado ("VINOS", "POSTRES"),
 *    y el backfill anterior solo cubría el mensaje largo de "ver más". Por eso "VINOS" y
 *    "POSTRES" aparecían como si alguien las hubiera tipeado.
 *    De acá en adelante ya se marcan bien desde el request (`tipo`), esto es solo para lo
 *    que quedó guardado antes.
 *
 * 2. Agrega `idioma`: en qué idioma se atendió la consulta. Ahora que el chat es
 *    multi-idioma, la misma pregunta llega como "quiero una pizza", "quero uma pizza" y
 *    "I want a pizza", y sin el dato el panel no puede explicar por qué se fragmenta.
 *    Además le sirve al comercio para saber qué porcentaje de sus clientes no habla español.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->char('idioma', 2)->nullable()->after('origen')->index();
        });

        // Un cliente podría tipear "VINOS" a mano, pero es raro y el costo de perder ese
        // caso es mucho menor que tener las categorías compitiendo con las preguntas.
        foreach (Comercio::all() as $comercio) {
            $nombres = collect($comercio->getCategoriasDelMenu())
                ->pluck('nombre')
                ->filter()
                ->map(fn ($n) => trim($n))
                ->unique()
                ->values();

            if ($nombres->isEmpty()) {
                continue;
            }

            DB::table('chat_conversations')
                ->where('comercio_id', $comercio->id)
                ->where('origen', 'escrito')
                ->whereIn(DB::raw('TRIM(user_message)'), $nombres->all())
                ->update(['origen' => 'categoria']);
        }
    }

    public function down(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->dropIndex(['idioma']);
            $table->dropColumn('idioma');
        });
    }
};
