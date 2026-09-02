<?php

namespace Tests\Feature;

use App\Models\Comanda;
use App\Models\Comercio;
use App\Models\Mesa;
use App\Models\Plan;
use App\Models\User;
use App\Services\ComandaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * La pantalla del mozo: quién entra y qué puede tocar.
 *
 * Es la parte más expuesta de la feature: son rutas públicas, sin el login de Laravel,
 * que muestran y modifican los pedidos de un local en vivo. Lo que se prueba acá es que
 * el link solo no alcance, que el PIN tenga freno, y que un link válido no sirva para
 * meterse en otro comercio.
 */
class MozoAccesoTest extends TestCase
{
    use RefreshDatabase;

    private function comercioConMozo(string $pin = '1234', array $features = ['mesas', 'comandas']): Comercio
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

        $comercio = Comercio::factory()->for(User::factory())->create([
            'plan_id' => $plan->id,
            'pedido_habilitado' => true,
        ]);

        $comercio->activarAccesoMozo($pin);

        return $comercio->fresh();
    }

    private function entrar(Comercio $comercio, string $pin = '1234'): void
    {
        $this->post(route('mozo.pin.validar', $comercio->mozo_token), ['pin' => $pin]);
    }

    private function comandaCon(Comercio $comercio, int $numeroMesa = 7): Comanda
    {
        $mesa = Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => $numeroMesa]);

        return app(ComandaService::class)->abrirOTomar($mesa);
    }

    public function test_el_link_solo_no_alcanza_hay_que_poner_el_pin(): void
    {
        $comercio = $this->comercioConMozo();

        $this->get(route('mozo.tablero', $comercio->mozo_token))
            ->assertRedirect(route('mozo.pin', $comercio->mozo_token));
    }

    public function test_con_el_pin_correcto_se_entra_al_tablero(): void
    {
        $comercio = $this->comercioConMozo();
        $this->comandaCon($comercio);

        $this->post(route('mozo.pin.validar', $comercio->mozo_token), ['pin' => '1234'])
            ->assertRedirect(route('mozo.tablero', $comercio->mozo_token));

        $this->get(route('mozo.tablero', $comercio->mozo_token))
            ->assertOk()
            ->assertSee($comercio->nombre);
    }

    public function test_el_pin_incorrecto_no_entra(): void
    {
        $comercio = $this->comercioConMozo();

        $this->post(route('mozo.pin.validar', $comercio->mozo_token), ['pin' => '9999'])
            ->assertSessionHasErrors('pin');

        $this->get(route('mozo.tablero', $comercio->mozo_token))->assertRedirect();
    }

    public function test_el_pin_se_bloquea_despues_de_varios_intentos(): void
    {
        $comercio = $this->comercioConMozo();

        // Cuatro dígitos son 10.000 combinaciones: sin freno se prueban todas en minutos.
        for ($i = 0; $i < 8; $i++) {
            $this->post(route('mozo.pin.validar', $comercio->mozo_token), ['pin' => '0000']);
        }

        // Ahora ni siquiera el PIN correcto pasa.
        $this->post(route('mozo.pin.validar', $comercio->mozo_token), ['pin' => '1234'])
            ->assertSessionHasErrors('pin');

        $this->get(route('mozo.tablero', $comercio->mozo_token))->assertRedirect();

        RateLimiter::clear('mozo-pin:'.$comercio->id.':127.0.0.1');
    }

    public function test_un_token_inventado_da_404_y_no_confirma_nada(): void
    {
        $this->comercioConMozo();

        // 404 y no 403: un 403 le confirmaría a quien prueba links que ese existe.
        $this->get(route('mozo.pin', 'token-inventado'))->assertNotFound();
        $this->get(route('mozo.tablero', 'token-inventado'))->assertNotFound();
    }

    public function test_rotar_el_link_deja_afuera_a_las_tablets_abiertas(): void
    {
        $comercio = $this->comercioConMozo();
        $this->entrar($comercio);
        $tokenViejo = $comercio->mozo_token;

        $comercio->rotarTokenMozo();

        // El link viejo deja de existir y la sesión de esa tablet ya no vale.
        $this->get(route('mozo.tablero', $tokenViejo))->assertNotFound();
        $this->get(route('mozo.tablero', $comercio->fresh()->mozo_token))->assertRedirect();
    }

    public function test_cambiar_el_pin_no_invalida_el_link(): void
    {
        $comercio = $this->comercioConMozo();
        $token = $comercio->mozo_token;

        $comercio->activarAccesoMozo('5678');

        $this->assertSame($token, $comercio->fresh()->mozo_token);
        $this->post(route('mozo.pin.validar', $token), ['pin' => '5678'])->assertRedirect();
    }

    public function test_sin_la_feature_comandas_la_pantalla_se_apaga(): void
    {
        $comercio = $this->comercioConMozo('1234', ['mesas']);

        $this->get(route('mozo.pin', $comercio->mozo_token))->assertForbidden();
    }

    public function test_el_tablero_muestra_todas_las_mesas_no_solo_las_ocupadas(): void
    {
        $comercio = $this->comercioConMozo();
        $this->comandaCon($comercio, 7);
        Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 8]);
        $this->entrar($comercio);

        // El mozo mira el salón, no una lista de pedidos: la mesa libre tiene que estar
        // en su lugar de siempre.
        $respuesta = $this->getJson(route('mozo.estado', $comercio->mozo_token));

        $respuesta->assertOk()
            ->assertJsonPath('mesas.0.numero', 7)
            ->assertJsonPath('mesas.0.ocupada', true)
            ->assertJsonPath('mesas.1.numero', 8)
            ->assertJsonPath('mesas.1.ocupada', false);
    }

    public function test_el_mozo_agrega_un_item_a_mano(): void
    {
        $comercio = $this->comercioConMozo();
        $comanda = $this->comandaCon($comercio);
        $this->entrar($comercio);

        $this->postJson(route('mozo.item.agregar', [$comercio->mozo_token, $comanda]), [
            'nombre' => 'Agua sin gas',
            'qty' => 2,
            'precio' => '$1.200',
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('comanda_items', [
            'comanda_id' => $comanda->id,
            'nombre' => 'Agua sin gas',
            'qty' => 2,
            'origen' => 'mozo',
        ]);
    }

    public function test_el_mozo_corrige_la_cantidad_y_borra_un_item(): void
    {
        $comercio = $this->comercioConMozo();
        $comanda = $this->comandaCon($comercio);
        $item = app(ComandaService::class)->agregarDelMozo($comanda, 'Milanesa', 1, '$3.800');
        $this->entrar($comercio);

        $this->patchJson(route('mozo.item.cantidad', [$comercio->mozo_token, $comanda, $item]), ['qty' => 3])
            ->assertOk();
        $this->assertSame(3, $item->fresh()->qty);

        $this->deleteJson(route('mozo.item.borrar', [$comercio->mozo_token, $comanda, $item]))
            ->assertOk();
        $this->assertDatabaseMissing('comanda_items', ['id' => $item->id]);
    }

    public function test_cerrar_la_mesa_la_libera_sin_borrar_lo_pedido(): void
    {
        $comercio = $this->comercioConMozo();
        $comanda = $this->comandaCon($comercio);
        app(ComandaService::class)->agregarDelMozo($comanda, 'Milanesa', 1, '$3.800');
        $this->entrar($comercio);

        $this->postJson(route('mozo.comanda.cerrar', [$comercio->mozo_token, $comanda]))
            ->assertOk()
            ->assertJsonPath('mesas.0.ocupada', false);

        $this->assertSame(Comanda::CERRADA, $comanda->fresh()->estado);
        $this->assertDatabaseHas('comanda_items', ['comanda_id' => $comanda->id, 'nombre' => 'Milanesa']);
    }

    public function test_un_link_de_mozo_no_sirve_para_tocar_las_comandas_de_otro_local(): void
    {
        $mio = $this->comercioConMozo();
        $ajeno = $this->comercioConMozo();
        $comandaAjena = $this->comandaCon($ajeno, 3);
        $this->entrar($mio);

        // Link válido + id de una comanda de otro comercio: el caso que hay que cerrar.
        $this->postJson(route('mozo.comanda.cerrar', [$mio->mozo_token, $comandaAjena]))
            ->assertNotFound();

        $this->assertSame(Comanda::ABIERTA, $comandaAjena->fresh()->estado);
    }

    public function test_salir_pide_el_pin_de_nuevo(): void
    {
        $comercio = $this->comercioConMozo();
        $this->entrar($comercio);

        $this->post(route('mozo.salir', $comercio->mozo_token))
            ->assertRedirect(route('mozo.pin', $comercio->mozo_token));

        $this->get(route('mozo.tablero', $comercio->mozo_token))->assertRedirect();
    }

    public function test_las_apis_del_mozo_responden_401_cuando_vence_la_sesion(): void
    {
        $comercio = $this->comercioConMozo();
        $comanda = $this->comandaCon($comercio);

        // Sin PIN validado: el JS de la tablet usa el 401 para volver a la pantalla del PIN
        // en vez de quedarse mostrando datos viejos.
        $this->postJson(route('mozo.comanda.cerrar', [$comercio->mozo_token, $comanda]))
            ->assertStatus(401);
    }

    public function test_el_hash_del_pin_no_sale_en_las_respuestas(): void
    {
        $comercio = $this->comercioConMozo();

        $this->assertArrayNotHasKey('mozo_pin_hash', $comercio->toArray());
    }
}
