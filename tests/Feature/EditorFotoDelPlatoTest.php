<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * En el editor del menú se ve qué platos tienen foto sin abrirlos de a uno.
 */
class EditorFotoDelPlatoTest extends TestCase
{
    use RefreshDatabase;

    public function test_cada_plato_muestra_si_tiene_foto_y_la_categoria_cuantos_no(): void
    {
        $comercio = Comercio::factory()->for(User::factory())->create();
        $menu = Menu::factory()->for($comercio)->create([
            'activo' => true,
            'estado' => Menu::ESTADO_LISTO,
            'contexto_gpt' => json_encode(['categorias' => [
                ['nombre' => 'Avocados', 'platos' => [
                    ['nombre' => 'Avocado Toast', 'precio' => '$8.900', 'imagen' => 'storage/comercios/1/platos/toast.jpg'],
                    ['nombre' => 'Avocado Integral', 'precio' => '$8.900'],
                    ['nombre' => 'Avocado Chicken', 'precio' => '$22.500', 'imagen' => ''],
                ]],
                ['nombre' => 'Wraps', 'platos' => [
                    ['nombre' => 'Wrap de pollo', 'precio' => '$9.000', 'imagen' => 'storage/comercios/1/platos/wrap.jpg'],
                ]],
            ]]),
        ]);

        $html = $this->actingAs($comercio->user)
            ->get(route('menus.edit', [$comercio, $menu]))
            ->assertOk()
            ->getContent();

        // El que tiene foto muestra la miniatura; los otros dos, "sin foto".
        $this->assertMatchesRegularExpression('/id="plato-foto-indicador-0-0"\s+data-tiene-foto="1"/', $html);
        $this->assertStringContainsString('storage/comercios/1/platos/toast.jpg', $html);
        $this->assertDoesNotMatchRegularExpression('/id="plato-foto-indicador-0-1"\s+data-tiene-foto/', $html);
        $this->assertDoesNotMatchRegularExpression('/id="plato-foto-indicador-0-2"\s+data-tiene-foto/', $html);

        // La cabecera cuenta los que faltan; con todas las fotos, el contador queda oculto.
        $this->assertMatchesRegularExpression('/data-sin-foto-cat="0"\s*>2 sin foto</', $html);
        $this->assertMatchesRegularExpression('/data-sin-foto-cat="1"\s+style="display:none"\s*>0 sin foto</', $html);
    }
}
