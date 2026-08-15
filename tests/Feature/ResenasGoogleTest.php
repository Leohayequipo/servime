<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Pedido de reseña en Google.
 *
 * Lo que se cuida acá es que el dato viva en el comercio y no se pierda solo, y que el
 * bloque no aparezca cuando no corresponde: un botón que abre la nada es peor que no
 * tener el botón.
 */
class ResenasGoogleTest extends TestCase
{
    use RefreshDatabase;

    /** `pro` trae analytics; `basico` no, y es el que sirve para probar el gating. */
    private function comercioConPlan(string $plan = 'pro', array $attrs = []): Comercio
    {
        return Comercio::factory()->create(array_merge([
            'plan_id' => Plan::where('key', $plan)->value('id'),
        ], $attrs));
    }

    public function test_no_pide_resena_sin_link_aunque_este_activo(): void
    {
        $comercio = $this->comercioConPlan(attrs: [
            'resenas_activo' => true,
            'google_review_url' => null,
        ]);

        // El switch prendido no alcanza: el botón abriría la nada.
        $this->assertFalse($comercio->pideResena());
    }

    public function test_no_pide_resena_con_link_pero_apagado(): void
    {
        $comercio = $this->comercioConPlan(attrs: [
            'resenas_activo' => false,
            'google_review_url' => 'https://g.page/r/abc',
        ]);

        $this->assertFalse($comercio->pideResena());
    }

    public function test_pide_resena_con_las_dos_cosas(): void
    {
        $comercio = $this->comercioConPlan(attrs: [
            'resenas_activo' => true,
            'google_review_url' => 'https://g.page/r/abc',
        ]);

        $this->assertTrue($comercio->pideResena());
    }

    public function test_el_bloque_solo_se_inyecta_en_el_chat_cuando_corresponde(): void
    {
        $comercio = $this->comercioConPlan(attrs: [
            'resenas_activo' => false,
            'google_review_url' => 'https://g.page/r/abc',
        ]);

        // Ojo: `smResena` a secas no sirve como marca, porque el chat siempre trae la
        // llamada al contador (con optional chaining, así es inofensiva sin el partial).
        // Lo que tiene que faltar es el bloque y, sobre todo, la URL.
        // Se busca el host y no la URL entera: la URL viaja por `@json`, que escapa las
        // barras (`https:\/\/g.page\/...`). El host no tiene barras y no se toca.
        // El assertOk NO es decorativo: sin él, un 500 pasa el test igual, porque la
        // pantalla de error de Laravel vuelca el código fuente de la vista y ahí aparecen
        // todas las cadenas que se buscan. Ya pasó una vez.
        $this->get("/menu/{$comercio->slug}/chat")
            ->assertOk()
            ->assertDontSee('bloque-resena', false)
            ->assertDontSee('g.page', false);

        $comercio->update(['resenas_activo' => true]);

        $this->get("/menu/{$comercio->slug}/chat")
            ->assertOk()
            ->assertSee('bloque-resena', false)
            ->assertSee('g.page', false);
    }

    public function test_borrar_el_logo_no_borra_el_link_de_resenas(): void
    {
        // El borrado de logo reusa el endpoint de update con un request PARCIAL. Con el
        // patrón `?? null` de sus vecinos, esto le borraba al comercio el link sin que
        // nadie tocara ese campo.
        $user = User::factory()->create();
        $comercio = $this->comercioConPlan(attrs: [
            'user_id' => $user->id,
            'google_review_url' => 'https://g.page/r/abc',
            'resenas_activo' => true,
        ]);

        $this->actingAs($user)->put("/comercios/{$comercio->slug}", [
            'nombre' => $comercio->nombre,
            'delete_logo' => '1',
            'primary_color' => '#3B82F6',
            'secondary_color' => '#1E40AF',
            'accent_color' => '#F59E0B',
            'background_color' => '#FFFFFF',
            'chatbot_name' => 'Asistente IA',
        ]);

        $comercio->refresh();
        $this->assertSame('https://g.page/r/abc', $comercio->google_review_url);
        $this->assertTrue($comercio->resenas_activo);
    }

