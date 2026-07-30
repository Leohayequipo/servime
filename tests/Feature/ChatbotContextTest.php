<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Comercio;
use App\Models\Menu;
use App\Services\ChatbotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatbotContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_contexto_del_chat_usa_solo_el_menu_activo(): void
    {
        $comercio = Comercio::factory()->create();

        Menu::factory()->for($comercio)->create([
            'activo' => false,
            'procesado' => true,
            'contexto_gpt' => json_encode(['categorias' => [['nombre' => 'Cat', 'platos' => [['nombre' => 'PlatoViejo', 'precio' => '100']]]]]),
        ]);
        Menu::factory()->for($comercio)->create([
            'activo' => true,
            'procesado' => true,
            'contexto_gpt' => json_encode(['categorias' => [['nombre' => 'Cat', 'platos' => [['nombre' => 'PlatoNuevo', 'precio' => '200']]]]]),
        ]);

        $preview = (new ChatbotService)->getContextPreview($comercio, 5000);

        $this->assertStringContainsString('PlatoNuevo', $preview['context_preview']);
        $this->assertStringNotContainsString('PlatoViejo', $preview['context_preview']);
    }

    public function test_detecta_platos_mencionados_con_datos_exactos_del_json(): void
    {
        $comercio = Comercio::factory()->create();
        Menu::factory()->for($comercio)->create([
            'activo' => true,
            'procesado' => true,
            'contexto_gpt' => json_encode(['categorias' => [['nombre' => 'Hamburguesas', 'platos' => [
                ['nombre' => 'Barrica Burger', 'precio' => '$18.500', 'descripcion' => 'Cheddar y panceta'],
                ['nombre' => 'Veggie', 'precio' => '$16.900', 'descripcion' => 'De lentejas'],
            ]]]]),
        ]);

        $respuesta = 'Te recomiendo la Barrica Burger, es la más pedida. ¡Imperdible!';
        $platos = (new ChatbotService)->detectarPlatosMencionados($comercio, $respuesta);

        // Detecta solo la mencionada, con precio exacto del JSON
        $this->assertCount(1, $platos);
        $this->assertSame('Barrica Burger', $platos[0]['nombre']);
        $this->assertSame('$18.500', $platos[0]['precio']);
    }

    private function comercioConMenu(): Comercio
    {
        $comercio = Comercio::factory()->create();
        Menu::factory()->for($comercio)->create([
            'activo' => true,
            'procesado' => true,
            'contexto_gpt' => json_encode(['categorias' => [
                ['nombre' => 'Pizzas', 'platos' => [
                    ['nombre' => 'Muzzarella (Grande)', 'precio' => '$14.000'],
                    ['nombre' => 'Muzzarella (Chica)', 'precio' => '$9.000'],
                ]],
                ['nombre' => 'Bebidas', 'platos' => [['nombre' => 'Coca Cola 354 ml', 'precio' => '$2.500']]],
                ['nombre' => 'Postres', 'platos' => [['nombre' => 'Cheesecake frutos rojos', 'precio' => '$6.000']]],
            ]]),
        ]);

        return $comercio;
    }

    public function test_las_tarjetas_salen_del_marcador_declarado_no_de_la_prosa(): void
    {
        config(['services.openai.api_key' => 'test-key']);
        $comercio = $this->comercioConMenu();

        // La prosa parafrasea los nombres ("una Coca Cola", "la muzza grande"): con match
        // por texto no saldría ninguna tarjeta. El marcador los declara exactos.
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => "Te recomiendo la muzza grande con una Coca Cola bien fría.\n".
            '[[PLATOS: Muzzarella (Grande) | Coca Cola 354 ml]]',
        ]]]], 200)]);

        $service = new ChatbotService;
        $respuesta = $service->processMessage($comercio, 'algo para comer', 'sess-d1');

        // El marcador no le llega al cliente
        $this->assertStringNotContainsString('[[PLATOS', $respuesta);
        $this->assertStringContainsString('muzza grande', $respuesta);

        $platos = $service->detectarPlatosMencionados($comercio, $respuesta);
        $this->assertSame(['Muzzarella (Grande)', 'Coca Cola 354 ml'], array_column($platos, 'nombre'));
        $this->assertSame('$14.000', $platos[0]['precio']);
    }

    public function test_resuelve_un_nombre_declarado_con_prefijo_de_mas(): void
    {
        config(['services.openai.api_key' => 'test-key']);
        $comercio = $this->comercioConMenu();

        // El modelo declara "Pizza Muzzarella (Grande)"; en el menú es "Muzzarella (Grande)"
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => "Va la grande.\n[[PLATOS: Pizza Muzzarella (Grande)]]",
        ]]]], 200)]);

        $service = new ChatbotService;
        $respuesta = $service->processMessage($comercio, 'una pizza', 'sess-d6');

        $this->assertSame(
            ['Muzzarella (Grande)'],
            array_column($service->detectarPlatosMencionados($comercio, $respuesta), 'nombre')
        );
    }

    public function test_un_nombre_declarado_ambiguo_no_genera_tarjeta(): void
    {
        config(['services.openai.api_key' => 'test-key']);
        $comercio = $this->comercioConMenu();

        // "Muzzarella" sin tamaño podría ser Grande o Chica: no adivinamos
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => "Probá una muzza.\n[[PLATOS: Muzzarella]]",
        ]]]], 200)]);

        $service = new ChatbotService;
        $respuesta = $service->processMessage($comercio, 'una pizza', 'sess-d7');

        $this->assertSame([], $service->detectarPlatosMencionados($comercio, $respuesta));
    }

    public function test_un_plato_declarado_que_no_existe_en_el_menu_se_descarta(): void
    {
        config(['services.openai.api_key' => 'test-key']);
        $comercio = $this->comercioConMenu();

        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => "Probá esto.\n[[PLATOS: Muzzarella (Grande) | Ravioles de espinaca]]",
        ]]]], 200)]);

        $service = new ChatbotService;
        $respuesta = $service->processMessage($comercio, 'algo rico', 'sess-d2');
        $platos = $service->detectarPlatosMencionados($comercio, $respuesta);

        $this->assertSame(['Muzzarella (Grande)'], array_column($platos, 'nombre'));
    }

    public function test_marcador_cortado_por_max_tokens_igual_se_limpia_y_se_usa(): void
    {
        config(['services.openai.api_key' => 'test-key']);
        $comercio = $this->comercioConMenu();

        // Sin "]]" de cierre: la respuesta se cortó a mitad del marcador
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => "Una muzza grande va perfecta.\n[[PLATOS: Muzzarella (Grande)",
        ]]]], 200)]);

        $service = new ChatbotService;
        $respuesta = $service->processMessage($comercio, 'una pizza', 'sess-d3');

        $this->assertStringNotContainsString('[[PLATOS', $respuesta);
        $this->assertSame(
            ['Muzzarella (Grande)'],
            array_column($service->detectarPlatosMencionados($comercio, $respuesta), 'nombre')
        );
    }

    public function test_sin_marcador_cae_al_match_por_texto(): void
    {
        config(['services.openai.api_key' => 'test-key']);
        $comercio = $this->comercioConMenu();

        // El modelo se olvidó el marcador: tiene que seguir funcionando por texto
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => 'Te recomiendo la Muzzarella (Grande), es un clásico.',
        ]]]], 200)]);

        $service = new ChatbotService;
        $respuesta = $service->processMessage($comercio, 'una pizza', 'sess-d4');

        $this->assertSame(
            ['Muzzarella (Grande)'],
            array_column($service->detectarPlatosMencionados($comercio, $respuesta), 'nombre')
        );
    }

    public function test_el_marcador_no_se_emite_en_el_streaming(): void
    {
        config(['services.openai.api_key' => 'test-key']);
        $comercio = $this->comercioConMenu();

        // SSE de OpenAI con el marcador partido en tokens, como llega de verdad
        $tokens = ['Probá ', 'la muzza', ' grande.', "\n", '[', '[PLATOS', ': Muzzarella', ' (Grande)', ']]'];
        $sse = '';
        foreach ($tokens as $t) {
            $sse .= 'data: '.json_encode(['choices' => [['delta' => ['content' => $t]]]])."\n";
        }
        $sse .= "data: [DONE]\n";
        Http::fake(['*' => Http::response($sse, 200)]);

        $emitido = '';
        $service = new ChatbotService;
        $full = $service->streamMessage($comercio, 'una pizza', 'sess-d5', null, null, function ($d) use (&$emitido) {
            $emitido .= $d;
        });

        // Nada del marcador salió por el stream ni quedó en el texto final
        $this->assertStringNotContainsString('[', $emitido);
        $this->assertStringNotContainsString('PLATOS', $emitido);
        $this->assertSame('Probá la muzza grande.', trim($emitido));
        $this->assertStringNotContainsString('[[PLATOS', $full);

        $this->assertSame(
            ['Muzzarella (Grande)'],
            array_column($service->detectarPlatosMencionados($comercio, $full), 'nombre')
        );
    }

    public function test_un_plato_del_carrito_no_vuelve_a_salir_como_tarjeta(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        $comercio = Comercio::factory()->create();
        Menu::factory()->for($comercio)->create([
            'activo' => true,
            'procesado' => true,
            'contexto_gpt' => json_encode(['categorias' => [
                ['nombre' => 'Principales', 'platos' => [['nombre' => 'Milanesa napolitana', 'precio' => '$12.000']]],
                ['nombre' => 'Postres', 'platos' => [['nombre' => 'Flan casero', 'precio' => '$5.000']]],
            ]]),
        ]);

        $cart = Cart::create(['comercio_id' => $comercio->id, 'session_id' => 'sess-9', 'total_centavos' => 0]);
        CartItem::create(['cart_id' => $cart->id, 'nombre' => 'Milanesa napolitana', 'qty' => 1, 'price_centavos' => 1200000, 'price_text' => '$12.000']);

        Http::fake(['*' => Http::response([
            'choices' => [['message' => ['content' => 'Tu Milanesa napolitana pide un Flan casero para cerrar.']]],
        ], 200)]);

        $service = new ChatbotService;
        $respuesta = $service->processMessage($comercio, 'algo dulce', 'sess-9');
        $platos = $service->detectarPlatosMencionados($comercio, $respuesta);

        // La milanesa está en el carrito y el cliente no la nombró: solo debe salir el postre.
        $this->assertCount(1, $platos);
        $this->assertSame('Flan casero', $platos[0]['nombre']);
    }

    public function test_si_el_cliente_nombra_lo_que_ya_tiene_la_tarjeta_si_sale(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        $comercio = Comercio::factory()->create();
        Menu::factory()->for($comercio)->create([
            'activo' => true,
            'procesado' => true,
            'contexto_gpt' => json_encode(['categorias' => [
                ['nombre' => 'Principales', 'platos' => [['nombre' => 'Milanesa napolitana', 'precio' => '$12.000']]],
            ]]),
        ]);

        $cart = Cart::create(['comercio_id' => $comercio->id, 'session_id' => 'sess-10', 'total_centavos' => 0]);
        CartItem::create(['cart_id' => $cart->id, 'nombre' => 'Milanesa napolitana', 'qty' => 1, 'price_centavos' => 1200000, 'price_text' => '$12.000']);

        Http::fake(['*' => Http::response([
            'choices' => [['message' => ['content' => 'Sumamos otra Milanesa napolitana entonces.']]],
        ], 200)]);

        $service = new ChatbotService;
        $respuesta = $service->processMessage($comercio, 'quiero otra milanesa napolitana', 'sess-10');
        $platos = $service->detectarPlatosMencionados($comercio, $respuesta);

        $this->assertCount(1, $platos);
        $this->assertSame('Milanesa napolitana', $platos[0]['nombre']);
    }

    public function test_el_carrito_de_la_sesion_entra_en_el_contexto_del_prompt(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        $comercio = Comercio::factory()->create();
        Menu::factory()->for($comercio)->create([
            'activo' => true,
            'procesado' => true,
            'contexto_gpt' => json_encode(['categorias' => [['nombre' => 'Pescados', 'platos' => [
                ['nombre' => 'Lenguado', 'precio' => '$20.000'],
            ]]]]),
        ]);

        $cart = Cart::create(['comercio_id' => $comercio->id, 'session_id' => 'sess-1', 'total_centavos' => 0]);
        CartItem::create(['cart_id' => $cart->id, 'nombre' => 'Lenguado', 'qty' => 1, 'price_centavos' => 2000000, 'price_text' => '$20.000']);

        Http::fake(['*' => Http::response([
            'choices' => [['message' => ['content' => 'Un vino blanco te iría genial.']]],
        ], 200)]);

        (new ChatbotService)->processMessage($comercio, '¿qué tomo?', 'sess-1');

        Http::assertSent(function ($request) {
            $body = json_encode($request->data());

            return str_contains($body, 'YA AGREG') && str_contains($body, 'Lenguado');
        });
    }
}
