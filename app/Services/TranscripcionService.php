<?php

namespace App\Services;

use App\Models\Comercio;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pasa a texto los audios del chat (el botón del micrófono, estilo WhatsApp).
 *
 * Antes el dictado lo hacía el navegador (Web Speech API), y en muchos teléfonos el
 * botón ni aparecía: Firefox, los navegadores de Samsung y Xiaomi y el navegador interno
 * de Instagram no la traen. Grabar audio (MediaRecorder) sí lo soportan casi todos, así
 * que el navegador graba y la transcripción se hace acá.
 *
 * El audio no se guarda: se transcribe y se descarta.
 */
class TranscripcionService
{
    /** Tope de una nota de voz. El front corta solo al llegar acá. */
    public const SEGUNDOS_MAXIMOS = 30;

    /**
     * El texto del audio, o null si no se pudo (OpenAI caído, audio vacío o ilegible).
     */
    public function transcribir(UploadedFile $audio, Comercio $comercio, string $locale): ?string
    {
        $apiKey = config('services.openai.api_key');
        if (empty($apiKey)) {
            return null;
        }

        try {
            $respuesta = Http::withHeaders(['Authorization' => 'Bearer '.$apiKey])
                ->timeout(30)
                // OpenAI decide el formato por la extensión del nombre, no por el contenido:
                // Safari graba mp4 y Chrome webm, y el blob llega sin nombre útil.
                ->attach('file', file_get_contents($audio->getRealPath()), 'audio.'.$this->extension($audio))
                ->post(config('services.openai.base_url', 'https://api.openai.com/v1').'/audio/transcriptions', [
                    'model' => config('services.openai.transcribe_model'),
                    // El idioma en el que está la interfaz: sin esto, un "hola" corto puede
                    // salir transcripto en otro idioma.
                    'language' => substr($locale, 0, 2),
                    'prompt' => $this->vocabulario($comercio),
                    'response_format' => 'json',
                ]);

            if (! $respuesta->successful()) {
                Log::warning('Transcripción: respuesta no exitosa', [
                    'comercio_id' => $comercio->id,
                    'status' => $respuesta->status(),
                    'body' => mb_substr($respuesta->body(), 0, 300),
                ]);

                return null;
            }

            $texto = trim((string) $respuesta->json('text'));

            return $texto !== '' ? $texto : null;
        } catch (\Throwable $e) {
            Log::warning('Transcripción: error', ['comercio_id' => $comercio->id, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Los nombres de la carta, como pista para el modelo.
     *
     * Los platos tienen nombres que no están en ningún diccionario ("Capresse", "Barrica
     * Burger", "J y Q"): con la lista delante el modelo los escribe como están en la carta,
     * y así después la búsqueda del menú los encuentra.
     */
    private function vocabulario(Comercio $comercio): string
    {
        $nombres = array_column(app(ChatbotService::class)->platosDelMenu($comercio), 'nombre');
        $texto = $comercio->nombre.'. '.implode(', ', array_unique($nombres));

        // El prompt de transcripción es corto: lo que no entra se pierde igual.
        return mb_substr($texto, 0, 800);
    }

    private function extension(UploadedFile $audio): string
    {
        $mime = (string) $audio->getMimeType();

        return match (true) {
            str_contains($mime, 'webm') => 'webm',
            str_contains($mime, 'ogg') => 'ogg',
            str_contains($mime, 'mp4'), str_contains($mime, 'm4a'), str_contains($mime, 'aac') => 'mp4',
            str_contains($mime, 'mpeg') => 'mp3',
            str_contains($mime, 'wav') => 'wav',
            default => 'webm',
        };
    }
}
