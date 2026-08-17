<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * El menú clásico muestra el archivo original del comercio dentro de un <iframe>.
 *
 * Por eso importa distinguir "la base tiene una ruta" de "el archivo está": con el archivo
 * faltante, el iframe carga esa URL igual y el cliente sentado en la mesa termina viendo un
 * 403, un 404 o la pantalla de login DENTRO del menú del local.
 */
class MenuClasicoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function comercioConMenu(?string $archivo): Comercio
    {
        $comercio = Comercio::factory()->create();

        Menu::factory()->for($comercio)->activo()->create([
            'archivo_original' => $archivo,
            'formato' => 'pdf',
            'procesado' => true,
        ]);

        return $comercio;
    }

    public function test_muestra_el_archivo_cuando_esta(): void
    {
        $ruta = 'comercios/1/menus/carta.pdf';
        $comercio = $this->comercioConMenu($ruta);
        Storage::disk('public')->put($ruta, '%PDF-1.4 falso');

        $this->get("/menu/{$comercio->slug}/classic")
            ->assertOk()
            ->assertSee('iframe', false)
            ->assertDontSee('Menú no disponible', false);
    }

    public function test_si_el_archivo_no_esta_cae_al_mensaje_amable_y_no_al_iframe(): void
    {
        // La base apunta a un PDF que no existe: es el caso que rompía.
        $comercio = $this->comercioConMenu('comercios/1/menus/no-esta.pdf');

        $this->get("/menu/{$comercio->slug}/classic")
            ->assertOk()
            ->assertDontSee('<iframe', false)
            ->assertSee('Menú no disponible', false);
    }

    public function test_sin_archivo_en_la_base_tambien_cae_al_mensaje(): void
    {
        $comercio = $this->comercioConMenu(null);

        $this->get("/menu/{$comercio->slug}/classic")
            ->assertOk()
            ->assertDontSee('<iframe', false)
            ->assertSee('Menú no disponible', false);
    }

    public function test_el_mensaje_ofrece_el_chat_como_salida(): void
    {
        // Que el menú no esté no puede dejar al cliente sin nada: el asistente sigue
        // sabiendo la carta, porque sale del JSON y no del archivo.
        $comercio = $this->comercioConMenu('comercios/1/menus/no-esta.pdf');

        $this->get("/menu/{$comercio->slug}/classic")
            ->assertOk()
            ->assertSee(route('menu.chat', $comercio), false);
    }
}
