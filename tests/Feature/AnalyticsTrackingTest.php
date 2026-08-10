<?php

namespace Tests\Feature;

use App\Models\ChatConversation;
use App\Models\Comercio;
use App\Models\Plan;
use App\Models\TrackingEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tracking de eventos del menú público + panel de analytics (binding, scope, gating).
 */
class AnalyticsTrackingTest extends TestCase
{
    use RefreshDatabase;

    // ---- Endpoint de tracking ----

    public function test_registra_un_evento_valido(): void
    {
        $comercio = Comercio::factory()->create();

        $response = $this->postJson(route('track', $comercio), [
            'event' => 'menu_choice',
            'session_id' => 'sess_1',
            'data' => ['choice' => 'ia'],
        ]);

        $response->assertNoContent();
        $this->assertDatabaseHas('tracking_events', [
            'comercio_id' => $comercio->id,
            'event' => 'menu_choice',
            'session_id' => 'sess_1',
        ]);
    }

    public function test_ignora_eventos_fuera_de_la_whitelist(): void
    {
        $comercio = Comercio::factory()->create();

        $this->postJson(route('track', $comercio), ['event' => 'evento_raro'])
            ->assertNoContent();

        $this->assertDatabaseCount('tracking_events', 0);
    }

    public function test_el_evento_es_obligatorio(): void
    {
        $comercio = Comercio::factory()->create();

        $this->postJson(route('track', $comercio), [])->assertStatus(422);
    }

    // ---- Panel de analytics: binding, scope y gating ----

    public function test_super_admin_ve_el_dashboard_de_analytics(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get(route('analytics.dashboard'))->assertOk();
    }

    public function test_comercio_con_feature_ve_su_panel(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create([
            'plan_id' => Plan::where('key', 'pro')->value('id'),
        ]);

        $this->actingAs($user)->get(route('analytics.comercio', $comercio))->assertOk();
    }

    public function test_comercio_sin_feature_no_ve_su_panel(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create([
            'plan_id' => Plan::where('key', 'basico')->value('id'),
        ]);

        $this->actingAs($user)->get(route('analytics.comercio', $comercio))->assertForbidden();
    }

    public function test_no_ve_analytics_de_un_comercio_ajeno(): void
    {
        $user = User::factory()->create();
        $ajeno = Comercio::factory()->create([
            'plan_id' => Plan::where('key', 'pro')->value('id'),
        ]);

        $this->actingAs($user)->get(route('analytics.comercio', $ajeno))->assertForbidden();
    }

    public function test_clasifica_la_intencion_de_los_mensajes(): void
    {
        $comercio = Comercio::factory()->create();

        $mensajes = ['¿Cuál es el horario?', '¿Hacen envíos a domicilio?', 'Quiero algo vegano', '¿Cuánto cuesta la pizza?'];
        foreach ($mensajes as $m) {
            ChatConversation::create([
                'comercio_id' => $comercio->id,
                'session_id' => 'sess_'.uniqid(),
                'user_message' => $m,
                'bot_response' => 'ok',
                'used_openai' => true,
                'response_time_ms' => 1000,
            ]);
        }

        $intents = (new \App\Services\AnalyticsInsightsService)->intents($comercio->id);

        $this->assertSame(4, $intents['total']);
        $this->assertSame(1, $intents['counts']['Horario']);
        $this->assertSame(1, $intents['counts']['Envío / Delivery']);
        $this->assertSame(1, $intents['counts']['Dieta / Restricciones']);
        $this->assertSame(1, $intents['counts']['Precio']);
    }

