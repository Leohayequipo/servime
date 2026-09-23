<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Las duplas de la carta ("el pancho va bien con la coca"), calculadas una vez.
 *
 * Van en el menú y no en una tabla aparte porque son un derivado de `contexto_gpt`: si
 * el menú se borra o se reemplaza, esto no tiene por qué sobrevivirlo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->json('recomendaciones')->nullable()->after('contexto_gpt');
        });
    }

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn('recomendaciones');
        });
    }
};
