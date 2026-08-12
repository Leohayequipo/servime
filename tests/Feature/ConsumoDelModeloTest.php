<?php

namespace Tests\Feature;

use App\Services\ChatbotService;
use Tests\TestCase;

/**
 * El `usage` que devuelve OpenAI en cada respuesta se descartaba.
 *
 * Sin él no hay forma de saber cuánto cuesta realmente cada comercio —la cuota cuenta
 * MENSAJES, y un menú de 291 platos consume el doble que uno de 42 gastando lo mismo—
 * ni si el descuento del prompt caching está entrando de verdad en producción.
 */
class ConsumoDelModeloTest extends TestCase
{
    private function registrar(ChatbotService $servicio, ?array $usage, string $modelo = 'gpt-5.6-luna'): void
    {
        $metodo = new \ReflectionMethod($servicio, 'registrarConsumo');
        $metodo->setAccessible(true);
        $metodo->invoke($servicio, $usage, $modelo);
    }

    private function payload(string $modelo, bool $stream): array
    {
        $servicio = new ChatbotService;
        $metodo = new \ReflectionMethod($servicio, 'payloadOpenAI');
        $metodo->setAccessible(true);

        return $metodo->invoke($servicio, $modelo, [['role' => 'user', 'content' => 'hola']], 900, $stream);
    }

    public function test_guarda_los_tokens_de_entrada_salida_y_cache(): void
    {
        $servicio = new ChatbotService;

        $this->registrar($servicio, [
            'prompt_tokens' => 7333,
            'completion_tokens' => 123,
            'prompt_tokens_details' => ['cached_tokens' => 6940],
            'completion_tokens_details' => ['reasoning_tokens' => 60],
        ]);

        $this->assertSame([
            'modelo' => 'gpt-5.6-luna',
            'tokens_entrada' => 7333,
            'tokens_cacheados' => 6940,
            'tokens_salida' => 123,
            'tokens_razonamiento' => 60,
        ], $servicio->ultimoConsumo());
    }

    public function test_los_modelos_sin_razonamiento_ni_cache_quedan_en_cero(): void
    {
        $servicio = new ChatbotService;

        // gpt-4o-mini no informa reasoning_tokens, y en la primera llamada el caché está frío.
        $this->registrar($servicio, ['prompt_tokens' => 6954, 'completion_tokens' => 105], 'gpt-4o-mini');

        $consumo = $servicio->ultimoConsumo();
        $this->assertSame(0, $consumo['tokens_cacheados']);
        $this->assertSame(0, $consumo['tokens_razonamiento']);
        $this->assertSame('gpt-4o-mini', $consumo['modelo']);
    }

    public function test_sin_llamada_al_modelo_no_hay_consumo(): void
    {
        $servicio = new ChatbotService;

        // Cuota agotada o fallback: la respuesta no salió del modelo, no hay nada que registrar.
        $this->assertSame([], $servicio->ultimoConsumo());

        $this->registrar($servicio, null);
        $this->assertSame([], $servicio->ultimoConsumo());

        $this->registrar($servicio, []);
        $this->assertSame([], $servicio->ultimoConsumo());
    }

    public function test_el_stream_pide_el_consumo_explicitamente(): void
    {
        // Sin `include_usage` el stream no informa nada, y las charlas reales van casi
        // todas por streaming: quedarían todas sin medir.
        foreach (['gpt-4o-mini', 'gpt-5.6-luna'] as $modelo) {
            $this->assertSame(
                ['include_usage' => true],
                $this->payload($modelo, true)['stream_options'],
                $modelo
            );
            $this->assertArrayNotHasKey('stream_options', $this->payload($modelo, false), $modelo);
        }
    }
}
