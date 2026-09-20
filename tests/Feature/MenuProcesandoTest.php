<?php

namespace Tests\Feature;

use App\Jobs\ProcessMenuJob;
use App\Models\Comercio;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * El camino de subir un menú: el comercio tiene que ver qué está pasando.
 *
 * Antes la subida lo devolvía a la lista con un cartel de "en unos minutos estará listo"
 * y una tarjeta con un badge que no se actualizaba nunca: había que recargar a mano hasta
 * adivinar. Y si el job fallaba, la única salida era volver a subir el archivo.
 */
class MenuProcesandoTest extends TestCase
{
    use RefreshDatabase;

    private function comercioDe(User $user): Comercio
    {
        return Comercio::factory()->create(['user_id' => $user->id]);
    }

    private function dueno(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    public function test_subir_un_menu_lleva_al_editor_de_ese_menu(): void
    {
        Queue::fake();
        Storage::fake('public');

        $user = $this->dueno();
        $comercio = $this->comercioDe($user);

        $response = $this->actingAs($user)->post(route('menus.store', $comercio), [
            'nombre' => 'La carta',
            'archivo' => UploadedFile::fake()->image('carta.jpg'),
        ]);

        $menu = Menu::where('comercio_id', $comercio->id)->firstOrFail();

        // A la lista no: ahí el progreso no se ve.
        $response->assertRedirect(route('menus.edit', [$comercio, $menu]));
        $this->assertSame(Menu::ESTADO_PROCESANDO, $menu->estado);
        Queue::assertPushed(ProcessMenuJob::class);
    }

    public function test_al_crear_un_menu_la_configuracion_esta_escondida(): void
    {
        // Antes de subir el archivo sólo importan el nombre y el archivo: asistente,
        // colores y portada aparecen recién en el editor. Se esconden con `hidden` y no
        // se quitan del HTML porque el JS de la pantalla los usa sin chequear.
        $user = $this->dueno();
        $comercio = $this->comercioDe($user);

        $this->actingAs($user)->get(route('menus.create', $comercio))
            ->assertOk()
            ->assertSee('Subí tu carta y la IA saca los platos')
            // Ni el celular de la vista previa ni la barra de publicar: todavía no hay menú.
            ->assertDontSee('id="previewFrame"', false)
            ->assertDontSee('Publicar</button>', false);
    }

    public function test_en_el_editor_la_configuracion_se_ve(): void
    {
        $user = $this->dueno();
        $comercio = $this->comercioDe($user);
        $menu = Menu::factory()->create(['comercio_id' => $comercio->id]);

        $this->actingAs($user)->get(route('menus.edit', [$comercio, $menu]))
            ->assertOk()
            ->assertSee('id="seccion-platos"', false)
            ->assertSee('id="seccion-asistente"', false)
            ->assertSee('id="previewFrame"', false)
            ->assertDontSee('Subí tu carta y la IA saca los platos');
    }

    public function test_mientras_procesa_no_se_muestra_el_editor(): void
    {
        // El editor guarda con un update sobre el mismo menú que el job está escribiendo:
        // si queda a mano mientras la IA trabaja, un "Actualizar" pisa lo recién extraído.
        $user = $this->dueno();
        $comercio = $this->comercioDe($user);
        $menu = Menu::factory()->create([
            'comercio_id' => $comercio->id,
            'estado' => Menu::ESTADO_PROCESANDO,
            'procesado' => false,
        ]);

        $this->actingAs($user)->get(route('menus.edit', [$comercio, $menu]))
            ->assertOk()
            ->assertViewIs('menus.procesando')
            ->assertSee('Estamos leyendo tu menú')
            // Nada del formulario de edición: ni guardar, ni la portada, ni el chatbot.
            ->assertDontSee('Actualizar Menú')
            ->assertDontSee('id="menu-form"', false);
    }

    public function test_cuando_termina_vuelve_a_aparecer_el_editor(): void
    {
        $user = $this->dueno();
        $comercio = $this->comercioDe($user);
        $menu = Menu::factory()->create([
            'comercio_id' => $comercio->id,
            'estado' => Menu::ESTADO_LISTO,
            'procesado' => true,
        ]);

        $this->actingAs($user)->get(route('menus.edit', [$comercio, $menu]))
            ->assertOk()
            ->assertViewIs('menus.create');
    }

    public function test_un_menu_importado_de_excel_no_queda_esperando_a_la_ia(): void
    {
        // El Excel trae el contenido hecho: no hay job que lo procese. Si el menú nace con
        // el default 'procesando' de la columna, el editor lo manda a la pantalla de
        // progreso a esperar para siempre algo que nunca va a pasar.
        $user = $this->dueno();
        $comercio = $this->comercioDe($user);

        $menu = new Menu;
        $menu->comercio_id = $comercio->id;
        $menu->nombre = 'Importado';
        $menu->formato = 'excel';
        $menu->procesado = true;
        $menu->estado = Menu::ESTADO_LISTO;
        $menu->save();

        $this->assertSame(Menu::ESTADO_LISTO, $menu->refresh()->estado);

        $this->actingAs($user)->get(route('menus.edit', [$comercio, $menu]))
            ->assertOk()
            ->assertViewIs('menus.create');
    }

    public function test_el_editor_pregunta_por_el_estado_hasta_que_termina(): void
    {
        $user = $this->dueno();
        $comercio = $this->comercioDe($user);
        $menu = Menu::factory()->create([
            'comercio_id' => $comercio->id,
            'estado' => Menu::ESTADO_PROCESANDO,
            'procesado' => false,
        ]);

        $this->actingAs($user)->get(route('menus.estado', [$comercio, $menu]))
            ->assertOk()
            ->assertJson(['estado' => Menu::ESTADO_PROCESANDO, 'listo' => false, 'error' => false]);

        $menu->update(['estado' => Menu::ESTADO_LISTO, 'procesado' => true]);

        $this->actingAs($user)->get(route('menus.estado', [$comercio, $menu]))
            ->assertOk()
            ->assertJson(['estado' => Menu::ESTADO_LISTO, 'listo' => true]);
    }

    public function test_reintentar_vuelve_a_encolar_el_mismo_archivo(): void
    {
        // El archivo ya está en el servidor: no hay por qué pedirlo de nuevo.
        Queue::fake();

        $user = $this->dueno();
        $comercio = $this->comercioDe($user);
        $menu = Menu::factory()->create([
            'comercio_id' => $comercio->id,
            'estado' => Menu::ESTADO_ERROR,
            'procesado' => false,
            'archivo_original' => "comercios/{$comercio->id}/menus/carta.pdf",
        ]);

        $this->actingAs($user)->post(route('menus.reintentar', [$comercio, $menu]))
            ->assertOk()
            ->assertJson(['ok' => true]);

        Queue::assertPushed(ProcessMenuJob::class);
        $this->assertSame(Menu::ESTADO_PROCESANDO, $menu->refresh()->estado);
    }

    public function test_sin_archivo_no_hay_nada_que_reintentar(): void
    {
        Queue::fake();

        $user = $this->dueno();
        $comercio = $this->comercioDe($user);
        $menu = Menu::factory()->create([
            'comercio_id' => $comercio->id,
            'estado' => Menu::ESTADO_ERROR,
            'archivo_original' => null,
        ]);

        $this->actingAs($user)->post(route('menus.reintentar', [$comercio, $menu]))
            ->assertStatus(422);

        Queue::assertNothingPushed();
    }

    public function test_un_comercio_ajeno_no_puede_espiar_ni_reencolar(): void
    {
        // `reintentar` gasta una llamada a la IA: no puede quedar abierto al que pase.
        Queue::fake();

        $comercio = $this->comercioDe($this->dueno());
        $menu = Menu::factory()->create([
            'comercio_id' => $comercio->id,
            'estado' => Menu::ESTADO_PROCESANDO,
            'archivo_original' => "comercios/{$comercio->id}/menus/carta.pdf",
        ]);

        $ajeno = $this->dueno();

        $this->actingAs($ajeno)->get(route('menus.estado', [$comercio, $menu]))->assertForbidden();
        $this->actingAs($ajeno)->post(route('menus.reintentar', [$comercio, $menu]))->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_publicar_desde_el_editor_vuelve_al_editor(): void
    {
        // La pantalla es para hacer todo en un lugar: publicar y que te mande a la lista
        // justo al terminar lo sacaba del editor.
        $user = $this->dueno();
        $comercio = $this->comercioDe($user);
        $publicado = Menu::factory()->activo()->create(['comercio_id' => $comercio->id]);
        $borrador = Menu::factory()->create(['comercio_id' => $comercio->id, 'activo' => false]);

        $this->actingAs($user)->get(route('menus.edit', [$comercio, $borrador]))
            ->assertSee('Borrador')
            ->assertSee('Publicar</button>', false);

        $this->actingAs($user)
            ->post(route('menus.activar', [$comercio, $borrador]), ['volver' => 'editor'])
            ->assertRedirect(route('menus.edit', [$comercio, $borrador]));

        $this->assertTrue($borrador->fresh()->activo);
        $this->assertFalse($publicado->fresh()->activo);

        $this->actingAs($user)->get(route('menus.edit', [$comercio, $borrador]))
            ->assertSee('Publicado')
            ->assertDontSee('Publicar</button>', false);
    }

    public function test_activar_desde_la_lista_sigue_yendo_a_la_lista(): void
    {
        $user = $this->dueno();
        $comercio = $this->comercioDe($user);
        $menu = Menu::factory()->create(['comercio_id' => $comercio->id, 'activo' => false]);

        $this->actingAs($user)->post(route('menus.activar', [$comercio, $menu]))
            ->assertRedirect(route('menus.index', $comercio));
    }

    public function test_el_admin_tampoco_ve_el_editor_mientras_procesa(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $menu = Menu::factory()->procesando()->create();

        $this->actingAs($admin)->get(route('admin.menus.edit', $menu))
            ->assertOk()
            ->assertViewIs('menus.procesando');
    }

    public function test_el_admin_ve_el_editor_en_secciones(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $menu = Menu::factory()->create();

        $this->actingAs($admin)->get(route('admin.menus.edit', $menu))
            ->assertOk()
            ->assertSee('id="seccion-platos"', false)
            ->assertSee('id="previewFrame"', false);
    }

    public function test_los_platos_se_ven_por_categoria_plegada_con_lo_que_les_falta(): void
    {
        $user = $this->dueno();
        $comercio = $this->comercioDe($user);
        $menu = Menu::factory()->create([
            'comercio_id' => $comercio->id,
            'contexto_gpt' => json_encode(['categorias' => [
                ['nombre' => 'Pizzas', 'platos' => [
                    ['nombre' => 'Muzzarella', 'precio' => '8500', 'descripcion' => 'Salsa y muzza', 'tags' => ['veggie'], 'estrella' => true],
                    ['nombre' => 'Napolitana', 'precio' => '9200', 'descripcion' => ''],
                ]],
                ['nombre' => 'Promociones del día', 'platos' => [['nombre' => 'Promo 2x1', 'precio' => '1']]],
            ]]),
        ]);

        $this->actingAs($user)->get(route('menus.edit', [$comercio, $menu]))
            ->assertOk()
            // El resumen de la sección cerrada: lo que tiene y lo que le falta. Las promos
            // no cuentan como plato ni como categoría.
            ->assertSee('1 categoría · 2 platos · 1 sin descripción · 2 sin foto')
            ->assertSee('id="editor-categorias"', false)
            ->assertSee('data-categoria-editor="0"', false)
            ->assertSee('2 platos')
            ->assertSee('1 sin descripción')
            // Las promos tienen su propia sección: no aparecen como categoría de platos.
            ->assertDontSee('data-categoria-editor="1"', false)
            // De estos ids cuelga el JavaScript de edición: si cambian, deja de guardar.
            ->assertSee('id="platos-container-0"', false)
            ->assertSee('id="nombre-display-0-0"', false)
            ->assertSee('id="precio-input-0-0"', false)
            ->assertSee('id="estrella-0-0"', false)
            ->assertSee('id="plato-imagen-container-0-0"', false)
            ->assertSee('id="descripcion-display-0-1"', false)
            ->assertSee('id="tags-list-0-0"', false)
            ->assertSee('data-plato-index="1"', false)
            ->assertSee('veggie');
    }

    public function test_el_archivo_de_un_menu_no_se_reemplaza_se_sube_una_carta_nueva(): void
    {
        // Reprocesar encima del mismo menú pisaba en silencio precios, descripciones,
        // etiquetas y estrellas corregidos a mano, y borraba las promociones (viven en el
        // mismo JSON). Ya no hay ruta que lo haga: el editor ofrece subir una carta nueva.
        Queue::fake();
        Storage::fake('public');

        $user = $this->dueno();
        $comercio = $this->comercioDe($user);
        $menu = Menu::factory()->create(['comercio_id' => $comercio->id]);

        $this->actingAs($user)->get(route('menus.edit', [$comercio, $menu]))
            ->assertOk()
            ->assertSee('Subir una carta nueva')
            ->assertSee(route('menus.create', $comercio), false)
            ->assertDontSee('name="archivo"', false)
            ->assertDontSee('id="menu-form"', false);

        $this->actingAs($user)
            ->put("/comercios/{$comercio->slug}/menus/{$menu->id}", [
                'archivo' => UploadedFile::fake()->image('otra.jpg'),
            ])
            ->assertMethodNotAllowed();

        Queue::assertNothingPushed();
    }

    public function test_al_crear_si_esta_el_formulario_que_sube_el_archivo(): void
    {
        $user = $this->dueno();
        $comercio = $this->comercioDe($user);

        $this->actingAs($user)->get(route('menus.create', $comercio))
            ->assertOk()
            ->assertSee('id="menu-form"', false)
            ->assertSee('name="archivo"', false)
            ->assertSee(route('menus.store', $comercio), false);
    }
}