    public function test_el_visitante_que_vuelve_suma_otra_visita_y_no_estira_la_primera(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $comercio = Comercio::factory()->create([
            'plan_id' => Plan::where('key', 'pro')->value('id'),
        ]);

        // Mismo dispositivo (mismo session_id de localStorage) en dos días distintos:
        // una visita de 5 minutos y, diez días después, otra de 2.
        $this->evento($comercio, 'vuelve', 'page_view', now()->subDays(10), ['view' => 'choose']);
        $this->evento($comercio, 'vuelve', 'menu_choice', now()->subDays(10)->addMinutes(5), ['choice' => 'ia']);
        $this->evento($comercio, 'vuelve', 'page_view', now()->subMinutes(2), ['view' => 'chat']);
        $this->evento($comercio, 'vuelve', 'dish_want', now(), ['nombre' => 'Milanesa']);

        // Un visitante de una sola vez, para distinguir nuevos de recurrentes
        $this->evento($comercio, 'unica', 'page_view', now()->subDay(), ['view' => 'choose']);

        $metrics = $this->actingAs($admin)
            ->get(route('analytics.comercio', $comercio))
            ->assertOk()
            ->viewData('metrics');

        $this->assertSame(3, $metrics['totalVisitas']);
        $this->assertSame(2, $metrics['totalVisitantes']);
        $this->assertSame(1, $metrics['visitors']['recurrentes']);
        $this->assertSame(1, $metrics['visitors']['nuevos']);

        // El corte por inactividad lo fija totalVisitas = 3: sin él, los eventos de este
        // visitante (separados por 10 días) caerían todos en una sola visita.

        // El embudo también cuenta visitas: 3 con page_view, 2 que vieron la pantalla inicial
        $this->assertSame(3, $metrics['funnel']['visitas']);
        $this->assertSame(2, $metrics['funnel']['vieron_eleccion']);
        $this->assertSame(1, $metrics['funnel']['eligieron']);
        $this->assertSame(1, $metrics['funnel']['interesados']);
    }

    public function test_los_eventos_sin_session_id_no_cuentan_como_visita(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $comercio = Comercio::factory()->create([
            'plan_id' => Plan::where('key', 'pro')->value('id'),
        ]);

        $this->evento($comercio, 'con_id', 'page_view', now(), ['view' => 'choose']);
        $this->evento($comercio, null, 'page_view', now(), ['view' => 'choose']);

        $metrics = $this->actingAs($admin)
            ->get(route('analytics.comercio', $comercio))
            ->assertOk()
            ->viewData('metrics');

        $this->assertSame(1, $metrics['totalVisitas']);
        $this->assertSame(1, $metrics['funnel']['visitas']);
    }

    /**
     * Evento de tracking con fecha e id de visitante controlados.
     */
    private function evento(Comercio $comercio, ?string $sessionId, string $event, $cuando, array $data): void
    {
        TrackingEvent::factory()->create([
            'comercio_id' => $comercio->id,
            'session_id' => $sessionId,
            'event' => $event,
            'data' => $data,
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Mobile/15E148',
            'created_at' => $cuando,
        ]);
    }

    public function test_el_panel_renderiza_con_datos_de_eventos_y_conversaciones(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $comercio = Comercio::factory()->create([
            'plan_id' => Plan::where('key', 'pro')->value('id'),
        ]);

        // Eventos variados (ejercita IA/clásico, promos, platos, heatmap, embudo)
        TrackingEvent::factory()->count(25)->create(['comercio_id' => $comercio->id]);

        // Conversaciones (ejercita top palabras y stats)
        foreach (['Quiero algo vegetariano', '¿Tienen postres veganos?', 'Recomendame postres'] as $msg) {
            ChatConversation::create([
                'comercio_id' => $comercio->id,
                'session_id' => 'sess_'.uniqid(),
                'user_message' => $msg,
                'bot_response' => 'ok',
                'used_openai' => true,
                'response_time_ms' => 1200,
            ]);
        }

        $this->actingAs($admin)->get(route('analytics.comercio', $comercio))->assertOk();
    }

    // ---- Por dónde entran (desde / label del menu_choice) ----

