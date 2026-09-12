<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cuál menú se le muestra al cliente cuando el comercio tiene varios.
 *
 * Sólo uno puede estar activo, pero un comercio puede quedarse sin ninguno marcado
 * —borrando el que lo estaba, por ejemplo—. Cuatro lugares responden "¿cuál muestro?"
 * y tres caían al último procesado; `getCategoriasDelMenu()` y `getTagsDelMenu()` no,
 * y devolvían vacío.
 *
 * Fallaba en silencio: la carta se veía bien y el chat respondía bien, pero los chips
 * de la portada caían al genérico "¿Qué tienen?" en lugar de ofrecer las promos y las
 * categorías del comercio.
 */
class MenuVigenteTest extends TestCase
{
    use RefreshDatabase;

    private function menuCon(Comercio $comercio, array $categorias, bool $activo): Menu
    {
        return Menu::factory()->for($comercio)->create([
            'procesado' => true,
            'activo' => $activo,
            'contexto_gpt' => json_encode(['categorias' => $categorias]),
        ]);
    }

    private function categoria(string $nombre, array $tags = []): array
    {
        return [
            'nombre' => $nombre,
            'platos' => [['nombre' => 'Plato', 'precio' => '100', 'tags' => $tags]],
        ];
    }

    public function test_con_un_menu_activo_gana_el_activo(): void
    {
        $comercio = Comercio::factory()->create();

        $this->menuCon($comercio, [$this->categoria('Viejas')], activo: false);
        $this->menuCon($comercio, [$this->categoria('Vigentes')], activo: true);

        $nombres = array_column($comercio->getCategoriasDelMenu(), 'nombre');

        $this->assertContains('Vigentes', $nombres);
        $this->assertNotContains('Viejas', $nombres);
    }

    public function test_sin_ninguno_activo_gana_el_ultimo_procesado(): void
    {
        $comercio = Comercio::factory()->create();

        $this->menuCon($comercio, [$this->categoria('La vieja')], activo: false);
        $ultimo = $this->menuCon($comercio, [$this->categoria('La nueva')], activo: false);

        // Antes esto devolvía [] y los chips de la portada quedaban genéricos.
        $this->assertSame($ultimo->id, $comercio->menuVigente()?->id);
        $this->assertContains('La nueva', array_column($comercio->getCategoriasDelMenu(), 'nombre'));
    }

    public function test_los_tags_usan_la_misma_regla(): void
    {
        $comercio = Comercio::factory()->create();
        $this->menuCon($comercio, [$this->categoria('Ensaladas', ['SIN_TACC'])], activo: false);

        $this->assertContains('SIN_TACC', $comercio->getTagsDelMenu());
    }

    public function test_los_chips_de_la_portada_no_quedan_genericos_sin_menu_activo(): void
    {
        $comercio = Comercio::factory()->create();

        // El nombre importa: `getCategoriasDelMenu()` marca la promo comparando el
        // nombre contra "promociones del día" literal, no por un flag en el JSON.
        $this->menuCon($comercio, [
            ['nombre' => 'Promociones del Día', 'platos' => []],
            $this->categoria('Pizzas', ['SIN_TACC']),
        ], activo: false);

        $labels = array_column($comercio->getChipsSugeridos(), 'label');

        // Esto es lo que ve el cliente en la portada. El fallback "¿Qué tienen?" sólo
        // debería salir cuando de verdad no hay nada que ofrecer.
        $this->assertContains('Ver promos', $labels);
        $this->assertNotContains('¿Qué tienen?', $labels);
    }

    public function test_un_menu_sin_procesar_no_cuenta(): void
    {
        $comercio = Comercio::factory()->create();
        Menu::factory()->for($comercio)->create(['procesado' => false, 'activo' => true]);

        // Un menú a medio procesar no se le muestra a nadie, ni siquiera como plan B.
        $this->assertNull($comercio->menuVigente());
        $this->assertSame([], $comercio->getCategoriasDelMenu());
    }
}
