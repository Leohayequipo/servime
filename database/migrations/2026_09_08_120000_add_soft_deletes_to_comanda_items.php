<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lo que el mozo borra de una comanda deja rastro.
     *
     * Hasta acá el borrado era físico: si el mozo tocaba la × en la fila equivocada —y con
     * lo apretados que están esos botones en una tablet va a pasar— no había con qué
     * reconstruir. Tampoco había forma de resolver un "nosotros pedimos dos".
     *
     * Mismo mecanismo que `menus`, que ya usa SoftDeletes. El tablero y los totales no
     * cambian: Eloquent excluye lo borrado por su cuenta.
     *
     * No se guarda QUIÉN lo borró a propósito: el acceso del mozo es un PIN compartido por
     * el turno, así que una columna con el usuario diría siempre lo mismo o mentiría.
     */
    public function up(): void
    {
        Schema::table('comanda_items', function (Blueprint $table) {
            $table->softDeletes();

            // El histórico se consulta por comercio y por fecha; `comanda_items` no tiene
            // comercio_id propio (cuelga de `comandas`), así que el índice acompaña el
            // filtro que sí se puede hacer acá.
            $table->index(['comanda_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('comanda_items', function (Blueprint $table) {
            $table->dropIndex(['comanda_id', 'deleted_at']);
            $table->dropSoftDeletes();
        });
    }
};
