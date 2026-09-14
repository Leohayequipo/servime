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

    public function test_sin_personalidad_elegida_la_pantalla_marca_la_que_ya_usa_el_chat(): void
    {
        // Antes aparecían todos los chips sin marcar y parecía que el asistente no tenía
        // personalidad, cuando en realidad hablaba con la del prompt base.
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create(['chatbot_config' => null]);
        $menu = Menu::factory()->for($comercio)->create();

        $html = $this->actingAs($user)
            ->get(route('menus.edit', [$comercio, $menu]))
            ->assertOk()
            ->getContent();

        foreach (['tono', 'trato', 'emojis'] as $campo) {
            $valor = Comercio::PERSONALIDAD_POR_DEFECTO[$campo];
            $this->assertMatchesRegularExpression(
                '/name="cfg_'.$campo.'" value="'.$valor.'"[^>]*checked/',
                $html,
                "El chip de {$campo} por defecto ({$valor}) no aparece marcado."
            );
        }
    }

    public function test_si_eligio_una_personalidad_se_marca_la_suya_y_no_la_de_defecto(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create([
            'chatbot_config' => ['tono' => 'canchero', 'trato' => 'tuteo', 'emojis' => 'ninguno'],
        ]);
        $menu = Menu::factory()->for($comercio)->create();

        $html = $this->actingAs($user)
            ->get(route('menus.edit', [$comercio, $menu]))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/name="cfg_tono" value="canchero"[^>]*checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/name="cfg_tono" value="calido_atento"[^>]*checked/', $html);
        $this->assertMatchesRegularExpression('/name="cfg_trato" value="tuteo"[^>]*checked/', $html);
        $this->assertMatchesRegularExpression('/name="cfg_emojis" value="ninguno"[^>]*checked/', $html);
    }

    public function test_la_personalidad_por_defecto_es_la_del_prompt_base(): void
    {
        // Marcar estos chips "no cambia nada" SÓLO si el prompt base habla así. Si alguien
        // reescribe el prompt (lo vuelve formal, le saca el voseo o los emojis), este test
        // avisa que la pantalla quedó mostrando una personalidad que el chat ya no tiene.
        $comercio = Comercio::factory()->create(['chatbot_config' => null]);

        $servicio = new ChatbotService;
        $metodo = new \ReflectionMethod($servicio, 'promptSistema');
        $metodo->setAccessible(true);
        $prompt = $metodo->invoke($servicio, $comercio, '');

        $this->assertSame(
            ['tono' => 'calido_atento', 'trato' => 'voseo', 'emojis' => 'moderado'],
            Comercio::PERSONALIDAD_POR_DEFECTO
        );
        $this->assertStringContainsString('cálida', $prompt, 'tono: el prompt base ya no es cálido');
        $this->assertMatchesRegularExpression('/\bSos\b/', $prompt, 'trato: el prompt base ya no está en voseo');
        $this->assertStringContainsString('con moderación', $prompt, 'emojis: el prompt base ya no los pide con moderación');

        // Y sin personalidad elegida no se agrega ninguna indicación: manda el prompt base.
        $this->assertStringNotContainsString('Hablá en un tono', $this->indicaciones($comercio));
    }
}
