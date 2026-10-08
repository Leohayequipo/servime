<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Las features que el panel ofrece tienen que ser las mismas que el validador acepta.
 *
 * Se desincronizaron en producción: al sumar `mesas` y `comandas` se agregaron los
 * checkboxes a la vista pero no al `Rule::in` del controlador, así que el super admin las
 * veía, las marcaba, y guardar respondía "The selected features.5 is invalid". La opción
 * existía y no servía para nada.
 *
 * Ahora las dos salen de `Plan::FEATURES`, y esto lo verifica una por una: si alguien
 * agrega la próxima feature en un solo lado, este test falla antes del deploy.
 */
class PlanFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private function plan(array $features = []): Plan
    {
        return Plan::create([
            'key' => 'test-'.uniqid(),
            'nombre' => 'Test',
            'mensajes_ia_mes' => 1000,
            'modelo_ia' => 'gpt-4o-mini',
            'max_tokens_chat' => 600,
            'features' => $features,
            'activo' => true,
        ]);
    }

    private function datos(array $features): array
    {
        return [
            'nombre' => 'Test',
            'modelo_ia' => 'gpt-4o-mini',
            'max_tokens_chat' => 600,
            'features' => $features,
        ];
    }

    public function test_todas_las_features_del_panel_se_pueden_guardar(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        // Una por una: si sólo se probaran todas juntas, una inválida haría fallar el lote
        // sin decir cuál es.
        foreach (array_keys(Plan::FEATURES) as $feature) {
            $plan = $this->plan();

            // Con las casillas que necesita (el asistente lo trae el plan: 1000 mensajes).
            $features = [...array_diff(Plan::REQUISITOS[$feature] ?? [], ['asistente']), $feature];

            $this->actingAs($admin)
                ->put(route('admin.plans.update', $plan), $this->datos($features))
                ->assertSessionHasNoErrors("La feature «{$feature}» se ofrece en el panel pero el validador la rechaza.");

            $this->assertSame($features, $plan->fresh()->features);
        }
    }

    public function test_se_pueden_marcar_todas_a_la_vez(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $plan = $this->plan();
        $todas = array_keys(Plan::FEATURES);

        $this->actingAs($admin)
            ->put(route('admin.plans.update', $plan), $this->datos($todas))
            ->assertSessionHasNoErrors();

        $this->assertSame($todas, $plan->fresh()->features);
    }

    public function test_una_feature_inventada_se_sigue_rechazando(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $plan = $this->plan(['analytics']);

        $this->actingAs($admin)
            ->put(route('admin.plans.update', $plan), $this->datos(['analytics', 'poderes_magicos']))
            ->assertSessionHasErrors('features.1');

        // Y no se guardó nada a medias.
        $this->assertSame(['analytics'], $plan->fresh()->features);
    }

    public function test_el_panel_muestra_una_casilla_por_feature(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $plan = $this->plan();

        $respuesta = $this->actingAs($admin)->get(route('admin.plans.edit', $plan));

        $respuesta->assertOk();
        foreach (Plan::FEATURES as $clave => $etiqueta) {
            $respuesta->assertSee('value="'.$clave.'"', false);
            $respuesta->assertSee($etiqueta, false);
        }
    }

    public function test_desmarcar_todas_deja_el_plan_sin_features(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $plan = $this->plan(['analytics', 'mesas']);

        $datos = $this->datos([]);
        unset($datos['features']);   // un formulario sin ninguna casilla marcada no manda la clave

        $this->actingAs($admin)->put(route('admin.plans.update', $plan), $datos)->assertSessionHasNoErrors();

        $this->assertSame([], $plan->fresh()->features);
    }

    public function test_tildar_una_trae_las_casillas_que_necesita(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $plan = $this->plan();

        // La clasificación vive en el panel de Analytics; las comandas son por mesa. No se
        // traba el guardado: se completa, como hace el formulario al tildarlas.
        $this->actingAs($admin)
            ->put(route('admin.plans.update', $plan), $this->datos(['comandas', 'intencion_ia']))
            ->assertSessionHasNoErrors();

        $this->assertSame(['analytics', 'intencion_ia', 'mesas', 'comandas'], $plan->fresh()->features);
    }

    public function test_sin_asistente_la_clasificacion_de_intencion_no_se_guarda(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $plan = $this->plan();

        // Clasifica los mensajes del chat: en un plan sin asistente no hay qué clasificar.
        // Se guarda el resto, sin error.
        $this->actingAs($admin)
            ->put(route('admin.plans.update', $plan), $this->datos(['analytics', 'intencion_ia']) + ['mensajes_ia_mes' => 0])
            ->assertSessionHasNoErrors();

        $this->assertSame(['analytics'], $plan->fresh()->features);

        // Vacío es ilimitado, o sea que tiene asistente.
        $this->actingAs($admin)
            ->put(route('admin.plans.update', $plan), $this->datos(['analytics', 'intencion_ia']) + ['mensajes_ia_mes' => ''])
            ->assertSessionHasNoErrors();

        $this->assertSame(['analytics', 'intencion_ia'], $plan->fresh()->features);
    }

    public function test_en_el_panel_cada_casilla_cuelga_de_la_que_necesita(): void
    {
        $orden = Plan::featuresParaElPanel();

        // Todas, cada una una vez.
        $this->assertEqualsCanonicalizing(array_keys(Plan::FEATURES), array_keys($orden));

        // Y la que depende va justo debajo de la suya.
        $claves = array_keys($orden);
        $this->assertSame('analytics', $orden['intencion_ia']);
        $this->assertSame(array_search('analytics', $claves) + 1, array_search('intencion_ia', $claves));
        $this->assertSame('mesas', $orden['comandas']);
        $this->assertSame(array_search('mesas', $claves) + 1, array_search('comandas', $claves));
        $this->assertNull($orden['analytics']);
    }

    public function test_toda_feature_tiene_su_texto_para_el_cliente(): void
    {
        // Pasó: se sumaron cuatro features y la landing mostraba "lista_pedido" tal cual.
        foreach (array_keys(Plan::FEATURES) as $feature) {
            $this->assertArrayHasKey($feature, Plan::FEATURES_PUBLICAS, "La feature «{$feature}» no tiene texto público.");
        }
    }

    public function test_la_landing_no_muestra_claves_internas(): void
    {
        $plan = $this->plan(array_keys(Plan::FEATURES));
        $plan->update(['orden' => 1, 'precio' => 1000]);

        $html = $this->get('/')->assertOk()->getContent();

        foreach (array_keys(Plan::FEATURES) as $feature) {
            $this->assertStringContainsString(e(Plan::nombrePublico($feature)), $html);
            if (str_contains($feature, '_') || $feature !== strtolower(Plan::nombrePublico($feature))) {
                $this->assertDoesNotMatchRegularExpression('/>\s*'.preg_quote($feature, '/').'\s*</', $html, "La landing muestra «{$feature}» tal cual.");
            }
        }
    }
}
