<?php

namespace Tests\Feature;

use App\Services\ChatbotService;
use Tests\TestCase;

/**
 * El body de /chat/completions no es el mismo para todas las familias de modelos.
 *
 * Los GPT-5 razonan y rechazan con HTTP 400 cuatro parámetros que los GPT-4 aceptan.
 * Sin esto, cambiarle el modelo a un plan desde /admin/plans dejaba el chat devolviendo
 * 400 en cada consulta, y el comercio se quedaba sin chatbot sin que nadie lo tocara.
 */
class ModeloOpenAiPayloadTest extends TestCase
{
    private function payload(string $modelo, bool $stream = false): array
    {
        $servicio = new ChatbotService;
        $metodo = new \ReflectionMethod($servicio, 'payloadOpenAI');
        $metodo->setAccessible(true);

        return $metodo->invoke($servicio, $modelo, [['role' => 'user', 'content' => 'hola']], 900, $stream);
    }

    public function test_los_modelos_gpt4_conservan_los_parametros_de_siempre(): void
    {
        $payload = $this->payload('gpt-4o-mini');

        $this->assertSame(900, $payload['max_tokens']);
        $this->assertSame(0.7, $payload['temperature']);
        $this->assertArrayHasKey('presence_penalty', $payload);
        $this->assertArrayHasKey('frequency_penalty', $payload);
        $this->assertArrayNotHasKey('max_completion_tokens', $payload);
    }

    public function test_los_modelos_de_razonamiento_usan_max_completion_tokens(): void
    {
        foreach (['gpt-5.6-luna', 'gpt-5-mini', 'gpt-5-nano', 'gpt-5.4', 'o3'] as $modelo) {
            $payload = $this->payload($modelo);

            // Los 900 de la respuesta más 1.500 para pensar: el razonamiento se descuenta
            // del mismo tope, y sin la reserva luna devolvió una respuesta vacía.
            $this->assertSame(900 + 1500, $payload['max_completion_tokens'], $modelo);
            $this->assertArrayNotHasKey('max_tokens', $payload, $modelo);
        }
    }

    public function test_los_modelos_de_razonamiento_no_mandan_temperature_ni_penalties(): void
    {
        // Verificado contra la API: cada uno de estos devuelve 400 con gpt-5.6-luna.
        $payload = $this->payload('gpt-5.6-luna');

        $this->assertArrayNotHasKey('temperature', $payload);
        $this->assertArrayNotHasKey('presence_penalty', $payload);
        $this->assertArrayNotHasKey('frequency_penalty', $payload);
    }

    public function test_el_stream_se_agrega_en_las_dos_familias(): void
    {
        $this->assertTrue($this->payload('gpt-4o-mini', true)['stream']);
        $this->assertTrue($this->payload('gpt-5.6-luna', true)['stream']);

        $this->assertArrayNotHasKey('stream', $this->payload('gpt-4o-mini'));
        $this->assertArrayNotHasKey('stream', $this->payload('gpt-5.6-luna'));
    }

    public function test_el_modelo_y_los_mensajes_van_siempre(): void
    {
        foreach (['gpt-4o-mini', 'gpt-5.6-luna'] as $modelo) {
            $payload = $this->payload($modelo);

            $this->assertSame($modelo, $payload['model']);
            $this->assertSame([['role' => 'user', 'content' => 'hola']], $payload['messages']);
        }
    }
}
