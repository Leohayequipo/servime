<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Menu;
use App\Services\ChatbotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
