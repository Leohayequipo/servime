<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Qué pidió el cliente que el menú NO tiene, declarado por el modelo en [[FALTA: ...]].
 *
 * Es la demanda insatisfecha del panel de analytics. Se guarda declarado en vez de
 * inferido de "el bot no recomendó nada", porque el prompt le pide ofrecer alternativas:
 * si le piden sushi ofrece pizzas, así que el contador de platos no sirve como señal
 * (medido: detectaba 1 de cada 3 casos).
 *
 * Viene normalizado a 1-3 palabras y en el idioma del menú ("sushi", "sin TACC",
 * "delivery"), así agrupa bien entre distintas formas de preguntar y entre idiomas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->string('falta', 60)->nullable()->after('platos_sugeridos')->index();
        });
    }

    public function down(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->dropIndex(['falta']);
            $table->dropColumn('falta');
        });
    }
};
