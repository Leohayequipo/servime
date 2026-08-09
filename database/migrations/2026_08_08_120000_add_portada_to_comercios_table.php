<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Portada: la foto que representa al comercio y de la que se deriva el
     * tema del menú público.
     *
     * Va aparte de `logo_url` a propósito. Hasta ahora había un solo campo de
     * imagen y todo lo subido pasaba por saveSquareLogo(), que recorta a
     * 512x512: una foto del local llegaba cuadrada y recortada. Son dos cosas
     * distintas — el logo es el isotipo (chico, contrastado, idealmente con
     * transparencia) y la portada es una foto del lugar o de un plato.
     */
    public function up(): void
    {
        Schema::table('comercios', function (Blueprint $table) {
            $table->string('portada_url')->nullable()->after('logo_url');

            // Derivados que genera BrandThemeService. Se guardan porque
            // producirlos cuesta ~1s y esta pantalla se abre en cada escaneo.
            $table->string('portada_fondo_url')->nullable()->after('portada_url');
            $table->string('portada_hero_url')->nullable()->after('portada_fondo_url');
            $table->string('portada_thumb_url')->nullable()->after('portada_hero_url');

            // Base, velo, superficies, acento sugerido, contraste y hash del
            // original (para no regenerar si vuelven a subir lo mismo).
            $table->json('tema_derivado')->nullable()->after('portada_thumb_url');

            // vacio | procesando | listo | error — mismo patrón que los menús.
            $table->string('portada_estado', 20)->default('vacio')->after('tema_derivado');
        });
    }

    public function down(): void
    {
        Schema::table('comercios', function (Blueprint $table) {
            $table->dropColumn([
                'portada_url',
                'portada_fondo_url',
                'portada_hero_url',
                'portada_thumb_url',
                'tema_derivado',
                'portada_estado',
            ]);
        });
    }
};
