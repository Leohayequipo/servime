<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Menu;
use App\Models\Mesa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El QR de la mesa entra una vez por `?m=<token>` y la mesa queda en la sesión.
 *
 * Lo más importante que se prueba acá es lo que NO tiene que pasar: un token roto, de
 * otro comercio o de una mesa dada de baja no puede dejar a un cliente sin poder ver
 * la carta. El menú público es lo único que ve la gente en el local.
 */
class MesaPublicaTest extends TestCase
{
    use RefreshDatabase;

    private function comercioConMenu(array $extra = []): Comercio
    {
        $comercio = Comercio::factory()->create($extra);

        Menu::factory()->create([
            'comercio_id' => $comercio->id,
            'activo' => true,
            'procesado' => true,
        ]);

        return $comercio;
    }

    public function test_escanear_el_qr_deja_la_mesa_en_la_sesion(): void
    {
        $comercio = $this->comercioConMenu();
        $mesa = Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 7]);

        $this->get(route('menu.public', $comercio).'?m='.$mesa->token)
            ->assertOk();

        $this->assertSame($mesa->id, session('mesa:'.$comercio->id.'.id'));
    }

    public function test_la_mesa_sobrevive_a_navegar_entre_las_pantallas_del_menu(): void
    {
        $comercio = $this->comercioConMenu();
        $mesa = Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 7]);

        // Escanea en la portada y después navega, ya sin el `?m=` en la URL.
        $this->get(route('menu.public', $comercio).'?m='.$mesa->token)->assertOk();
        $this->get(route('menu.chat', $comercio))->assertOk();
        $this->get(route('menu.classic', $comercio))->assertOk();

        $this->assertSame($mesa->id, session('mesa:'.$comercio->id.'.id'));
    }

    public function test_el_numero_de_mesa_se_muestra_en_la_pantalla_del_chat(): void
    {
        $comercio = $this->comercioConMenu(['pedido_habilitado' => true]);
        $mesa = Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 7]);

        // `lang=es` explícito: el entorno de tests resuelve el locale en inglés y el
        // rótulo saldría como "Table 7", que es correcto pero no es lo que se afirma acá.
        $this->get(route('menu.public', $comercio).'?m='.$mesa->token.'&lang=es');

        // Es lo que el cliente le muestra al mozo: el número tiene que estar a la vista.
        $this->get(route('menu.chat', $comercio))
            ->assertOk()
            ->assertSee('Mesa 7');
    }

    public function test_sin_mesa_la_pantalla_del_chat_no_habla_de_mesas(): void
    {
        $comercio = $this->comercioConMenu(['pedido_habilitado' => true]);

        // La clase del span, no la del CSS (que está siempre en la hoja de estilos).
        $this->get(route('menu.chat', $comercio))
            ->assertOk()
            ->assertDontSee('class="wish-mesa"', false)
            ->assertDontSee('class="mesa-chip', false);
    }

    public function test_un_token_inexistente_no_rompe_el_menu(): void
    {
        $comercio = $this->comercioConMenu();

        $this->get(route('menu.public', $comercio).'?m=basura99')
            ->assertOk();

        $this->assertNull(session('mesa:'.$comercio->id));
    }

    public function test_el_token_de_otro_comercio_se_ignora(): void
    {
        $comercio = $this->comercioConMenu();
        $otro = Comercio::factory()->create();
        $mesaAjena = Mesa::factory()->create(['comercio_id' => $otro->id, 'numero' => 3]);

        $this->get(route('menu.public', $comercio).'?m='.$mesaAjena->token)
            ->assertOk();

        $this->assertNull(session('mesa:'.$comercio->id));
    }

    public function test_una_mesa_desactivada_no_asigna_mesa(): void
    {
        $comercio = $this->comercioConMenu();
        $mesa = Mesa::factory()->inactiva()->create(['comercio_id' => $comercio->id, 'numero' => 9]);

        $this->get(route('menu.public', $comercio).'?m='.$mesa->token)
            ->assertOk();

        $this->assertNull(session('mesa:'.$comercio->id));
    }

    public function test_el_tracking_guarda_la_mesa_sin_que_el_front_la_mande(): void
    {
        $comercio = $this->comercioConMenu();
        $mesa = Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 7]);

        $this->get(route('menu.public', $comercio).'?m='.$mesa->token);
        $this->postJson(route('track', $comercio), [
            'event' => 'dish_want',
            'session_id' => 'session_abc',
            'data' => ['nombre' => 'Milanesa'],
        ])->assertNoContent();

        $this->assertDatabaseHas('tracking_events', [
            'comercio_id' => $comercio->id,
            'mesa_id' => $mesa->id,
            'event' => 'dish_want',
        ]);
    }

    public function test_el_carrito_queda_asociado_a_la_mesa(): void
    {
        $comercio = $this->comercioConMenu(['pedido_habilitado' => true]);
        $mesa = Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 4]);

        $this->get(route('menu.public', $comercio).'?m='.$mesa->token);
        $this->postJson(route('api.cart.incr'), [
            'comercio_id' => $comercio->id,
            'session_id' => 'session_abc',
            'nombre' => 'Milanesa',
            'delta' => 1,
            'price_text' => '$3.800',
        ])->assertOk();

        $this->assertDatabaseHas('carts', [
            'comercio_id' => $comercio->id,
            'session_id' => 'session_abc',
            'mesa_id' => $mesa->id,
        ]);
    }

    public function test_el_carrito_de_una_visita_anterior_se_reasigna_a_la_mesa_nueva(): void
    {
        $comercio = $this->comercioConMenu(['pedido_habilitado' => true]);
        $ayer = Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 2]);
        $hoy = Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 5]);

        // Visita de ayer, en la mesa 2.
        $this->get(route('menu.public', $comercio).'?m='.$ayer->token);
        $this->postJson(route('api.cart.incr'), [
            'comercio_id' => $comercio->id, 'session_id' => 'session_abc', 'nombre' => 'Milanesa',
        ])->assertOk();

        // Vuelve otro día y se sienta en la 5: el carrito es el mismo registro (misma
        // sesión del navegador), pero la actividad es de la mesa nueva.
        $this->get(route('menu.public', $comercio).'?m='.$hoy->token);
        $this->postJson(route('api.cart.incr'), [
            'comercio_id' => $comercio->id, 'session_id' => 'session_abc', 'nombre' => 'Flan',
        ])->assertOk();

        $this->assertDatabaseHas('carts', [
            'session_id' => 'session_abc',
            'mesa_id' => $hoy->id,
        ]);
    }

    public function test_sin_qr_de_mesa_todo_sigue_funcionando_como_antes(): void
    {
        $comercio = $this->comercioConMenu(['pedido_habilitado' => true]);

        $this->get(route('menu.public', $comercio))->assertOk();
        $this->postJson(route('track', $comercio), [
            'event' => 'page_view',
            'session_id' => 'session_abc',
            'data' => ['view' => 'choose'],
        ])->assertNoContent();

        $this->assertDatabaseHas('tracking_events', [
            'comercio_id' => $comercio->id,
            'mesa_id' => null,
            'event' => 'page_view',
        ]);
    }
}
