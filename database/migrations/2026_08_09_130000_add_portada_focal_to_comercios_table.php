<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Punto focal de la portada: qué parte de la foto tiene que quedar a la vista.
     *
     * Se guarda en porcentaje (0–100) porque va directo a `object-position` del
     * CSS. El encuadre deja de ser un recorte hecho en el servidor y pasa a
     * resolverse en el navegador, lo que trae tres cosas:
     *
     *   - Moverlo es instantáneo: no hay que regenerar ninguna imagen.
     *   - Se adapta solo a cada ancho de pantalla, en vez de quedar congelado.
     *   - El mismo punto sirve para el hero y para la miniatura.
     *
     * Antes el recorte salía de un `$cy = 0.38` hardcodeado, elegido a ojo
     * mirando una foto concreta, y encima el navegador recortaba otra vez por
     * su cuenta (`object-fit: cover`) según el ancho del celular.
     */
    public function up(): void
    {
        Schema::table('comercios', function (Blueprint $table) {
            $table->unsignedTinyInteger('portada_focal_x')->default(50)->after('portada_thumb_url');
            $table->unsignedTinyInteger('portada_focal_y')->default(50)->after('portada_focal_x');
        });
    }

    public function down(): void
    {
        Schema::table('comercios', function (Blueprint $table) {
            $table->dropColumn(['portada_focal_x', 'portada_focal_y']);
        });
    }
};
