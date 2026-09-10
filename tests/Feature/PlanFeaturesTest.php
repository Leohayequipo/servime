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

            $this->actingAs($admin)
                ->put(route('admin.plans.update', $plan), $this->datos([$feature]))
                ->assertSessionHasNoErrors("La feature «{$feature}» se ofrece en el panel pero el validador la rechaza.");

            $this->assertSame([$feature], $plan->fresh()->features);
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
}
