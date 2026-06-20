<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Menu;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Planes: cuota mensual de IA, reset manual, gating de features y panel admin.
 */
class PlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_tres_planes_se_siembran_en_la_migracion(): void
    {
        $this->assertDatabaseHas('plans', ['key' => 'basico']);
        $this->assertDatabaseHas('plans', ['key' => 'pro']);
        $this->assertDatabaseHas('plans', ['key' => 'premium']);
    }

    // ---- Cuota y uso de IA (modelo) ----

    public function test_registrar_uso_ia_incrementa_y_reset_lo_vuelve_a_cero(): void
    {
        $comercio = Comercio::factory()->create(['plan_id' => Plan::where('key', 'basico')->value('id')]);

        $this->assertTrue($comercio->puedeUsarIA());
        $comercio->registrarUsoIA();
        $this->assertEquals(1, $comercio->fresh()->mensajesIaUsados());

        $comercio->resetUsoIA();
        $this->assertEquals(0, $comercio->fresh()->mensajesIaUsados());
    }

    public function test_uso_ia_se_resetea_solo_al_cambiar_de_mes(): void
    {
        $comercio = Comercio::factory()->create([
            'ia_mensajes_periodo' => now()->subMonth()->format('Y-m'),
            'ia_mensajes_usados' => 999,
        ]);

        $this->assertEquals(0, $comercio->mensajesIaUsados());
        $this->assertTrue($comercio->puedeUsarIA());
    }

    public function test_no_puede_usar_ia_si_llego_al_limite_del_plan(): void
    {
        $plan = Plan::create([
            'key' => 'limitado', 'nombre' => 'Limitado', 'mensajes_ia_mes' => 5,
            'modelo_ia' => 'gpt-4o-mini', 'max_tokens_chat' => 600, 'features' => [], 'activo' => true,
        ]);
        $comercio = Comercio::factory()->create([
            'plan_id' => $plan->id,
            'ia_mensajes_periodo' => now()->format('Y-m'),
            'ia_mensajes_usados' => 5,
        ]);

        $this->assertFalse($comercio->puedeUsarIA());
    }

    public function test_plan_ilimitado_siempre_puede_usar_ia(): void
    {
        $comercio = Comercio::factory()->create([
            'plan_id' => Plan::where('key', 'premium')->value('id'),
            'ia_mensajes_periodo' => now()->format('Y-m'),
            'ia_mensajes_usados' => 999999,
        ]);

        $this->assertTrue($comercio->puedeUsarIA());
    }

    // ---- Chatbot respeta la cuota ----

    public function test_chatbot_responde_mensaje_amable_y_no_usa_ia_si_se_agoto_la_cuota(): void
    {
        $plan = Plan::create([
            'key' => 'sincuota', 'nombre' => 'Sin Cuota', 'mensajes_ia_mes' => 1,
            'modelo_ia' => 'gpt-4o-mini', 'max_tokens_chat' => 600, 'features' => [], 'activo' => true,
        ]);
        $comercio = Comercio::factory()->create([
            'plan_id' => $plan->id,
            'enable_ai_responses' => true,
            'ia_mensajes_periodo' => now()->format('Y-m'),
            'ia_mensajes_usados' => 1,
        ]);

        $response = $this->postJson(route('chat.api', $comercio), ['message' => 'Hola']);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertStringContainsString('no está disponible', $response->json('response'));
        $this->assertDatabaseHas('chat_conversations', [
            'comercio_id' => $comercio->id,
            'used_openai' => false,
        ]);
    }

    // ---- Gating de features ----

    public function test_exportar_excel_bloqueado_sin_la_feature(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create([
            'plan_id' => Plan::where('key', 'basico')->value('id'), // sin 'excel'
        ]);
        $menu = Menu::factory()->for($comercio)->create();

        $this->actingAs($user)
            ->get(route('menus.export', [$comercio, $menu]))
            ->assertForbidden();
    }

    public function test_promociones_bloqueadas_sin_la_feature(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create([
            'plan_id' => Plan::where('key', 'basico')->value('id'), // sin 'promociones'
        ]);
        $menu = Menu::factory()->for($comercio)->create();

        $this->actingAs($user)
            ->post(route('menus.save.promociones', [$comercio, $menu]))
            ->assertForbidden();
    }

    public function test_super_admin_no_es_bloqueado_por_el_gating_de_features(): void
    {
        // El super admin pasa el middleware aunque el comercio esté en Básico (sin features).
        $admin = User::factory()->superAdmin()->create();
        $comercio = Comercio::factory()->create([
            'plan_id' => Plan::where('key', 'basico')->value('id'),
        ]);
        $menu = Menu::factory()->for($comercio)->create();

        $this->assertNotEquals(403, $this->actingAs($admin)
            ->post(route('menus.save.promociones', [$comercio, $menu]))->status());
    }

    // ---- Panel admin de planes ----

    public function test_usuario_comun_no_puede_ver_panel_de_planes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.plans.index'))->assertForbidden();
    }

    public function test_super_admin_ve_el_panel_de_planes(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($admin)->get(route('admin.plans.index'));

        $response->assertOk();
        $response->assertSee('Básico');
        $response->assertSee('Premium');
    }

    public function test_super_admin_puede_asignar_plan_a_un_comercio(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $comercio = Comercio::factory()->create(['plan_id' => Plan::where('key', 'basico')->value('id')]);
        $premium = Plan::where('key', 'premium')->first();

        $response = $this->actingAs($admin)->put(route('admin.comercios.assign-plan', $comercio), [
            'plan_id' => $premium->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('comercios', ['id' => $comercio->id, 'plan_id' => $premium->id]);
    }

    public function test_super_admin_puede_resetear_uso_de_ia(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $comercio = Comercio::factory()->create([
            'ia_mensajes_periodo' => now()->format('Y-m'),
            'ia_mensajes_usados' => 480,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.comercios.reset-ia', $comercio));

        $response->assertRedirect();
        $this->assertEquals(0, $comercio->fresh()->ia_mensajes_usados);
    }

    public function test_super_admin_puede_editar_un_plan(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $basico = Plan::where('key', 'basico')->first();

        $response = $this->actingAs($admin)->put(route('admin.plans.update', $basico), [
            'nombre' => 'Arranque',
            'modelo_ia' => 'gpt-4o-mini',
            'max_tokens_chat' => 700,
            'mensajes_ia_mes' => 800,
            'features' => ['analytics'],
            'activo' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('plans', ['id' => $basico->id, 'nombre' => 'Arranque', 'mensajes_ia_mes' => 800]);
    }
}
