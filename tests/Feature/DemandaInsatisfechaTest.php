<?php

namespace Tests\Feature;

use App\Models\ChatConversation;
use App\Models\Comercio;
use App\Services\AnalyticsInsightsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemandaInsatisfechaTest extends TestCase
{
    use RefreshDatabase;

    private function conversacion(Comercio $comercio, string $msg, ?string $falta, string $origen = 'escrito'): void
    {
        ChatConversation::create([
            'comercio_id' => $comercio->id,
            'session_id' => 'sess_'.uniqid(),
            'user_message' => $msg,
            'origen' => $origen,
            'falta' => $falta,
            'platos_sugeridos' => 0,
            'bot_response' => 'ok',
            'used_openai' => true,
            'response_time_ms' => 1000,
        ]);
    }

    public function test_ver_mas_no_vuelve_a_contar_el_mismo_faltante(): void
    {
        $comercio = Comercio::factory()->create();

        // El cliente pregunta una vez y después toca "Ver más opciones": la interfaz
        // reenvía la consulta y el modelo declara el mismo faltante de nuevo.
        $this->conversacion($comercio, '¿Tienen sushi?', 'sushi');
        $this->conversacion($comercio, 'Mostrame todas las opciones que coincidan con lo que te pregunté.', 'sushi', 'ver_mas');

        $demanda = (new AnalyticsInsightsService)->demandaInsatisfecha($comercio);

        $this->assertCount(1, $demanda);
        $this->assertSame('sushi', $demanda[0]['consulta']);
        $this->assertSame(1, $demanda[0]['veces']);
    }

    public function test_los_chips_de_categoria_tampoco_cuentan(): void
    {
        $comercio = Comercio::factory()->create();

        $this->conversacion($comercio, 'Mostrame TODAS las opciones de la categoría Postres', 'helado', 'categoria');

        $this->assertSame([], (new AnalyticsInsightsService)->demandaInsatisfecha($comercio));
    }

    public function test_si_el_plato_esta_en_la_carta_no_es_demanda_sino_una_falla_del_asistente(): void
    {
        $comercio = Comercio::factory()->create();
        \App\Models\Menu::factory()->activo()->create([
            'comercio_id' => $comercio->id,
            'contexto_gpt' => json_encode(['categorias' => [
                ['nombre' => 'Fritos', 'platos' => [['nombre' => 'Rabas', 'precio' => '$26.000']]],
            ]], JSON_UNESCAPED_UNICODE),
        ]);

        // El modelo declaró que no tenía rabas… con las rabas en el menú. Le pasó de verdad,
        // seis veces, y el panel llegó a sugerirle al comercio que las sumara a la carta.
        $this->conversacion($comercio, '¿tienen rabas?', 'rabas');
        $this->conversacion($comercio, '¿tienen sushi?', 'sushi');

        $insights = new AnalyticsInsightsService;

        // Al comercio solo le llega lo que de verdad no tiene.
        $this->assertSame([['consulta' => 'sushi', 'veces' => 1]], $insights->demandaInsatisfecha($comercio));

        // Y lo otro no se pierde: es nuestro problema, y se mira desde el panel de admin.
        $this->assertSame([['consulta' => 'rabas', 'veces' => 1]], $insights->faltantesQueSiEstan($comercio));
    }

    public function test_dos_clientes_que_lo_piden_cuentan_dos_veces(): void
    {
        $comercio = Comercio::factory()->create();

        $this->conversacion($comercio, '¿Tienen algo sin TACC?', 'Sin TACC');
        $this->conversacion($comercio, 'hay opciones sin tacc?', 'sin tacc');
        $this->conversacion($comercio, '¿Tienen sushi?', 'sushi');

        $demanda = (new AnalyticsInsightsService)->demandaInsatisfecha($comercio);

        // Se agrupa por concepto normalizado y gana la forma más frecuente (empate = la primera).
        $this->assertCount(2, $demanda);
        $this->assertSame(2, $demanda[0]['veces']);
        $this->assertSame('Sin TACC', $demanda[0]['consulta']);
    }
}
