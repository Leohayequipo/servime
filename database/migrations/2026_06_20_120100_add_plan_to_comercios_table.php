<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comercios', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('user_id')
                ->constrained('plans')->nullOnDelete();
            $table->string('ia_mensajes_periodo', 7)->nullable()->after('plan_id'); // YYYY-MM
            $table->unsignedInteger('ia_mensajes_usados')->default(0)->after('ia_mensajes_periodo');
        });

        // Asignar el plan Básico a los comercios existentes.
        $basicoId = DB::table('plans')->where('key', 'basico')->value('id');
        if ($basicoId) {
            DB::table('comercios')->whereNull('plan_id')->update(['plan_id' => $basicoId]);
        }
    }

    public function down(): void
    {
        Schema::table('comercios', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn(['plan_id', 'ia_mensajes_periodo', 'ia_mensajes_usados']);
        });
    }
};
