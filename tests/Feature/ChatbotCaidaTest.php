<?php

namespace Tests\Feature;

use App\Models\ChatConversation;
use App\Models\Comercio;
use App\Models\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Qué pasa cuando OpenAI se cae o se queda sin crédito.
 *
 * El chat contesta igual con un texto de respaldo, pero eso NO puede quedar registrado
 * como si la IA hubiera atendido: la tarjeta "Sin responder" del panel es la que avisa
 * que el asistente está caído, y con el flag hardcodeado marcaba 0 mientras OpenAI
 * rechazaba absolutamente todo.
 */
class ChatbotCaidaTest extends TestCase
{
    use RefreshDatabase;

    private function comercioConMenu(): Comercio
    {
        $comercio = Comercio::factory()->create(['enable_ai_responses' => true]);
        Menu::factory()->create(['comercio_id' => $comercio->id]);

        return $comercio;
    }

    /**
     * Ejecuta el stream y descarta lo que emite.
     *
     * No se usa streamedContent(): el callback del controller hace ob_end_flush() de TODOS
     * los niveles de buffer (lo necesita para que los tokens salgan en vivo) y se lleva
     * puesto el que abre PHPUnit, que después revienta con "No buffer to delete".
     */
    private function correrStream($respuesta): void
    {
        $nivel = ob_get_level();
        ob_start();
        $respuesta->baseResponse->sendContent();

        // El callback cierra hasta el nivel 0, incluidos los buffers que abrió PHPUnit.
        // Se reponen para dejar el nivel como estaba: si no, PHPUnit marca el test como
        // risky ("closed output buffers other than its own").
        while (ob_get_level() > $nivel) {
            ob_end_clean();
        }
        while (ob_get_level() < $nivel) {
            ob_start();
        }
    }

    /** Lo que devuelve OpenAI cuando se acabó el crédito. */
    private function sinCredito(): void
    {
        config(['services.openai.api_key' => 'test-key']);
        Http::fake([
            '*/chat/completions' => Http::response([
                'error' => ['message' => 'You have no credits remaining.', 'code' => 'credit_balance_exhausted'],
            ], 429),
        ]);
    }

    public function test_si_openai_no_responde_la_consulta_queda_marcada_como_sin_responder(): void
    {
        $this->sinCredito();
        $comercio = $this->comercioConMenu();

        $this->correrStream($this->post(route('chat.stream', $comercio), [
            'message' => '¿Qué me recomendás?',
            'session_id' => 'sess_caida',
        ]));

        $conv = ChatConversation::forComercio($comercio->id)->latest('id')->first();

        $this->assertNotNull($conv, 'La consulta tiene que quedar registrada aunque falle la IA.');
        $this->assertFalse((bool) $conv->used_openai);
    }

    public function test_una_caida_de_openai_no_le_consume_cuota_al_comercio(): void
    {
        $this->sinCredito();
        $comercio = $this->comercioConMenu();
        $usadosAntes = (int) $comercio->fresh()->ia_mensajes_usados;

        $this->correrStream($this->post(route('chat.stream', $comercio), [
            'message' => '¿Tienen postres?',
            'session_id' => 'sess_caida2',
        ]));

        $this->assertSame($usadosAntes, (int) $comercio->fresh()->ia_mensajes_usados);
    }
}
