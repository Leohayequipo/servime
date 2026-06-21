<?php

namespace App\Services;

use App\Models\Comercio;
use App\Models\IntentClassificationRun;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Clasifica con IA la intención de los mensajes del chat que el rule-based
 * no alcanzó. Incremental (solo mensajes sin intent) y en lotes para bajar costo.
 * Premium (gateado en la ruta). Registra cada corrida para seguimiento del super admin.
 */
class IntentClassifierService
{
    private const MODELO = 'gpt-4o-mini';

    private const LOTE = 40;        // mensajes por llamada

    private const MAX_MENSAJES = 400; // tope por corrida (acota costo/tiempo)

    /**
     * Clasifica los mensajes pendientes (sin intent) de los últimos 30 días.
     *
     * @return array{procesados:int, error?:string}
     */
    public function classifyPending(Comercio $comercio): array
    {
        $apiKey = config('services.openai.api_key');
        if (empty($apiKey)) {
            return ['procesados' => 0, 'error' => 'OpenAI API key no configurada'];
        }

        $pendientes = $comercio->chatConversations()
            ->whereNull('intent')
            ->where('created_at', '>=', now()->subDays(30))
            ->orderBy('created_at', 'desc')
            ->limit(self::MAX_MENSAJES)
            ->get(['id', 'user_message']);

        if ($pendientes->isEmpty()) {
            return ['procesados' => 0];
        }

        $categorias = AnalyticsInsightsService::categorias();
        $procesados = 0;

        foreach ($pendientes->chunk(self::LOTE) as $lote) {
            $clasificadas = $this->clasificarLote($lote->pluck('user_message')->all(), $categorias, $apiKey);

            foreach ($lote->values() as $i => $conv) {
                $cat = $clasificadas[$i] ?? 'Otras';
                $conv->update(['intent' => $cat, 'intent_source' => 'ia']);
                $procesados++;
            }
        }

        IntentClassificationRun::create([
            'comercio_id' => $comercio->id,
            'mensajes_procesados' => $procesados,
            'modelo' => self::MODELO,
        ]);

        return ['procesados' => $procesados];
    }

    /**
     * Manda un lote de mensajes a la IA y devuelve la categoría de cada uno (en orden).
     *
     * @param  array<int, string>  $mensajes
     * @param  array<int, string>  $categorias
     * @return array<int, string>
     */
    private function clasificarLote(array $mensajes, array $categorias, string $apiKey): array
    {
        $lista = '';
        foreach ($mensajes as $i => $m) {
            $lista .= ($i + 1).'. '.str_replace(["\n", "\r"], ' ', (string) $m)."\n";
        }

        $system = 'Sos un clasificador de intención para el chat de un restaurante. '
            .'Para cada mensaje de cliente devolvé UNA categoría EXACTA de esta lista: '
            .implode(', ', $categorias).'. '
            .'Respondé SOLO un JSON con la forma {"categorias": ["...", "..."]} en el MISMO orden y cantidad que los mensajes.';

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post(config('services.openai.base_url', 'https://api.openai.com/v1').'/chat/completions', [
                'model' => self::MODELO,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $lista],
                ],
                'temperature' => 0,
                'response_format' => ['type' => 'json_object'],
            ]);

            if (! $response->successful()) {
                Log::error('IntentClassifier: error OpenAI', ['status' => $response->status()]);

                return [];
            }

            $contenido = $response->json('choices.0.message.content', '{}');
            $data = json_decode($contenido, true);
            $cats = is_array($data['categorias'] ?? null) ? $data['categorias'] : [];

            // Validar que cada categoría esté en la lista permitida
            return array_map(fn ($c) => in_array($c, $categorias, true) ? $c : 'Otras', $cats);
        } catch (\Throwable $e) {
            Log::error('IntentClassifier: excepción', ['msg' => $e->getMessage()]);

            return [];
        }
    }
}
