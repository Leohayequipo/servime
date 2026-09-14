<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El item "Menús" del nav tiene que llegar a TODOS los comercios del usuario.
 *
 * Salía de un `comercios()->first()`: con dos comercios quedaba clavado en el primero y
 * al otro sólo se llegaba por Comercios → Ver menús. No es un caso raro: el límite de un
 * comercio por usuario aplica sólo al alta que hace el propio usuario, el super admin le
 * puede asignar los que quiera.
 */
class NavMenusTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioCon(int $comercios): User
    {
        $user = User::factory()->create(['role' => 'user']);
        Comercio::factory()->count($comercios)->create(['user_id' => $user->id]);

        return $user;
    }

    public function test_con_un_comercio_el_nav_va_derecho_a_sus_menus(): void
    {
        $user = $this->usuarioCon(1);
        $comercio = $user->comercios->first();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('menus.index', $comercio), false);
    }

    public function test_con_dos_comercios_el_nav_ofrece_los_dos(): void
    {
        $user = $this->usuarioCon(2);
        [$primero, $segundo] = [$user->comercios[0], $user->comercios[1]];

        $html = $this->actingAs($user)->get(route('dashboard'))->assertOk();

        // El que fallaba es el segundo: el nav se quedaba con el primero de la lista.
        $html->assertSee(route('menus.index', $primero), false)
            ->assertSee(route('menus.index', $segundo), false)
            // Con escape: el nombre del comercio sale por Blade, y los que genera el
            // factory traen apóstrofes cada tanto.
            ->assertSee($segundo->nombre);
    }

    public function test_sin_comercios_el_nav_no_ofrece_menus(): void
    {
        // Sin comercio no hay menú que mostrar: el item no se dibuja y la tarjeta del
        // dashboard cae al listado, que es donde se crea el primero.
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('/menus', false);
    }

    public function test_el_super_admin_sigue_viendo_el_listado_global(): void
    {
        // Su nav no apunta a un comercio propio: va a las pantallas de /admin.
        $admin = User::factory()->superAdmin()->create();
        Comercio::factory()->create();

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('admin.menus.index'), false);
    }
}
