<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_visits', function (Blueprint $table) {
            // Se marca en true sólo cuando el navegador confirma por JS que hay alguien.
            // Los bots no ejecutan JS: quedan guardados (para poder verlos) pero NO suman.
            $table->boolean('humano')->default(false)->after('device');

            $table->index(['humano', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('site_visits', function (Blueprint $table) {
            $table->dropIndex(['humano', 'created_at']);
            $table->dropColumn('humano');
        });
    }
};
