<?php

namespace Tests\Feature;

use App\Http\Controllers\MenuController;
use App\Models\Comercio;
use App\Models\Menu;
use App\Models\User;
use App\Services\ChatbotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La estrella vive en el PLATO, dentro del JSON del menú.
 *
 * Antes era una lista de nombres sueltos en `comercios.chatbot_config`: renombrar un plato
 * dejaba la estrella apuntando a algo que ya no existía, y el prompt le pedía al modelo
 * recomendar un plato que no estaba en la carta.
 */
class PlatosEstrellaTest extends TestCase
{
    use RefreshDatabase;

    private function menu(Comercio $comercio): Menu
    {
        return Menu::factory()->for($comercio)->activo()->create([
            'contexto_gpt' => json_encode(['categorias' => [[
                'nombre' => 'Principales',
                'platos' => [
                    ['nombre' => 'Milanesa'],
                    ['nombre' => 'Pizza'],
                    ['nombre' => 'Empanada'],
                    ['nombre' => 'Ensalada'],
                    ['nombre' => 'Lomito'],
                    ['nombre' => 'Hamburguesa'],
                    ['nombre' => 'Tarta'],
                ],
            ]]]),
        ]);
    }

    private function marcar(User $user, Comercio $comercio, Menu $menu, int $plato, bool $estrella = true)
    {
        return $this->actingAs($user)->postJson(
            route('menus.update.plato.estrella', [$comercio, $menu]),
            ['estrella' => $estrella, 'categoria_index' => 0, 'plato_index' => $plato]
        );
    }

    public function test_marca_y_desmarca_un_plato_estrella(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create();
        $menu = $this->menu($comercio);

        $this->marcar($user, $comercio, $menu, 0)->assertOk()->assertJson(['estrella' => true, 'total' => 1]);
        $this->assertSame(['Milanesa'], (new ChatbotService)->platosEstrella($comercio->fresh()));

        $this->marcar($user, $comercio, $menu, 0, false)->assertOk()->assertJson(['estrella' => false, 'total' => 0]);
        $this->assertSame([], (new ChatbotService)->platosEstrella($comercio->fresh()));
    }

    public function test_la_estrella_viaja_con_el_plato_al_renombrarlo(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create();
        $menu = $this->menu($comercio);

        $this->marcar($user, $comercio, $menu, 0)->assertOk();

        $this->actingAs($user)->postJson(route('menus.update.plato.nombre', [$comercio, $menu]), [
            'nombre' => 'Milanesa Napolitana',
            'categoria_index' => 0,
            'plato_index' => 0,
        ])->assertOk();

        // Con la lista de nombres vieja, acá quedaba apuntando a "Milanesa" y el prompt
        // mandaba a recomendar un plato inexistente.
        $this->assertSame(['Milanesa Napolitana'], (new ChatbotService)->platosEstrella($comercio->fresh()));
    }

    public function test_no_deja_pasar_del_maximo(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create();
        $menu = $this->menu($comercio);

        for ($i = 0; $i < MenuController::MAX_ESTRELLAS; $i++) {
            $this->marcar($user, $comercio, $menu, $i)->assertOk();
        }

        // Marcarlos todos equivale a no marcar ninguno, y suma tokens al prompt de cada consulta.
        $this->marcar($user, $comercio, $menu, MenuController::MAX_ESTRELLAS)
            ->assertStatus(422)
            ->assertJsonPath('error', fn ($e) => str_contains($e, (string) MenuController::MAX_ESTRELLAS));

        $this->assertCount(MenuController::MAX_ESTRELLAS, (new ChatbotService)->platosEstrella($comercio->fresh()));
    }

    public function test_las_estrellas_entran_en_el_prompt(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create();
        $menu = $this->menu($comercio);
        $this->marcar($user, $comercio, $menu, 1)->assertOk();

        $servicio = new ChatbotService;
        $metodo = new \ReflectionMethod($servicio, 'indicacionesLocal');
        $metodo->setAccessible(true);

        $this->assertStringContainsString('platos estrella: Pizza.', $metodo->invoke($servicio, $comercio->fresh()));
    }

    public function test_un_comercio_ajeno_no_puede_marcar(): void
    {
        $duenio = User::factory()->create();
        $comercio = Comercio::factory()->for($duenio)->create();
        $menu = $this->menu($comercio);

        $this->marcar(User::factory()->create(), $comercio, $menu, 0)->assertStatus(403);
        $this->assertSame([], (new ChatbotService)->platosEstrella($comercio->fresh()));
    }

    public function test_rechaza_indices_que_no_existen(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create();
        $menu = $this->menu($comercio);

        $this->marcar($user, $comercio, $menu, 99)->assertStatus(400);
    }
}
