<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Comanda;
use App\Models\ComandaItem;
use App\Models\Comercio;
use App\Models\Mesa;
use App\Services\ComandaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La comanda de la mesa: junta lo que piden todos los teléfonos del grupo.
 *
 * Lo central que se prueba: que dos teléfonos de la misma mesa caigan en la MISMA
 * comanda (si no, el mozo ve el pedido partido en dos), y que cerrar no borre nada.
 */
class ComandaTest extends TestCase
{
    use RefreshDatabase;

    private function servicio(): ComandaService
    {
        return app(ComandaService::class);
    }

    private function carritoCon(Comercio $comercio, string $sessionId, array $items): Cart
    {
        $cart = Cart::create([
            'comercio_id' => $comercio->id,
            'session_id' => $sessionId,
            'total_centavos' => 0,
        ]);

        foreach ($items as $nombre => $datos) {
            CartItem::create([
                'cart_id' => $cart->id,
                'nombre' => $nombre,
                'qty' => $datos['qty'] ?? 1,
                'price_centavos' => $datos['centavos'] ?? null,
                'price_text' => $datos['texto'] ?? null,
            ]);
        }

        return $cart->load('items');
    }

    private function mesa(?Comercio $comercio = null, int $numero = 7): Mesa
    {
        $comercio ??= Comercio::factory()->create();

        return Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => $numero]);
    }

    public function test_pedir_pasa_el_carrito_a_una_ronda_de_la_comanda(): void
    {
        $mesa = $this->mesa();
        $cart = $this->carritoCon($mesa->comercio, 'tel-a', [
            'Milanesa' => ['qty' => 2, 'centavos' => 380000, 'texto' => '$3.800'],
            'Papas' => ['qty' => 1, 'centavos' => 150000],
        ]);

        $resultado = $this->servicio()->enviarCarrito($cart, $mesa);

        $this->assertSame(1, $resultado['ronda']);
        $this->assertSame(2, $resultado['items']);
        $this->assertSame(2, $resultado['comanda']->items()->count());
        $this->assertSame(910000, $resultado['comanda']->fresh()->total_centavos);
    }

    public function test_el_carrito_queda_vacio_para_que_no_se_duplique_el_pedido(): void
    {
        $mesa = $this->mesa();
        $cart = $this->carritoCon($mesa->comercio, 'tel-a', ['Milanesa' => ['qty' => 1]]);

        $this->servicio()->enviarCarrito($cart, $mesa);

        // Si el carrito quedara cargado, tocar "Pedir" otra vez mandaría todo de nuevo.
        $this->assertSame(0, $cart->fresh()->items()->count());
        $this->assertSame(0, $cart->fresh()->total_centavos);
    }

    public function test_dos_telefonos_de_la_misma_mesa_caen_en_la_misma_comanda(): void
    {
        $mesa = $this->mesa();
        $unoDeLaMesa = $this->carritoCon($mesa->comercio, 'tel-a', ['Milanesa' => ['qty' => 1]]);
        $otroDeLaMesa = $this->carritoCon($mesa->comercio, 'tel-b', ['Ravioles' => ['qty' => 1]]);

        $primero = $this->servicio()->enviarCarrito($unoDeLaMesa, $mesa);
        $segundo = $this->servicio()->enviarCarrito($otroDeLaMesa, $mesa);

        // Una sola comanda, dos rondas: es todo el punto de la feature.
        $this->assertSame($primero['comanda']->id, $segundo['comanda']->id);
        $this->assertSame(1, Comanda::count());
        $this->assertSame(1, $primero['ronda']);
        $this->assertSame(2, $segundo['ronda']);
    }

    public function test_cada_item_recuerda_de_que_telefono_salio(): void
    {
        $mesa = $this->mesa();
        $this->servicio()->enviarCarrito(
            $this->carritoCon($mesa->comercio, 'tel-a', ['Milanesa' => ['qty' => 1]]),
            $mesa
        );
        $this->servicio()->enviarCarrito(
            $this->carritoCon($mesa->comercio, 'tel-b', ['Ravioles' => ['qty' => 1]]),
            $mesa
        );

        $this->assertSame('tel-a', ComandaItem::where('nombre', 'Milanesa')->value('session_id'));
        $this->assertSame('tel-b', ComandaItem::where('nombre', 'Ravioles')->value('session_id'));
    }

    public function test_una_mesa_no_puede_tener_dos_comandas_abiertas(): void
    {
        $mesa = $this->mesa();

        $primera = $this->servicio()->abrirOTomar($mesa);
        $segunda = $this->servicio()->abrirOTomar($mesa);

        $this->assertSame($primera->id, $segunda->id);
        $this->assertSame(1, Comanda::where('mesa_id', $mesa->id)->abiertas()->count());
    }

    public function test_el_indice_unico_impide_una_segunda_comanda_abierta_aunque_se_fuerce(): void
    {
        $mesa = $this->mesa();
        $this->servicio()->abrirOTomar($mesa);

        // Simula la carrera que el lock no llegaría a frenar: dos inserts a la vez.
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        Comanda::create([
            'comercio_id' => $mesa->comercio_id,
            'mesa_id' => $mesa->id,
            'estado' => Comanda::ABIERTA,
            'abierta_at' => now(),
            'mesa_abierta_id' => $mesa->id,
        ]);
    }

    public function test_el_mozo_agrega_un_item_a_mano_como_ronda_nueva(): void
    {
        $mesa = $this->mesa();
        $comanda = $this->servicio()->enviarCarrito(
            $this->carritoCon($mesa->comercio, 'tel-a', ['Milanesa' => ['qty' => 1, 'centavos' => 380000]]),
            $mesa
        )['comanda'];

        $item = $this->servicio()->agregarDelMozo($comanda, 'Agua sin gas', 1, '$1.200', 'Sin hielo');

        $this->assertSame(2, $item->ronda);
        $this->assertSame(ComandaItem::ORIGEN_MOZO, $item->origen);
        $this->assertSame(120000, $item->price_centavos);
        $this->assertSame('Sin hielo', $item->nota);
        $this->assertSame(500000, $comanda->fresh()->total_centavos);
    }

    public function test_cambiar_la_cantidad_recalcula_el_total(): void
    {
        $mesa = $this->mesa();
        $comanda = $this->servicio()->enviarCarrito(
            $this->carritoCon($mesa->comercio, 'tel-a', ['Milanesa' => ['qty' => 1, 'centavos' => 380000]]),
            $mesa
        )['comanda'];
        $item = $comanda->items()->first();

        $this->servicio()->cambiarCantidad($item, 3);

        $this->assertSame(3, $item->fresh()->qty);
        $this->assertSame(1140000, $comanda->fresh()->total_centavos);
    }

    public function test_poner_la_cantidad_en_cero_borra_el_item(): void
    {
        $mesa = $this->mesa();
        $comanda = $this->servicio()->enviarCarrito(
            $this->carritoCon($mesa->comercio, 'tel-a', ['Milanesa' => ['qty' => 1, 'centavos' => 380000]]),
            $mesa
        )['comanda'];

        $this->servicio()->cambiarCantidad($comanda->items()->first(), 0);

        $this->assertSame(0, $comanda->items()->count());
        $this->assertSame(0, $comanda->fresh()->total_centavos);
    }

    public function test_lo_que_el_mozo_borra_queda_en_el_historial(): void
    {
        $mesa = $this->mesa();
        $comanda = $this->servicio()->enviarCarrito(
            $this->carritoCon($mesa->comercio, 'tel-a', ['Milanesa' => ['qty' => 1, 'centavos' => 380000]]),
            $mesa
        )['comanda'];
        $item = $comanda->items()->first();

        $this->servicio()->borrarItem($item);

        // Fuera de la comanda y del total...
        $this->assertSame(0, $comanda->items()->count());
        $this->assertSame(0, $comanda->fresh()->total_centavos);
        // ...pero no perdido: sirve para resolver un "nosotros pedimos dos" o para
        // deshacer un toque en la fila equivocada.
        $this->assertDatabaseHas('comanda_items', ['id' => $item->id]);
        $this->assertNotNull(ComandaItem::withTrashed()->find($item->id)->deleted_at);
    }

    /**
     * `comanda_items` no tiene `comercio_id` propio: cuelga de `comandas`. Sin pasar por
     * ahí, el histórico de un local muestra lo borrado en el de al lado.
     */
    public function test_el_historial_de_borrados_no_mezcla_comercios(): void
    {
        $mio = Comercio::factory()->create();
        $ajeno = Comercio::factory()->create();

        foreach ([[$mio, 'Milanesa mía'], [$ajeno, 'Milanesa ajena']] as [$comercio, $plato]) {
            $mesa = $this->mesa($comercio, 1);
            $comanda = $this->servicio()->enviarCarrito(
                $this->carritoCon($comercio, 'tel-'.$comercio->id, [$plato => ['qty' => 1]]),
                $mesa
            )['comanda'];
            $this->servicio()->borrarItem($comanda->items()->first());
        }

        $borrados = ComandaItem::borradosDe($mio)->get();

        $this->assertCount(1, $borrados);
        $this->assertSame('Milanesa mía', $borrados->first()->nombre);
    }

    public function test_el_historial_no_trae_lo_que_sigue_pedido(): void
    {
        $mesa = $this->mesa();
        $comanda = $this->servicio()->abrirOTomar($mesa);
        $this->servicio()->agregarDelMozo($comanda, 'Milanesa');
        $borrado = $this->servicio()->agregarDelMozo($comanda, 'Flan');

        $this->servicio()->borrarItem($borrado);

        $borrados = ComandaItem::borradosDe($mesa->comercio)->get();

        $this->assertCount(1, $borrados);
        $this->assertSame('Flan', $borrados->first()->nombre);
    }

    public function test_poner_la_cantidad_en_cero_tambien_deja_rastro(): void
    {
        $mesa = $this->mesa();
        $comanda = $this->servicio()->abrirOTomar($mesa);
        $item = $this->servicio()->agregarDelMozo($comanda, 'Milanesa');

        // Bajar a cero borra el ítem: tiene que dejar el mismo rastro que la ×.
        $this->servicio()->cambiarCantidad($item, 0);

        $this->assertSame(1, ComandaItem::borradosDe($mesa->comercio)->count());
    }

    public function test_cerrar_la_mesa_no_borra_lo_que_se_pidio(): void
    {
        $mesa = $this->mesa();
        $comanda = $this->servicio()->enviarCarrito(
            $this->carritoCon($mesa->comercio, 'tel-a', ['Milanesa' => ['qty' => 2, 'centavos' => 380000]]),
            $mesa
        )['comanda'];

        $this->servicio()->cerrar($comanda);

        $comanda = $comanda->fresh();
        $this->assertSame(Comanda::CERRADA, $comanda->estado);
        $this->assertNotNull($comanda->cerrada_at);
        // El histórico se conserva: es lo que sostiene las métricas y cualquier reclamo.
        $this->assertSame(1, $comanda->items()->count());
        $this->assertSame(760000, $comanda->total_centavos);
    }

    public function test_despues_de_cerrar_la_mesa_queda_libre_para_el_proximo_grupo(): void
    {
        $mesa = $this->mesa();
        $primera = $this->servicio()->abrirOTomar($mesa);

        $this->servicio()->cerrar($primera);
        $segunda = $this->servicio()->abrirOTomar($mesa);

        $this->assertNotSame($primera->id, $segunda->id);
        $this->assertSame(2, Comanda::where('mesa_id', $mesa->id)->count());
        $this->assertSame(1, Comanda::where('mesa_id', $mesa->id)->abiertas()->count());
    }

    public function test_cerrar_dos_veces_no_rompe_ni_pisa_la_fecha(): void
    {
        $mesa = $this->mesa();
        $comanda = $this->servicio()->abrirOTomar($mesa);

        $this->servicio()->cerrar($comanda);
        $cerradaAt = $comanda->fresh()->cerrada_at;
        $this->servicio()->cerrar($comanda->fresh(), 'auto');

        $this->assertSame('mozo', $comanda->fresh()->cerrada_por);
        $this->assertEquals($cerradaAt, $comanda->fresh()->cerrada_at);
    }

    /**
     * La defensa que reemplaza al cron.
     *
     * Sin esto, la mesa que quedó abierta de anoche se le devuelve entera a la gente que
     * se sienta al mediodía: mismas rondas, mismo total, todo mezclado. Y pasaba cada vez
     * que el `schedule:run` del servidor no corría, sin que nadie se enterara.
     */
    public function test_una_comanda_vencida_no_se_le_devuelve_al_grupo_siguiente(): void
    {
        $mesa = $this->mesa();
        $anoche = $this->servicio()->enviarCarrito(
            $this->carritoCon($mesa->comercio, 'tel-anoche', ['Milanesa' => ['qty' => 1, 'centavos' => 380000]]),
            $mesa
        )['comanda'];

        // Se fueron anoche y nadie cerró la mesa.
        $anoche->update(['abierta_at' => now()->subHours(14)]);
        $anoche->items()->update(['created_at' => now()->subHours(14)]);

        $hoy = $this->servicio()->abrirOTomar($mesa);

        $this->assertNotSame($anoche->id, $hoy->id, 'El grupo nuevo heredó la comanda vieja.');
        $this->assertSame(0, $hoy->items()->count());
        $this->assertSame(0, $hoy->total_centavos);

        // La vieja quedó cerrada y con lo suyo, no borrada.
        $anoche = $anoche->fresh();
        $this->assertSame(Comanda::CERRADA, $anoche->estado);
        $this->assertSame('auto', $anoche->cerrada_por);
        $this->assertSame(1, $anoche->items()->count());
    }

    public function test_una_comanda_con_actividad_reciente_se_sigue_reusando(): void
    {
        $mesa = $this->mesa();
        $primera = $this->servicio()->abrirOTomar($mesa);
        $this->servicio()->agregarDelMozo($primera, 'Milanesa');

        // El segundo comensal de la misma mesa tiene que caer en la misma comanda.
        $this->assertSame($primera->id, $this->servicio()->abrirOTomar($mesa)->id);
    }

    /**
     * Una mesa que abrió hace horas pero acaba de pedir el postre está comiendo, no
     * olvidada: el vencimiento se mide desde el último pedido, no desde la apertura.
     */
    public function test_una_sobremesa_larga_no_cuenta_como_vencida(): void
    {
        $mesa = $this->mesa();
        $comanda = $this->servicio()->abrirOTomar($mesa);
        $comanda->update(['abierta_at' => now()->subHours(6)]);
        $this->servicio()->agregarDelMozo($comanda, 'Flan');

        $this->assertFalse($this->servicio()->estaVencida($comanda->fresh()->load('items')));
        $this->assertSame($comanda->id, $this->servicio()->abrirOTomar($mesa)->id);
    }

    public function test_las_comandas_sin_actividad_se_cierran_solas(): void
    {
        $comercio = Comercio::factory()->create();
        $vieja = $this->servicio()->abrirOTomar($this->mesa($comercio, 1));
        $reciente = $this->servicio()->abrirOTomar($this->mesa($comercio, 2));

        // La mesa 1 quedó abierta desde ayer: el mozo se olvidó de cerrarla.
        $vieja->update(['abierta_at' => now()->subHours(5)]);

        $cerradas = $this->servicio()->cerrarInactivas();

        $this->assertSame(1, $cerradas);
        $this->assertSame(Comanda::CERRADA, $vieja->fresh()->estado);
        $this->assertSame('auto', $vieja->fresh()->cerrada_por);
        $this->assertSame(Comanda::ABIERTA, $reciente->fresh()->estado);
    }

    public function test_una_comanda_con_pedidos_recientes_no_se_cierra_sola(): void
    {
        $mesa = $this->mesa();
        $comanda = $this->servicio()->abrirOTomar($mesa);
        // Se sentaron hace cinco horas, pero acaban de pedir el postre.
        $comanda->update(['abierta_at' => now()->subHours(5)]);
        $this->servicio()->agregarDelMozo($comanda, 'Flan');

        $this->assertSame(0, $this->servicio()->cerrarInactivas());
        $this->assertSame(Comanda::ABIERTA, $comanda->fresh()->estado);
    }

    public function test_los_items_sin_precio_no_rompen_el_total(): void
    {
        $mesa = $this->mesa();
        $comanda = $this->servicio()->abrirOTomar($mesa);

        $this->servicio()->agregarDelMozo($comanda, 'Milanesa', 1, '$3.800');
        $this->servicio()->agregarDelMozo($comanda, 'Servilletas extra');

        // El de sin precio suma cero en vez de tirar el cálculo: por eso el total es
        // orientativo y la pantalla del mozo lo aclara.
        $this->assertSame(380000, $comanda->fresh()->total_centavos);
    }
}
