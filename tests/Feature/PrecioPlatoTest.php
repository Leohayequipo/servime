<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Menu;
use App\Models\User;
use App\Support\Precio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El campo de precio del panel multiplicaba los precios por cien.
 *
 * Mostraba "$5.700,00" como `570000` —le sacaba la coma y los centavos quedaban pegados— y
 * al perder el foco guardaba eso mismo. Bastaba pasar por el campo, sin escribir nada, para
 * que un desayuno de $9.800 pasara a $980.000 en el menú que ve el cliente.
 */
class PrecioPlatoTest extends TestCase
{
    use RefreshDatabase;

    private function menu(Comercio $comercio): Menu
    {
        return Menu::factory()->for($comercio)->activo()->create([
            'contexto_gpt' => json_encode(['categorias' => [[
                'nombre' => 'Desayunos',
                'platos' => [['nombre' => 'Tostadas', 'precio' => '$5.700,00']],
            ]]]),
        ]);
    }

    private function guardar(User $user, Comercio $comercio, Menu $menu, string $precio)
    {
        return $this->actingAs($user)->postJson(route('menus.update.plato.precio', [$comercio, $menu]), [
            'precio' => $precio,
            'categoria_index' => 0,
            'plato_index' => 0,
        ]);
    }

    private function precioGuardado(Menu $menu): string
    {
        return json_decode($menu->fresh()->contexto_gpt, true)['categorias'][0]['platos'][0]['precio'];
    }

    public function test_el_input_muestra_el_precio_sin_romperlo(): void
    {
        // Este era el bug de raíz: "$5.700,00" se mostraba como "570000".
        $this->assertSame('5.700,00', Precio::paraEditar('$5.700,00'));
        $this->assertSame('4.500', Precio::paraEditar('$4.500'));
        $this->assertSame('2x1', Precio::paraEditar('2x1'));
    }

    public function test_tocar_el_campo_sin_escribir_no_cambia_el_precio(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create();
        $menu = $this->menu($comercio);

        // Lo que manda el navegador al perder el foco es lo que el input está mostrando.
        $this->guardar($user, $comercio, $menu, Precio::paraEditar('$5.700,00'))->assertOk();

        $this->assertSame('$5.700,00', $this->precioGuardado($menu));
    }

    public function test_un_numero_pelado_se_formatea(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create();
        $menu = $this->menu($comercio);

        $this->guardar($user, $comercio, $menu, '9800')->assertOk();

        $this->assertSame('$9.800', $this->precioGuardado($menu));
    }

    public function test_respeta_lo_que_escribio_el_comercio(): void
    {
        // Entran menús de cualquier país: no podemos adivinar si el punto es miles o decimal,
        // así que cuando hay separadores se respeta la decisión de quien lo escribió.
        $this->assertSame('$12.50', Precio::formatear('12.50'));
        $this->assertSame('$5.700,00', Precio::formatear('5.700,00'));
        $this->assertSame('2x1', Precio::formatear('2x1'));
        $this->assertSame('consultar', Precio::formatear('consultar'));
        $this->assertSame('', Precio::formatear(''));
    }

    public function test_editar_el_mismo_precio_muchas_veces_no_lo_corre(): void
    {
        // El ciclo mostrar -> guardar -> mostrar tiene que ser estable: si cada vuelta le
        // agregara o sacara algo, el precio se iría corriendo con cada edición.
        foreach (['$4.500', '$5.700,00', '$12.50', '2x1'] as $precio) {
            $vuelta = Precio::formatear(Precio::paraEditar($precio));
            $this->assertSame($precio, $vuelta, "el precio $precio no es estable");
        }
    }
}
