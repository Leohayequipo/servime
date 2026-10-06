<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El buscador de la carta: la misma búsqueda en PHP que usa el asistente, sin IA.
 */
class BuscadorCartaTest extends TestCase
{
    use RefreshDatabase;

    private function comercioConCarta(array $extra = []): Comercio
    {
        $comercio = Comercio::factory()->create($extra);

        Menu::factory()->for($comercio)->create([
            'activo' => true,
            'estado' => Menu::ESTADO_LISTO,
            'contexto_gpt' => json_encode(['categorias' => [
                ['nombre' => 'Para comer', 'platos' => [
                    ['nombre' => 'Pancho', 'precio' => '$3.000'],
                    ['nombre' => 'Empanadas', 'precio' => '$2.000'],
                ]],
                ['nombre' => 'Hamburguesas', 'platos' => [
                    ['nombre' => 'Hamburguesa con Queso', 'precio' => '$14.000'],
                    ['nombre' => 'Hamburguesa Completa', 'precio' => '$19.000'],
                    ['nombre' => 'Hamburguesa Veggie', 'precio' => '$18.000'],
                    ['nombre' => 'Hamburguesa Doble', 'precio' => '$20.000'],
                ]],
                ['nombre' => 'Guarniciones', 'platos' => [
                    ['nombre' => 'Papas fritas', 'precio' => '$4.000'],
                ]],
            ]]),
        ]);

        return $comercio;
    }

    private function buscar(Comercio $comercio, string $q)
    {
        return $this->postJson(route('menu.buscar', $comercio), ['q' => $q, 'session_id' => 'sess_busca']);
    }

    public function test_devuelve_los_nombres_para_filtrar_la_lista(): void
    {
        $comercio = $this->comercioConCarta();

        // NOMBRES y no HTML: la carta ya está dibujada y el front esconde lo que no coincide.
        $this->buscar($comercio, 'pancho')
            ->assertOk()
            ->assertJsonPath('encontro', true)
            ->assertJsonPath('platos.0', 'Pancho')
            ->assertJsonPath('texto', '');
    }

    public function test_trae_todos_los_que_coinciden_y_no_los_primeros(): void
    {
        $comercio = $this->comercioConCarta();

        // El que busca "hamburguesa" quiere ver las cuatro: esto filtra la carta.
        $this->assertCount(4, $this->buscar($comercio, 'hamburguesa')->json('platos'));
    }

    /**
     * Los casos que fallaron probando la carta contra una carta real: lo que escribe
     * cualquiera en una mesa.
     */
    public function test_encuentra_aunque_escriban_mal_cortado_o_en_ingles(): void
    {
        $comercio = $this->comercioConCarta();

        foreach (['panch', 'panchos', 'empandas', 'fries', 'burger'] as $consulta) {
            $this->assertNotEmpty($this->buscar($comercio, $consulta)->json('platos'), "No encontró nada con: {$consulta}");
        }

        $this->assertSame('Papas fritas', $this->buscar($comercio, 'fries')->json('platos.0'));
    }

    public function test_lo_que_no_encuentra_queda_registrado(): void
    {
        $comercio = $this->comercioConCarta();

        // Es lo que el cliente quiso preguntar y la carta no supo contestar.
        $this->buscar($comercio, 'sushi')
            ->assertOk()
            ->assertJsonPath('encontro', false)
            ->assertJsonPath('platos', []);

        $this->assertDatabaseHas('tracking_events', [
            'comercio_id' => $comercio->id,
            'event' => 'busqueda_sin_resultado',
            'session_id' => 'sess_busca',
        ]);
    }

    public function test_en_ingles_lo_que_no_encuentra_tambien_cuenta_como_no_encontrado(): void
    {
        $comercio = $this->comercioConCarta();

        // El texto sale traducido; el "encontró" no puede depender de él.
        $this->withHeader('Accept-Language', 'en')
            ->postJson(route('menu.buscar', $comercio), ['q' => 'sushi', 'session_id' => 's'])
            ->assertJsonPath('encontro', false);

        $this->assertDatabaseHas('tracking_events', ['event' => 'busqueda_sin_resultado']);
    }

    public function test_la_carta_tiene_buscador_y_atajos_por_categoria(): void
    {
        $comercio = $this->comercioConCarta(['menu_clasico_vista' => Comercio::VISTA_LISTA]);

        $this->get(route('menu.classic', $comercio))
            ->assertOk()
            ->assertSee('Buscar en la carta')
            ->assertSee('data-cat="Guarniciones"', false)
            ->assertSee('data-nombre="Pancho"', false);
    }

    public function test_sin_asistente_no_se_ofrece_explorar_con_ia(): void
    {
        $comercio = $this->comercioConCarta([
            'menu_clasico_vista' => Comercio::VISTA_LISTA,
            'enable_ai_responses' => false,
        ]);

        $this->get(route('menu.classic', $comercio))
            ->assertOk()
            ->assertDontSee('Explorar con IA');
    }
}
