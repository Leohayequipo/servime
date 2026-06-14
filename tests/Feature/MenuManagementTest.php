<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_activating_a_menu_deactivates_the_others(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create();
        $menuA = Menu::factory()->for($comercio)->activo()->create();
        $menuB = Menu::factory()->for($comercio)->create(); // inactivo

        $response = $this->actingAs($user)
            ->post(route('menus.activar', [$comercio, $menuB]));

        $response->assertRedirect(route('menus.index', $comercio));

        // Solo uno queda activo
        $this->assertFalse($menuA->fresh()->activo);
        $this->assertTrue($menuB->fresh()->activo);
        $this->assertSame(1, $comercio->menus()->where('activo', true)->count());
    }

    public function test_deleting_a_menu_is_a_soft_delete(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create();
        $menu = Menu::factory()->for($comercio)->create();

        $response = $this->actingAs($user)
            ->delete(route('menus.destroy', [$comercio, $menu]));

        $response->assertRedirect(route('menus.index', $comercio));
        $this->assertSoftDeleted('menus', ['id' => $menu->id]);
    }

    public function test_user_can_not_activate_menu_of_another_comercio(): void
    {
        $user = User::factory()->create();
        $ajeno = Comercio::factory()->create();
        $menu = Menu::factory()->for($ajeno)->create();

        $response = $this->actingAs($user)
            ->post(route('menus.activar', [$ajeno, $menu]));

        $response->assertForbidden();
        $this->assertFalse($menu->fresh()->activo);
    }
}
