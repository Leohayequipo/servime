<?php

namespace Tests\Feature;

use App\Models\Comanda;
use App\Models\Comercio;
use App\Models\Menu;
use App\Models\Mesa;
use App\Models\Plan;
use App\Models\User;
use App\Services\ComandaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El cliente confirma su pedido desde el menú público.
 *
 * La prueba que sostiene toda la decisión de diseño es
 * `test_lo_que_no_confirmo_no_llega_a_la_comanda`: armar la lista tiene que seguir siendo
 * privado. Si eso se rompe, el mozo ve seis platos por cada uno que la mesa pide.
 */
class ComandaClienteTest extends TestCase
{
    use RefreshDatabase;

    // `lista_pedido` va siempre: sin la lista del cliente ("Lo quiero") no hay carrito que
    // mandar, y acá lo que se prueba es el paso siguiente, el pedido a la comanda.
    private function comercio(array $features = ['mesas', 'comandas', 'lista_pedido'], array $extra = []): Comercio
    {
        $plan = Plan::create([
            'key' => 'test-'.uniqid(),
            'nombre' => 'Test',
            'mensajes_ia_mes' => 1000,
            'modelo_ia' => 'gpt-4o-mini',
            'max_tokens_chat' => 600,
            'features' => $features,
            'activo' => true,
        ]);

        $comercio = Comercio::factory()->for(User::factory())->create(array_merge([
            'plan_id' => $plan->id,
            'pedido_habilitado' => true,
        ], $extra));

        Menu::factory()->create(['comercio_id' => $comercio->id, 'activo' => true, 'procesado' => true]);

        return $comercio;
    }

    private function sentarseEn(Mesa $mesa): void
    {
        $this->get(route('menu.public', $mesa->comercio).'?m='.$mesa->token);
    }

    private function agregarAlCarrito(Comercio $comercio, string $sessionId, string $nombre, ?string $precio = null): void
    {
        $this->postJson(route('api.cart.incr'), array_filter([
            'comercio_id' => $comercio->id,
            'session_id' => $sessionId,
            'nombre' => $nombre,
            'delta' => 1,
            'price_text' => $precio,
        ]))->assertOk();
    }

    private function pedir(Comercio $comercio, string $sessionId)
    {
        return $this->postJson(route('api.comanda.pedir'), [
            'comercio_id' => $comercio->id,
            'session_id' => $sessionId,
        ]);
    }

