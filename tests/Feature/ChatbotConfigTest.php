<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Menu;
use App\Models\User;
use App\Services\ChatbotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotConfigTest extends TestCase
{
    use RefreshDatabase;

    private function indicaciones(Comercio $comercio): string
    {
        $servicio = new ChatbotService;
        $metodo = new \ReflectionMethod($servicio, 'indicacionesLocal');
        $metodo->setAccessible(true);

        return $metodo->invoke($servicio, $comercio);
    }

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

    public function test_vegano_y_sin_tacc_son_focos_distintos(): void
    {
        // Un menú real trae una categoría "CELIACOS" con bife de chorizo y lomo: son sin
        // TACC y no tienen nada de vegano. Mientras fue un solo foco ("veganas o sin TACC"),
        // el prompt habilitaba al asistente a cruzarlas, y son información de salud.
        $comercio = Comercio::factory()->create(['chatbot_config' => ['focos' => ['sin_tacc']]]);

        $indicaciones = $this->indicaciones($comercio);

        $this->assertStringContainsString('opciones sin TACC (aptas celíacos)', $indicaciones);
        $this->assertStringNotContainsString('vegan', $indicaciones);
    }

    public function test_el_foco_vegano_no_promete_sin_tacc(): void
    {
        $comercio = Comercio::factory()->create(['chatbot_config' => ['focos' => ['veganas']]]);

        $indicaciones = $this->indicaciones($comercio);

        $this->assertStringContainsString('opciones veganas o vegetarianas', $indicaciones);
        $this->assertStringNotContainsString('TACC', $indicaciones);
    }

    public function test_la_pantalla_ofrece_los_focos_de_la_constante(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create();
        $menu = Menu::factory()->for($comercio)->create();

        $html = $this->actingAs($user)
            ->get(route('menus.edit', [$comercio, $menu]))
            ->assertOk()
            ->getContent();

        // La pantalla, la validación y el prompt salen todos de ChatbotService::FOCOS: si
        // alguien agrega uno, no puede quedar fuera de la pantalla ni del `in:` del request.
        foreach (ChatbotService::FOCOS as $clave => $foco) {
            $this->assertStringContainsString('value="'.$clave.'"', $html);
            $this->assertStringContainsString($foco['label'], $html);
        }
    }

    public function test_la_validacion_acepta_todos_los_focos_de_la_constante(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create();
        $menu = Menu::factory()->for($comercio)->create();

        $this->actingAs($user)
            ->postJson(route('menus.update.chatbot.config', [$comercio, $menu]), [
                'chatbot_config' => ['focos' => array_keys(ChatbotService::FOCOS)],
            ])
            ->assertOk();

        $this->assertSame(array_keys(ChatbotService::FOCOS), $comercio->fresh()->chatbot_config['focos']);
    }
}
