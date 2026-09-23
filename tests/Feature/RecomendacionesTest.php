<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Menu;
use App\Models\Plan;
use App\Models\User;
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

    public function test_si_no_encuentra_nada_ofrece_algo_igual(): void
    {
        $comercio = $this->comercioConCarta();

        $respuesta = app(RecomendadorService::class)->responder($comercio, 'tienen sushi?');

        $this->assertStringContainsString('No encontré eso en la carta', $respuesta['texto']);
        $this->assertNotEmpty($respuesta['platos']);
    }

    public function test_el_chat_sin_cuota_recomienda_en_vez_de_avisar_que_no_esta_disponible(): void
    {
        $plan = Plan::where('key', 'basico')->first();
        $plan->update(['mensajes_ia_mes' => 1]);

        $comercio = $this->comercioConCarta([
            'user_id' => User::factory()->create(['role' => 'user'])->id,
            'plan_id' => $plan->id,
        ]);
        $comercio->menus()->first()->update(['recomendaciones' => ['platos' => [
            'pancho' => ['con' => ['Coca 500'], 'parecidos' => [], 'motivo' => 'El combo de siempre'],
        ]]]);

        // Cuota agotada: a partir de acá el asistente con IA no contesta más.
        $comercio->registrarUsoIA();
        $this->assertFalse($comercio->refresh()->puedeUsarIA());

        $respuesta = $this->postJson(route('chat.api', $comercio), [
            'message' => 'tienen panchos?',
            'session_id' => 'sess_sin_ia',
        ]);

        $respuesta->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('platos.0.nombre', 'Pancho')
            ->assertJsonPath('platos.1.nombre', 'Coca 500');

        $this->assertStringContainsString('Coca 500', $respuesta->json('response'));
        $this->assertStringNotContainsString('no está disponible', $respuesta->json('response'));

        // Y no se gastó cuota ni se llamó a OpenAI.
        $this->assertSame(1, $comercio->refresh()->mensajesIaUsados());
        $this->assertDatabaseHas('chat_conversations', [
            'comercio_id' => $comercio->id,
            'used_openai' => false,
        ]);
    }

    public function test_el_menu_clasico_muestra_va_bien_con(): void
    {
        $comercio = $this->comercioConCarta(['menu_clasico_vista' => Comercio::VISTA_LISTA]);
        $comercio->menus()->first()->update(['recomendaciones' => ['platos' => [
            'pancho' => ['con' => ['Coca 500', 'Papas fritas'], 'parecidos' => ['Empanadas'], 'motivo' => 'El combo de siempre'],
        ]]]);

        $this->get(route('menu.classic', $comercio))
            ->assertOk()
            ->assertSee('Va bien con')
            ->assertSee('Coca 500 · Papas fritas');
    }
}