    public function test_lo_que_no_confirmo_no_llega_a_la_comanda(): void
    {
        $comercio = $this->comercio();
        $mesa = Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 7]);
        $this->sentarseEn($mesa);

        // El cliente curioseando: toca tres platos y no confirma nada.
        $this->agregarAlCarrito($comercio, 'tel-a', 'Milanesa');
        $this->agregarAlCarrito($comercio, 'tel-a', 'Ravioles');
        $this->agregarAlCarrito($comercio, 'tel-a', 'Pizza');

        // El mozo no tiene que ver absolutamente nada todavía.
        $this->assertSame(0, Comanda::count());
        $this->assertDatabaseCount('comanda_items', 0);
    }

    public function test_al_confirmar_el_pedido_entra_como_ronda(): void
    {
        $comercio = $this->comercio();
        $mesa = Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 7]);
        $this->sentarseEn($mesa);
        $this->agregarAlCarrito($comercio, 'tel-a', 'Milanesa', '$3.800');

        $respuesta = $this->pedir($comercio, 'tel-a');

        $respuesta->assertOk()->assertJson(['success' => true, 'ronda' => 1, 'mesa' => 7]);
        $this->assertDatabaseHas('comanda_items', ['nombre' => 'Milanesa', 'origen' => 'cliente', 'ronda' => 1]);
    }

    public function test_dos_telefonos_de_la_misma_mesa_arman_una_sola_comanda(): void
    {
        $comercio = $this->comercio();
        $mesa = Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 7]);

        $this->sentarseEn($mesa);
        $this->agregarAlCarrito($comercio, 'tel-a', 'Milanesa');
        $this->pedir($comercio, 'tel-a')->assertOk();

        // Otro comensal de la misma mesa, con su propio teléfono.
        $this->agregarAlCarrito($comercio, 'tel-b', 'Ravioles');
        $segundo = $this->pedir($comercio, 'tel-b');

        $segundo->assertOk()->assertJson(['ronda' => 2]);
        $this->assertSame(1, Comanda::count());
    }

    public function test_pedir_con_el_carrito_vacio_no_genera_una_ronda(): void
    {
        $comercio = $this->comercio();
        $mesa = Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 7]);
        $this->sentarseEn($mesa);

        $this->pedir($comercio, 'tel-a')->assertStatus(422);

        $this->assertDatabaseCount('comanda_items', 0);
    }

    public function test_sin_haber_escaneado_una_mesa_no_se_puede_pedir(): void
    {
        $comercio = $this->comercio();
        $this->agregarAlCarrito($comercio, 'tel-a', 'Milanesa');

        // Entró por el link de las redes: no hay mesa a la que mandar el pedido.
        $this->pedir($comercio, 'tel-a')->assertStatus(409);

        $this->assertSame(0, Comanda::count());
    }

    public function test_sin_la_feature_comandas_el_endpoint_esta_cerrado(): void
    {
        $comercio = $this->comercio(['mesas', 'lista_pedido']);
        $mesa = Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 7]);
        $this->sentarseEn($mesa);
        $this->agregarAlCarrito($comercio, 'tel-a', 'Milanesa');

        $this->pedir($comercio, 'tel-a')->assertStatus(403);
    }

    public function test_el_boton_pedir_solo_aparece_con_mesa_escaneada(): void
    {
        $comercio = $this->comercio();
        $mesa = Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 7]);

        // Sin escanear: la pantalla es exactamente la de siempre.
        $this->get(route('menu.chat', $comercio))->assertOk()->assertDontSee('id="btnPedir"', false);

        $this->sentarseEn($mesa);
        $this->get(route('menu.chat', $comercio))->assertOk()->assertSee('id="btnPedir"', false);
    }

    /**
     * El que entra por el QR público del local, por las redes o por un link compartido no
     * puede pedir (no hay mesa a la que mandar el pedido): la lista es para mostrársela al
     * camarero. No se lo manda a escanear "el QR de su mesa", porque hay locales que usan
     * sólo el QR público y ese QR no existe.
     */
    public function test_al_que_entra_por_el_qr_publico_se_le_dice_que_se_lo_muestre_al_camarero(): void
    {
        $comercio = $this->comercio();
        Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 1]);

        $this->get(route('menu.chat', $comercio).'?lang=es')
            ->assertOk()
            ->assertDontSee('id="btnPedir"', false)
            ->assertDontSee('Escaneá el código QR de tu mesa', false)
            ->assertSee('Cuando venga el camarer@, mostrale esta pantalla.', false);
    }

    public function test_el_comercio_sin_comandas_no_menciona_pedidos_por_telefono(): void
    {
        // Sin la feature tampoco hay botón: la lista es para mostrársela al camarero.
        $comercio = $this->comercio(['mesas', 'lista_pedido']);

        $this->get(route('menu.chat', $comercio).'?lang=es')
            ->assertOk()
            ->assertDontSee('id="btnPedir"', false)
            ->assertSee('Cuando venga el camarer@, mostrale esta pantalla.', false);
    }

    public function test_despues_de_pedir_el_carrito_queda_vacio(): void
    {
        $comercio = $this->comercio();
        $mesa = Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 7]);
        $this->sentarseEn($mesa);
        $this->agregarAlCarrito($comercio, 'tel-a', 'Milanesa');
        $this->pedir($comercio, 'tel-a')->assertOk();

        $carrito = $this->postJson(route('api.cart.show'), [
            'comercio_id' => $comercio->id,
            'session_id' => 'tel-a',
        ]);

        $carrito->assertOk()->assertJson(['items' => []]);
    }

    public function test_el_cliente_ve_lo_que_pidio_la_mesa_y_reconoce_lo_suyo(): void
    {
        $comercio = $this->comercio();
        $mesa = Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 7]);
        $this->sentarseEn($mesa);
        $this->agregarAlCarrito($comercio, 'tel-a', 'Milanesa');
        $this->pedir($comercio, 'tel-a')->assertOk();
        $this->agregarAlCarrito($comercio, 'tel-b', 'Ravioles');
        $this->pedir($comercio, 'tel-b')->assertOk();

        $respuesta = $this->postJson(route('api.comanda.mia'), [
            'comercio_id' => $comercio->id,
            'session_id' => 'tel-a',
        ]);

        // Ve las dos cosas (la mesa pide junta) pero sabe cuál pidió él.
        $respuesta->assertOk()
            ->assertJsonPath('pedido.0.nombre', 'Milanesa')
            ->assertJsonPath('pedido.0.mio', true)
            ->assertJsonPath('pedido.1.nombre', 'Ravioles')
            ->assertJsonPath('pedido.1.mio', false);
    }

    public function test_si_el_mozo_cerro_la_mesa_el_cliente_deja_de_ver_el_pedido(): void
    {
        $comercio = $this->comercio();
        $mesa = Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 7]);
        $this->sentarseEn($mesa);
        $this->agregarAlCarrito($comercio, 'tel-a', 'Milanesa');
        $this->pedir($comercio, 'tel-a')->assertOk();

        app(ComandaService::class)->cerrar(Comanda::first());

        // Sin esto, el que se queda con el teléfono abierto ve un pedido fantasma.
        $this->postJson(route('api.comanda.mia'), [
            'comercio_id' => $comercio->id,
            'session_id' => 'tel-a',
        ])->assertOk()->assertJson(['pedido' => []]);
    }

    public function test_el_item_agregado_por_el_mozo_se_ve_marcado_como_tal(): void
    {
        $comercio = $this->comercio();
        $mesa = Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 7]);
        $this->sentarseEn($mesa);
        $this->agregarAlCarrito($comercio, 'tel-a', 'Milanesa');
        $this->pedir($comercio, 'tel-a')->assertOk();

        app(ComandaService::class)->agregarDelMozo(Comanda::first(), 'Agua sin gas');

        $this->postJson(route('api.comanda.mia'), [
            'comercio_id' => $comercio->id,
            'session_id' => 'tel-a',
        ])->assertOk()
            ->assertJsonPath('pedido.1.nombre', 'Agua sin gas')
            ->assertJsonPath('pedido.1.del_mozo', true);
    }
}
