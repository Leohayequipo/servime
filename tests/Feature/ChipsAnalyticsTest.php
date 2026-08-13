<?php

namespace Tests\Feature;

use App\Models\ChatConversation;
use App\Models\Comercio;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rendimiento de los atajos en el panel.
 *
 * Se agrupa por el `id` del chip, que sale del texto + la pregunta: si se agrupara por
 * texto, editarle la frase a un atajo mezclaría en un solo número dos versiones que
 * rindieron distinto, y eso es justo lo que se quiere comparar.
 */
class ChipsAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function comercio(User $user): Comercio
    {
        $plan = Plan::create([
            'key' => 'test-'.uniqid(), 'nombre' => 'Test', 'mensajes_ia_mes' => 100,
            'modelo_ia' => 'gpt-4o-mini', 'max_tokens_chat' => 600,
            'features' => ['analytics'], 'activo' => true,
        ]);

        return Comercio::factory()->for($user)->create(['plan_id' => $plan->id]);
    }

    private function click(Comercio $comercio, string $sessionId, ?array $chip, int $platos = 1, string $origen = 'chip_chat'): ChatConversation
    {
        return ChatConversation::create([
            'comercio_id' => $comercio->id,
            'session_id' => $sessionId,
            'user_message' => $chip['label'] ?? 'texto',
            'bot_response' => 'ok',
            'origen' => $origen,
            'platos_sugeridos' => $platos,
            'used_openai' => true,
            'metadata' => $chip ? ['chip' => $chip] : [],
        ]);
    }

    private function stats(Comercio $comercio): array
    {
        return \App\Support\Chip::rendimiento($comercio->id, 30);
    }

    public function test_dos_versiones_del_mismo_atajo_se_miden_por_separado(): void
    {
        $comercio = $this->comercio(User::factory()->create());

        $this->click($comercio, 's1', ['id' => 'aaa11111', 'label' => 'Algo dulce']);
        $this->click($comercio, 's2', ['id' => 'aaa11111', 'label' => 'Algo dulce']);
        $this->click($comercio, 's3', ['id' => 'bbb22222', 'label' => 'Postres para cerrar']);

        $filas = collect($this->stats($comercio))->keyBy('label');

        $this->assertSame(2, $filas['Algo dulce']['clicks']);
        $this->assertSame(1, $filas['Postres para cerrar']['clicks']);
    }

    public function test_el_nombre_sale_del_click_y_no_de_la_configuracion_actual(): void
    {
        $comercio = $this->comercio(User::factory()->create());
        $this->click($comercio, 's1', ['id' => 'ccc33333', 'label' => 'Un atajo que despues borré']);

        // El comercio no tiene ese chip configurado y aun así el reporte lo nombra.
        $this->assertSame([], $comercio->chips ?? []);
        $this->assertSame('Un atajo que despues borré', $this->stats($comercio)[0]['label']);
    }

    public function test_mide_si_el_atajo_sirvio_para_algo(): void
    {
        $comercio = $this->comercio(User::factory()->create());

        // Un atajo muy tocado pero sin platos es una promesa que el menú no puede cumplir.
        $this->click($comercio, 's1', ['id' => 'ddd44444', 'label' => 'Para compartir'], platos: 0);
        $this->click($comercio, 's2', ['id' => 'ddd44444', 'label' => 'Para compartir'], platos: 0);
        $this->click($comercio, 's3', ['id' => 'ddd44444', 'label' => 'Para compartir'], platos: 3);

        $fila = $this->stats($comercio)[0];

        $this->assertSame(3, $fila['clicks']);
        $this->assertSame(1, $fila['con_platos']);
        $this->assertSame(33, $fila['efectividad']);
    }

    public function test_distingue_el_atajo_que_abrio_la_conversacion(): void
    {
        $comercio = $this->comercio(User::factory()->create());

        // En 's1' el chip es lo primero; en 's2' el cliente ya venía escribiendo.
        $this->click($comercio, 's1', ['id' => 'eee55555', 'label' => 'Rompe el hielo']);
        ChatConversation::create([
            'comercio_id' => $comercio->id, 'session_id' => 's2', 'user_message' => 'hola',
            'bot_response' => 'ok', 'origen' => 'escrito', 'used_openai' => true,
        ]);
        $this->click($comercio, 's2', ['id' => 'eee55555', 'label' => 'Rompe el hielo']);

        $fila = $this->stats($comercio)[0];

        $this->assertSame(2, $fila['clicks']);
        $this->assertSame(1, $fila['arranques']);
    }

    public function test_los_clicks_viejos_sin_id_no_se_pierden(): void
    {
        $comercio = $this->comercio(User::factory()->create());

        // Anteriores a los atajos configurables: se agrupan por su texto.
        $this->click($comercio, 's1', null, origen: 'chip');
        $this->click($comercio, 's2', null, origen: 'chip');

        $filas = $this->stats($comercio);

        $this->assertCount(1, $filas);
        $this->assertSame(2, $filas[0]['clicks']);
        $this->assertSame('anterior', $filas[0]['superficie']);
    }

    public function test_separa_los_atajos_de_la_portada_de_los_del_chat(): void
    {
        $comercio = $this->comercio(User::factory()->create());

        $this->click($comercio, 's1', ['id' => 'fff66666', 'label' => 'Del chat'], origen: 'chip_chat');
        $this->click($comercio, 's2', ['id' => 'ggg77777', 'label' => 'De portada'], origen: 'chip_portada');

        $filas = collect($this->stats($comercio))->keyBy('label');

        $this->assertSame('chat', $filas['Del chat']['superficie']);
        $this->assertSame('portada', $filas['De portada']['superficie']);
    }

    public function test_el_panel_del_comercio_muestra_el_widget(): void
    {
        $user = User::factory()->create();
        $comercio = $this->comercio($user);
        $this->click($comercio, 's1', ['id' => 'hhh88888', 'label' => 'Meriendas para compartir']);

        $this->actingAs($user)
            ->get(route('analytics.comercio', $comercio))
            ->assertOk()
            ->assertSee('Meriendas para compartir')
            ->assertSee('Preguntaron');
    }

    public function test_cuenta_los_toques_de_la_portada_aunque_no_lleguen_a_preguntar(): void
    {
        // El toque en la portada y la consulta al asistente son dos cosas distintas: se
        // puede tocar el atajo y abandonar antes de que responda. Esa diferencia es la que
        // ninguno de los dos widgets anteriores mostraba.
        $comercio = $this->comercio(User::factory()->create());

        foreach ([1, 2, 3] as $i) {
            \Illuminate\Support\Facades\DB::table('tracking_events')->insert([
                'comercio_id' => $comercio->id,
                'session_id' => "s$i",
                'event' => 'menu_choice',
                'data' => json_encode(['choice' => 'ia', 'desde' => 'chip', 'label' => 'Ver promos', 'chip' => 'iii99999']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Solo uno de los tres llegó a preguntar.
        $this->click($comercio, 's1', ['id' => 'iii99999', 'label' => 'Ver promos'], origen: 'chip_portada');

        $fila = $this->stats($comercio)[0];

        $this->assertSame(3, $fila['clicks'], 'los tres toques');
        $this->assertSame(1, $fila['preguntas'], 'uno solo preguntó');
        $this->assertSame(2, $fila['abandonos'], 'dos se fueron antes');
    }
}
