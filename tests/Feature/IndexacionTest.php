<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lo que ve Google: un solo dominio, y la demo de la landing fuera de las búsquedas.
 */
class IndexacionTest extends TestCase
{
    use RefreshDatabase;

    private function comercioConMenu(string $slug): Comercio
    {
        $comercio = Comercio::factory()->create(['slug' => $slug]);
        Menu::factory()->for($comercio)->create([
            'procesado' => true,
            'activo' => true,
            'contexto_gpt' => '{"categorias":[{"nombre":"Pizzas","platos":[{"nombre":"Muzzarella","precio":"8900"}]}]}',
            'archivo_original' => null,
        ]);

        return $comercio;
    }

    // ---- www ----

    public function test_www_redirige_permanente_al_dominio_sin_www_con_la_ruta_y_la_mesa(): void
    {
        // El `?m=` es la mesa del QR: un QR impreso con www tiene que seguir andando.
        $this->get('https://www.servime.net/menu/la-pizzeria/classic?m=abc123')
            ->assertStatus(301)
            ->assertRedirect('https://servime.net/menu/la-pizzeria/classic?m=abc123');
    }

    public function test_en_produccion_va_directo_a_https(): void
    {
        $this->app['env'] = 'production';

        $this->get('http://www.servime.net/')
            ->assertStatus(301)
            ->assertRedirect('https://servime.net/');
    }

    public function test_un_post_por_www_no_se_redirige(): void
    {
        // Redirigido llegaría como GET y se perdería lo que mandaba.
        $this->post('https://www.servime.net/')->assertStatus(405);
    }

    public function test_sin_www_no_redirige(): void
    {
        $this->get('https://servime.net/')->assertOk();
    }

    // ---- La demo ----

    public function test_la_demo_va_con_noindex_y_el_resto_de_los_menus_no(): void
    {
        config(['servime.demo_slug' => 'la-demo']);
        $demo = $this->comercioConMenu('la-demo');
        $real = $this->comercioConMenu('la-pizzeria');

        $this->get(route('menu.classic', $demo))->assertOk()
            ->assertSee('<meta name="robots" content="noindex, follow">', false);

        $this->get(route('menu.classic', $real))->assertOk()
            ->assertSee('<meta name="robots" content="index, follow, max-image-preview:large">', false);
    }

    public function test_el_sitemap_lista_los_menus_pero_no_la_demo(): void
    {
        config(['servime.demo_slug' => 'la-demo']);
        $this->comercioConMenu('la-demo');
        $this->comercioConMenu('la-pizzeria');

        $this->get('/sitemap.xml')->assertOk()
            ->assertSee('/menu/la-pizzeria', false)
            ->assertDontSee('/menu/la-demo', false);
    }

    public function test_sin_demo_configurada_ningun_menu_es_demo(): void
    {
        config(['servime.demo_slug' => null]);

        $this->assertFalse($this->comercioConMenu('la-pizzeria')->esDemo());
    }
}
