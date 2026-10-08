<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Mesa;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La pantalla de mesas del panel: quién entra y qué puede tocar.
 *
 * Lo que se protege acá es que un comercio no pueda operar sobre las mesas de otro:
 * las rutas llevan el comercio por slug y la mesa por id, así que sin el chequeo de
 * pertenencia alcanzaba con probar ids para rotarle el QR a un local ajeno.
 */
class MesaPanelTest extends TestCase
{
    use RefreshDatabase;

    private function planConMesas(): Plan
    {
        return Plan::create([
            'key' => 'con-mesas',
            'nombre' => 'Con mesas',
            'mensajes_ia_mes' => 1000,
            'modelo_ia' => 'gpt-4o-mini',
            'max_tokens_chat' => 600,
            'features' => ['mesas'],
            'activo' => true,
        ]);
    }

    private function planSinMesas(): Plan
    {
        return Plan::create([
            'key' => 'sin-mesas',
            'nombre' => 'Sin mesas',
            'mensajes_ia_mes' => 500,
            'modelo_ia' => 'gpt-4o-mini',
            'max_tokens_chat' => 600,
            'features' => [],
            'activo' => true,
        ]);
    }

    private function comercioCon(Plan $plan, ?User $dueno = null): Comercio
    {
        $dueno ??= User::factory()->create();

        return Comercio::factory()->for($dueno)->create(['plan_id' => $plan->id]);
    }

    public function test_el_dueno_define_la_cantidad_y_se_crean_las_mesas(): void
    {
        $comercio = $this->comercioCon($this->planConMesas());

        $this->actingAs($comercio->user)
            ->post(route('mesas.sincronizar', $comercio), ['cantidad' => 12])
            ->assertRedirect(route('mesas.index', $comercio));

        $this->assertSame(12, $comercio->mesas()->count());
    }

    public function test_sin_la_feature_en_el_plan_la_pantalla_da_403(): void
    {
        $comercio = $this->comercioCon($this->planSinMesas());

        $this->actingAs($comercio->user)
            ->get(route('mesas.index', $comercio))
            ->assertForbidden();
    }

    public function test_un_comercio_no_puede_tocar_las_mesas_de_otro(): void
    {
        $plan = $this->planConMesas();
        $mio = $this->comercioCon($plan);
        $ajeno = $this->comercioCon($plan);

        $this->actingAs($mio->user)
            ->get(route('mesas.index', $ajeno))
            ->assertForbidden();
    }

    public function test_no_se_puede_rotar_el_token_de_una_mesa_de_otro_comercio(): void
    {
        $plan = $this->planConMesas();
        $mio = $this->comercioCon($plan);
        $ajeno = $this->comercioCon($plan);
        $mesaAjena = Mesa::factory()->create(['comercio_id' => $ajeno->id, 'numero' => 1]);
        $tokenOriginal = $mesaAjena->token;

        // Comercio propio en la URL + id de una mesa ajena: el caso que hay que cerrar.
        $this->actingAs($mio->user)
            ->post(route('mesas.rotar', [$mio, $mesaAjena]))
            ->assertNotFound();

        $this->assertSame($tokenOriginal, $mesaAjena->fresh()->token);
    }

    public function test_el_super_admin_puede_entrar_a_cualquier_comercio(): void
    {
        $comercio = $this->comercioCon($this->planSinMesas());
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->get(route('mesas.index', $comercio))
            ->assertOk();
    }

    public function test_la_pantalla_lista_las_mesas_y_ofrece_imprimirlas(): void
    {
        $comercio = $this->comercioCon($this->planConMesas());
        $this->actingAs($comercio->user)->post(route('mesas.sincronizar', $comercio), ['cantidad' => 3]);

        $this->actingAs($comercio->user)
            ->get(route('mesas.index', $comercio))
            ->assertOk()
            ->assertSee('Mesa 1')
            ->assertSee('Mesa 3')
            ->assertSee(route('mesas.imprimir', $comercio));
    }

    public function test_la_hoja_imprimible_trae_una_tarjeta_por_mesa_en_servicio(): void
    {
        $comercio = $this->comercioCon($this->planConMesas());
        $this->actingAs($comercio->user)->post(route('mesas.sincronizar', $comercio), ['cantidad' => 4]);
        $this->actingAs($comercio->user)->post(route('mesas.sincronizar', $comercio), ['cantidad' => 2]);

        $respuesta = $this->actingAs($comercio->user)->get(route('mesas.imprimir', $comercio));

        $respuesta->assertOk()
            ->assertSee('MESA 1')
            ->assertSee('MESA 2')
            // Las que están fuera de servicio no se imprimen: sería pegar un cartel muerto.
            ->assertDontSee('MESA 3')
            ->assertSee('data:image/png;base64,', false);
    }