    public function test_vaciar_el_link_apaga_el_switch_solo(): void
    {
        $user = User::factory()->create();
        $comercio = $this->comercioConPlan(attrs: [
            'user_id' => $user->id,
            'google_review_url' => 'https://g.page/r/abc',
            'resenas_activo' => true,
        ]);

        $this->actingAs($user)->put("/comercios/{$comercio->slug}", [
            'nombre' => $comercio->nombre,
            'google_review_url' => '',
            'primary_color' => '#3B82F6',
            'secondary_color' => '#1E40AF',
            'accent_color' => '#F59E0B',
            'background_color' => '#FFFFFF',
            'chatbot_name' => 'Asistente IA',
        ]);

        $comercio->refresh();
        $this->assertNull($comercio->google_review_url);
        $this->assertFalse($comercio->resenas_activo);
    }

    public function test_el_link_tiene_que_ser_http(): void
    {
        $user = User::factory()->create();
        $comercio = $this->comercioConPlan(attrs: ['user_id' => $user->id]);

        // Termina como href en el menú público: un `javascript:` ahí es XSS.
        $this->actingAs($user)->put("/comercios/{$comercio->slug}", [
            'nombre' => $comercio->nombre,
            'google_review_url' => 'javascript:alert(1)',
            'primary_color' => '#3B82F6',
            'secondary_color' => '#1E40AF',
            'accent_color' => '#F59E0B',
            'background_color' => '#FFFFFF',
            'chatbot_name' => 'Asistente IA',
        ])->assertSessionHasErrors('google_review_url');

        $this->assertNull($comercio->refresh()->google_review_url);
    }

    public function test_no_se_puede_activar_sin_link(): void
    {
        $user = User::factory()->create();
        $comercio = $this->comercioConPlan(attrs: [
            'user_id' => $user->id,
            'google_review_url' => null,
        ]);

        $this->actingAs($user)
            ->postJson("/comercios/{$comercio->slug}/menus/update-resenas", ['activo' => true])
            ->assertStatus(422);

        $this->assertFalse($comercio->refresh()->resenas_activo);
    }

    public function test_el_switch_guarda(): void
    {
        $user = User::factory()->create();
        $comercio = $this->comercioConPlan(attrs: [
            'user_id' => $user->id,
            'google_review_url' => 'https://g.page/r/abc',
        ]);

        $this->actingAs($user)
            ->postJson("/comercios/{$comercio->slug}/menus/update-resenas", ['activo' => true])
            ->assertOk()
            ->assertJson(['success' => true, 'activo' => true]);

        $this->assertTrue($comercio->refresh()->resenas_activo);
    }

    public function test_no_se_puede_tocar_el_switch_de_otro_comercio(): void
    {
        $ajeno = User::factory()->create();
        $comercio = $this->comercioConPlan(attrs: [
            'google_review_url' => 'https://g.page/r/abc',
        ]);

        $this->actingAs($ajeno)
            ->postJson("/comercios/{$comercio->slug}/menus/update-resenas", ['activo' => true])
            ->assertStatus(403);
    }

    public function test_sin_la_feature_del_plan_no_se_puede_activar(): void
    {
        $user = User::factory()->create();
        $comercio = $this->comercioConPlan('basico', attrs: [
            'user_id' => $user->id,
            'google_review_url' => 'https://g.page/r/abc',
        ]);

        $this->actingAs($user)
            ->postJson("/comercios/{$comercio->slug}/menus/update-resenas", ['activo' => true])
            ->assertStatus(403);
    }

