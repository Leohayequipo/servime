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
