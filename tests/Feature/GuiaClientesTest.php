<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\TrackingEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La guía de bienvenida del menú público (menu/partials/guia): el tour que señala dónde
 * tocar. La prende o apaga cada comercio; el botón del editor la fuerza con ?guia=1.
 *
 * La lógica de "una vez por teléfono" vive en el navegador (localStorage) y se probó con
 * Chrome; acá se fija lo del servidor: cuándo se manda el script y cuándo no.
 */
class GuiaClientesTest extends TestCase
{
    use RefreshDatabase;

    private const MARCA = 'servime_guia_';

    private function comercio(array $atributos = []): Comercio
    {
        return Comercio::factory()->for(User::factory())->create($atributos);
    }

    public function test_arranca_prendida(): void
    {
        $this->assertTrue($this->comercio()->fresh()->guia_clientes);
    }

    public function test_prendida_el_chat_trae_la_guia_y_el_boton_para_repetirla(): void
    {
        $this->get(route('menu.chat', $this->comercio()))
            ->assertOk()
            ->assertSee(self::MARCA, false)
            ->assertSee('id="guiaAyuda"', false);
    }

    public function test_apagada_no_se_manda_nada(): void
    {
        $this->get(route('menu.chat', $this->comercio(['guia_clientes' => false])))
            ->assertOk()
            ->assertDontSee(self::MARCA, false)
            ->assertDontSee('id="guiaAyuda"', false);
    }

    public function test_el_boton_del_editor_la_muestra_aunque_este_apagada(): void
    {
        $comercio = $this->comercio(['guia_clientes' => false]);

        $this->actingAs($comercio->user)
            ->get(route('menu.chat', $comercio).'?preview=1&guia=1')
            ->assertOk()
            ->assertSee(self::MARCA, false);
    }

    public function test_en_la_vista_previa_comun_no_sale_sola(): void
    {
        // Si no, al comerciante le saltaba cada vez que el celular del editor cargaba el chat.
        $comercio = $this->comercio();

        $this->actingAs($comercio->user)
            ->get(route('menu.chat', $comercio).'?preview=1')
            ->assertOk()
            ->assertDontSee(self::MARCA, false);
    }

    public function test_el_comercio_la_prende_y_apaga_desde_el_editor(): void
    {
        $comercio = $this->comercio();

        $this->actingAs($comercio->user)
            ->postJson(route('menus.update.chatbot.config', $comercio), ['guia_clientes' => false])
            ->assertOk()->assertJson(['success' => true]);
        $this->assertFalse($comercio->fresh()->guia_clientes);

        $this->actingAs($comercio->user)
            ->postJson(route('menus.update.chatbot.config', $comercio), ['guia_clientes' => true])
            ->assertOk();
        $this->assertTrue($comercio->fresh()->guia_clientes);
    }

    public function test_guardar_otra_cosa_no_la_toca(): void
    {
        $comercio = $this->comercio(['guia_clientes' => false]);

        $this->actingAs($comercio->user)
            ->postJson(route('menus.update.chatbot.config', $comercio), ['chatbot_name' => 'Pepe'])
            ->assertOk();

        $this->assertFalse($comercio->fresh()->guia_clientes);
    }

    public function test_otro_usuario_no_la_puede_cambiar(): void
    {
        $comercio = $this->comercio();

        $this->actingAs(User::factory()->create())
            ->postJson(route('menus.update.chatbot.config', $comercio), ['guia_clientes' => false])
            ->assertForbidden();

        $this->assertTrue($comercio->fresh()->guia_clientes);
    }

    public function test_se_registra_si_la_ven_la_terminan_o_la_cierran(): void
    {
        $comercio = $this->comercio();

        foreach (['guia_vista', 'guia_completa', 'guia_cerrada'] as $evento) {
            $this->postJson(route('track', $comercio), ['event' => $evento, 'session_id' => 's1', 'data' => ['pantalla' => 'chat']]);
        }

        $this->assertSame(
            ['guia_vista', 'guia_completa', 'guia_cerrada'],
            TrackingEvent::where('comercio_id', $comercio->id)->orderBy('id')->pluck('event')->all()
        );
    }
}
