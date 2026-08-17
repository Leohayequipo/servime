<?php

namespace Tests\Feature;

use App\Models\Comercio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Una portada "lista" en la base cuyos archivos no están en el disco.
 *
 * No es un caso hipotético: pasó en producción el 15/08/2026, cuando el `rm -rf` del
 * deploy borró public_html/storage y la copia de vuelta salió incompleta.
 *
 * Lo que se cuida acá es que el menú público NO pida archivos que no existen. La vista
 * `menu.portada` emite tres <img> (fondo, hero, thumb): son tres 404 por visita, de cada
 * cliente. Como todos los clientes de un local salen por el mismo wifi, varias mesas
 * abriendo un menú roto parecen una sola IP escaneando el servidor, y un fail2ban que
 * mire HTTP puede dejar al local entero afuera de su propio menú.
 */
class PortadaSinArchivosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function comercioConPortada(): Comercio
    {
        return Comercio::factory()->create([
            'portada_estado' => 'listo',
            'portada_url' => 'comercios/7/portada/original.jpg',
            'portada_fondo_url' => 'comercios/7/portada/fondo.webp',
            'portada_hero_url' => 'comercios/7/portada/hero.webp',
            'portada_thumb_url' => 'comercios/7/portada/thumb.webp',
            // Un tema completo: la vista indexa velo y superficies sin chequear, así que
            // con las claves a medias no llega ni a renderizar (y no es lo que se prueba acá).
            'tema_derivado' => [
                'base' => '#3B2A16',
                'velo' => [
                    'centro' => 'rgba(74,53,28,.30)', 'halo' => 'rgba(59,42,22,.58)',
                    'arriba' => 'rgba(59,42,22,.72)', 'medio' => 'rgba(49,35,18,.66)',
                    'abajo' => 'rgba(38,27,14,.84)',
                ],
                'superficies' => [
                    'fondo' => 'rgba(59,42,22,.55)', 'hover' => 'rgba(74,53,28,.65)',
                    'borde' => 'rgba(242,231,213,.10)', 'borde_arriba' => 'rgba(242,231,213,.18)',
                ],
            ],
        ]);
    }

    private function ponerArchivos(Comercio $c, array $rutas): void
    {
        foreach ($rutas as $ruta) {
            Storage::disk('public')->put($ruta, 'imagen');
        }
    }

    public function test_con_los_tres_archivos_usa_la_portada(): void
    {
        $comercio = $this->comercioConPortada();
        $this->ponerArchivos($comercio, [
            $comercio->portada_fondo_url,
            $comercio->portada_hero_url,
            $comercio->portada_thumb_url,
        ]);

        $this->assertTrue($comercio->tienePortada());
        $this->get("/menu/{$comercio->slug}")->assertOk()->assertSee('hero.webp', false);
    }

    public function test_sin_los_archivos_no_pide_ninguna_imagen(): void
    {
        // La base dice "listo" y el disco está vacío: es el caso de producción.
        $comercio = $this->comercioConPortada();

        $this->assertFalse($comercio->tienePortada());

        $this->get("/menu/{$comercio->slug}")
            ->assertOk()
            ->assertDontSee('hero.webp', false)
            ->assertDontSee('fondo.webp', false)
            ->assertDontSee('thumb.webp', false);
    }

    public function test_falta_uno_solo_y_tambien_se_cae_al_disenio_sin_portada(): void
    {
        // Con el thumb faltante, hero y fondo cargarían — pero ese 404 igual se emite en
        // cada visita. Es una sola URL rota, y una sola alcanza para la ráfaga.
        $comercio = $this->comercioConPortada();
        $this->ponerArchivos($comercio, [
            $comercio->portada_fondo_url,
            $comercio->portada_hero_url,
        ]);

        $this->assertFalse($comercio->tienePortada());
        $this->get("/menu/{$comercio->slug}")->assertOk()->assertDontSee('thumb.webp', false);
    }

    public function test_el_menu_sigue_funcionando_sin_portada(): void
    {
        // Degradar no puede significar romper: la pantalla de elección tiene que seguir
        // ofreciendo las dos puertas al menú.
        $comercio = $this->comercioConPortada();

        $this->get("/menu/{$comercio->slug}")
            ->assertOk()
            ->assertSee(route('menu.chat', $comercio), false)
            ->assertSee(route('menu.classic', $comercio), false);
    }

    public function test_no_repite_la_consulta_al_disco_en_la_misma_visita(): void
    {
        // tienePortada() se llama más de una vez por request; sin memoria serían tres
        // stat() por llamada en la pantalla que más tráfico tiene del sistema.
        $comercio = $this->comercioConPortada();

        $this->assertFalse($comercio->tienePortada());
        $this->assertFalse($comercio->tienePortada());

        // Aunque aparezcan los archivos, la instancia ya resolvió: la memoria es por request.
        $this->ponerArchivos($comercio, [
            $comercio->portada_fondo_url,
            $comercio->portada_hero_url,
            $comercio->portada_thumb_url,
        ]);
        $this->assertFalse($comercio->tienePortada());

        // Una instancia nueva sí lo ve.
        $this->assertTrue($comercio->fresh()->tienePortada());
    }
}