    public function test_las_pantallas_del_panel_abren(): void
    {
        // Ninguna de las dos estaba cubierta, y un error de Blade acá es un 500 en la cara
        // del comercio que no aparece en ningún otro test.
        $user = User::factory()->create();
        $comercio = $this->comercioConPlan(attrs: [
            'user_id' => $user->id,
            'google_review_url' => 'https://g.page/r/abc',
            'resenas_activo' => true,
        ]);

        $this->actingAs($user)
            ->get("/comercios/{$comercio->slug}/edit")
            ->assertOk()
            ->assertSee('Link de reseñas de Google', false);

        $this->actingAs($user)
            ->get("/comercios/{$comercio->slug}/menus/create")
            ->assertOk()
            ->assertSee('resenas-switch', false);
    }

    public function test_el_switch_no_aparece_sin_la_feature_del_plan(): void
    {
        $user = User::factory()->create();
        $comercio = $this->comercioConPlan('basico', attrs: [
            'user_id' => $user->id,
            'google_review_url' => 'https://g.page/r/abc',
        ]);

        $this->actingAs($user)
            ->get("/comercios/{$comercio->slug}/menus/create")
            ->assertOk()
            ->assertDontSee('resenas-switch', false);
    }

    public function test_los_textos_por_defecto_no_se_guardan_en_la_columna(): void
    {
        // Quedan NULL para que una mejora de redacción llegue a todos los que nunca
        // editaron. Sembrados, cada comercio quedaría congelado con la versión del día
        // que se dio de alta.
        $comercio = $this->comercioConPlan();

        $this->assertNull($comercio->resena_titulo);
        $this->assertSame(Comercio::RESENA_TITULO, $comercio->resenaTitulo());
        $this->assertSame(Comercio::RESENA_TEXTO, $comercio->resenaTexto());
    }

    public function test_el_comercio_puede_editar_los_textos(): void
    {
        $user = User::factory()->create();
        $comercio = $this->comercioConPlan(attrs: [
            'user_id' => $user->id,
            'google_review_url' => 'https://g.page/r/abc',
            'resenas_activo' => true,
        ]);

        $this->actingAs($user)
            ->postJson("/comercios/{$comercio->slug}/menus/update-resenas", [
                'titulo' => '¿Se fueron contentos?',
                'texto' => 'Contalo en Google, nos ayuda un montón.',
            ])
            ->assertOk()
            ->assertJson(['por_defecto' => false]);

        $comercio->refresh();
        $this->assertSame('¿Se fueron contentos?', $comercio->resenaTitulo());

        // Y el cliente los ve en el menú.
        $this->get("/menu/{$comercio->slug}/chat")
            ->assertOk()
            ->assertSee('Se fueron contentos', false);
    }

    public function test_guardar_los_textos_no_apaga_el_switch(): void
    {
        // El interruptor y los textos se guardan por separado, cada uno con su botón: un
        // request que solo trae textos no puede pisar `activo`.
        $user = User::factory()->create();
        $comercio = $this->comercioConPlan(attrs: [
            'user_id' => $user->id,
            'google_review_url' => 'https://g.page/r/abc',
            'resenas_activo' => true,
        ]);

        $this->actingAs($user)
            ->postJson("/comercios/{$comercio->slug}/menus/update-resenas", ['titulo' => 'Hola'])
            ->assertOk();

        $this->assertTrue($comercio->refresh()->resenas_activo);
    }

    public function test_vaciar_los_textos_vuelve_a_los_de_fabrica(): void
    {
        $user = User::factory()->create();
        $comercio = $this->comercioConPlan(attrs: [
            'user_id' => $user->id,
            'resena_titulo' => 'Algo propio',
            'resena_texto' => 'Otro texto',
        ]);

        $this->actingAs($user)
            ->postJson("/comercios/{$comercio->slug}/menus/update-resenas", [
                'titulo' => '',
                'texto' => '   ',
            ])
            ->assertOk()
            ->assertJson(['por_defecto' => true]);

        $comercio->refresh();
        // NULL y no cadena vacía: si no, el cliente vería un bloque sin título.
        $this->assertNull($comercio->resena_titulo);
        $this->assertSame(Comercio::RESENA_TITULO, $comercio->resenaTitulo());
    }

