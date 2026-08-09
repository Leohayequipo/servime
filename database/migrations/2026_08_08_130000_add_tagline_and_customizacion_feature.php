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
            /**
             * Frase corta bajo el nombre en el menú público.
             *
             * Va aparte de `descripcion`: esa es un texto largo y recortarla
             * para usarla de bajada corta a mitad de frase ("...con un diseño
             * elegante y…"). Son dos textos con usos distintos.
             */
            $table->string('tagline', 80)->nullable()->after('descripcion');
        });

        // La portada personalizada es una feature de plan. Se agrega a Premium;
        // desde /admin/plans se puede mover a otros planes sin tocar código.
        foreach (DB::table('plans')->where('key', 'premium')->get() as $plan) {
            $features = json_decode($plan->features ?? '[]', true) ?: [];

            if (! in_array('customizacion', $features, true)) {
                $features[] = 'customizacion';
                DB::table('plans')->where('id', $plan->id)->update([
                    'features' => json_encode($features),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('comercios', function (Blueprint $table) {
            $table->dropColumn('tagline');
        });

        foreach (DB::table('plans')->where('key', 'premium')->get() as $plan) {
            $features = json_decode($plan->features ?? '[]', true) ?: [];

            DB::table('plans')->where('id', $plan->id)->update([
                'features' => json_encode(array_values(array_diff($features, ['customizacion']))),
            ]);
        }
    }
};
