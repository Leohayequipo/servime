<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Planes Free y Mínimo, y el tope de cartas procesadas.
 *
 * - Free ($0): la carta sin asistente, con "Lo quiero" para mostrarle al mozo.
 * - Mínimo ($2.000): lo mismo, más mesas con QR propio y pedidos a la tablet del mozo.
 *
 * Ninguno de los dos está escrito en el código: son planes comunes, editables desde
 * /admin/plans como los demás. Lo que los hace distintos son sus datos (precio, 0 mensajes
 * de IA, qué funciones tienen tildadas y cuántas cartas pueden procesar).
 *
 * El tope de cartas son DOS números y no uno: al principio el comercio sube, corrige y
 * vuelve a subir, y después la carta cambia poco. `null` = sin límite, que es lo que
 * quedan los planes que ya existían.
 *
 * Se siembra en la migración y no en un seeder porque `DatabaseSeeder` no corre en
 * producción (ver la skill `planes`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedSmallInteger('cartas_primer_mes')->nullable()->after('max_tokens_chat');
            $table->unsignedSmallInteger('cartas_por_mes')->nullable()->after('cartas_primer_mes');
        });

        // La función nueva existía de hecho en todos los planes ("Lo quiero" está en el chat
        // de todos): se tilda en los que ya están para que nadie pierda nada.
        foreach (DB::table('plans')->get() as $plan) {
            $features = json_decode($plan->features ?? '[]', true) ?: [];

            if (! in_array('lista_pedido', $features, true)) {
                $features[] = 'lista_pedido';
            }

            DB::table('plans')->where('id', $plan->id)->update(['features' => json_encode($features)]);
        }

        // Los dos nuevos van primeros en la escalera: se corre el resto dos lugares. El
        // plan de demo (`orden = 0`) no se toca, porque ese no se muestra.
        if (! DB::table('plans')->where('key', 'free')->exists()) {
            DB::table('plans')->where('orden', '>', 0)->increment('orden', 2);
        }

        $ahora = now();
        $comunes = [
            'mensajes_ia_mes' => 0,   // 0 = sin asistente: el menú no ofrece el chat
            'modelo_ia' => 'gpt-4o-mini',
            'max_tokens_chat' => 900,
            'cartas_primer_mes' => 3,
            'cartas_por_mes' => 1,
            // Se crean APAGADOS: un plan activo aparece en la landing pública, y lanzarlos
            // tiene que ser una decisión y no un efecto del deploy. El lanzamiento es tildar
            // "activo" en los dos desde /admin/plans y, en ese mismo momento, "Mesas" y
            // "Comandas" en Básico y Pro (si no, pasar de Mínimo a Básico o Pro le saca al
            // comercio los pedidos a la tablet).
            'activo' => false,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ];

        if (! DB::table('plans')->where('key', 'free')->exists()) {
            DB::table('plans')->insert($comunes + [
                'key' => 'free',
                'nombre' => 'Free',
                'descripcion' => 'Tu carta digital con QR, sin costo.',
                'precio' => 0,
                'features' => json_encode(['promociones', 'lista_pedido']),
                'orden' => 1,
            ]);
        }

        if (! DB::table('plans')->where('key', 'minimo')->exists()) {
            DB::table('plans')->insert($comunes + [
                'key' => 'minimo',
                'nombre' => 'Mínimo',
                'descripcion' => 'Tu carta con QR por mesa y los pedidos directo al mozo.',
                'precio' => 2000,
                'features' => json_encode(['promociones', 'lista_pedido', 'mesas', 'comandas']),
                'orden' => 2,
            ]);
        }
    }

    public function down(): void
    {
        if (DB::table('plans')->where('key', 'free')->exists()) {
            DB::table('plans')->whereIn('key', ['free', 'minimo'])->delete();
            DB::table('plans')->where('orden', '>', 2)->decrement('orden', 2);
        }

        foreach (DB::table('plans')->get() as $plan) {
            $features = array_values(array_diff(
                json_decode($plan->features ?? '[]', true) ?: [],
                ['lista_pedido']
            ));
            DB::table('plans')->where('id', $plan->id)->update(['features' => json_encode($features)]);
        }

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['cartas_primer_mes', 'cartas_por_mes']);
        });
    }
};
