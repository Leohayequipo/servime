<?php

namespace Tests\Feature;

use App\Models\Comanda;
use App\Models\Comercio;
use App\Models\Mesa;
use App\Services\ComandaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El comando que cierra las mesas que nadie cerró.
 *
 * Sin esto, la mesa que se fue a las 23 sigue figurando ocupada al día siguiente y el
 * mozo del turno nuevo abre la pantalla y ve el salón lleno. Con eso deja de confiar en
 * la pantalla, que es lo único que no se puede permitir.
 */
class CerrarComandasInactivasTest extends TestCase
{
    use RefreshDatabase;

    private function comandaDeHace(int $horas, int $numeroMesa): Comanda
    {
        $comercio = Comercio::factory()->create();
        $mesa = Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => $numeroMesa]);

        $comanda = app(ComandaService::class)->abrirOTomar($mesa);
        $comanda->update(['abierta_at' => now()->subHours($horas)]);

        return $comanda;
    }

    public function test_cierra_las_que_llevan_horas_sin_pedidos(): void
    {
        $vieja = $this->comandaDeHace(5, 1);

        $this->artisan('comandas:cerrar-inactivas')
            ->expectsOutputToContain('Se cerraron 1 comanda(s)')
            ->assertSuccessful();

        $this->assertSame(Comanda::CERRADA, $vieja->fresh()->estado);
        $this->assertSame('auto', $vieja->fresh()->cerrada_por);
    }

    public function test_no_toca_las_mesas_que_estan_comiendo(): void
    {
        $reciente = $this->comandaDeHace(1, 1);

        $this->artisan('comandas:cerrar-inactivas')
            ->expectsOutputToContain('No había comandas')
            ->assertSuccessful();

        $this->assertSame(Comanda::ABIERTA, $reciente->fresh()->estado);
    }

    public function test_el_umbral_se_puede_ajustar_por_parametro(): void
    {
        $comanda = $this->comandaDeHace(2, 1);

        $this->artisan('comandas:cerrar-inactivas', ['--horas' => 1])->assertSuccessful();

        $this->assertSame(Comanda::CERRADA, $comanda->fresh()->estado);
    }

    public function test_correrlo_dos_veces_no_rompe(): void
    {
        $this->comandaDeHace(5, 1);

        $this->artisan('comandas:cerrar-inactivas')->assertSuccessful();
        $this->artisan('comandas:cerrar-inactivas')
            ->expectsOutputToContain('No había comandas')
            ->assertSuccessful();
    }

    public function test_al_cerrarse_sola_la_mesa_queda_libre(): void
    {
        $comanda = $this->comandaDeHace(5, 1);
        $mesa = $comanda->mesa;

        $this->artisan('comandas:cerrar-inactivas')->assertSuccessful();

        // La mesa tiene que poder abrir una comanda nueva: si `mesa_abierta_id` quedara
        // apuntando, el índice único bloquearía al próximo grupo que se siente.
        $nueva = app(ComandaService::class)->abrirOTomar($mesa);

        $this->assertNotSame($comanda->id, $nueva->id);
        $this->assertSame(Comanda::ABIERTA, $nueva->estado);
    }
}
