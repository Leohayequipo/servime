<?php

namespace App\Support;

/**
 * Arma el body de /chat/completions según la familia del modelo.
 *
 * Los GPT-5 (y los `o*`) razonan y rechazan con HTTP 400 cuatro parámetros que los GPT-4
 * aceptan: `max_tokens` (piden `max_completion_tokens`), `temperature` distinta del default,
 * `presence_penalty` y `frequency_penalty`.
 *
 * Vive acá y no en ChatbotService porque lo necesitan los dos chats que llaman a OpenAI —el
 * asistente del menú y el chat de ventas de la landing— y con una copia en cada uno alcanza
 * con arreglar un solo lado para que el otro empiece a devolver 400 sin que nadie lo note.
 *
 * ⚠️ Al apuntar un chat a un modelo de razonamiento hay que revisar también el límite de
 * tokens: el razonamiento se descuenta de `max_completion_tokens`, así que un valor pensado
 * para un GPT-4 puede consumirse entero pensando y devolver una respuesta vacía.
 */
trait ArmaPayloadOpenAI
{
    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array{temperature?: float, presence_penalty?: float, frequency_penalty?: float}  $ajustesClasicos
     *                                                                                                            Solo se aplican a las familias que los aceptan; en razonamiento se ignoran.
     * @return array<string, mixed>
     */
    private function payloadOpenAI(string $model, array $messages, int $maxTokens, bool $stream = false, array $ajustesClasicos = []): array
    {
        $payload = ['model' => $model, 'messages' => $messages];

        if ($this->esModeloDeRazonamiento($model)) {
            $payload['max_completion_tokens'] = $maxTokens;
        } else {
            $payload['max_tokens'] = $maxTokens;
            $payload['temperature'] = $ajustesClasicos['temperature'] ?? 0.7;        // Más creatividad para descripciones gourmet
            $payload['presence_penalty'] = $ajustesClasicos['presence_penalty'] ?? 0.3;  // Evita repeticiones
            $payload['frequency_penalty'] = $ajustesClasicos['frequency_penalty'] ?? 0.2; // Fomenta variedad en vocabulario
        }

        if ($stream) {
            $payload['stream'] = true;
            // Sin esto el stream no informa el consumo y las charlas reales —que van casi
            // todas por streaming— quedarían sin medir.
            $payload['stream_options'] = ['include_usage' => true];
        }

        return $payload;
    }

    /** Familias que usan la API de razonamiento (`max_completion_tokens`, sin penalties). */
    private function esModeloDeRazonamiento(string $model): bool
    {
        return (bool) preg_match('/^(gpt-5|o[1-9])/i', $model);
    }
}
