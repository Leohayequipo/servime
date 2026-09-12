<?php

namespace Tests\Feature;

use App\Jobs\GenerateBrandThemeJob;
use App\Models\Comercio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Seguimiento del procesado de la portada.
 *
 * El panel consulta el estado solo: antes decía "generando… recargá en unos
 * segundos" indefinidamente, así que si la cola estaba caída el comerciante
 * no tenía ninguna señal.
 */
class PortadaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_dueno_puede_consultar_el_estado(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create(['portada_estado' => 'procesando']);

        $this->actingAs($user)
            ->getJson(route('comercios.portada.estado', $comercio))
            ->assertOk()
            ->assertJson(['estado' => 'procesando', 'listo' => false]);
    }

    public function test_un_tercero_no_puede_consultar_el_estado(): void
    {
        $ajeno = Comercio::factory()->create(['portada_estado' => 'procesando']);

        $this->actingAs(User::factory()->create())
            ->getJson(route('comercios.portada.estado', $ajeno))
            ->assertForbidden();
    }

    public function test_reintentar_vuelve_a_encolar_el_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create([
            'portada_url' => 'comercios/1/portada/original.jpg',
            'portada_estado' => 'error',
        ]);

        $this->actingAs($user)
            ->postJson(route('comercios.portada.reintentar', $comercio))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame('procesando', $comercio->refresh()->portada_estado);
        Queue::assertPushed(GenerateBrandThemeJob::class);
    }

    public function test_reintentar_sin_portada_no_encola_nada(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create(['portada_url' => null]);

        $this->actingAs($user)
            ->postJson(route('comercios.portada.reintentar', $comercio))
            ->assertStatus(422);

        Queue::assertNothingPushed();
    }

    public function test_el_punto_focal_se_guarda_y_no_regenera_imagenes(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create([
            'portada_url' => 'comercios/1/portada/original.jpg',
            'portada_estado' => 'listo',
            'portada_focal_x' => 50,
            'portada_focal_y' => 50,
        ]);

        $this->actingAs($user)->put(route('comercios.update', $comercio), [
            'nombre' => $comercio->nombre,
            'primary_color' => '#3B82F6',
            'secondary_color' => '#1E40AF',
            'accent_color' => '#F59E0B',
            'background_color' => '#FFFFFF',
            'chatbot_name' => 'Asistente IA',
            'portada_focal_x' => 50,
            'portada_focal_y' => 12,
        ])->assertRedirect();

        $comercio->refresh();
        $this->assertSame(12, $comercio->portada_focal_y);
        $this->assertSame('50% 12%', $comercio->focoPortada());

        // Mover el encuadre es sólo CSS: no cuesta un reprocesado.
        Queue::assertNothingPushed();
    }

    public function test_el_formulario_muestra_la_barra_cuando_esta_procesando(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create([
            'portada_url' => 'comercios/1/portada/original.jpg',
            'portada_estado' => 'procesando',
        ]);

        // La portada se configura en la pantalla del menú, no en la del comercio:
        // es lo primero que ve el cliente al escanear el QR, así que va con el
        // resto de lo que se le muestra.
        $this->actingAs($user)
            ->get(route('menus.create', $comercio))
            ->assertOk()
            ->assertSee('portadaProgreso')
            ->assertSee('Generando el tema');
    }

    public function test_la_pantalla_del_comercio_ya_no_trae_la_portada(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create([
            'portada_url' => 'comercios/1/portada/original.jpg',
            'portada_estado' => 'procesando',
        ]);

        // Quedarse duplicada era el riesgo: dos pantallas escribiendo el mismo
        // campo, cada una con su propio estado en pantalla.
        $this->actingAs($user)
            ->get(route('comercios.edit', $comercio))
            ->assertOk()
            ->assertDontSee('portadaProgreso')
            ->assertDontSee('name="portada"', false);
    }

    public function test_el_endpoint_ajax_guarda_encuadre_y_frase_sin_encolar(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create([
            'portada_url' => 'comercios/1/portada/original.jpg',
            'portada_estado' => 'listo',
            'portada_focal_x' => 50,
            'portada_focal_y' => 50,
            'tagline' => 'La de antes',
        ]);

        $this->actingAs($user)
            ->postJson(route('comercios.portada.guardar', $comercio), [
                'portada_focal_x' => 30,
                'portada_focal_y' => 70,
                'tagline' => 'Sabor de barrio',
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'estado' => 'listo']);

        $comercio->refresh();
        $this->assertSame('30% 70%', $comercio->focoPortada());
        $this->assertSame('Sabor de barrio', $comercio->tagline);
        Queue::assertNothingPushed();
    }

    public function test_un_tercero_no_puede_guardar_la_portada(): void
    {
        $ajeno = Comercio::factory()->create();

        $this->actingAs(User::factory()->create())
            ->postJson(route('comercios.portada.guardar', $ajeno), ['tagline' => 'Mía'])
            ->assertForbidden();

        $this->assertNull($ajeno->refresh()->tagline);
    }

    public function test_guardar_los_datos_del_comercio_no_borra_la_frase(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create(['tagline' => 'Sabor de barrio']);

        // El formulario del comercio ya no manda `tagline`. Si el controlador lo
        // escribiera igual con `?? null`, guardar la dirección le borraría la frase
        // al comercio sin que nadie tocara ese campo.
        $this->actingAs($user)->put(route('comercios.update', $comercio), [
            'nombre' => $comercio->nombre,
            'direccion' => 'Azopardo 1234',
            'primary_color' => '#3B82F6',
            'secondary_color' => '#1E40AF',
            'accent_color' => '#F59E0B',
            'background_color' => '#FFFFFF',
            'chatbot_name' => 'Asistente IA',
        ])->assertRedirect();

        $this->assertSame('Sabor de barrio', $comercio->refresh()->tagline);
    }
}
