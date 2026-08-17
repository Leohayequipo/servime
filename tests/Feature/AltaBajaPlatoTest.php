<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Alta y baja de productos desde la pantalla de menú.
 *
 * El menú vive como JSON en `menus.contexto_gpt` y el índice del plato es su identidad
 * para todos los endpoints de edición, así que borrar tiene que reindexar.
 */
class AltaBajaPlatoTest extends TestCase
{
    use RefreshDatabase;

    private function menu(Comercio $comercio): Menu
    {
        return Menu::factory()->for($comercio)->activo()->create([
            'contexto_gpt' => json_encode(['categorias' => [[
                'nombre' => 'Principales',
                'platos' => [
                    ['id' => 3, 'nombre' => 'Milanesa', 'precio' => '$5.000'],
                    ['id' => 7, 'nombre' => 'Pizza', 'precio' => '$7.000'],
                ],
            ]]]),
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function platos(Menu $menu): array
    {
        return json_decode($menu->fresh()->contexto_gpt, true)['categorias'][0]['platos'];
    }

    public function test_agrega_un_producto_al_final_de_la_categoria(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create();
        $menu = $this->menu($comercio);

        $this->actingAs($user)->postJson(route('menus.agregar.plato', [$comercio, $menu]), [
            'categoria_index' => 0,
            'nombre' => 'Lomito',
            'precio' => '9500',
            'descripcion' => 'Con papas',
        ])->assertOk()->assertJson(['success' => true, 'plato_index' => 2]);

        $platos = $this->platos($menu);

        $this->assertCount(3, $platos);
        $this->assertSame('Lomito', $platos[2]['nombre']);
        // Sin id, al reprocesar el menú el plato pierde la foto y el Excel no lo reconoce.
        $this->assertSame(8, $platos[2]['id']);
        $this->assertSame('Con papas', $platos[2]['descripcion']);
        // Entra formateado igual que si lo hubieran editado desde la pantalla.
        $this->assertSame('$9.500', $platos[2]['precio']);
        $this->assertSame([], $platos[2]['tags']);
    }

    /** JPEG real: el service que procesa la foto le toca los píxeles de verdad. */
    private function jpeg(): UploadedFile
    {
        $img = imagecreatetruecolor(400, 300);
        imagefill($img, 0, 0, imagecolorallocate($img, 200, 120, 40));
        $ruta = tempnam(sys_get_temp_dir(), 'test').'.jpg';
        imagejpeg($img, $ruta, 90);
        imagedestroy($img);

        return new UploadedFile($ruta, 'foto.jpg', 'image/jpeg', null, true);
    }

    public function test_la_foto_del_alta_cae_en_el_producto_nuevo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create();
        $menu = $this->menu($comercio);

        // El modal sube la foto en un segundo paso, con el índice que devuelve el alta:
        // si ese índice estuviera corrido, la foto le quedaría a otro producto.
        $platoIndex = $this->actingAs($user)->postJson(route('menus.agregar.plato', [$comercio, $menu]), [
            'categoria_index' => 0,
            'nombre' => 'Lomito',
        ])->assertOk()->json('plato_index');

        $this->actingAs($user)->post(route('menus.upload.plato.imagen', [$comercio, $menu]), [
            'imagen' => $this->jpeg(),
            'categoria_index' => 0,
            'plato_index' => $platoIndex,
        ])->assertOk()->assertJson(['success' => true]);

        $platos = $this->platos($menu);

        $this->assertArrayHasKey('imagen', $platos[2]);
        $this->assertArrayNotHasKey('imagen', $platos[0]);
        $this->assertArrayNotHasKey('imagen', $platos[1]);
    }

    public function test_el_producto_nuevo_necesita_nombre(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create();
        $menu = $this->menu($comercio);

        $this->actingAs($user)->postJson(route('menus.agregar.plato', [$comercio, $menu]), [
            'categoria_index' => 0,
            'nombre' => '',
        ])->assertStatus(422);

        $this->assertCount(2, $this->platos($menu));
    }

    public function test_agregar_rechaza_una_categoria_que_no_existe(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create();
        $menu = $this->menu($comercio);

        $this->actingAs($user)->postJson(route('menus.agregar.plato', [$comercio, $menu]), [
            'categoria_index' => 9,
            'nombre' => 'Lomito',
        ])->assertStatus(400);
    }

    public function test_borra_un_producto_y_reindexa_los_que_quedan(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create();
        $menu = $this->menu($comercio);

        $this->actingAs($user)->deleteJson(route('menus.delete.plato', [$comercio, $menu]), [
            'categoria_index' => 0,
            'plato_index' => 0,
        ])->assertOk()->assertJson(['success' => true]);

        $platos = $this->platos($menu);

        $this->assertCount(1, $platos);
        // Sin reindexar, el que queda seguiría en la posición 1 y el resto de los endpoints
        // (precio, nombre, estrella) editaría el plato equivocado.
        $this->assertSame('Pizza', $platos[0]['nombre']);
    }

    public function test_borrar_el_producto_se_lleva_su_imagen(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create();
        $menu = $this->menu($comercio);

        $ruta = Storage::disk('public')->putFile(
            "comercios/{$comercio->id}/menu-items",
            UploadedFile::fake()->image('milanesa.jpg')
        );

        $contenido = json_decode($menu->contexto_gpt, true);
        $contenido['categorias'][0]['platos'][0]['imagen'] = '/storage/'.$ruta;
        $menu->update(['contexto_gpt' => json_encode($contenido)]);

        $this->actingAs($user)->deleteJson(route('menus.delete.plato', [$comercio, $menu]), [
            'categoria_index' => 0,
            'plato_index' => 0,
        ])->assertOk();

        // Si la imagen sobrevive al plato, nadie más la referencia y ocupa storage para siempre.
        Storage::disk('public')->assertMissing($ruta);
    }

    public function test_borrar_rechaza_indices_que_no_existen(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create();
        $menu = $this->menu($comercio);

        $this->actingAs($user)->deleteJson(route('menus.delete.plato', [$comercio, $menu]), [
            'categoria_index' => 0,
            'plato_index' => 99,
        ])->assertStatus(400);

        $this->assertCount(2, $this->platos($menu));
    }

    public function test_un_comercio_ajeno_no_puede_agregar_ni_borrar(): void
    {
        $duenio = User::factory()->create();
        $comercio = Comercio::factory()->for($duenio)->create();
        $menu = $this->menu($comercio);
        $ajeno = User::factory()->create();

        $this->actingAs($ajeno)->postJson(route('menus.agregar.plato', [$comercio, $menu]), [
            'categoria_index' => 0,
            'nombre' => 'Lomito',
        ])->assertStatus(403);

        $this->actingAs($ajeno)->deleteJson(route('menus.delete.plato', [$comercio, $menu]), [
            'categoria_index' => 0,
            'plato_index' => 0,
        ])->assertStatus(403);

        $this->assertCount(2, $this->platos($menu));
    }
}
