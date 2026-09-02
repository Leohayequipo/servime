<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lo pedido en una comanda, agrupado por ronda.
     *
     * La `ronda` es lo que hace usable la pantalla del mozo: "agregame un agua" veinte
     * minutos después es una ronda nueva, no una edición de la primera. Sin eso el mozo
     * tiene que releer toda la lista buscando qué cambió desde la última vez que miró.
     *
     * El `origen` distingue lo que cargó el cliente de lo que agregó el mozo a mano, y
     * el `session_id` dice de qué teléfono de la mesa salió cada cosa.
     */
    public function up(): void
    {
        Schema::create('comanda_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comanda_id')->constrained('comandas')->cascadeOnDelete();
            $table->unsignedSmallInteger('ronda');
            $table->string('nombre');
            $table->unsignedSmallInteger('qty')->default(1);
            $table->integer('price_centavos')->nullable();
            $table->string('price_text')->nullable();
            $table->string('origen', 20);                     // cliente | mozo
            $table->string('session_id', 120)->nullable();
            // Para que el mozo no termine escribiendo "agua sin hielo" como nombre del
            // ítem, que después rompe cualquier ranking de platos del panel.
            $table->string('nota')->nullable();
            $table->timestamps();

            $table->index(['comanda_id', 'ronda']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comanda_items');
    }
};
