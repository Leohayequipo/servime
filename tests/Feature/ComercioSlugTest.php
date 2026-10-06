<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El slug es la URL pública del menú (/menu/{slug}) y está impresa en los QR
 * de las mesas: no puede chocar al crear ni cambiar al renombrar.
 */
class ComercioSlugTest extends TestCase
{
    use RefreshDatabase;

    private function datosValidos(array $extra = []): array
    {
        return array_merge([
            'nombre' => 'Azopardo 2',
            'descripcion' => 'El mejor bar del mundo',
            'direccion' => 'Lebenson',
            'horario' => 'lunes a viernes',
            'primary_color' => '#3B82F6',
            'secondary_color' => '#1E40AF',
            'accent_color' => '#F59E0B',
            'background_color' => '#FFFFFF',
            'chatbot_name' => 'Asistente IA',
        ], $extra);
    }

    public function test_nombre_repetido_no_rompe_y_genera_slug_con_sufijo(): void
    {
        Comercio::factory()->create(['nombre' => 'Azopardo 2', 'slug' => 'azopardo-2']);

        $admin = User::factory()->create(['role' => 'super_admin']);
        $dueno = User::factory()->create();

        // store() busca el plan Pro para asignarlo por defecto. Las migraciones
        // ya lo siembran, así que no se puede crear a ciegas.
        Plan::firstOrCreate(['key' => 'pro'], [
            'nombre' => 'Pro', 'mensajes_ia_mes' => 1000,
            'modelo_ia' => 'gpt-4o-mini', 'max_tokens_chat' => 600,
            'features' => [], 'activo' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('comercios.store'), $this->datosValidos([
            'user_id' => $dueno->id,
        ]));

        $response->assertRedirect();
        $this->assertDatabaseHas('comercios', ['slug' => 'azopardo-2-2', 'user_id' => $dueno->id]);
    }

    public function test_renombrar_actualiza_el_slug_y_archiva_el_anterior(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create([
            'nombre' => 'Azopardo Cade Bar',
            'slug' => 'azopardo-cade-bar',
        ]);

        $this->actingAs($user)->put(
            route('comercios.update', $comercio),
            $this->datosValidos(['nombre' => 'Azopardo Cafe Bar'])
        )->assertRedirect();

        $comercio->refresh();
        $this->assertSame('azopardo-cafe-bar', $comercio->slug);
        $this->assertDatabaseHas('comercio_slugs', [
            'comercio_id' => $comercio->id,
            'slug' => 'azopardo-cade-bar',
        ]);
    }

    public function test_la_url_vieja_sigue_llevando_al_menu(): void
    {
        $comercio = Comercio::factory()->create([
            'nombre' => 'Azopardo Cafe Bar',
            'slug' => 'azopardo-cafe-bar',
        ]);
        $comercio->slugsAnteriores()->create(['slug' => 'azopardo-cade-bar']);

        // Esto es lo que pasa cuando alguien escanea un QR impreso antes del
        // renombrado: tiene que ver el menú, no un 404.
        $this->get('/menu/azopardo-cade-bar')->assertOk();
    }

    public function test_super_admin_puede_fijar_la_url_a_mano(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $comercio = Comercio::factory()->create([
            'nombre' => 'Azopardo Cafe Bar',
            'slug' => 'azopardo-cafe-bar',
        ]);

        $this->actingAs($admin)->put(
            route('comercios.update', $comercio),
            $this->datosValidos(['nombre' => 'Azopardo Cafe Bar', 'slug' => 'azopardo'])
        )->assertRedirect();

        $comercio->refresh();
        $this->assertSame('azopardo', $comercio->slug);
        $this->assertTrue($comercio->slug_manual);
        // La vieja sigue viva. Se sigue el redirect porque un comercio sin asistente entra
        // directo a la carta: lo que importa acá es que la URL no dé 404.
        $this->followingRedirects()->get('/menu/azopardo-cafe-bar')->assertOk();
    }

    public function test_una_url_fijada_a_mano_no_se_pisa_al_renombrar(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create([
            'nombre' => 'Azopardo Cafe Bar',
            'slug' => 'azopardo',
            'slug_manual' => true,
        ]);

        $this->actingAs($user)->put(
            route('comercios.update', $comercio),
            $this->datosValidos(['nombre' => 'Azopardo Cafe Bar & Resto'])
        )->assertRedirect();

        $this->assertSame('azopardo', $comercio->refresh()->slug);
    }

    public function test_no_se_puede_tomar_una_url_que_otro_comercio_tuvo(): void
    {
        $otro = Comercio::factory()->create(['slug' => 'la-esquina-nueva']);
        $otro->slugsAnteriores()->create(['slug' => 'la-esquina']);

        $admin = User::factory()->create(['role' => 'super_admin']);
        $comercio = Comercio::factory()->create(['slug' => 'bar-nuevo']);

        // Reutilizarla mandaría los QR viejos del otro comercio a este menú.
        $this->actingAs($admin)->put(
            route('comercios.update', $comercio),
            $this->datosValidos(['nombre' => 'Bar Nuevo', 'slug' => 'la-esquina'])
        )->assertSessionHasErrors('slug');

        $this->assertSame('bar-nuevo', $comercio->refresh()->slug);
    }

    public function test_slug_unico_ignora_al_propio_comercio(): void
    {
        $comercio = Comercio::factory()->create(['nombre' => 'La Esquina', 'slug' => 'la-esquina']);

        $this->assertSame('la-esquina', Comercio::generarSlugUnico('La Esquina', $comercio->id));
        $this->assertSame('la-esquina-2', Comercio::generarSlugUnico('La Esquina'));
    }
}
