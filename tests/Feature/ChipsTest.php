<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Menu;
use App\Models\Plan;
use App\Models\User;
use App\Support\Chip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Atajos configurables por comercio, para la portada y para el chat.
 *
 * Antes los del chat eran cuatro frases fijas iguales para todos, y los de la portada salían
 * automáticos del menú sin que el comercio pudiera tocarlos: la regla los ordena como venían
 * en el PDF, así que aparecían "Adicionales" antes que las hamburguesas.
 */
class ChipsTest extends TestCase
{
    use RefreshDatabase;

    private function comercioConPlan(User $user, array $features): Comercio
    {
        $plan = Plan::create([
            'key' => 'test-'.uniqid(), 'nombre' => 'Test', 'mensajes_ia_mes' => 100,
            'modelo_ia' => 'gpt-4o-mini', 'max_tokens_chat' => 600,
            'features' => $features, 'activo' => true,
        ]);

        return Comercio::factory()->for($user)->create(['plan_id' => $plan->id]);
    }

    private function comercioConAnalytics(User $user): Comercio
    {
        return $this->comercioConPlan($user, ['analytics']);
    }

    private function guardar(User $user, Comercio $comercio, string $superficie, array $chips)
    {
        return $this->actingAs($user)->postJson(
            route('menus.update.chips', $comercio),
            ['superficie' => $superficie, 'chips' => $chips]
        );
    }

    public function test_el_id_sale_del_contenido_y_editar_genera_uno_nuevo(): void
    {
        // Es lo que hace comparable la analítica: si un mismo id acumulara varios textos en
        // la misma semana, el total no diría cuál versión funcionó mejor.
        $original = Chip::id('Algo dulce', '¿qué postres tienen?');
        $editado = Chip::id('Postres para cerrar', '¿qué postres tienen?');

        $this->assertNotSame($original, $editado);

        // Y si vuelve al texto anterior, vuelve el id anterior: la serie se reanuda sola.
        $this->assertSame($original, Chip::id('Algo dulce', '¿qué postres tienen?'));
    }

    public function test_el_emoji_no_corta_la_serie(): void
    {
        // El emoji es presentación: cambiarlo no debería arrancar una medición de cero.
        $chipA = Chip::normalizar(['emoji' => '🍰', 'label' => 'Algo dulce', 'q' => '¿postres?']);
        $chipB = Chip::normalizar(['emoji' => '🧁', 'label' => 'Algo dulce', 'q' => '¿postres?']);

        $this->assertSame($chipA['id'], $chipB['id']);
    }

    public function test_guarda_los_atajos_del_comercio(): void
    {
        $user = User::factory()->create();
        $comercio = $this->comercioConAnalytics($user);

        $this->guardar($user, $comercio, 'chat', [
            ['emoji' => '🥐', 'label' => 'Meriendas para compartir', 'q' => '¿qué tienen para merendar entre dos?'],
        ])->assertOk()->assertJsonPath('automaticos', false);

        $chips = $comercio->fresh()->chipsDe('chat');
        $this->assertCount(1, $chips);
        $this->assertSame('Meriendas para compartir', $chips[0]['label']);
        $this->assertSame('¿qué tienen para merendar entre dos?', $chips[0]['q']);
    }

    public function test_sin_atajos_propios_caen_los_automaticos_del_menu(): void
    {
        $user = User::factory()->create();
        $comercio = $this->comercioConAnalytics($user);
        Menu::factory()->for($comercio)->activo()->create([
            'contexto_gpt' => json_encode(['categorias' => [
                ['nombre' => 'Pizzas', 'platos' => [['nombre' => 'Muzzarella']]],
            ]]),
        ]);

        // Nadie se queda sin atajos por no haber entrado nunca a configurarlos.
        $this->assertNotEmpty($comercio->chipsDe('chat'));
    }

    public function test_vaciar_la_lista_vuelve_a_los_automaticos(): void
    {
        $user = User::factory()->create();
        $comercio = $this->comercioConAnalytics($user);
        $this->guardar($user, $comercio, 'chat', [['emoji' => '🥐', 'label' => 'Meriendas', 'q' => '¿qué hay?']])->assertOk();

        $this->guardar($user, $comercio, 'chat', [])->assertOk()->assertJsonPath('automaticos', true);

        $this->assertNull($comercio->fresh()->chips['chat'] ?? null);
    }

    public function test_las_dos_superficies_son_independientes(): void
    {
        $user = User::factory()->create();
        $comercio = $this->comercioConAnalytics($user);

        $this->guardar($user, $comercio, 'chat', [['emoji' => '🥐', 'label' => 'Del chat', 'q' => '¿del chat?']])->assertOk();

        $fresco = $comercio->fresh();
        $this->assertSame('Del chat', $fresco->chipsDe('chat')[0]['label']);
        $this->assertNull($fresco->chips['portada'] ?? null, 'la portada sigue en automático');
    }

    public function test_descarta_los_atajos_a_medias_y_aplica_el_tope(): void
    {
        // Sin frase el botón no dice nada; sin pregunta no hace nada.
        $this->assertNull(Chip::normalizar(['label' => 'Solo texto', 'q' => '']));
        $this->assertNull(Chip::normalizar(['label' => '', 'q' => '¿solo pregunta?']));

        // Repetidos: mismo texto y misma pregunta son el mismo chip.
        $this->assertCount(1, Chip::normalizarLista([
            ['label' => 'Algo dulce', 'q' => '¿postres?'],
            ['label' => 'Algo dulce', 'q' => '¿postres?'],
        ]));
    }

    public function test_hay_un_tope_de_atajos_por_pantalla(): void
    {
        // El tope no lo pone el layout —los dos se acomodan solos— sino la atención: más de
        // seis botones dejan de leerse de un vistazo y diluyen la medición de cada uno.
        $user = User::factory()->create();
        $comercio = $this->comercioConAnalytics($user);

        $justos = $pasados = [];
        for ($i = 0; $i < Chip::MAXIMO; $i++) {
            $justos[] = ['label' => "Atajo $i", 'q' => "¿pregunta $i?"];
        }
        $pasados = array_merge($justos, [['label' => 'Uno de más', 'q' => '¿y este?']]);

        foreach (Chip::SUPERFICIES as $superficie) {
            $this->guardar($user, $comercio, $superficie, $justos)->assertOk();
            $this->guardar($user, $comercio, $superficie, $pasados)->assertStatus(422);
        }
    }

    public function test_sin_la_feature_analytics_no_se_pueden_administrar(): void
    {
        $user = User::factory()->create();
        $comercio = $this->comercioConPlan($user, []);

        $this->guardar($user, $comercio, 'chat', [['emoji' => '🥐', 'label' => 'X', 'q' => '¿y?']])
            ->assertForbidden();
    }

    public function test_un_comercio_ajeno_no_puede_tocarlos(): void
    {
        $duenio = User::factory()->create();
        $comercio = $this->comercioConAnalytics($duenio);

        $this->guardar(User::factory()->create(), $comercio, 'chat', [['label' => 'X', 'q' => '¿y?']])
            ->assertStatus(403);
    }
}
