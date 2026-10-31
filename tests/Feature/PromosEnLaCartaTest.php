<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Menu;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Las promos en la carta en lista.
 *
 * El panel las guarda como la ÚLTIMA categoría del menú, y la carta las mostraba ahí: después
 * de veinte categorías, sin nada que las distinga. Para un comercio sin asistente (Free,
 * Mínimo) es lo único que ve el cliente, así que las promos que cargaba no las veía nadie.
 */
class PromosEnLaCartaTest extends TestCase
{
    use RefreshDatabase;

    // Las promos al final, como las deja MenuController::savePromociones().
    private const CARTA = '{"categorias":[
        {"nombre":"Pizzas","platos":[{"nombre":"Muzzarella","precio":"$8.900"}]},
        {"nombre":"Empanadas","platos":[{"nombre":"De carne","precio":"$1.200"}]},
        {"nombre":"Promociones del Día","platos":[{"id":1,"nombre":"2x1 en pintas","precio":"6000"}]}
    ]}';

    private const CARTA_SIN_PROMOS = '{"categorias":[
        {"nombre":"Pizzas","platos":[{"nombre":"Muzzarella","precio":"$8.900"}]}
    ]}';

    private function comercio(string $plan = 'free', string $carta = self::CARTA): Comercio
    {
        $comercio = Comercio::factory()->for(User::factory()->create(['role' => 'user']))->create([
            'plan_id' => Plan::where('key', $plan)->value('id'),
            'enable_ai_responses' => true,
        ]);

        Menu::factory()->create([
            'comercio_id' => $comercio->id,
            'activo' => true,
            'procesado' => true,
            'contexto_gpt' => $carta,
            'archivo_original' => null,
        ]);

        return $comercio;
    }

    public function test_las_promos_van_primero_en_la_carta_aunque_esten_guardadas_al_final(): void
    {
        $comercio = $this->comercio();

        $this->get(route('menu.classic', $comercio))
            ->assertOk()
            ->assertSeeInOrder([
                'data-categoria="Promociones del Día"',
                '2x1 en pintas',
                'data-categoria="Pizzas"',
                'data-categoria="Empanadas"',
            ], false);
    }

    public function test_el_atajo_de_promos_va_primero_y_marcado(): void
    {
        $comercio = $this->comercio();

        $this->get(route('menu.classic', $comercio))
            ->assertOk()
            ->assertSeeInOrder([
                'data-cat="">Todo',
                'class="chip-cat promo" data-cat="Promociones del Día">🔥 Promociones del Día',
                'class="chip-cat" data-cat="Pizzas"',
            ], false)
            ->assertSee('🔥 Promociones del Día</h2>', false);
    }

    public function test_sin_promos_la_carta_queda_como_estaba(): void
    {
        $comercio = $this->comercio(carta: self::CARTA_SIN_PROMOS);

        $this->get(route('menu.classic', $comercio))
            ->assertOk()
            ->assertSee('Muzzarella')
            ->assertDontSee('chip-cat promo', false)
            ->assertDontSee('🔥');
    }

    /**
     * El panel le decía a todos "aparecen en el menú del chat". Un comercio sin asistente no
     * tiene chat: se le dice dónde las van a ver sus clientes de verdad.
     */
    public function test_el_panel_no_le_promete_el_chat_a_un_comercio_sin_asistente(): void
    {
        $comercio = $this->comercio('free');

        $this->actingAs($comercio->user)
            ->get(route('menus.edit', [$comercio, $comercio->menus()->first()]))
            ->assertOk()
            ->assertSee('Aparecen arriba de todo en la carta')
            ->assertDontSee('En el chat aparecen');
    }

    public function test_con_asistente_el_panel_nombra_el_chat_y_la_carta(): void
    {
        $comercio = $this->comercio('pro');

        $this->actingAs($comercio->user)
            ->get(route('menus.edit', [$comercio, $comercio->menus()->first()]))
            ->assertOk()
            ->assertSee('En el chat aparecen como la primera categoría');
    }
}