    public function test_las_metricas_viven_en_analytics_y_no_en_el_editor(): void
    {
        // Configurar y medir son pantallas distintas: en el editor va el interruptor y los
        // textos, en analytics los números.
        $user = User::factory()->create();
        $comercio = $this->comercioConPlan(attrs: [
            'user_id' => $user->id,
            'google_review_url' => 'https://g.page/r/abc',
            'resenas_activo' => true,
        ]);

        DB::table('tracking_events')->insert([
            ['comercio_id' => $comercio->id, 'event' => 'resena_view', 'session_id' => 's1', 'created_at' => now(), 'updated_at' => now()],
            ['comercio_id' => $comercio->id, 'event' => 'resena_click', 'session_id' => 's1', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->actingAs($user)
            ->get("/comercios/{$comercio->slug}/menus/create")
            ->assertOk()
            ->assertSee('resenas-switch', false)
            ->assertDontSee('Se les mostró', false);

        $this->actingAs($user)
            ->get("/analytics/comercio/{$comercio->slug}")
            ->assertOk()
            ->assertSee('Reseñas en Google', false)
            ->assertSee('Se les mostró', false);
    }

    public function test_ignorados_no_puede_dar_negativo(): void
    {
        // Si el tracking pierde una impresión (bloqueador, pestaña cerrada antes del
        // beacon), los clicks pueden superarlas y la resta se iría abajo de cero.
        $comercio = $this->comercioConPlan();

        DB::table('tracking_events')->insert([
            ['comercio_id' => $comercio->id, 'event' => 'resena_click', 'session_id' => 's1', 'created_at' => now(), 'updated_at' => now()],
            ['comercio_id' => $comercio->id, 'event' => 'resena_dismiss', 'session_id' => 's2', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->assertSame(0, $comercio->estadisticasResenas()['ignorados']);
    }

    public function test_el_evento_de_cierre_se_registra(): void
    {
        $comercio = $this->comercioConPlan();

        $this->postJson("/api/track/{$comercio->slug}", [
            'event' => 'resena_dismiss',
            'session_id' => 'sesion-uno',
        ])->assertNoContent();

        $this->assertSame(1, DB::table('tracking_events')->where('event', 'resena_dismiss')->count());
    }

    public function test_los_eventos_de_resena_se_registran(): void
    {
        $comercio = $this->comercioConPlan();

        foreach (['resena_view', 'resena_click'] as $evento) {
            $this->postJson("/api/track/{$comercio->slug}", [
                'event' => $evento,
                'session_id' => 'sesion-uno',
            ])->assertNoContent();
        }

        $this->assertSame(1, DB::table('tracking_events')->where('event', 'resena_view')->count());
        $this->assertSame(1, DB::table('tracking_events')->where('event', 'resena_click')->count());
    }

    public function test_las_estadisticas_cuentan_personas_y_no_eventos(): void
    {
        $comercio = $this->comercioConPlan();

        // La misma sesión mandando el evento dos veces (reintento de red, doble toque) no
        // puede contar como dos personas: inflaría el número y arruinaría el porcentaje.
        foreach (['sesion-uno', 'sesion-uno', 'sesion-dos'] as $sesion) {
            DB::table('tracking_events')->insert([
                'comercio_id' => $comercio->id,
                'event' => 'resena_view',
                'session_id' => $sesion,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('tracking_events')->insert([
            'comercio_id' => $comercio->id,
            'event' => 'resena_click',
            'session_id' => 'sesion-uno',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $stats = $comercio->estadisticasResenas();

        $this->assertSame(2, $stats['impresiones']);
        $this->assertSame(1, $stats['clicks']);
    }

    public function test_las_estadisticas_no_miran_otros_comercios(): void
    {
        $comercio = $this->comercioConPlan();
        $otro = $this->comercioConPlan();

        DB::table('tracking_events')->insert([
            'comercio_id' => $otro->id,
            'event' => 'resena_view',
            'session_id' => 'sesion-uno',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(0, $comercio->estadisticasResenas()['impresiones']);
    }
}
