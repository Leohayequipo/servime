<?php

namespace Tests\Feature;

use App\Jobs\ProcessMenuJob;
use App\Models\Comercio;
use App\Models\Menu;
use App\Models\Procesamiento;
use App\Models\User;
use App\Services\MenuProcessorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Una carta en papel suele ser varias fotos. Se suben juntas, en el orden que elige el
 * comercio, y la IA las lee en UN solo pedido: son páginas de la misma carta, y leídas por
 * separado una categoría que sigue en la página siguiente salía dos veces.
 */
class MenuVariasFotosTest extends TestCase
{
    use RefreshDatabase;

    private function duenoConComercio(): array
    {
        $user = User::factory()->create(['role' => 'user']);

        return [$user, Comercio::factory()->create(['user_id' => $user->id])];
    }

    private function fotos(int $cantidad): array
    {
        return array_map(
            fn ($i) => UploadedFile::fake()->image("pagina-{$i}.jpg", 300, 400),
            range(1, $cantidad)
        );
    }

    public function test_varias_fotos_son_un_solo_menu_en_el_orden_en_que_llegan(): void
    {
        Queue::fake();
        Storage::fake('public');
        [$user, $comercio] = $this->duenoConComercio();

        $this->actingAs($user)->post(route('menus.store', $comercio), [
            'nombre' => 'Carta en fotos',
            'archivos' => $this->fotos(3),
        ])->assertRedirect();

        $menu = Menu::where('comercio_id', $comercio->id)->sole();

        $this->assertSame('image', $menu->formato);
        $this->assertCount(3, $menu->archivos);
        $this->assertTrue($menu->tieneVariasFotos());
        // El orden de llegada se respeta en los nombres guardados (`uniqid()-0`, `-1`, `-2`).
        foreach ($menu->archivos as $i => $ruta) {
            $this->assertStringEndsWith("-{$i}.jpg", $ruta);
            Storage::disk('public')->assertExists($ruta);
        }
        // La primera es el "archivo original": lo que leen la miniatura y el admin.
        $this->assertSame($menu->archivos[0], $menu->archivo_original);

        // Una carta para el tope del plan y un solo trabajo para la IA, no tres.
        $this->assertSame(1, Procesamiento::where('menu_id', $menu->id)->count());
        Queue::assertPushed(ProcessMenuJob::class, 1);
    }

    public function test_una_sola_foto_por_el_campo_nuevo_queda_como_siempre(): void
    {
        Queue::fake();
        Storage::fake('public');
        [$user, $comercio] = $this->duenoConComercio();

        $this->actingAs($user)->post(route('menus.store', $comercio), [
            'nombre' => 'Una foto',
            'archivos' => $this->fotos(1),
        ])->assertRedirect();

        $menu = Menu::where('comercio_id', $comercio->id)->sole();

        $this->assertNull($menu->archivos);
        $this->assertFalse($menu->tieneVariasFotos());
        $this->assertSame([$menu->archivo_original], $menu->archivosEnOrden());
    }

    public function test_un_pdf_no_se_mezcla_con_fotos(): void
    {
        Queue::fake();
        Storage::fake('public');
        [$user, $comercio] = $this->duenoConComercio();

        $this->actingAs($user)->post(route('menus.store', $comercio), [
            'nombre' => 'Mezcla',
            'archivos' => [
                UploadedFile::fake()->image('pagina.jpg'),
                UploadedFile::fake()->create('carta.pdf', 100, 'application/pdf'),
            ],
        ])->assertSessionHasErrors('archivos');

        $this->assertSame(0, Menu::count());
        Queue::assertNothingPushed();
    }

    public function test_no_se_pueden_subir_mas_de_diez_fotos(): void
    {
        Queue::fake();
        Storage::fake('public');
        [$user, $comercio] = $this->duenoConComercio();

        $this->actingAs($user)->post(route('menus.store', $comercio), [
            'nombre' => 'Demasiadas',
            'archivos' => $this->fotos(Menu::MAX_FOTOS + 1),
        ])->assertSessionHasErrors('archivos');

        $this->assertSame(0, Menu::count());
    }

