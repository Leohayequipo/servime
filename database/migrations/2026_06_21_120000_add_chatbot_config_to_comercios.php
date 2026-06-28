<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comercios', function (Blueprint $table) {
            // Personalización del asistente: { tono, trato, emojis, estrellas[], focos[], detalles }
            $table->json('chatbot_config')->nullable()->after('chatbot_name');
        });
    }

    public function down(): void
    {
        Schema::table('comercios', function (Blueprint $table) {
            $table->dropColumn('chatbot_config');
        });
    }
};
