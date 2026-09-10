<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mesas del local, cada una con su propio QR.
     *
     * Hasta acá el comercio tenía un solo QR para todo el salón, así que el sistema
     * no sabía desde dónde escaneaba el cliente. Con una fila por mesa se puede saber
     * en qué mesa pasa cada cosa: qué se mira, qué se pide y qué mesa no registra una
     * sola visita (que casi siempre significa que el cartel se despegó).
     *
     * El `token` es lo único que viaja en la URL del QR; el `numero` es lo que se
     * imprime. Van separados a propósito: si el número fuera la URL, cualquiera la
     * editaría a mano y se registraría como otra mesa. Además el token se puede rotar
     * (cartel fotografiado) sin cambiar el número que el cliente ve pintado en la mesa.
     */
    public function up(): void
    {
        Schema::create('mesas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comercio_id')->constrained('comercios')->cascadeOnDelete();
            $table->unsignedSmallInteger('numero');          // lo que se imprime
            $table->string('etiqueta')->nullable();          // "Barra", "Terraza"; opcional
            $table->string('token', 12)->unique();           // lo que viaja en la URL
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->unique(['comercio_id', 'numero']);
            $table->index(['comercio_id', 'activa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mesas');
    }
};
