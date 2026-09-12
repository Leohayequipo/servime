<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Menu;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * El alta de un comercio y lo que viene después.
 *
 * El alta pide cuatro datos; el menú que ve el cliente necesita bastante más. Antes
 * terminaba en el dashboard con un «creado exitosamente» y el comerciante no tenía
 * forma de saber que le faltaban el menú, la portada y los horarios.
 */
class AltaDeComercioTest extends TestCase
{
    use RefreshDatabase;

    private function planCon(array $features = []): Plan
    {
        return Plan::create([
            'key' => 'test-'.uniqid(), 'nombre' => 'Test', 'mensajes_ia_mes' => 100,
            'modelo_ia' => 'gpt-4o-mini', 'max_tokens_chat' => 600,
            'features' => $features, 'activo' => true,
        ]);
    }

    public function test_crear_un_comercio_lleva_a_completarlo_y_no_al_dashboard(): void
    {
        $user = User::factory()->create();

        $respuesta = $this->actingAs($user)->post(route('comercios.store'), [
            'nombre' => 'Bar Azopardo',
        ]);

        $comercio = Comercio::where('nombre', 'Bar Azopardo')->firstOrFail();

        $respuesta->assertRedirect(route('menus.index', $comercio));
        $this->assertStringContainsString('cargá tu menú', session('success'));
    }

    public function test_la_pantalla_de_menus_dice_lo_que_falta(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create([
            'plan_id' => $this->planCon(['customizacion'])->id,
            'horarios' => null,
            'logo_url' => null,
        ]);

        $this->actingAs($user)
            ->get(route('menus.index', $comercio))
            ->assertOk()
            ->assertSee('Para terminar de configurar tu menú')
            ->assertSee('Cargá tu menú')
            ->assertSee('Subí una portada')
            ->assertSee('Cargá tus horarios')
            ->assertSee('Subí tu logo');
    }

    public function test_lo_ya_hecho_se_muestra_tildado_y_no_desaparece(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create([
            'plan_id' => $this->planCon(['customizacion'])->id,
            'horarios' => ['1' => [['11:00', '20:00']]],
            'logo_url' => null,
        ]);

        // Los horarios ya están: el paso sigue en la lista, pero tildado. Mostrar sólo
        // lo que falta escondía el progreso y la lista cambiaba de tamaño sola.
        $pasos = collect($comercio->pasosConfiguracion())->keyBy('texto');

        $this->assertTrue($pasos['Cargá tus horarios']['completo']);
        $this->assertFalse($pasos['Subí tu logo']['completo']);

        $this->actingAs($user)
            ->get(route('menus.index', $comercio))
            ->assertOk()
            ->assertSee('Cargá tus horarios')
            ->assertSee('Subí tu logo');
    }

    public function test_con_todo_hecho_queda_la_linea_de_confirmacion(): void
    {
        Storage::fake('public');
        foreach (['original.jpg', 'hero.webp', 'thumb.webp', 'fondo.webp'] as $f) {
            Storage::disk('public')->put("comercios/1/portada/{$f}", 'x');
        }

        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create([
            'plan_id' => $this->planCon(['customizacion'])->id,
            'horarios' => ['1' => [['11:00', '20:00']]],
            'logo_url' => 'comercios/1/logos/logo.png',
            'portada_url' => 'comercios/1/portada/original.jpg',
            'portada_hero_url' => 'comercios/1/portada/hero.webp',
            'portada_thumb_url' => 'comercios/1/portada/thumb.webp',
            'portada_fondo_url' => 'comercios/1/portada/fondo.webp',
            'portada_estado' => 'listo',
            'tema_derivado' => ['base' => '#6B4E3D', 'acento_sugerido' => '#D97757'],
        ]);
        Menu::factory()->for($comercio)->create(['procesado' => true]);

        $this->assertSame([], $comercio->pasosPendientes());

        // El bloque no desaparece: se achica a una línea. Cuatro renglones tildados en
        // cada visita, durante meses, vuelven a ser ruido — pero ahora permanente.
        $this->actingAs($user)
            ->get(route('menus.index', $comercio))
            ->assertOk()
            ->assertSee('Tu menú está configurado.')
            ->assertDontSee('Para terminar de configurar tu menú');
    }

    public function test_no_se_ofrece_la_portada_al_plan_que_no_la_tiene(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create([
            'plan_id' => $this->planCon([])->id,
        ]);

        $textos = array_column($comercio->pasosPendientes(), 'texto');

        $this->assertNotContains('Subí una portada', $textos);
        $this->assertContains('Cargá tu menú', $textos);
    }

    public function test_los_campos_compartidos_salen_en_las_dos_pantallas(): void
    {
        // Dos usuarios: el que ya tiene comercio no puede entrar al alta (sólo se
        // permite uno por usuario), así que la pantalla de crear la mira el otro.
        $conComercio = User::factory()->create();
        $comercio = Comercio::factory()->for($conComercio)->create(['direccion' => 'Azopardo 1234']);
        $sinComercio = User::factory()->create();

        $pantallas = [
            [$sinComercio, route('comercios.create')],
            [$conComercio, route('comercios.edit', $comercio)],
        ];

        // Mismo componente en las dos: si alguien cambia un rótulo, cambia en ambas.
        foreach ($pantallas as [$quien, $url]) {
            $this->actingAs($quien)->get($url)
                ->assertOk()
                ->assertSee('Nombre del comercio', false)
                ->assertSee('Horario de atención', false)
                ->assertSee('name="descripcion"', false)
                ->assertSee('name="direccion"', false);
        }

        // Y en editar el componente trae los valores cargados.
        $this->actingAs($conComercio)
            ->get(route('comercios.edit', $comercio))
            ->assertSee('value="Azopardo 1234"', false);
    }

    public function test_el_tope_del_logo_rige_igual_al_crear_que_al_editar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create();

        // 3 MB contra el tope de 2. La regla ahora sale de un solo lugar, así que no
        // puede quedar distinta en una pantalla y en la otra.
        $pesado = fn () => UploadedFile::fake()->create('logo.png', 3072, 'image/png');

        $this->actingAs($user)
            ->post(route('comercios.store'), ['nombre' => 'Nuevo', 'logo' => $pesado()])
            ->assertSessionHasErrors('logo');

        $this->actingAs($user)
            ->put(route('comercios.update', $comercio), [
                'nombre' => $comercio->nombre,
                'primary_color' => '#3B82F6',
                'secondary_color' => '#1E40AF',
                'accent_color' => '#F59E0B',
                'background_color' => '#FFFFFF',
                'chatbot_name' => 'Asistente IA',
                'logo' => $pesado(),
            ])
            ->assertSessionHasErrors('logo');
    }
}
