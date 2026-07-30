<?php

use App\Models\Comercio;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El menú digital pasó de 4 colores configurables a UN acento sobre una paleta neutra
 * cálida fija. Esta migración:
 *
 *  1. Cambia el default de `primary_color` al ámbar de la paleta nueva (era '#3B82F6',
 *     el azul de Tailwind, que además es el color menos apetitoso en gastronomía).
 *  2. Mapea los comercios que tenían uno de los 8 temas viejos al acento cálido más
 *     cercano. Es necesario porque en los temas oscuros `primary_color` guardaba un
 *     CASI-NEGRO (p. ej. '#1C1917' en "Oro Oscuro"), y al usarlo como acento los
 *     botones quedaban invisibles.
 *
 * Los colores viejos se guardan en `secondary_color` sin tocar, así que down() puede
 * revertir el default; el mapeo por comercio no se puede deshacer con exactitud (era
 * ambiguo por diseño), pero queda registrado acá.
 */
return new class extends Migration
{
    /** primary_color viejo => clave del acento nuevo */
    private const MAPEO = [
        '#3B82F6' => 'ambar',      // "Profesional" — también era el default sin tocar
        '#10B981' => 'oliva',      // "Natural" (verde)
        '#8B5CF6' => 'vino',       // "Elegante" (púrpura)
        '#F97316' => 'terracota',  // "Energético" (naranja)
        '#1E293B' => 'ambar',      // "Noche Azul" — primary era casi-negro
        '#1C1917' => 'dorado',     // "Oro Oscuro" — primary era casi-negro
        '#1E1B4B' => 'vino',       // "Púrpura Profundo" — primary era casi-negro
        '#111827' => 'ambar',      // "Obsidiana" — primary era casi-negro
    ];

    public function up(): void
    {
        Schema::table('comercios', function (Blueprint $table) {
            $table->string('primary_color')->default(Comercio::ACENTO_POR_DEFECTO)->change();
        });

        foreach (self::MAPEO as $viejo => $claveAcento) {
            DB::table('comercios')
                ->whereRaw('UPPER(primary_color) = ?', [strtoupper($viejo)])
                ->update(['primary_color' => Comercio::ACENTOS[$claveAcento]['color']]);
        }
    }

    public function down(): void
    {
        Schema::table('comercios', function (Blueprint $table) {
            $table->string('primary_color')->default('#3B82F6')->change();
        });
    }
};
