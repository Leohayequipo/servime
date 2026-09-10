<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Deja la mesa registrada en toda la actividad del cliente.
     *
     * Va como columna y no como una clave dentro del `data` json de tracking_events
     * por dos motivos: el json no se indexa ni se joinea (y el panel necesita agrupar
     * por mesa), y guardar ahí el *número* quedaría inconsistente el día que el
     * comercio renumere sus mesas.
     *
     * `nullOnDelete` y no cascade: si se borra una mesa, el histórico se conserva sin
     * mesa en vez de desaparecer. Perder conversaciones y métricas por reordenar el
     * salón sería un precio absurdo.
     */
    public function up(): void
    {
        Schema::table('tracking_events', function (Blueprint $table) {
            $table->foreignId('mesa_id')->nullable()->after('comercio_id')
                ->constrained('mesas')->nullOnDelete();

            $table->index(['comercio_id', 'mesa_id', 'created_at']);
        });

        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->foreignId('mesa_id')->nullable()->after('comercio_id')
                ->constrained('mesas')->nullOnDelete();
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->foreignId('mesa_id')->nullable()->after('comercio_id')
                ->constrained('mesas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropForeign(['mesa_id']);
            $table->dropColumn('mesa_id');
        });

        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->dropForeign(['mesa_id']);
            $table->dropColumn('mesa_id');
        });

        Schema::table('tracking_events', function (Blueprint $table) {
            $table->dropIndex(['comercio_id', 'mesa_id', 'created_at']);
            $table->dropForeign(['mesa_id']);
            $table->dropColumn('mesa_id');
        });
    }
};