    public function test_el_panel_desglosa_por_donde_entran_y_que_atajo_usan(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $comercio = Comercio::factory()->create([
            'plan_id' => Plan::where('key', 'pro')->value('id'),
        ]);

        $ahora = now()->subHour();
        $this->evento($comercio, 'v1', 'menu_choice', $ahora, ['choice' => 'ia', 'desde' => 'cta']);
        $this->evento($comercio, 'v2', 'menu_choice', $ahora, ['choice' => 'ia', 'desde' => 'chip', 'label' => 'Sin TACC']);
        $this->evento($comercio, 'v3', 'menu_choice', $ahora, ['choice' => 'ia', 'desde' => 'chip', 'label' => 'Sin TACC']);
        $this->evento($comercio, 'v4', 'menu_choice', $ahora, ['choice' => 'classic', 'desde' => 'completo']);
        // Evento viejo, de antes del atributo: no tiene 'desde' y no se puede atribuir.
        $this->evento($comercio, 'v5', 'menu_choice', $ahora, ['choice' => 'ia']);

        $respuesta = $this->actingAs($admin)->get(route('analytics.comercio', $comercio))->assertOk();
        $metrics = $respuesta->viewData('metrics');

        $this->assertSame(['cta' => 1, 'chip' => 2, 'completo' => 1], $metrics['entradas']);
        $this->assertSame(2, $metrics['topChips']['Sin TACC']);
        // El "IA vs clásico" sigue contando todos, incluido el que no trae origen.
        $this->assertSame(4, $metrics['menuChoice']['ia']);

        $respuesta->assertSee('¿Por dónde entran?')->assertSee('Sin TACC');
    }

    // ---- Cierre del embudo (pedido para el mozo) y micrófono ----

    public function test_el_embudo_cierra_con_el_pedido_y_cuenta_el_microfono(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $comercio = Comercio::factory()->create([
            'plan_id' => Plan::where('key', 'pro')->value('id'),
        ]);

        $ahora = now()->subHour();
        // Visita completa: mira, quiere un plato y abre el resumen para el mozo.
        $this->evento($comercio, 'v1', 'page_view', $ahora, ['view' => 'chat']);
        $this->evento($comercio, 'v1', 'dish_want', $ahora, ['nombre' => 'Milanesa']);
        $this->evento($comercio, 'v1', 'order_summary', $ahora, ['items' => 2]);
        // Abrió el resumen vacío: no armó ningún pedido, no cuenta.
        $this->evento($comercio, 'v2', 'page_view', $ahora, ['view' => 'chat']);
        $this->evento($comercio, 'v2', 'order_summary', $ahora, ['items' => 0]);
        // Dictó dos veces en la misma visita: es una persona usando el micrófono, no dos.
        $this->evento($comercio, 'v3', 'page_view', $ahora, ['view' => 'chat']);
        $this->evento($comercio, 'v3', 'mic_start', $ahora, []);
        $this->evento($comercio, 'v3', 'mic_start', $ahora, []);

        $metrics = $this->actingAs($admin)
            ->get(route('analytics.comercio', $comercio))->assertOk()
            ->viewData('metrics');

        $this->assertSame(1, $metrics['funnel']['armaron_pedido']);
        $this->assertSame(1, $metrics['vozVisitas']);
        $this->assertSame(3, $metrics['totalVisitas']);
    }

    public function test_ia_vs_clasico_cuenta_visitas_y_no_clicks(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $comercio = Comercio::factory()->create([
            'plan_id' => Plan::where('key', 'pro')->value('id'),
        ]);

        $ahora = now()->subHour();
        // Un indeciso que va y vuelve cinco veces dentro de la misma visita: vale su
        // primera elección (ia) y no puede inclinar el ratio él solo.
        foreach ([0, 1, 2, 3, 4] as $i) {
            $this->evento($comercio, 'v1', 'menu_choice', $ahora->copy()->addMinutes($i),
                ['choice' => $i % 2 === 0 ? 'ia' : 'classic']);
        }
        // Dos visitas que eligieron el clásico y nada más.
        $this->evento($comercio, 'v2', 'menu_choice', $ahora, ['choice' => 'classic']);
        $this->evento($comercio, 'v3', 'menu_choice', $ahora, ['choice' => 'classic']);

        $metrics = $this->actingAs($admin)
            ->get(route('analytics.comercio', $comercio))->assertOk()
            ->viewData('metrics');

        $this->assertSame(1, $metrics['menuChoice']['ia']);
        $this->assertSame(2, $metrics['menuChoice']['classic']);
        // Cada visita vale una, así que la suma cierra con el escalón del embudo.
        $this->assertSame(3, $metrics['funnel']['eligieron']);
        $this->assertSame(1, $metrics['menuChoice']['ambos']);
    }

