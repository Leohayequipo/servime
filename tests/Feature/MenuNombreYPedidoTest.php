<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El nombre del menú y el interruptor del pedido en línea: los dos se guardan solos.
 *
 * El bug que motivó esto: el checkbox del pedido en línea nunca tuvo atributo `name`, así
 * que no viajaba con el formulario de la pantalla, y el `update()` del menú leía esa
 * ausencia como un "desactivar": cada vez que el comercio guardaba su menú, se le apagaba
 * el pedido en línea. Ese formulario y ese `update()` ya no existen (el menú no se
 * reprocesa: se sube una carta nueva), así que el caso no puede volver por ese lado.
 */
class MenuNombreYPedidoTest extends TestCase
{
    use RefreshDatabase;

    private function comercioConMenu(array $extra = []): Comercio
    {
        $comercio = Comercio::factory()->for(User::factory())->create($extra);

        Menu::factory()->create([
            'comercio_id' => $comercio->id,
            'nombre' => 'Carta original',
            'activo' => true,
            'procesado' => true,
        ]);

        return $comercio;
    }

    public function test_el_interruptor_del_pedido_guarda_de_verdad(): void
    {
        $comercio = $this->comercioConMenu(['pedido_habilitado' => false]);

        $this->actingAs($comercio->user)
            ->postJson(route('menus.update.chatbot.config', $comercio), ['pedido_habilitado' => true])
            ->assertOk();

        $this->assertTrue($comercio->fresh()->pedido_habilitado);

        // Y se puede volver a apagar: `has()` distingue "vino en false" de "no vino".
        $this->actingAs($comercio->user)
            ->postJson(route('menus.update.chatbot.config', $comercio), ['pedido_habilitado' => false])
            ->assertOk();

        $this->assertFalse($comercio->fresh()->pedido_habilitado);
    }

    public function test_cambiar_el_nombre_no_pasa_por_el_boton_que_procesa(): void
    {
        $comercio = $this->comercioConMenu();
        $menu = $comercio->menus()->first();

        $this->actingAs($comercio->user)
            ->postJson(route('menus.update.nombre', [$comercio, $menu]), ['nombre' => 'Carta de la noche'])
            ->assertOk()
            ->assertJson(['success' => true, 'nombre' => 'Carta de la noche']);

        $this->assertSame('Carta de la noche', $menu->fresh()->nombre);
        // Y no disparó ningún reprocesamiento.
        $this->assertTrue($menu->fresh()->procesado);
    }

    public function test_el_nombre_es_obligatorio(): void
    {
        $comercio = $this->comercioConMenu();
        $menu = $comercio->menus()->first();

        $this->actingAs($comercio->user)
            ->postJson(route('menus.update.nombre', [$comercio, $menu]), ['nombre' => '   '])
            ->assertStatus(422);

        $this->assertSame('Carta original', $menu->fresh()->nombre);
    }

    public function test_el_nombre_se_guarda_sin_espacios_de_sobra(): void
    {
        $comercio = $this->comercioConMenu();
        $menu = $comercio->menus()->first();

        $this->actingAs($comercio->user)
            ->postJson(route('menus.update.nombre', [$comercio, $menu]), ['nombre' => '  Carta de verano  '])
            ->assertOk();

        $this->assertSame('Carta de verano', $menu->fresh()->nombre);
    }

    public function test_no_se_puede_renombrar_el_menu_de_otro_comercio(): void
    {
        $mio = $this->comercioConMenu();
        $ajeno = $this->comercioConMenu();
        $menuAjeno = $ajeno->menus()->first();

        // Comercio propio en la URL + id de un menú ajeno.
        $this->actingAs($mio->user)
            ->postJson(route('menus.update.nombre', [$mio, $menuAjeno]), ['nombre' => 'Hackeado'])
            ->assertNotFound();

        $this->assertSame('Carta original', $menuAjeno->fresh()->nombre);
    }

    public function test_un_usuario_ajeno_no_puede_renombrar(): void
    {
        $comercio = $this->comercioConMenu();
        $menu = $comercio->menus()->first();
        $intruso = User::factory()->create();

        $this->actingAs($intruso)
            ->postJson(route('menus.update.nombre', [$comercio, $menu]), ['nombre' => 'Hackeado'])
            ->assertForbidden();

        $this->assertSame('Carta original', $menu->fresh()->nombre);
    }

    public function test_la_pantalla_marca_el_nombre_como_obligatorio(): void
    {
        $comercio = $this->comercioConMenu();
        $menu = $comercio->menus()->first();

        // En edición el nombre se guarda solo y no hay botón de procesar: el archivo no se
        // reemplaza, se sube una carta nueva.
        $this->actingAs($comercio->user)
            ->get(route('menus.edit', [$comercio, $menu]))
            ->assertOk()
            ->assertSee('data-guardar-en', false)
            ->assertDontSee('Crear y procesar menú');

        // Al crear sí: el botón dice lo que hace, no "guardar".
        $this->actingAs($comercio->user)
            ->get(route('menus.create', $comercio))
            ->assertOk()
            ->assertSee('Crear y procesar menú');
    }
}
