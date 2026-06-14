<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Aislamiento multi-tenant: un usuario común solo puede ver/editar/eliminar
 * SUS comercios. El super admin puede con cualquiera.
 */
class ComercioIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_sees_only_their_own_comercios_in_index(): void
    {
        $user = User::factory()->create();
        $propio = Comercio::factory()->for($user)->create(['nombre' => 'Mi Comercio']);
        $ajeno = Comercio::factory()->create(['nombre' => 'Comercio Ajeno']);

        $response = $this->actingAs($user)->get(route('comercios.index'));

        $response->assertOk();
        $response->assertSee('Mi Comercio');
        $response->assertDontSee('Comercio Ajeno');
    }

    public function test_user_can_not_edit_another_users_comercio(): void
    {
        $user = User::factory()->create();
        $ajeno = Comercio::factory()->create();

        $response = $this->actingAs($user)->get(route('comercios.edit', $ajeno));

        $response->assertForbidden();
    }

    public function test_user_can_not_update_another_users_comercio(): void
    {
        $user = User::factory()->create();
        $ajeno = Comercio::factory()->create(['nombre' => 'Original']);

        $response = $this->actingAs($user)->put(route('comercios.update', $ajeno), [
            'nombre' => 'Hackeado',
            'primary_color' => '#000000',
            'secondary_color' => '#000000',
            'accent_color' => '#000000',
            'background_color' => '#000000',
            'chatbot_name' => 'Bot',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('comercios', ['id' => $ajeno->id, 'nombre' => 'Original']);
    }

    public function test_user_can_not_delete_another_users_comercio(): void
    {
        $user = User::factory()->create();
        $ajeno = Comercio::factory()->create();

        $response = $this->actingAs($user)->delete(route('comercios.destroy', $ajeno));

        $response->assertForbidden();
        $this->assertDatabaseHas('comercios', ['id' => $ajeno->id]);
    }

    public function test_user_can_edit_their_own_comercio(): void
    {
        $user = User::factory()->create();
        $propio = Comercio::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('comercios.edit', $propio));

        $response->assertOk();
    }

    public function test_super_admin_can_edit_any_comercio(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $ajeno = Comercio::factory()->create();

        $response = $this->actingAs($admin)->get(route('comercios.edit', $ajeno));

        $response->assertOk();
    }
}