    public function test_el_panel_rankea_las_categorias_que_miran(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $comercio = Comercio::factory()->create([
            'plan_id' => Plan::where('key', 'pro')->value('id'),
        ]);

        $ahora = now()->subHour();
        // Volver dos veces a la misma categoría suma dos: acá la unidad es el toque.
        $this->evento($comercio, 'v1', 'categoria_tap', $ahora, ['nombre' => 'Postres']);
        $this->evento($comercio, 'v1', 'categoria_tap', $ahora, ['nombre' => 'Postres']);
        $this->evento($comercio, 'v2', 'categoria_tap', $ahora, ['nombre' => 'Pizzas']);
        // Sin nombre no se puede rankear: se descarta en vez de mostrar una fila vacía.
        $this->evento($comercio, 'v3', 'categoria_tap', $ahora, []);

        $respuesta = $this->actingAs($admin)->get(route('analytics.comercio', $comercio))->assertOk();
        $top = $respuesta->viewData('metrics')['topCategorias'];

        $this->assertSame(['Postres' => 2, 'Pizzas' => 1], $top->all());
        $respuesta->assertSee('Qué categorías miran', false);
    }

    public function test_el_panel_rankea_las_etiquetas_y_las_muestra_legibles(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $comercio = Comercio::factory()->create([
            'plan_id' => Plan::where('key', 'pro')->value('id'),
        ]);

        $ahora = now()->subHour();
        // El menú carga las etiquetas de cualquier forma: SIN_TACC, sin-tacc, sin tacc.
        // Las tres son la misma restricción y tienen que juntarse en una sola fila.
        $this->evento($comercio, 'v1', 'tag_tap', $ahora, ['nombre' => 'SIN_TACC']);
        $this->evento($comercio, 'v2', 'tag_tap', $ahora, ['nombre' => 'sin-tacc']);
        $this->evento($comercio, 'v3', 'tag_tap', $ahora, ['nombre' => 'sin tacc']);
        $this->evento($comercio, 'v4', 'tag_tap', $ahora, ['nombre' => 'VEGANO']);

        $respuesta = $this->actingAs($admin)->get(route('analytics.comercio', $comercio))->assertOk();

        $this->assertSame(['Sin tacc' => 3, 'Vegano' => 1], $respuesta->viewData('metrics')['topTags']->all());
        $respuesta->assertSee('Qué etiquetas filtran', false);
    }

    public function test_un_comercio_sin_datos_ve_los_widgets_vacios_y_no_pantalla_en_blanco(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $comercio = Comercio::factory()->create([
            'plan_id' => Plan::where('key', 'pro')->value('id'),
        ]);

        // Sin un solo evento: los widgets tienen que estar igual, avisando que no hay datos.
        // Si desaparecen, el comercio nunca se entera de que la función existe.
        $this->actingAs($admin)->get(route('analytics.comercio', $comercio))->assertOk()
            ->assertSee('¿Por dónde entran?', false)
            ->assertSee('Atajos más usados', false)
            ->assertSee('Qué categorías miran', false)
            ->assertSee('Todavía nadie abrió el listado de categorías', false)
            ->assertSee('Qué etiquetas filtran', false)
            ->assertSee('Todavía nadie filtró por etiquetas', false);
    }

    public function test_el_endpoint_acepta_los_eventos_nuevos(): void
    {
        $comercio = Comercio::factory()->create();

        foreach ([['order_summary', ['items' => 3]], ['mic_start', []], ['categoria_tap', ['nombre' => 'Postres']]] as [$event, $data]) {
            $this->postJson(route('track', $comercio), [
                'event' => $event,
                'session_id' => 'sess_1',
                'data' => $data,
            ])->assertNoContent();

            $this->assertDatabaseHas('tracking_events', [
                'comercio_id' => $comercio->id,
                'event' => $event,
            ]);
        }
    }
}
