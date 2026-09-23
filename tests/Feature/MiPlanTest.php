<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La pantalla "Mi plan" del comercio: qué tiene y qué le daría el siguiente.
 */
class MiPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_muestra_el_plan_del_comercio_y_los_otros_para_pedir(): void
    {
        config(['services.support.whatsapp' => '5491100000000']);

        $user = User::factory()->create(['role' => 'user']);
        $comercio = Comercio::factory()->for($user)->create([
            'nombre' => 'Bar del Puerto',
            'plan_id' => Plan::where('key', 'basico')->value('id'),
        ]);

        // Los nombres salen de la base: renombrar un plan no puede dejar la pantalla mintiendo.
        Plan::where('key', 'pro')->update(['nombre' => 'Profesional', 'precio' => 15000]);

        $this->actingAs($user)->get(route('mi-plan'))
            ->assertOk()
            ->assertSee('Bar del Puerto')
            ->assertSee('Plan '.Plan::where('key', 'basico')->value('nombre'))
            ->assertSee('Profesional')
            ->assertSee('$15.000')
            // Lo que suma el plan de arriba, con la etiqueta humana de la feature
            ->assertSee(Plan::FEATURES['analytics'])
            ->assertSee('wa.me/5491100000000');
    }

    public function test_el_plan_que_ya_tiene_no_se_ofrece(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        Comercio::factory()->for($user)->create([
            'plan_id' => Plan::where('key', 'premium')->value('id'),
        ]);

        $this->actingAs($user)->get(route('mi-plan'))
            ->assertOk()
            ->assertSee('Es el que estás usando.');
    }

    public function test_no_muestra_planes_inactivos_ni_el_de_demo(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        Comercio::factory()->for($user)->create([
            'plan_id' => Plan::where('key', 'basico')->value('id'),
        ]);

        Plan::create(['key' => 'demo', 'nombre' => 'PlanDeDemo', 'orden' => 0, 'activo' => true, 'modelo_ia' => 'gpt-4o-mini', 'max_tokens_chat' => 900]);
        Plan::create(['key' => 'viejo', 'nombre' => 'PlanViejo', 'orden' => 9, 'activo' => false, 'modelo_ia' => 'gpt-4o-mini', 'max_tokens_chat' => 900]);

        $this->actingAs($user)->get(route('mi-plan'))
            ->assertOk()
            ->assertDontSee('PlanDeDemo')
            ->assertDontSee('PlanViejo');
    }

    public function test_el_mensaje_de_whatsapp_dice_quien_escribe_y_en_que_plan_esta(): void
    {
        config(['services.support.whatsapp' => '5491100000000']);

        $user = User::factory()->create(['role' => 'user', 'name' => 'Ana Gómez', 'email' => 'ana@bar.com']);
        Comercio::factory()->for($user)->create([
            'nombre' => 'Bar del Puerto',
            'plan_id' => Plan::where('key', 'basico')->value('id'),
        ]);

        $basico = Plan::where('key', 'basico')->value('nombre');

        // Llega a un WhatsApp donde el número solo no dice qué cuenta hay que tocar.
        $this->actingAs($user)->get(route('mi-plan'))
            ->assertOk()
            ->assertSee(rawurlencode('Ana Gómez'))
            ->assertSee(rawurlencode('ana@bar.com'))
            ->assertSee(rawurlencode("Bar del Puerto (plan {$basico})"));
    }

    public function test_la_presentacion_nombra_todos_los_comercios_cuando_hay_varios(): void
    {
        $user = User::factory()->create(['role' => 'user', 'name' => 'Ana', 'email' => 'ana@bar.com']);
        $uno = Comercio::factory()->for($user)->create(['nombre' => 'Uno']);
        Comercio::factory()->for($user)->create(['nombre' => 'Dos']);

        $this->assertStringContainsString('Mis comercios: Uno', $user->presentacionParaSoporte());
        $this->assertStringContainsString('Dos', $user->presentacionParaSoporte());

        // Con un comercio concreto (el de la pantalla donde se pidió) se nombra sólo ese.
        $this->assertStringContainsString('de Uno', $user->presentacionParaSoporte($uno));
        $this->assertStringNotContainsString('Dos', $user->presentacionParaSoporte($uno));
    }

    public function test_el_super_admin_va_al_panel_de_planes(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get(route('mi-plan'))
            ->assertRedirect(route('admin.plans.index'));
    }

    public function test_el_link_esta_en_el_menu_del_comercio_y_no_en_el_del_admin(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        Comercio::factory()->for($user)->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('mi-plan'));

        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('mi-plan'));
    }
}
