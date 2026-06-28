<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_guarda_la_personalidad_del_asistente(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create();

        $this->actingAs($user)->put(route('comercios.update', $comercio), $this->payloadBase($comercio) + [
            'chatbot_config' => [
                'tono' => 'canchero',
                'trato' => 'voseo',
                'emojis' => 'moderado',
                'focos' => ['promos', 'bebidas'],
                'estrellas' => ['Barrica Burger'],
                'detalles' => 'Cervecería artesanal',
            ],
        ])->assertRedirect();

        $cfg = $comercio->fresh()->chatbot_config;
        $this->assertSame('canchero', $cfg['tono']);
        $this->assertSame('voseo', $cfg['trato']);
        $this->assertContains('promos', $cfg['focos']);
        $this->assertContains('Barrica Burger', $cfg['estrellas']);
    }

    public function test_rechaza_valores_de_personalidad_invalidos(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create();

        $this->actingAs($user)->put(route('comercios.update', $comercio), $this->payloadBase($comercio) + [
            'chatbot_config' => ['tono' => 'hacker', 'focos' => ['cualquiera']],
        ])->assertSessionHasErrors(['chatbot_config.tono', 'chatbot_config.focos.0']);
    }

    /**
     * Campos que el form de editar comercio envía siempre (visibles + ocultos).
     */
    private function payloadBase(Comercio $comercio): array
    {
        return [
            'nombre' => $comercio->nombre,
            'descripcion' => '',
            'direccion' => '',
            'horario' => '',
            'primary_color' => '#000000',
            'secondary_color' => '#000000',
            'accent_color' => '#000000',
            'background_color' => '#000000',
            'welcome_message' => '',
            'menu_description' => '',
            'contact_info' => '',
            'chatbot_name' => 'Bot',
            'show_contact_info' => '1',
            'enable_ai_responses' => '1',
            'page_title' => '',
            'page_description' => '',
            'favicon_url' => '',
            'pedido_habilitado' => '0',
        ];
    }
}
