<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * En el listado de cartas tiene que verse a primera vista cuál ven los clientes.
 *
 * Antes todas las tarjetas decían "MENU", todas tenían el mismo ✓ verde en la foto y la
 * activa sólo se distinguía por una pastillita "Activo".
 */
class CartaEnUsoTest extends TestCase
{
    use RefreshDatabase;

    private function listado(Comercio $comercio): string
    {
        return $this->actingAs($comercio->user)
            ->get(route('menus.index', $comercio))
            ->assertOk()
            ->getContent();
    }

    public function test_la_carta_activa_va_primera_y_marcada_como_en_uso(): void
    {
        $comercio = Comercio::factory()->for(User::factory())->create();
        Menu::factory()->for($comercio)->create(['nombre' => 'Carta vieja', 'procesado' => true, 'activo' => true, 'created_at' => now()->subDay()]);
        Menu::factory()->for($comercio)->create(['nombre' => 'Carta nueva', 'procesado' => true, 'activo' => false]);

        $html = $this->listado($comercio);

        // La activa primero, aunque sea la más vieja
        $this->assertLessThan(strpos($html, 'Carta nueva'), strpos($html, 'Carta vieja'));
        $this->assertSame(1, substr_count($html, 'Es la carta que ven tus clientes'));
        $this->assertStringContainsString('Tus clientes no la ven.', $html);
        $this->assertStringNotContainsString('No hay ninguna activada', $html);
    }

    public function test_sin_ninguna_activa_marca_la_que_igual_ven_los_clientes(): void
    {
        // menuVigente: sin activa, el público muestra la última procesada
        $comercio = Comercio::factory()->for(User::factory())->create();
        Menu::factory()->for($comercio)->create(['nombre' => 'Carta vieja', 'procesado' => true, 'activo' => false, 'created_at' => now()->subDay()]);
        Menu::factory()->for($comercio)->create(['nombre' => 'Carta nueva', 'procesado' => true, 'activo' => false]);

        $html = $this->listado($comercio);

        $this->assertSame(1, substr_count($html, 'Es la carta que ven tus clientes'));
        $this->assertStringContainsString('No hay ninguna activada', $html);
        $this->assertLessThan(strpos($html, 'Carta vieja'), strpos($html, 'Carta nueva'));
    }
}