    public function test_cada_mesa_ofrece_abrir_el_menu_como_lo_ve_un_cliente_sentado_ahi(): void
    {
        $comercio = $this->comercioCon($this->planConMesas());
        $this->actingAs($comercio->user)->post(route('mesas.sincronizar', $comercio), ['cantidad' => 2]);
        $mesa = $comercio->mesas()->first();

        // Con el token puesto: es entrar al menú como si hubiera escaneado ese QR.
        $this->actingAs($comercio->user)
            ->get(route('mesas.index', $comercio))
            ->assertOk()
            ->assertSee('?m='.$mesa->token, false);
    }

    public function test_el_qr_del_acceso_del_mozo_se_sirve_cuando_esta_activado(): void
    {
        $plan = Plan::create([
            'key' => 'con-comandas',
            'nombre' => 'Con comandas',
            'mensajes_ia_mes' => 1000,
            'modelo_ia' => 'gpt-4o-mini',
            'max_tokens_chat' => 600,
            'features' => ['mesas', 'comandas'],
            'activo' => true,
        ]);
        $comercio = $this->comercioCon($plan);

        // Sin activar todavía no hay nada que escanear.
        $this->actingAs($comercio->user)->get(route('mozo.qr', $comercio))->assertNotFound();

        $comercio->activarAccesoMozo('1234');

        $respuesta = $this->actingAs($comercio->user)->get(route('mozo.qr', $comercio->fresh()));
        $respuesta->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->assertNotFalse(getimagesizefromstring($respuesta->getContent()));
    }

    public function test_el_endpoint_del_qr_devuelve_un_png(): void
    {
        $comercio = $this->comercioCon($this->planConMesas());
        $this->actingAs($comercio->user)->post(route('mesas.sincronizar', $comercio), ['cantidad' => 1]);
        $mesa = $comercio->mesas()->first();

        $respuesta = $this->actingAs($comercio->user)->get(route('mesas.qr', [$comercio, $mesa]));

        $respuesta->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->assertNotFalse(getimagesizefromstring($respuesta->getContent()));
        // Que la CDN de producción no la guarde ni la "optimice" (la re-pide sin sesión).
        $this->assertStringContainsString('no-transform', $respuesta->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $respuesta->headers->get('Cache-Control'));
    }

    public function test_la_pantalla_trae_los_qr_embebidos_y_no_por_url(): void
    {
        // En producción, con la CDN de Hostinger adelante, <img src=".../qr.png"> salía roto:
        // la CDN pedía la imagen sin la sesión y contestaba "Invalid source image".
        $comercio = $this->comercioCon($this->planConMesas());
        $this->actingAs($comercio->user)->post(route('mesas.sincronizar', $comercio), ['cantidad' => 2]);
        $mesa = $comercio->mesas()->first();

        $html = $this->actingAs($comercio->user)->get(route('mesas.index', $comercio))->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, 'alt="Código QR de la mesa'));
        $this->assertMatchesRegularExpression('/<img src="data:image\/png;base64,[^"]+"\s+alt="Código QR de la mesa '.$mesa->numero.'"/', $html);
        $this->assertStringNotContainsString(route('mesas.qr', [$comercio, $mesa]), $html);
        // La descarga también sale del embebido, con nombre de archivo.
        $this->assertStringContainsString('download="qr-'.$comercio->slug.'-mesa-'.$mesa->numero.'.png"', $html);
    }

    public function test_pedir_mas_mesas_que_el_tope_no_pasa_la_validacion(): void
    {
        $comercio = $this->comercioCon($this->planConMesas());

        $this->actingAs($comercio->user)
            ->post(route('mesas.sincronizar', $comercio), ['cantidad' => 99999])
            ->assertSessionHasErrors('cantidad');

        $this->assertSame(0, $comercio->mesas()->count());
    }

    public function test_la_etiqueta_de_una_mesa_se_puede_editar(): void
    {
        $comercio = $this->comercioCon($this->planConMesas());
        $this->actingAs($comercio->user)->post(route('mesas.sincronizar', $comercio), ['cantidad' => 2]);
        $mesa = $comercio->mesas()->first();

        $this->actingAs($comercio->user)
            ->put(route('mesas.update', [$comercio, $mesa]), ['etiqueta' => 'Terraza'])
            ->assertRedirect();

        $this->assertSame('Terraza', $mesa->fresh()->etiqueta);
    }

    public function test_dar_de_baja_una_mesa_la_saca_de_servicio_sin_borrarla(): void
    {
        $comercio = $this->comercioCon($this->planConMesas());
        $this->actingAs($comercio->user)->post(route('mesas.sincronizar', $comercio), ['cantidad' => 2]);
        $mesa = $comercio->mesas()->first();

        $this->actingAs($comercio->user)->post(route('mesas.toggle', [$comercio, $mesa]));

        $this->assertFalse($mesa->fresh()->activa);
        $this->assertDatabaseHas('mesas', ['id' => $mesa->id]);
    }

    public function test_un_invitado_no_entra(): void
    {
        $comercio = $this->comercioCon($this->planConMesas());

        $this->get(route('mesas.index', $comercio))->assertRedirect(route('login'));
    }
}
