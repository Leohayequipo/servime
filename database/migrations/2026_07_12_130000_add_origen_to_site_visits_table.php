<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_visits', function (Blueprint $table) {
            // De dónde viene (canal): instagram, google, whatsapp, directo...
            $table->string('source', 40)->nullable()->after('referer');
            // Con qué entra: mobile / tablet / desktop
            $table->string('device', 20)->nullable()->after('source');
            // Desde dónde entra (geolocalización de la IP, se completa en background)
            $table->char('country_code', 2)->nullable()->after('device');
            $table->string('country', 80)->nullable()->after('country_code');
            $table->string('city', 80)->nullable()->after('country');

            $table->index('source');
            $table->index('device');
            $table->index('country_code');
        });
    }

    public function down(): void
    {
        Schema::table('site_visits', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->dropIndex(['device']);
            $table->dropIndex(['country_code']);
            $table->dropColumn(['source', 'device', 'country_code', 'country', 'city']);
        });
    }
};
