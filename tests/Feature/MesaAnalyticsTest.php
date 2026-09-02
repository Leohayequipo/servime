<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Mesa;
use App\Models\Plan;
use App\Models\TrackingEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Actividad por mesa en el panel de analytics.
 *
 * El número que importa es el de las mesas SIN actividad: es el que se traduce en ir a
 * mirar un cartel. Por eso se prueba con más detalle que el ranking.
 */
class MesaAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function comercioConMesas(array $features = ['analytics', 'mesas']): Comercio
    {
        $plan = Plan::create([
            'key' => 'test-'.uniqid(),
            'nombre' => 'Test',
            'mensajes_ia_mes' => 1000,
            'modelo_ia' => 'gpt-4o-mini',
            'max_tokens_chat' => 600,
            'features' => $features,
            'activo' => true,
        ]);

        return Comercio::factory()->for(User::factory())->create(['plan_id' => $plan->id]);
    }

    private function visita(Comercio $comercio, ?Mesa $mesa, string $sessionId, array $eventos = ['page_view']): void
    {
        foreach ($eventos as $evento) {
            TrackingEvent::create([
                'comercio_id' => $comercio->id,
                'mesa_id' => $mesa?->id,
                'session_id' => $sessionId,
                'event' => $evento,
                'data' => [],
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ]);
        }
    }

    public function test_avisa_de_las_mesas_de_las_que_no_entro_nadie(): void
    {
        $comercio = $this->comercioConMesas();
        $mesas = collect(range(1, 3))->map(
            fn ($n) => Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => $n])
        );

        // La mayoría del tráfico entra por QR de mesa: con eso la conclusión es sólida.
        $this->visita($comercio, $mesas[0], 'sesion-1');
        $this->visita($comercio, $mesas[0], 'sesion-2');

        $this->actingAs($comercio->user)
            ->get(route('analytics.comercio', $comercio))
            ->assertOk()
            ->assertSee('Revisá el cartel de 2 mesa(s)')
            // Las que no registran a nadie: casi siempre es el QR despegado.
            ->assertSee('2 y 3');
    }

    /**
     * El caso que hace que el aviso mienta.
     *
     * Si casi todos entran por el QR general del local (o por un link de las redes), sus
     * visitas no se le atribuyen a ninguna mesa y todas parecen muertas. Mandar al
     * comerciante a revisar carteles que están perfectos es peor que no decirle nada: la
     * próxima vez ignora el aviso.
     */
    public function test_no_afirma_que_el_cartel_falla_si_casi_nadie_entra_por_qr_de_mesa(): void
    {
        $comercio = $this->comercioConMesas();
        $mesa = Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 1]);
        Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 2]);

        $this->visita($comercio, $mesa, 'sesion-1');
        // Cuatro que entraron sin escanear ninguna mesa.
        foreach (range(1, 4) as $n) {
            $this->visita($comercio, null, "suelta-{$n}");
        }

        $respuesta = $this->actingAs($comercio->user)->get(route('analytics.comercio', $comercio));

        $respuesta->assertOk()
            ->assertDontSee('Revisá el cartel')
            ->assertSee('1 mesa(s) sin registrar a nadie')
            ->assertSee('puede no significar nada');
    }

    public function test_muestra_cuantas_visitas_entraron_sin_mesa(): void
    {
        $comercio = $this->comercioConMesas();
        $mesa = Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 1]);

        $this->visita($comercio, $mesa, 'sesion-1');
        $this->visita($comercio, null, 'redes-1');
        $this->visita($comercio, null, 'redes-2');

        // Sin este número, el "1 visita desde 1 mesa" no se puede interpretar.
        $this->actingAs($comercio->user)
            ->get(route('analytics.comercio', $comercio))
            ->assertOk()
            ->assertSee('2</span> visita(s) entraron sin mesa', false);
    }

    public function test_el_ranking_cuenta_visitas_y_no_eventos_sueltos(): void
    {
        $comercio = $this->comercioConMesas();
        $mesa = Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 1]);

        // Un solo cliente que toca ocho veces sigue siendo una visita.
        $this->visita($comercio, $mesa, 'sesion-1', array_fill(0, 8, 'dish_want'));

        $respuesta = $this->actingAs($comercio->user)->get(route('analytics.comercio', $comercio));

        $respuesta->assertOk()->assertSee('1 visita(s) escaneando el QR de una mesa');
    }

    public function test_cuenta_como_pedido_la_visita_que_abrio_el_resumen(): void
    {
        $comercio = $this->comercioConMesas();
        $conPedido = Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 1]);
        $sinPedido = Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 2]);

        $this->visita($comercio, $conPedido, 'sesion-1', ['page_view', 'dish_want', 'order_summary']);
        $this->visita($comercio, $sinPedido, 'sesion-2', ['page_view']);

        $respuesta = $this->actingAs($comercio->user)->get(route('analytics.comercio', $comercio));

        $respuesta->assertOk()->assertSee('Armaron pedido');
        $this->assertStringContainsString('Mesa 1', $respuesta->getContent());
    }

    public function test_la_actividad_sin_mesa_no_entra_en_el_conteo(): void
    {
        $comercio = $this->comercioConMesas();
        Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 1]);

        // Alguien que entró por el link de las redes, sin escanear ningún QR de mesa.
        $this->visita($comercio, null, 'sesion-suelta');

        $this->actingAs($comercio->user)
            ->get(route('analytics.comercio', $comercio))
            ->assertOk()
            ->assertSee('0 visita(s) escaneando el QR de una mesa')
            // Y aparece del otro lado, contada como visita sin mesa.
            ->assertSee('1</span> visita(s) entraron sin mesa', false);
    }

    public function test_sin_la_feature_mesas_la_seccion_no_aparece(): void
    {
        $comercio = $this->comercioConMesas(['analytics']);
        Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 1]);

        $this->actingAs($comercio->user)
            ->get(route('analytics.comercio', $comercio))
            ->assertOk()
            ->assertDontSee('Actividad por mesa');
    }

    public function test_con_la_feature_pero_sin_mesas_cargadas_tampoco_aparece(): void
    {
        $comercio = $this->comercioConMesas();

        $this->actingAs($comercio->user)
            ->get(route('analytics.comercio', $comercio))
            ->assertOk()
            ->assertDontSee('Actividad por mesa');
    }

    public function test_las_mesas_fuera_de_servicio_no_se_cuentan(): void
    {
        $comercio = $this->comercioConMesas();
        Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 1]);
        Mesa::factory()->inactiva()->create(['comercio_id' => $comercio->id, 'numero' => 2]);

        // Una mesa dada de baja no puede aparecer en la lista: no tiene cartel que revisar.
        // Sin tráfico todavía el aviso va en su forma suave (ver `alertaConfiable`).
        $this->actingAs($comercio->user)
            ->get(route('analytics.comercio', $comercio))
            ->assertOk()
            ->assertSee('1 mesa(s) sin registrar a nadie')
            ->assertSee('1 mesa(s) en servicio');
    }
}
