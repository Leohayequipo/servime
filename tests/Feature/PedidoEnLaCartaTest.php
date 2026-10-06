<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Menu;
use App\Models\Mesa;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Lo quiero", "Mi pedido" y "Pedir" en la carta, según el plan y la mesa.
 */
class PedidoEnLaCartaTest extends TestCase
{
    use RefreshDatabase;

    private function comercioEn(string $key, array $extra = []): Comercio
    {
        $user = User::factory()->create(['role' => 'user']);
        $comercio = Comercio::factory()->for($user)->create($extra + [
            'plan_id' => Plan::where('key', $key)->value('id'),
            'enable_ai_responses' => true,
            'pedido_habilitado' => true,
            'menu_clasico_vista' => Comercio::VISTA_LISTA,
        ]);

        Menu::factory()->for($comercio)->create([
            'activo' => true,
            'estado' => Menu::ESTADO_LISTO,
            'contexto_gpt' => json_encode(['categorias' => [
                ['nombre' => 'Para comer', 'platos' => [['nombre' => 'Pancho', 'precio' => '$3.000']]],
                ['nombre' => 'Bebidas', 'platos' => [['nombre' => 'Coca 500', 'precio' => '$2.500']]],
            ]]),
        ]);

        return $comercio;
    }

    public function test_en_el_free_se_arma_la_lista_para_mostrarsela_al_mozo(): void
    {
        $comercio = $this->comercioEn('free');

        $this->get(route('menu.classic', $comercio))
            ->assertOk()
            ->assertSee('class="btn-quiero"', false)
            ->assertSee('id="fabPedido"', false)
            ->assertSee('mostrale esta pantalla')
            // Sin mesas no hay a quién mandarle el pedido.
            ->assertDontSee('id="btnPedir"', false);
    }

    public function test_el_minimo_con_qr_de_mesa_puede_pedir(): void
    {
        $comercio = $this->comercioEn('minimo');
        $mesa = Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 4]);

        // Escanea el QR de la mesa: la mesa queda en la sesión.
        $this->get(route('menu.public', $comercio).'?m='.$mesa->token);

        $this->get(route('menu.classic', $comercio))
            ->assertOk()
            ->assertSee('id="btnPedir"', false);
    }

    public function test_el_minimo_sin_qr_de_mesa_avisa_que_hay_que_escanearlo(): void
    {
        $comercio = $this->comercioEn('minimo');

        // En castellano: sin idioma, el menú elige por el navegador y ese texto ya tenía
        // traducción al inglés.
        $this->get(route('menu.classic', $comercio).'?lang=es')
            ->assertOk()
            ->assertDontSee('id="btnPedir"', false)
            ->assertSee('Escaneá el código QR de tu mesa');
    }

    public function test_el_pedido_de_la_carta_le_llega_al_mozo(): void
    {
        $comercio = $this->comercioEn('minimo');
        $mesa = Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 4]);
        $this->get(route('menu.public', $comercio).'?m='.$mesa->token);

        // Lo que hace "Lo quiero" y después "Pedir", con los mismos endpoints del chat.
        $this->postJson(route('api.cart.incr'), [
            'comercio_id' => $comercio->id, 'session_id' => 'sess_carta', 'nombre' => 'Pancho', 'delta' => 1, 'price_text' => '$3.000',
        ])->assertOk();

        $this->postJson('/api/comanda/pedir', ['comercio_id' => $comercio->id, 'session_id' => 'sess_carta'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('mesa', 4);
    }

    public function test_si_el_plan_no_trae_la_lista_no_hay_lo_quiero_ni_carrito(): void
    {
        $comercio = $this->comercioEn('free');
        $plan = $comercio->planEfectivo();
        $plan->update(['features' => array_values(array_diff($plan->features, ['lista_pedido']))]);

        $this->get(route('menu.classic', $comercio))
            ->assertOk()
            ->assertDontSee('class="btn-quiero"', false);

        $this->postJson(route('api.cart.incr'), [
            'comercio_id' => $comercio->id, 'session_id' => 's', 'nombre' => 'Pancho', 'delta' => 1,
        ])->assertForbidden();
    }

    public function test_en_el_chat_el_boton_pedido_depende_de_la_casilla_del_plan(): void
    {
        // Básico trae asistente, así que el menú tiene chat.
        $comercio = $this->comercioEn('basico');

        $this->get(route('menu.chat', $comercio))
            ->assertOk()
            ->assertSee('id="fabWish"', false)
            ->assertSee('id="wishPanel"', false);

        // Sin la casilla, el interruptor del comercio prendido no alcanza: antes mostraba
        // el botón "Pedido" con una lista a la que no se podía sumar nada.
        $plan = $comercio->planEfectivo();
        $plan->update(['features' => array_values(array_diff($plan->features, ['lista_pedido']))]);

        $this->get(route('menu.chat', $comercio->fresh()))
            ->assertOk()
            ->assertDontSee('id="fabWish"', false)
            ->assertDontSee('id="wishPanel"', false);
    }

    public function test_el_panel_no_ofrece_el_interruptor_si_el_plan_no_trae_la_lista(): void
    {
        $comercio = $this->comercioEn('free');
        $menu = $comercio->menus()->first();

        $this->actingAs($comercio->user)->get(route('menus.edit', [$comercio, $menu]))
            ->assertOk()
            ->assertSee('id="pedido-card"', false);

        $plan = $comercio->planEfectivo();
        $plan->update(['features' => array_values(array_diff($plan->features, ['lista_pedido']))]);

        $this->actingAs($comercio->user)->get(route('menus.edit', [$comercio, $menu]))
            ->assertOk()
            ->assertDontSee('id="pedido-card"', false);
    }

    public function test_mirar_un_plato_y_quererlo_se_registran_distinto(): void
    {
        $comercio = $this->comercioEn('free');

        // Antes, abrir el plato se guardaba como "Lo quiero" y el panel los mezclaba.
        $this->postJson(route('track', $comercio), ['event' => 'dish_view', 'session_id' => 's', 'data' => ['nombre' => 'Pancho']])
            ->assertNoContent();

        $this->assertDatabaseHas('tracking_events', ['comercio_id' => $comercio->id, 'event' => 'dish_view']);
    }
}
