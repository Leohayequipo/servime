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
}
