<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El desplegable de modelos del panel de planes.
 *
 * El riesgo que se cubre acá no es que falte una opción: es que un modelo cargado por SQL
 * —lo normal cuando sale uno nuevo— desaparezca del desplegable, y entonces tocar
 * cualquier otro campo del plan lo revierta sin avisar. Eso se descubre semanas después,
 * cuando la factura no baja.
 */
class ModeloDelPlanTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->superAdmin()->create();
    }

    public function test_el_desplegable_ofrece_luna(): void
    {
        $plan = Plan::where('key', 'premium')->first();

        $this->actingAs($this->admin())
            ->get("/admin/plans/{$plan->id}/edit")
            ->assertOk()
            ->assertSee('gpt-5.6-luna', false);
    }

    public function test_un_modelo_cargado_por_sql_sigue_apareciendo(): void
    {
        // El caso real: se carga un modelo nuevo por SQL antes de que exista en la lista.
        $plan = Plan::where('key', 'premium')->first();
        $plan->update(['modelo_ia' => 'gpt-9-todavia-no-existe']);

        $this->assertContains('gpt-9-todavia-no-existe', $plan->modelosParaElegir());

        $this->actingAs($this->admin())
            ->get("/admin/plans/{$plan->id}/edit")
            ->assertOk()
            ->assertSee('gpt-9-todavia-no-existe', false);
    }

    public function test_guardar_otro_campo_no_cambia_el_modelo(): void
    {
        // Es el bug que motiva todo esto: editar el precio no puede tocar el modelo.
        $plan = Plan::where('key', 'premium')->first();
        $plan->update(['modelo_ia' => 'gpt-5.6-luna']);

        $this->actingAs($this->admin())->put("/admin/plans/{$plan->id}", [
            'nombre' => $plan->nombre,
            'descripcion' => $plan->descripcion,
            'precio' => 12345,
            'mensajes_ia_mes' => $plan->mensajes_ia_mes,
            'modelo_ia' => 'gpt-5.6-luna',
            'max_tokens_chat' => $plan->max_tokens_chat ?? 900,
            'orden' => $plan->orden ?? 1,
        ]);

        $plan->refresh();
        $this->assertSame('gpt-5.6-luna', $plan->modelo_ia);
        $this->assertSame(12345.0, (float) $plan->precio);
    }

    public function test_no_se_repite_si_ya_esta_en_la_lista(): void
    {
        $plan = Plan::where('key', 'premium')->first();
        $plan->update(['modelo_ia' => 'gpt-4o']);

        $modelos = $plan->modelosParaElegir();

        $this->assertSame(count($modelos), count(array_unique($modelos)));
        $this->assertSame(Plan::MODELOS_IA, $modelos);
    }
}
