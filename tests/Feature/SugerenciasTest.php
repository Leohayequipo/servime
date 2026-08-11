<?php

namespace Tests\Feature;

use App\Models\ChatConversation;
use App\Models\Comercio;
use App\Services\AnalyticsInsightsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * "Sugerencias para tu negocio": que no tire fruta.
 *
 * Cada una tiene que decir su número y no aparecer cuando el volumen es tan bajo que
 * dos consultas sueltas serían casualidad.
 */
class SugerenciasTest extends TestCase
{
    use RefreshDatabase;

    /** Métricas mínimas con la forma que espera suggestions(). */
    private function metrics(array $override = []): array
    {
        return array_merge([
            'funnel' => ['visitas' => 0, 'vieron_eleccion' => 0, 'eligieron' => 0, 'interesados' => 0, 'armaron_pedido' => 0],
            'topPromos' => new Collection,
        ], $override);
    }

    private function mensajes(Comercio $comercio, string $msg, int $veces): void
    {
        foreach (range(1, $veces) as $i) {
            ChatConversation::create([
                'comercio_id' => $comercio->id,
                'session_id' => 'sess_'.uniqid(),
                'user_message' => $msg,
                'bot_response' => 'ok',
                'used_openai' => true,
                'response_time_ms' => 1000,
            ]);
        }
    }

    private function textos(array $sugerencias): string
    {
        return implode(' | ', array_column($sugerencias, 'text'));
    }

    public function test_lo_que_piden_y_no_esta_va_primero_y_con_el_numero(): void
    {
        $comercio = Comercio::factory()->create();
        $demanda = [['consulta' => 'opciones veganas', 'veces' => 3]];

        $sug = (new AnalyticsInsightsService)->suggestions(
            $comercio, $this->metrics(), ['total' => 0, 'counts' => [], 'ia' => 0], $demanda
        );

        $this->assertStringContainsString('3 clientes preguntaron por "opciones veganas"', $sug[0]['text']);
    }

    public function test_una_sola_consulta_sin_respuesta_no_genera_sugerencia(): void
    {
        $comercio = Comercio::factory()->create();
        $demanda = [['consulta' => 'sushi', 'veces' => 1]];

        $sug = (new AnalyticsInsightsService)->suggestions(
            $comercio, $this->metrics(), ['total' => 0, 'counts' => [], 'ia' => 0], $demanda
        );

        $this->assertSame([], $sug);
    }

    public function test_con_poco_volumen_no_afirma_que_hay_demanda(): void
    {
        $comercio = Comercio::factory()->create();
        // 4 consultas de delivery alcanzaban el umbral viejo (3) pero el total es bajísimo.
        $this->mensajes($comercio, '¿Hacen delivery?', 4);
        $insights = new AnalyticsInsightsService;

        $sug = $insights->suggestions(
            $comercio, $this->metrics(), $insights->intents($comercio)
        );

        $this->assertSame([], $sug);
    }

    public function test_con_volumen_suficiente_avisa_y_dice_cuantos_preguntaron(): void
    {
        $comercio = Comercio::factory()->create();
        $this->mensajes($comercio, '¿Hacen delivery?', 6);
        $this->mensajes($comercio, 'Quiero una milanesa', 8);
        $insights = new AnalyticsInsightsService;

        $sug = $insights->suggestions(
            $comercio, $this->metrics(), $insights->intents($comercio)
        );

        $this->assertStringContainsString('6 personas preguntaron por envíos', $this->textos($sug));
    }

