<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pedirle al cliente que deje su reseña en Google, desde el chat del menú.
 *
 * VA EN `comercios`, NO EN EL MENÚ. El link es de la ficha del negocio: no cambia cuando
 * suben una carta nueva. Colgado del menú, el próximo import de Excel se lo llevaría
 * puesto —el import rearma el JSON desde cero— y el comercio se quedaría sin el botón sin
 * entender por qué. Es el mismo mecanismo que borraba los platos estrella.
 *
 * Son dos campos y no uno a propósito: con solo la URL, "apagarlo" obligaría a borrar el
 * link y volver a buscarlo en Google Maps para reactivarlo. El comercio quiere poder
 * pausarlo (obra, se fue el cocinero, dos semanas malas) sin perder lo que ya cargó.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comercios', function (Blueprint $table) {
            $table->string('google_review_url')->nullable()->after('chips');
            $table->boolean('resenas_activo')->default(false)->after('google_review_url');
        });
    }

    public function down(): void
    {
        Schema::table('comercios', function (Blueprint $table) {
            $table->dropColumn(['google_review_url', 'resenas_activo']);
        });
    }
};
