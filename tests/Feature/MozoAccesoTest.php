<?php

namespace Tests\Feature;

use App\Models\Comanda;
use App\Models\ComandaItem;
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

    /**
     * El mozo que abre a la mañana no tiene que ver el salón lleno de mesas de anoche.
     *
     * Antes esto dependía de que el cron del servidor hubiera corrido; ahora el tablero
     * limpia solo al dibujarse.
     */
    public function test_el_tablero_no_muestra_mesas_que_quedaron_abiertas_de_anoche(): void
    {
        $comercio = $this->comercioConMozo();
        $comanda = $this->comandaCon($comercio, 3);
        app(ComandaService::class)->agregarDelMozo($comanda, 'Milanesa');

        $comanda->update(['abierta_at' => now()->subHours(14)]);
        $comanda->items()->update(['created_at' => now()->subHours(14)]);
        $this->entrar($comercio);

        $this->getJson(route('mozo.estado', $comercio->mozo_token))
            ->assertOk()
            ->assertJsonPath('mesas.0.ocupada', false);

        $this->assertSame(Comanda::CERRADA, $comanda->fresh()->estado);
        $this->assertSame('auto', $comanda->fresh()->cerrada_por);
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

    /**
     * El caso más común del salón: se sientan, no tocan el teléfono y le dictan el pedido
     * al mozo. Sin esto, la pantalla sólo servía para las mesas que ya habían pedido
     * solas, que son justamente las que menos ayuda necesitan.
     */
    public function test_el_mozo_abre_una_mesa_sin_que_nadie_haya_pedido(): void
    {
        $comercio = $this->comercioConMozo();
        $mesa = Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 4]);
        $this->entrar($comercio);

        $this->assertSame(0, Comanda::count());

        $this->postJson(route('mozo.mesa.abrir', [$comercio->mozo_token, $mesa]))
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('mesas.0.ocupada', true);

        $this->assertSame(1, Comanda::where('mesa_id', $mesa->id)->abiertas()->count());
    }

    public function test_abrir_dos_veces_la_misma_mesa_no_duplica_la_comanda(): void
    {
        $comercio = $this->comercioConMozo();
        $mesa = Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 4]);
        $this->entrar($comercio);

        // Dos mozos tocando la misma mesa a la vez tienen que caer en la misma comanda.
        $primera = $this->postJson(route('mozo.mesa.abrir', [$comercio->mozo_token, $mesa]));
        $segunda = $this->postJson(route('mozo.mesa.abrir', [$comercio->mozo_token, $mesa]));

        $this->assertSame(
            $primera->json('comanda_id'),
            $segunda->json('comanda_id')
        );
        $this->assertSame(1, Comanda::count());
    }

    public function test_despues_de_abrirla_el_mozo_puede_cargarle_lo_que_le_dictaron(): void
    {
        $comercio = $this->comercioConMozo();
        $mesa = Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 4]);
        $this->entrar($comercio);

        $comandaId = $this->postJson(route('mozo.mesa.abrir', [$comercio->mozo_token, $mesa]))
            ->json('comanda_id');

        $this->postJson(route('mozo.item.agregar', [$comercio->mozo_token, $comandaId]), [
            'nombre' => 'Milanesa napolitana',
            'qty' => 2,
            'precio' => '$3.800',
        ])->assertOk();

        $this->assertDatabaseHas('comanda_items', [
            'comanda_id' => $comandaId,
            'nombre' => 'Milanesa napolitana',
            'qty' => 2,
            'origen' => 'mozo',
            'ronda' => 1,
        ]);
    }

    public function test_no_se_puede_abrir_una_mesa_de_otro_local(): void
    {
        $mio = $this->comercioConMozo();
        $ajeno = $this->comercioConMozo();
        $mesaAjena = Mesa::factory()->create(['comercio_id' => $ajeno->id, 'numero' => 9]);
        $this->entrar($mio);

        $this->postJson(route('mozo.mesa.abrir', [$mio->mozo_token, $mesaAjena]))
            ->assertNotFound();

        $this->assertSame(0, Comanda::count());
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

    /**
     * La pantalla necesita saber CUÁL ítem entró para poder destacarlo.
     *
     * Sin esa señal el mozo no sabe si su toque hizo algo, lo repite, y el plato entra
     * dos veces — se descubre recién cuando llega el pedido a la mesa.
     */
    public function test_agregar_devuelve_el_item_para_poder_señalarlo(): void
    {
        $comercio = $this->comercioConMozo();
        $comanda = $this->comandaCon($comercio);
        $this->entrar($comercio);

        $respuesta = $this->postJson(route('mozo.item.agregar', [$comercio->mozo_token, $comanda]), [
            'nombre' => 'Café con leche',
            'qty' => 1,
        ]);

        $respuesta->assertOk();
        $this->assertSame(
            ComandaItem::where('comanda_id', $comanda->id)->value('id'),
            $respuesta->json('item_id')
        );
    }

    /**
     * Lo último que entró va arriba: es lo que el mozo necesita ver, y al final de la
     * lista es donde el ojo llega último.
     */
    public function test_lo_ultimo_que_entro_va_primero(): void
    {
        $comercio = $this->comercioConMozo();
        $comanda = $this->comandaCon($comercio);
        $servicio = app(ComandaService::class);
        $this->entrar($comercio);

        $servicio->agregarDelMozo($comanda, 'Milanesa');   // ronda 1
        $servicio->agregarDelMozo($comanda, 'Flan');       // ronda 2
        $servicio->agregarDelMozo($comanda, 'Café');       // ronda 3

        $rondas = $this->getJson(route('mozo.estado', $comercio->mozo_token))
            ->assertOk()
            ->json('mesas.0.rondas');

        $this->assertSame([3, 2, 1], array_column($rondas, 'ronda'));
        $this->assertSame('Café', $rondas[0]['items'][0]['nombre']);
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

        // Sale de la comanda, pero queda en el historial: el mozo puede haber tocado la
        // fila equivocada, o la mesa puede reclamar después.
        $this->assertSame(0, $comanda->items()->count());
        $this->assertNotNull(ComandaItem::withTrashed()->find($item->id)->deleted_at);
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

    /**
     * El botón "Cerrar mesa" está a un toque en el tablero: cerrar la equivocada va a
     * pasar. Sin deshacer, había que abrir una comanda nueva y recargar todo a mano.
     */
    public function test_se_puede_deshacer_el_cierre_de_una_mesa_recien_cerrada(): void
    {
        $comercio = $this->comercioConMozo();
        $comanda = $this->comandaCon($comercio);
        app(ComandaService::class)->agregarDelMozo($comanda, 'Milanesa', 1, '$3.800');
        app(ComandaService::class)->cerrar($comanda);
        $this->entrar($comercio);

        $this->postJson(route('mozo.comanda.reabrir', [$comercio->mozo_token, $comanda]))
            ->assertOk()
            ->assertJsonPath('mesas.0.ocupada', true);

        // La MISMA comanda, con sus ítems: si abriera una nueva, el grupo quedaría
        // partido en dos registros y ni el total ni los tiempos servirían.
        $comanda = $comanda->fresh();
        $this->assertSame(Comanda::ABIERTA, $comanda->estado);
        $this->assertNull($comanda->cerrada_at);
        $this->assertSame(1, $comanda->items()->count());
        $this->assertSame(1, Comanda::count());
    }

    public function test_pasada_la_ventana_ya_no_se_puede_reabrir(): void
    {
        $comercio = $this->comercioConMozo();
        $comanda = $this->comandaCon($comercio);
        app(ComandaService::class)->cerrar($comanda);
        // Ya no es un error del mozo: es un grupo que se fue hace rato.
        $comanda->update(['cerrada_at' => now()->subMinutes(ComandaService::MINUTOS_PARA_REABRIR + 5)]);
        $this->entrar($comercio);

        $this->postJson(route('mozo.comanda.reabrir', [$comercio->mozo_token, $comanda]))
            ->assertStatus(409);

        $this->assertSame(Comanda::CERRADA, $comanda->fresh()->estado);
    }

    public function test_no_se_reabre_si_ya_hay_gente_nueva_en_esa_mesa(): void
    {
        $comercio = $this->comercioConMozo();
        $vieja = $this->comandaCon($comercio);
        $mesa = $vieja->mesa;
        app(ComandaService::class)->cerrar($vieja);

        // Se sentó otro grupo antes de que el mozo tocara "Reabrir".
        $nueva = app(ComandaService::class)->abrirOTomar($mesa);
        $this->entrar($comercio);

        $this->postJson(route('mozo.comanda.reabrir', [$comercio->mozo_token, $vieja]))
            ->assertStatus(409);

        $this->assertSame(Comanda::CERRADA, $vieja->fresh()->estado);
        $this->assertSame(Comanda::ABIERTA, $nueva->fresh()->estado);
    }

    public function test_la_mesa_libre_avisa_que_se_puede_reabrir(): void
    {
        $comercio = $this->comercioConMozo();
        $comanda = $this->comandaCon($comercio);
        app(ComandaService::class)->cerrar($comanda);
        $this->entrar($comercio);

        $this->getJson(route('mozo.estado', $comercio->mozo_token))
            ->assertOk()
            ->assertJsonPath('mesas.0.ocupada', false)
            ->assertJsonPath('mesas.0.reabrir_id', $comanda->id);
    }

    public function test_las_mesas_cerradas_del_dia_se_pueden_consultar(): void
    {
        $comercio = $this->comercioConMozo();
        $comanda = $this->comandaCon($comercio, 5);
        app(ComandaService::class)->agregarDelMozo($comanda, 'Milanesa', 2, '$3.800');
        app(ComandaService::class)->cerrar($comanda);
        $this->entrar($comercio);

        $this->getJson(route('mozo.cerradas', $comercio->mozo_token))
            ->assertOk()
            ->assertJsonPath('comandas.0.numero', 5)
            ->assertJsonPath('comandas.0.items_total', 2)
            ->assertJsonPath('comandas.0.cerrada_por', 'mozo')
            // Con el detalle de lo que pidieron: es para responder un reclamo.
            ->assertJsonPath('comandas.0.rondas.0.items.0.nombre', 'Milanesa');
    }

    public function test_las_cerradas_de_un_local_no_aparecen_en_el_de_al_lado(): void
    {
        $mio = $this->comercioConMozo();
        $ajeno = $this->comercioConMozo();
        app(ComandaService::class)->cerrar($this->comandaCon($ajeno, 9));
        $this->entrar($mio);

        $this->getJson(route('mozo.cerradas', $mio->mozo_token))
            ->assertOk()
            ->assertJsonCount(0, 'comandas');
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
