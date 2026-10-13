<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Las fotos de una carta subida en varias partes, en el orden en que se leen.
     *
     * Muchos comercios tienen la carta en papel y le sacan una foto por página. Antes
     * tenían que elegir una sola o armar un PDF por su cuenta.
     *
     * Solo se llena con dos fotos o más. `archivo_original` sigue apuntando a la primera:
     * la miniatura de la lista, el admin y los chequeos de "¿el archivo está?" lo leen a él
     * y siguen funcionando sin saber que existe esta columna.
     */
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->json('archivos')->nullable()->after('archivo_original');
        });
    }

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn('archivos');
        });
    }
};
