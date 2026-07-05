<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            // procesando | listo | error
            $table->string('estado', 20)->default('procesando')->after('procesado');
            $table->index('estado');
        });

        // Backfill según el booleano existente: procesado=true → listo, resto → procesando.
        DB::table('menus')->where('procesado', true)->update(['estado' => 'listo']);
        DB::table('menus')->where('procesado', false)->update(['estado' => 'procesando']);
    }

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropIndex(['estado']);
            $table->dropColumn('estado');
        });
    }
};
