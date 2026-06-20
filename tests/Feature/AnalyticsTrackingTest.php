<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Plan;
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
}
