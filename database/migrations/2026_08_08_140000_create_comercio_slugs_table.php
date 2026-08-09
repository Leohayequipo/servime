<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Historial de slugs: las URLs viejas siguen funcionando.
     *
     * El slug es la URL pública del menú y está impresa en los QR de las mesas.
     * Sin esto había que elegir entre dos males: congelarlo (y que una typo del
     * primer día quede para siempre) o dejar que cambie (y romper todos los QR
     * en silencio cuando alguien corrige el nombre). Guardando los anteriores
     * como alias, el slug puede cambiar y los códigos viejos siguen entrando.
     */
    public function up(): void
    {
        Schema::create('comercio_slugs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comercio_id')->constrained('comercios')->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->timestamp('created_at')->nullable();
        });

        Schema::table('comercios', function (Blueprint $table) {
            /**
             * Marca que el slug lo fijó el admin a mano.
             *
             * Sin esto, un renombrado posterior del comerciante pisaría la URL
             * que el admin eligió a propósito.
             */
            $table->boolean('slug_manual')->default(false)->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('comercios', function (Blueprint $table) {
            $table->dropColumn('slug_manual');
        });

        Schema::dropIfExists('comercio_slugs');
    }
};
