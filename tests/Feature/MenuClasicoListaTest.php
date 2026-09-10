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
 * El menú clásico puede mostrar el archivo del comercio o la carta en lista.
 *
 * Lo que más valor tiene acá es el último caso: el comercio que cargó su menú por Excel,
 * o al que se le perdió el archivo, veía "Menú no disponible" teniendo todos los platos
 * cargados. Ahora ve su carta igual.
 */
class MenuClasicoListaTest extends TestCase
{
    use RefreshDatabase;

    private const CARTA = '{"categorias":[
        {"nombre":"Pizzas","platos":[
            {"nombre":"Muzzarella","precio":"$8.900","descripcion":"Con aceitunas"},
            {"nombre":"Napolitana","precio":"$9.400"}
        ]},
        {"nombre":"Empanadas","platos":[{"nombre":"De carne","precio":"$1.200"}]}
    ]}';

    private function comercioConCarta(array $extra = [], bool $conArchivo = false): Comercio
    {
        $comercio = Comercio::factory()->for(User::factory())->create($extra);

        $archivo = null;
        if ($conArchivo) {
            Storage::fake('public');
            $archivo = UploadedFile::fake()->create('menu.pdf', 10)->store('menus', 'public');
        }

        Menu::factory()->create([
            'comercio_id' => $comercio->id,
            'activo' => true,
            'procesado' => true,
            'contexto_gpt' => self::CARTA,
            'archivo_original' => $archivo,
            'formato' => 'pdf',
        ]);

        return $comercio;
    }

    public function test_sin_archivo_muestra_la_carta_en_lista_en_vez_de_menu_no_disponible(): void
    {
        $comercio = $this->comercioConCarta();

        $this->get(route('menu.classic', $comercio))
            ->assertOk()
            ->assertSee('Muzzarella')
            ->assertSee('$8.900')
            ->assertSee('Pizzas')
            ->assertDontSee('Menú no disponible');
    }

    public function test_por_defecto_un_comercio_con_archivo_sigue_mostrando_su_archivo(): void
    {
        // Nadie que diseñó su PDF se tiene que encontrar con que su menú cambió solo.
        $comercio = $this->comercioConCarta(conArchivo: true);

        $this->get(route('menu.classic', $comercio))
            ->assertOk()
            ->assertSee('<iframe', false)
            ->assertDontSee('class="lista-plato"', false);
    }

    public function test_el_comercio_puede_elegir_que_abra_con_la_lista(): void
    {
        $comercio = $this->comercioConCarta(['menu_clasico_vista' => 'lista'], conArchivo: true);

        $this->get(route('menu.classic', $comercio))
            ->assertOk()
            ->assertSee('Muzzarella')
            ->assertDontSee('<iframe', false);
    }

    public function test_el_cliente_puede_pasar_de_una_vista_a_la_otra(): void
    {
        $comercio = $this->comercioConCarta(conArchivo: true);

        // Abre con el archivo y ofrece la lista...
        $this->get(route('menu.classic', $comercio))
            ->assertOk()
            ->assertSee('Ver como lista');

        // ...y con ?vista=lista muestra la carta y ofrece volver al original.
        $this->get(route('menu.classic', $comercio).'?vista=lista')
            ->assertOk()
            ->assertSee('Muzzarella')
            ->assertSee('Ver el menú original');
    }

    public function test_sin_archivo_no_se_ofrece_alternar_porque_no_hay_a_que(): void
    {
        $comercio = $this->comercioConCarta();

        $this->get(route('menu.classic', $comercio))
            ->assertOk()
            ->assertDontSee('Ver el menú original')
            ->assertDontSee('Ver como lista');
    }

    public function test_una_vista_inventada_en_la_url_no_rompe_nada(): void
    {
        $comercio = $this->comercioConCarta(conArchivo: true);

        $this->get(route('menu.classic', $comercio).'?vista=basura')
            ->assertOk()
            ->assertSee('<iframe', false);
    }

    /**
     * Elegir "mostrar tu archivo" sin tener archivo no cambia nada: la vista pública cae
     * igual a la lista. Ofrecerlo sin avisar deja al comerciante tocando una opción que
     * no hace nada y sin entender por qué.
     */
    public function test_sin_archivo_la_opcion_de_mostrarlo_esta_deshabilitada_y_avisada(): void
    {
        $comercio = $this->comercioConCarta();

        $this->actingAs($comercio->user)
            ->get(route('menus.create', $comercio))
            ->assertOk()
            ->assertSee('No hay ningún archivo cargado')
            ->assertSee('disabled', false);
    }

    /**
     * La vista del menú la renderizan tres rutas distintas y el dato lo necesitan las tres.
     *
     * Ya falló una vez: el bloque se agregó pasando la variable desde el controlador, pero
     * `/admin/menus/{id}/edit` es de AdminController y no la mandaba, así que esa pantalla
     * tiraba "Undefined variable" — un 500 en la cara del super admin. Ahora el dato sale
     * del modelo y ningún controlador tiene que acordarse, pero las tres se prueban.
     */
    public function test_las_tres_pantallas_del_menu_abren_sin_romperse(): void
    {
        $comercio = $this->comercioConCarta();
        $menu = $comercio->menus()->first();
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($comercio->user)->get(route('menus.create', $comercio))->assertOk();
        $this->actingAs($comercio->user)->get(route('menus.edit', [$comercio, $menu]))->assertOk();
        $this->actingAs($admin)->get(route('admin.menus.edit', $menu))->assertOk();
    }

    public function test_con_archivo_la_opcion_se_puede_elegir(): void
    {
        $comercio = $this->comercioConCarta(conArchivo: true);

        $this->actingAs($comercio->user)
            ->get(route('menus.create', $comercio))
            ->assertOk()
            ->assertDontSee('No hay ningún archivo cargado');
    }

    public function test_el_dueno_guarda_su_preferencia_desde_el_panel(): void
    {
        $comercio = $this->comercioConCarta();

        $this->actingAs($comercio->user)
            ->postJson(route('menus.update.vista.clasica', $comercio), ['vista' => 'lista'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame('lista', $comercio->fresh()->menu_clasico_vista);
    }

    public function test_un_comercio_ajeno_no_puede_cambiar_la_preferencia(): void
    {
        $comercio = $this->comercioConCarta();
        $intruso = User::factory()->create();

        $this->actingAs($intruso)
            ->postJson(route('menus.update.vista.clasica', $comercio), ['vista' => 'lista'])
            ->assertForbidden();

        $this->assertSame('archivo', $comercio->fresh()->menu_clasico_vista);
    }

    public function test_solo_se_aceptan_las_dos_vistas_conocidas(): void
    {
        $comercio = $this->comercioConCarta();

        $this->actingAs($comercio->user)
            ->postJson(route('menus.update.vista.clasica', $comercio), ['vista' => 'cualquiera'])
            ->assertStatus(422);
    }
}