    public function test_el_consejo_cambia_si_el_comercio_ya_cargo_el_dato(): void
    {
        $insights = new AnalyticsInsightsService;

        // Sin horarios cargados: el consejo es que los cargue.
        $sinHorarios = Comercio::factory()->create(['horarios' => null]);
        $this->mensajes($sinHorarios, '¿Qué horario tienen?', 6);
        $this->mensajes($sinHorarios, 'quiero una milanesa', 8);

        $this->assertStringContainsString(
            'no lo tenés cargado',
            $this->textos($insights->suggestions($sinHorarios, $this->metrics(), $insights->intents($sinHorarios)))
        );

        // Con horarios cargados, el mismo dato significa otra cosa: no lo están viendo.
        // Antes le pedíamos cargar algo que ya había cargado, y eso quema la credibilidad.
        $conHorarios = Comercio::factory()->create(['horarios' => ['1' => [['11:00', '20:00']]]]);
        $this->mensajes($conHorarios, '¿Qué horario tienen?', 6);
        $this->mensajes($conHorarios, 'quiero una milanesa', 8);

        $texto = $this->textos($insights->suggestions($conHorarios, $this->metrics(), $insights->intents($conHorarios)));
        $this->assertStringContainsString('aunque lo tenés cargado', $texto);
        $this->assertStringNotContainsString('no lo tenés cargado', $texto);
    }

    public function test_avisa_en_que_tema_el_asistente_se_queda_sin_respuestas(): void
    {
        $comercio = Comercio::factory()->create();

        // 6 consultas por dieta y en 5 no encontró nada: falta etiquetar platos.
        foreach (range(1, 6) as $i) {
            ChatConversation::create([
                'comercio_id' => $comercio->id,
                'session_id' => 'sess_'.$i,
                'user_message' => '¿tienen algo sin tacc?',
                'bot_response' => 'ok',
                'platos_sugeridos' => $i === 1 ? 2 : 0,
                'used_openai' => true,
                'response_time_ms' => 900,
            ]);
        }
        $this->mensajes($comercio, 'quiero una milanesa', 8);

        $insights = new AnalyticsInsightsService;
        $texto = $this->textos($insights->suggestions($comercio, $this->metrics(), $insights->intents($comercio)));

        $this->assertStringContainsString('De 6 consultas sobre "dieta / restricciones", en 5', $texto);
    }

    public function test_el_embudo_dice_cuantas_personas_se_fueron(): void
    {
        $comercio = Comercio::factory()->create();

        $sug = (new AnalyticsInsightsService)->suggestions(
            $comercio,
            $this->metrics(['funnel' => ['visitas' => 40, 'vieron_eleccion' => 32, 'eligieron' => 13, 'interesados' => 0, 'armaron_pedido' => 0]]),
            ['total' => 0, 'counts' => [], 'ia' => 0]
        );

        $this->assertStringContainsString('19 de las 32 personas', $sug[0]['text']);
    }

    public function test_con_pocas_visitas_no_opina_del_embudo(): void
    {
        $comercio = Comercio::factory()->create();

        $sug = (new AnalyticsInsightsService)->suggestions(
            $comercio,
            $this->metrics(['funnel' => ['visitas' => 12, 'vieron_eleccion' => 11, 'eligieron' => 1, 'interesados' => 0, 'armaron_pedido' => 0]]),
            ['total' => 0, 'counts' => [], 'ia' => 0]
        );

        $this->assertSame([], $sug);
    }

    public function test_no_muestra_mas_de_cinco(): void
    {
        $comercio = Comercio::factory()->create();
        foreach (['¿Hacen delivery?', '¿Qué horario tienen?', '¿Se puede pagar con tarjeta?', '¿Puedo reservar mesa?', '¿Dónde están ubicados?', '¿Tienen opciones veganas?'] as $msg) {
            $this->mensajes($comercio, $msg, 5);
        }
        $insights = new AnalyticsInsightsService;

        $sug = $insights->suggestions(
            $comercio,
            $this->metrics(['funnel' => ['visitas' => 40, 'vieron_eleccion' => 32, 'eligieron' => 1, 'interesados' => 0, 'armaron_pedido' => 0]]),
            $insights->intents($comercio),
            [['consulta' => 'sushi', 'veces' => 4]]
        );

        $this->assertCount(5, $sug);
        // Y el orden manda: lo que piden y no está queda arriba de todo.
        $this->assertStringContainsString('sushi', $sug[0]['text']);
    }
}
