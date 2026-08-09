<?php

namespace Tests\Feature;

use App\Models\Comercio;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Abierto ahora" del menú público.
 *
 * Antes era un cartel fijo que decía que el local estaba abierto a cualquier
 * hora. Ahora sale de `horarios` (estructurado), y si no hay dato no se
 * muestra nada — no se inventa.
 */
class HorariosTest extends TestCase
{
    use RefreshDatabase;

    private function comercio(array $horarios): Comercio
    {
        return Comercio::factory()->create(['horarios' => $horarios]);
    }

    public function test_sin_horarios_cargados_devuelve_null(): void
    {
        // null y no false: la vista distingue "cerrado" de "no sé".
        $this->assertNull($this->comercio([])->estaAbierto());
        $this->assertNull(Comercio::factory()->create(['horarios' => null])->estaAbierto());
    }

    public function test_dentro_y_fuera_del_turno(): void
    {
        $c = $this->comercio(['1' => [['11:00', '20:00']]]);

        $this->assertTrue($c->estaAbierto(Carbon::parse('2026-08-10 12:00')));   // lunes
        $this->assertFalse($c->estaAbierto(Carbon::parse('2026-08-10 09:00')));
        $this->assertFalse($c->estaAbierto(Carbon::parse('2026-08-10 21:00')));
        $this->assertFalse($c->estaAbierto(Carbon::parse('2026-08-11 12:00')));  // martes: cerrado
    }

    public function test_turno_que_cruza_la_medianoche(): void
    {
        // "Lunes a viernes de 19 a 2AM" — uno de los horarios reales cargados.
        $c = $this->comercio(['1' => [['19:00', '02:00']]]);

        $this->assertTrue($c->estaAbierto(Carbon::parse('2026-08-10 20:00')), 'lunes de noche');
        $this->assertTrue($c->estaAbierto(Carbon::parse('2026-08-11 01:00')), 'martes 1AM sigue siendo el turno del lunes');
        $this->assertFalse($c->estaAbierto(Carbon::parse('2026-08-11 03:00')), 'martes 3AM ya cerró');
    }

    public function test_turno_partido(): void
    {
        $c = $this->comercio(['6' => [['12:00', '15:00'], ['20:00', '23:30']]]);

        $this->assertTrue($c->estaAbierto(Carbon::parse('2026-08-15 13:00')));
        $this->assertFalse($c->estaAbierto(Carbon::parse('2026-08-15 17:00')), 'entre turnos');
        $this->assertTrue($c->estaAbierto(Carbon::parse('2026-08-15 22:00')));
    }

    public function test_proxima_apertura(): void
    {
        $c = $this->comercio(['1' => [['11:00', '20:00']], '2' => [['11:00', '20:00']]]);

        $this->assertSame('11:00', $c->proximaApertura(Carbon::parse('2026-08-10 09:00')), 'mismo día: sólo la hora');
        $this->assertSame('Martes 11:00', $c->proximaApertura(Carbon::parse('2026-08-10 21:00')), 'otro día: con el día');
    }

    public function test_el_menu_publico_no_muestra_estado_sin_horarios(): void
    {
        $c = Comercio::factory()->create(['horarios' => null]);

        $this->get(route('menu.public', $c))
            ->assertOk()
            ->assertDontSee('ABIERTO AHORA');
    }
}
