<?php

namespace Tests\Feature;

use App\Models\SiteVisit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContadorVisitantesTest extends TestCase
{
    use RefreshDatabase;

    private function visita(string $visitorId, bool $humano): void
    {
        SiteVisit::create(['visitor_id' => $visitorId, 'path' => '/', 'source' => 'directo', 'humano' => $humano]);
    }

    public function test_el_hero_muestra_los_visitantes_unicos_contando_bots(): void
    {
        $this->visita('persona-1', true);
        $this->visita('persona-1', true);   // volvió: sigue siendo un visitante
        $this->visita('persona-2', true);
        $this->visita('bot-1', false);      // los bots entran a este número a propósito

        $this->assertSame(3, SiteVisit::stats()['visitantes']);

        $this->get('/')->assertOk()->assertSee('visitantes ya pasaron por ServiME');
    }

    public function test_sin_visitas_el_hero_no_muestra_el_contador(): void
    {
        $this->get('/')->assertOk()->assertDontSee('visitantes ya pasaron por ServiME');
    }

    public function test_el_numero_sale_con_separador_de_miles(): void
    {
        SiteVisit::insert(array_map(fn ($i) => [
            'visitor_id' => "v-{$i}", 'path' => '/', 'source' => 'directo',
            'humano' => $i % 2 === 0, 'created_at' => now(), 'updated_at' => now(),
        ], range(1, 1200)));

        $this->get('/')->assertOk()->assertSee('1.200');
    }
}