    /** Un menú de tres fotos ya guardado, listo para procesar. */
    private function menuDeTresFotos(): Menu
    {
        Storage::fake('public');
        [, $comercio] = $this->duenoConComercio();

        $rutas = [];
        foreach ($this->fotos(3) as $i => $foto) {
            $rutas[] = $foto->storeAs("comercios/{$comercio->id}/menus", "foto-{$i}.jpg", 'public');
        }

        return Menu::factory()->procesando()->create([
            'comercio_id' => $comercio->id,
            'formato' => 'image',
            'archivo_original' => $rutas[0],
            'archivos' => $rutas,
        ]);
    }

    private function cartaJson(string $categoria, string $plato): string
    {
        return json_encode(['categorias' => [[
            'nombre' => $categoria,
            'platos' => [['nombre' => $plato, 'precio' => '$1.000', 'descripcion' => '']],
        ]]]);
    }

    public function test_las_fotos_van_juntas_y_en_orden_en_un_solo_pedido_a_gemini(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        $menu = $this->menuDeTresFotos();

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => $this->cartaJson('Pizzas', 'Muzzarella')]]],
                    'finishReason' => 'STOP',
                ]],
            ]),
        ]);

        (new MenuProcessorService)->processMenu($menu);

        // Un solo pedido, con las tres fotos y cada una precedida por su número de página.
        Http::assertSentCount(1);
        Http::assertSent(function (Request $request) {
            $partes = $request['contents'][0]['parts'];
            $fotos = array_values(array_filter($partes, fn ($p) => isset($p['inline_data'])));
            $paginas = array_values(array_filter(
                array_column($partes, 'text'),
                fn ($t) => str_starts_with($t, 'Página ')
            ));

            return count($fotos) === 3
                && $paginas === ['Página 1 de 3:', 'Página 2 de 3:', 'Página 3 de 3:'];
        });

        $menu->refresh();
        $this->assertSame(Menu::ESTADO_LISTO, $menu->estado);
        $this->assertSame('Muzzarella', $menu->platos()[0]['nombre']);
        $this->assertStringContainsString('con Gemini', $menu->texto_extraido);
    }

    public function test_si_gemini_falla_openai_lee_cada_foto_y_se_unen(): void
    {
        config([
            'services.gemini.api_key' => 'test-key',
            'services.openai.api_key' => 'test-key',
            'services.openai.model' => 'gpt-4o',
        ]);
        $menu = $this->menuDeTresFotos();

        $respuestasOpenAI = [
            $this->cartaJson('Entradas', 'Rabas'),
            $this->cartaJson('Pizzas', 'Fugazza'),
            $this->cartaJson('Postres', 'Flan'),
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'caído'], 500),
            'api.openai.com/*' => Http::sequence()
                ->push(['choices' => [['message' => ['content' => $respuestasOpenAI[0]], 'finish_reason' => 'stop']]])
                ->push(['choices' => [['message' => ['content' => $respuestasOpenAI[1]], 'finish_reason' => 'stop']]])
                ->push(['choices' => [['message' => ['content' => $respuestasOpenAI[2]], 'finish_reason' => 'stop']]]),
        ]);

        (new MenuProcessorService)->processMenu($menu);

        // Una llamada a OpenAI por foto.
        Http::assertSentCount(1 + 3);

        $menu->refresh();
        $this->assertSame(Menu::ESTADO_LISTO, $menu->estado);
        $this->assertEqualsCanonicalizing(
            ['Rabas', 'Fugazza', 'Flan'],
            array_column($menu->platos(), 'nombre')
        );
    }

    public function test_el_menu_clasico_muestra_todas_las_fotos_en_orden(): void
    {
        $menu = $this->menuDeTresFotos();
        $menu->update(['estado' => Menu::ESTADO_LISTO, 'procesado' => true, 'activo' => true]);

        $respuesta = $this->get(route('menu.classic', $menu->comercio).'?vista=archivo')->assertOk();

        $respuesta->assertSeeInOrder(array_map(fn ($ruta) => 'storage/'.$ruta, $menu->archivos), false);
    }
}
