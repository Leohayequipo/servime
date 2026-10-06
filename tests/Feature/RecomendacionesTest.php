<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Menu;
use App\Models\Plan;
use App\Services\RecomendadorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Recomendaciones calculadas una vez por carta y servidas sin IA.
 */
class RecomendacionesTest extends TestCase
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
                ['nombre' => 'Bebidas', 'platos' => [
                    ['nombre' => 'Coca 500', 'precio' => '$2.500'],
                ]],
                ['nombre' => 'Guarniciones', 'platos' => [
                    ['nombre' => 'Papas fritas', 'precio' => '$4.000'],
                ]],
            ]]),
        ]);

        return $comercio;
    }

    private function respuestaIA(array $recomendaciones): void
    {
        config(['services.openai.api_key' => 'test-key']);

        Http::fake(['*/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => json_encode(['recomendaciones' => $recomendaciones])]]],
        ])]);
    }

    public function test_guarda_las_duplas_de_la_carta(): void
    {
        $comercio = $this->comercioConCarta();
        $menu = $comercio->menus()->first();

        $this->respuestaIA([
            ['plato' => 'Pancho', 'con' => ['Coca 500', 'Papas fritas'], 'parecidos' => ['Empanadas'], 'motivo' => 'El combo de siempre'],
        ]);

        $resultado = app(RecomendadorService::class)->generarYGuardar($menu);

        $this->assertSame(1, $resultado['platos']);
        $this->assertSame(
            ['Coca 500', 'Papas fritas'],
            $menu->refresh()->recomendaciones['platos']['pancho']['con']
        );
    }

    public function test_descarta_lo_que_la_ia_inventa(): void
    {
        $comercio = $this->comercioConCarta();
        $menu = $comercio->menus()->first();

        // Es la guarda que hace confiable a todo esto: el modelo se equivoca, y lo que no
        // existe en la carta no puede llegar a la mesa.
        $this->respuestaIA([
            ['plato' => 'Pancho', 'con' => ['Cerveza artesanal', 'Coca 500', 'Pancho'], 'motivo' => 'Va bien'],
            ['plato' => 'Sushi', 'con' => ['Coca 500'], 'motivo' => 'Plato que no existe'],
            ['plato' => 'Empanadas', 'con' => ['Milanesa'], 'motivo' => 'Ningun complemento real'],
        ]);

        app(RecomendadorService::class)->generarYGuardar($menu);
        $guardadas = $menu->refresh()->recomendaciones['platos'];

        // Del pancho sobrevive sólo la coca: la cerveza no está en la carta y no se
        // recomienda a sí mismo.
        $this->assertSame(['Coca 500'], $guardadas['pancho']['con']);
        // Un plato que no existe, o al que no le quedó ningún complemento real, no se guarda.
        $this->assertArrayNotHasKey('sushi', $guardadas);
        $this->assertArrayNotHasKey('empanadas', $guardadas);
    }

    public function test_lo_de_la_misma_categoria_es_alternativa_y_no_acompanamiento(): void
    {
        $comercio = $this->comercioConCarta();
        $menu = $comercio->menus()->first();

        // El modelo lo manda como acompañamiento; la categoría de la carta decide que no.
        // Bajo "Va bien con", otra comida se lee como "pedite las dos".
        $this->respuestaIA([
            ['plato' => 'Pancho', 'con' => ['Empanadas', 'Coca 500'], 'motivo' => 'Para la mesa'],
        ]);

        app(RecomendadorService::class)->generarYGuardar($menu);
        $fila = $menu->refresh()->recomendaciones['platos']['pancho'];

        $this->assertSame(['Coca 500'], $fila['con']);
        $this->assertSame(['Empanadas'], $fila['parecidos']);
    }

    public function test_sin_api_key_no_rompe_ni_guarda_nada(): void
    {
        config(['services.openai.api_key' => '']);
        $menu = $this->comercioConCarta()->menus()->first();

        $resultado = app(RecomendadorService::class)->generarYGuardar($menu);

        $this->assertArrayHasKey('error', $resultado);
        $this->assertNull($menu->refresh()->recomendaciones);
    }

    public function test_responde_sin_ia_con_lo_que_encontro_y_su_complemento(): void
    {
        $comercio = $this->comercioConCarta();
        $menu = $comercio->menus()->first();
        $menu->update(['recomendaciones' => ['platos' => [
            'pancho' => ['con' => ['Coca 500'], 'parecidos' => [], 'motivo' => 'El combo de siempre'],
        ]]]);

        $respuesta = app(RecomendadorService::class)->responder($comercio, 'tienen panchos?');

        $this->assertStringContainsString('Pancho', $respuesta['texto']);
        $this->assertStringContainsString('Coca 500', $respuesta['texto']);
        // Lo buscado primero y el complemento después: las tarjetas salen en ese orden.
        $this->assertSame(['Pancho', 'Coca 500'], array_column($respuesta['platos'], 'nombre'));
    }

    /**
     * Los cuatro casos que fallaron probando el menú sin asistente contra una carta real.
     * Con el modelo encendido ninguno se notaba: él tiene la carta entera delante.
     */
    public function test_encuentra_aunque_escriban_mal_cortado_o_en_ingles(): void
    {
        $comercio = $this->comercioConCarta();
        $recomendador = app(RecomendadorService::class);

        foreach (['tienen panchos?', 'pancho', 'panchos', 'panch'] as $consulta) {
            $this->assertSame(
                'Pancho',
                $recomendador->responder($comercio, $consulta)['platos'][0]['nombre'] ?? null,
                "No encontró el pancho con: {$consulta}"
            );
        }

        // Una letra de más o de menos (el tipeo en un celular, en una mesa)
        $this->assertSame('Empanadas', $recomendador->responder($comercio, 'empandas')['platos'][0]['nombre']);

        // Y el cliente que escribe en inglés
        $this->assertSame('Papas fritas', $recomendador->responder($comercio, 'fries')['platos'][0]['nombre']);
    }

    public function test_un_saludo_no_es_una_busqueda_fallida(): void
    {
        $comercio = $this->comercioConCarta();

        foreach (['Hola!', 'buenas', 'Buen día'] as $saludo) {
            $respuesta = app(RecomendadorService::class)->responder($comercio, $saludo);

            $this->assertStringNotContainsString('No encontré', $respuesta['texto'], "Contestó mal a: {$saludo}");
            $this->assertNotEmpty($respuesta['platos']);
        }
    }

    public function test_si_no_encuentra_nada_ofrece_algo_igual(): void
    {
        $comercio = $this->comercioConCarta();

        $respuesta = app(RecomendadorService::class)->responder($comercio, 'tienen sushi?');

        $this->assertStringContainsString('No encontré eso en la carta', $respuesta['texto']);
        $this->assertNotEmpty($respuesta['platos']);
    }

    public function test_el_buscador_devuelve_los_nombres_para_filtrar_la_lista(): void
    {
        $comercio = $this->comercioConCarta();

        // Devuelve NOMBRES y no HTML: la carta ya está dibujada en la página y el front
        // sólo esconde lo que no coincide.
        $this->postJson(route('menu.buscar', $comercio), ['q' => 'panch', 'session_id' => 'sess_busca'])
            ->assertOk()
            ->assertJsonPath('encontro', true)
            ->assertJsonPath('platos.0', 'Pancho');
    }

    public function test_lo_que_el_buscador_no_encuentra_queda_registrado(): void
    {
        $comercio = $this->comercioConCarta();

        // Es el dato que le da sentido al plan sin asistente: lo que el cliente quiso
        // preguntar y el menú no supo contestar.
        $this->postJson(route('menu.buscar', $comercio), ['q' => 'sushi', 'session_id' => 'sess_busca'])
            ->assertOk()
            ->assertJsonPath('encontro', false);

        $this->assertDatabaseHas('tracking_events', [
            'comercio_id' => $comercio->id,
            'event' => 'busqueda_sin_resultado',
            'session_id' => 'sess_busca',
        ]);
    }

    public function test_el_menu_clasico_tiene_buscador_y_atajos_por_categoria(): void
    {
        $comercio = $this->comercioConCarta(['menu_clasico_vista' => Comercio::VISTA_LISTA]);

        $this->get(route('menu.classic', $comercio))
            ->assertOk()
            ->assertSee('Buscar en la carta')
            ->assertSee('data-cat="Bebidas"', false)
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

    public function test_el_menu_clasico_muestra_va_bien_con(): void
    {
        $comercio = $this->comercioConCarta(['menu_clasico_vista' => Comercio::VISTA_LISTA]);
        $comercio->menus()->first()->update(['recomendaciones' => ['platos' => [
            'pancho' => ['con' => ['Coca 500', 'Papas fritas'], 'parecidos' => ['Empanadas'], 'motivo' => 'El combo de siempre'],
        ]]]);

        // Los acompañamientos van como chips tocables en la fila; los parecidos viajan en
        // el plato y se muestran recién al abrirlo, para no llenar la lista de texto.
        $this->get(route('menu.classic', $comercio))
            ->assertOk()
            ->assertSee('data-con="Coca 500|Papas fritas"', false)
            ->assertSee('data-parecidos="Empanadas"', false)
            ->assertSee("verPlato('Coca 500')", false)
            ->assertSee('Va bien con');
    }
}
