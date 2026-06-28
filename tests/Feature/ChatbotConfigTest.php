<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Menu;
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
        $menu = Menu::factory()->for($comercio)->create();

        $this->actingAs($user)
            ->postJson(route('menus.update.chatbot.config', [$comercio, $menu]), [
                'chatbot_config' => [
                    'tono' => 'canchero',
                    'trato' => 'voseo',
                    'emojis' => 'moderado',
                    'focos' => ['promos', 'bebidas'],
                    'estrellas' => ['Barrica Burger'],
                    'detalles' => 'Cervecería artesanal',
                ],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

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
        $menu = Menu::factory()->for($comercio)->create();

        $this->actingAs($user)
            ->postJson(route('menus.update.chatbot.config', [$comercio, $menu]), [
                'chatbot_config' => ['tono' => 'hacker', 'focos' => ['cualquiera']],
            ])
            ->assertStatus(422);
    }
}
