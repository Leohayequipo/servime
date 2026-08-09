<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Horarios estructurados, para poder saber de verdad si está abierto.
     *
     * El campo `horario` que ya existe es texto libre y quedó impresentable
     * para calcular nada — hay de todo: "Lunes a viernes de 19 a 2AM" (cruza
     * medianoche), "12  a 23", horarios distintos por día en una sola línea,
     * letras Unicode de Instagram y emojis. Se mantiene tal cual para mostrar,
     * porque está escrito con las palabras del comercio.
     *
     * Formato: { "1": [["11:00","20:00"]], "2": [...], ..., "7": [] }
     * La clave es el día ISO (1 = lunes … 7 = domingo) y cada día tiene una
     * lista de turnos. Lista vacía = cerrado ese día. Si el cierre es menor o
     * igual a la apertura, el turno cruza la medianoche.
     *
     * null = sin cargar. En ese caso la pantalla NO muestra estado, en vez de
     * inventar que está abierto.
     */
    public function up(): void
    {
        Schema::table('comercios', function (Blueprint $table) {
            $table->json('horarios')->nullable()->after('horario');
        });
    }

    public function down(): void
    {
        Schema::table('comercios', function (Blueprint $table) {
            $table->dropColumn('horarios');
        });
    }
};
