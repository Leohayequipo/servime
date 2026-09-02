<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Qué muestra el "menú clásico": el archivo que subió el comercio, o la lista de
     * platos que salió del procesamiento.
     *
     * Arranca en 'archivo' porque es lo que hacía hasta ahora: un comercio que diseñó su
     * PDF no se tiene que encontrar con que su menú cambió solo. La lista se elige desde
     * el panel.
     *
     * La excepción la resuelve el modelo, no la columna: el comercio que cargó su menú
     * por Excel y no tiene archivo ve la lista igual, porque si no vería el cartel de
     * "menú no disponible" teniendo los platos cargados.
     */
    public function up(): void
    {
        Schema::table('comercios', function (Blueprint $table) {
            $table->string('menu_clasico_vista', 10)->default('archivo')->after('pedido_habilitado');
        });
    }

    public function down(): void
    {
        Schema::table('comercios', function (Blueprint $table) {
            $table->dropColumn('menu_clasico_vista');
        });
    }
};
