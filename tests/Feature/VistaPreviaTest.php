<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El menú público adentro del celular de la vista previa del editor.
 *
 * Tiene que ser idéntico al real, pero sin ensuciar nada: cada edición del comercio
 * recargaba la vista previa y, con el tracking prendido, le sumaba visitas falsas.
 */
class VistaPreviaTest extends TestCase
{
    use RefreshDatabase;

    private function comercio(): Comercio
    {
        $dueno = User::factory()->create(['role' => 'user']);
        $comercio = Comercio::factory()->create(['user_id' => $dueno->id]);
        // El publicado con contenido propio: sin platos, el chatbot cae al último menú que
        // tenga, y eso haría pasar por "menú vigente" a cualquier borrador del test.
        Menu::factory()->activo()->create([
            'comercio_id' => $comercio->id,
            'contexto_gpt' => json_encode(['categorias' => [
                ['nombre' => 'Principales', 'platos' => [['nombre' => 'Milanesa publicada', 'precio' => '9000']]],
            ]]),
        ]);

        return $comercio;
    }

    /**
     * Las pantallas públicas registran eventos contra esta URL.
     *
     * Tal como queda escrita en la página: el parcial la imprime con @json, que escapa las
     * barras. Buscada "limpia" no aparecía nunca, y los assertDontSee pasaban de casualidad.
     */
    private function urlTracking(Comercio $comercio): string
    {
        return json_encode(route('track', $comercio));
    }

    public function test_el_dueno_en_vista_previa_no_registra_estadisticas(): void
    {
        $comercio = $this->comercio();

        foreach (['menu.public', 'menu.chat', 'menu.classic'] as $ruta) {
            $this->actingAs($comercio->user)
                ->get(route($ruta, $comercio).'?preview=1')
                ->assertOk()
                ->assertSee('window.SM_VISTA_PREVIA = true', false)
                ->assertDontSee($this->urlTracking($comercio), false);
        }
    }

    public function test_un_cliente_no_puede_saltearse_las_estadisticas_con_el_parametro(): void
    {
        // Si alcanzara con ?preview=1, cualquiera podría quedar afuera de las
        // estadísticas del local agregándolo a mano.
        $comercio = $this->comercio();

        $this->get(route('menu.public', $comercio).'?preview=1')
            ->assertOk()
            ->assertSee($this->urlTracking($comercio), false)
            ->assertDontSee('window.SM_VISTA_PREVIA', false);
    }

    public function test_el_dueno_de_otro_comercio_tampoco(): void
    {
        $comercio = $this->comercio();
        $otro = User::factory()->create(['role' => 'user']);

        $this->actingAs($otro)
            ->get(route('menu.public', $comercio).'?preview=1')
            ->assertSee($this->urlTracking($comercio), false)
            ->assertDontSee('window.SM_VISTA_PREVIA', false);
    }

    public function test_el_super_admin_si_puede_ver_la_vista_previa(): void
    {
        $comercio = $this->comercio();
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('menu.public', $comercio).'?preview=1')
            ->assertSee('window.SM_VISTA_PREVIA = true', false)
            ->assertDontSee($this->urlTracking($comercio), false);
    }

    public function test_sin_el_parametro_el_dueno_ve_su_menu_normal(): void
    {
        // Mirar su propio menú fuera del editor no cambia nada: sólo el iframe pide preview.
        $comercio = $this->comercio();

        $this->actingAs($comercio->user)
            ->get(route('menu.public', $comercio))
            ->assertSee($this->urlTracking($comercio), false)
            ->assertDontSee('window.SM_VISTA_PREVIA', false);
    }

    public function test_la_vista_previa_muestra_el_menu_que_se_edita_y_no_el_vigente(): void
    {
        // Editando un menú todavía sin publicar, el celular mostraba el publicado.
        $comercio = $this->comercio();
        $borrador = Menu::factory()->create([
            'comercio_id' => $comercio->id,
            'activo' => false,
            'contexto_gpt' => json_encode(['categorias' => [
                ['nombre' => 'Postres', 'platos' => [['nombre' => 'Flan del borrador', 'precio' => '5000']]],
            ]]),
        ]);

        $this->actingAs($comercio->user)
            ->get(route('menu.classic', $comercio).'?vista=lista&preview=1&menu='.$borrador->id)
            ->assertOk()
            ->assertSee('Flan del borrador')
            ->assertDontSee('Milanesa publicada');

        // Y sin vista previa el parámetro se ignora: el menú de los clientes no cambia.
        $this->get(route('menu.classic', $comercio).'?vista=lista&menu='.$borrador->id)
            ->assertSee('Milanesa publicada')
            ->assertDontSee('Flan del borrador');
    }

    public function test_no_se_puede_previsualizar_el_menu_de_otro_comercio(): void
    {
        $comercio = $this->comercio();
        $ajeno = Menu::factory()->create([
            'contexto_gpt' => json_encode(['categorias' => [
                ['nombre' => 'X', 'platos' => [['nombre' => 'Plato ajeno secreto', 'precio' => '1']]],
            ]]),
        ]);

        $this->actingAs($comercio->user)
            ->get(route('menu.classic', $comercio).'?vista=lista&preview=1&menu='.$ajeno->id)
            ->assertOk()
            ->assertDontSee('Plato ajeno secreto');
    }

    public function test_en_vista_previa_el_chat_avisa_que_no_responde(): void
    {
        // Cada mensaje es una llamada a la IA descontada de la cuota del plan.
        $comercio = $this->comercio();

        $this->actingAs($comercio->user)
            ->get(route('menu.chat', $comercio).'?preview=1')
            ->assertOk()
            ->assertSee('Vista previa: el chat no responde acá');
    }

    public function test_en_vista_previa_se_ve_el_pedido_de_resena_aunque_este_apagado(): void
    {
        // En la vida real aparece a los 20 minutos y sólo si el cliente usó el chat: en una
        // vista previa no pasaría nunca, y el comercio escribía esos textos a ciegas.
        $comercio = $this->comercio();
        $comercio->update([
            'resenas_activo' => false,
            'google_review_url' => null,
            // Sin acentos a propósito: @json los escribe como ¿ y el test no los encontraría.
            'resena_titulo' => 'Contanos como la pasaron',
        ]);

        $this->actingAs($comercio->user)
            ->get(route('menu.chat', $comercio).'?preview=1')
            ->assertOk()
            ->assertSee('Contanos como la pasaron', false)
            ->assertSee('SM_VISTA_PREVIA', false);

        // Para un cliente sigue sin existir: no hay link ni está activo.
        $this->get(route('menu.chat', $comercio))
            ->assertOk()
            ->assertDontSee('Contanos como la pasaron', false);
    }
}
