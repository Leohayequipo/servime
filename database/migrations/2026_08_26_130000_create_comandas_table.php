<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La comanda de una mesa: lo que pidió el grupo desde que se sentó hasta que se va.
     *
     * Es el nivel que faltaba. Los `carts` son de a un teléfono (la clave es el
     * session_id del navegador) y viven para siempre, así que no servían para juntar lo
     * que piden cuatro personas sentadas en la misma mesa, ni para saber cuándo termina
     * una visita y empieza otra.
     *
     * Cerrar NO es borrar: la comanda queda con estado 'cerrada' y sus ítems intactos.
     * Si se borrara, se perderían las métricas por mesa y no habría forma de reconstruir
     * un reclamo ("nosotros pedimos dos, nos trajeron uno").
     */
    public function up(): void
    {
        Schema::create('comandas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comercio_id')->constrained('comercios')->cascadeOnDelete();
            $table->foreignId('mesa_id')->constrained('mesas')->cascadeOnDelete();
            $table->string('estado', 20)->default('abierta');   // abierta | cerrada
            $table->bigInteger('total_centavos')->default(0);
            $table->timestamp('abierta_at');
            $table->timestamp('cerrada_at')->nullable();
            $table->string('cerrada_por', 20)->nullable();      // mozo | auto
            $table->timestamps();

            /**
             * Garantiza a nivel base de datos que una mesa no tenga dos comandas abiertas.
             *
             * Vale mesa_id mientras está abierta y NULL cuando se cierra: en MySQL (y en
             * SQLite) los NULL no colisionan entre sí en un índice único, así que la
             * restricción sólo aplica a las abiertas.
             *
             * No alcanza con chequearlo en PHP: dos teléfonos de la misma mesa tocando
             * "Pedir" en el mismo instante pasan los dos por el `if` antes de que
             * cualquiera inserte, y la mesa termina con dos comandas y el pedido partido
             * al medio. Eso se descubre en pleno servicio y es carísimo de diagnosticar.
             */
            $table->unsignedBigInteger('mesa_abierta_id')->nullable()->unique();

            $table->index(['comercio_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comandas');
    }
};
