<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\Comercio;
use App\Services\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    private $chatbotService;

    public function __construct(ChatbotService $chatbotService)
    {
        $this->chatbotService = $chatbotService;
    }

    /**
     * Procesar mensaje del chatbot con OpenAI (usando slug)
     */
    public function chat(Request $request, Comercio $comercio): JsonResponse
    {
        try {
            $request->validate([
                'message' => 'required|string|max:500',
                'tipo' => 'nullable|string|in:categoria,ver_mas',
                'scope' => 'nullable|string|max:500',
            ]);

            $message = $request->input('message');
            $modoRespuesta = $request->input('tipo'); // 'categoria' | 'ver_mas' | null
            $scopeRespuesta = $modoRespuesta ? $request->input('scope') : null;
            $startTime = microtime(true);
            // DEBUG: Log del mensaje recibido
            \Log::info('Chatbot Debug - Mensaje recibido', [
                'comercio_id' => $comercio->id,
                'message' => $message,
                'message_length' => strlen($message),
            ]);

            // Obtener session_id persistente ANTES de procesar
            $sessionId = $this->getSessionId($request, $comercio);

            // Verificar si las respuestas con IA están habilitadas
            if (! $comercio->enable_ai_responses) {
                throw new \Exception('Respuestas con IA deshabilitadas');
            }

            // Verificar cuota mensual de IA según el plan del comercio
            if (! $comercio->puedeUsarIA()) {
                $limitResponse = 'Por el momento el asistente no está disponible. '
                    .'Podés ver el menú completo en esta misma página. ¡Gracias por tu paciencia!';
                $responseTime = round((microtime(true) - $startTime) * 1000);
                $this->logConversation($comercio, $message, $limitResponse, false, $responseTime, $request, $sessionId);

                return response()->json([
                    'response' => $limitResponse,
                    'timestamp' => now(),
                    'success' => true,
                ]);
            }

            // Usar el servicio de chatbot con OpenAI (pasar sessionId para memoria de conversación)
            $response = $this->chatbotService->processMessage($comercio, $message, $sessionId, $modoRespuesta, $scopeRespuesta);
            $responseTime = round((microtime(true) - $startTime) * 1000);

            // Contabilizar el consumo de IA del mes (para la cuota del plan)
            $comercio->registrarUsoIA();

            // Log de la conversación (usar session_id obtenido anteriormente)
            $this->logConversation($comercio, $message, $response, true, $responseTime, $request, $sessionId);

            // Platos del menú mencionados en la respuesta (datos exactos del JSON: foto/precio/botón)
            $platos = $this->chatbotService->detectarPlatosMencionados($comercio, $response);

            return response()->json([
                'response' => $response,
                'platos' => $platos,
                'timestamp' => now(),
                'success' => true,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Error de validación
            \Log::error('Error de validación en ChatbotController: '.$e->getMessage());

            return response()->json([
                'response' => 'Por favor, envía un mensaje válido.',
                'timestamp' => now(),
                'success' => false,
                'error' => 'Error de validación',
            ], 400);
        } catch (\Exception $e) {
            // Log del error para debugging
            \Log::error('Error en ChatbotController: '.$e->getMessage(), [
                'comercio_id' => $comercio->id ?? null,
                'comercio_slug' => $request->route('comercio')?->slug ?? 'unknown',
                'message' => $message ?? 'N/A',
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            // Fallback a respuesta básica si OpenAI falla
            try {
                $fallbackResponse = $this->generateFallbackResponse($comercio, $message ?? '');
                $responseTime = isset($startTime) ? round((microtime(true) - $startTime) * 1000) : 0;

                // Log de la conversación con fallback (generar session_id si no existe)
                $sessionId = $this->getSessionId($request, $comercio);
                $this->logConversation($comercio, $message ?? '', $fallbackResponse, false, $responseTime, $request, $sessionId);

                return response()->json([
                    'response' => $fallbackResponse,
                    'timestamp' => now(),
                    'success' => false,
                    'error' => 'Error al procesar el mensaje. Usando respuesta básica.',
                ]);
            } catch (\Exception $fallbackError) {
                // Si incluso el fallback falla, devolver un error genérico
                \Log::error('Error en fallback de ChatbotController: '.$fallbackError->getMessage());

                return response()->json([
                    'response' => 'Lo siento, hubo un error al procesar tu mensaje. Por favor, intenta de nuevo.',
                    'timestamp' => now(),
                    'success' => false,
                    'error' => 'Error crítico',
                ], 500);
            }
        }
    }

    /**
     * Respuesta de fallback si OpenAI no está disponible
     */
    private function generateFallbackResponse(Comercio $comercio, string $message): string
    {
        $message = strtolower($message);

        if (str_contains($message, 'hola') || str_contains($message, 'buenas')) {
            return "¡Hola! Bienvenido a {$comercio->nombre}. ¿En qué puedo ayudarte con nuestro menú?";
        }

        if (str_contains($message, 'menu') || str_contains($message, 'platos')) {
            return 'Tenemos varios menús disponibles. Puedes verlos en la página. ¿Te interesa algún tipo de comida en particular?';
        }

        if (str_contains($message, 'precio') || str_contains($message, 'cuesta')) {
            return 'Los precios están disponibles en nuestros menús. ¿Hay algún plato específico que te interese?';
        }

        if (str_contains($message, 'horario') || str_contains($message, 'abierto')) {
            return "Para información sobre horarios, te recomiendo contactar directamente con {$comercio->nombre}.";
        }

        return 'Gracias por tu mensaje. Estoy aquí para ayudarte con información sobre nuestro menú. ¿Hay algo específico que te gustaría saber?';
    }

    /**
     * Log de conversación en la base de datos
     */
    private function logConversation(Comercio $comercio, string $message, string $response, bool $usedOpenAI, int $responseTime, Request $request, ?string $sessionId = null): void
    {
        try {
            // Si no se pasó session_id, obtenerlo
            if (! $sessionId) {
                $sessionId = $this->getSessionId($request, $comercio);
            }

            ChatConversation::create([
                'comercio_id' => $comercio->id,
                'session_id' => $sessionId,
                'user_message' => $message,
                'bot_response' => $response,
                'used_openai' => $usedOpenAI,
                'response_time_ms' => $responseTime,
                'user_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'comercio_name' => $comercio->nombre,
                    'message_length' => strlen($message),
                    'response_length' => strlen($response),
                    'timestamp' => now()->toISOString(),
                    'session_id_source' => $request->header('X-Session-ID') ? 'header' : 'inferred',
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error logging conversation: '.$e->getMessage());
        }
    }

    /**
     * Generar o obtener session ID persistente
     */
    private function getSessionId(Request $request, Comercio $comercio): string
    {
        // 1. Primero intentar obtener del header (lo más confiable)
        $sessionId = $request->header('X-Session-ID');

        if ($sessionId) {
            return $sessionId;
        }

        // 2. Intentar encontrar una sesión reciente basada en IP + User Agent + Comercio
        // (dentro de los últimos 30 minutos para considerar la misma sesión)
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $thirtyMinutesAgo = now()->subMinutes(30);

        $recentSession = ChatConversation::where('comercio_id', $comercio->id)
            ->where('user_ip', $ip)
            ->where('user_agent', $userAgent)
            ->where('created_at', '>=', $thirtyMinutesAgo)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($recentSession && $recentSession->session_id) {
            // Reutilizar session_id de la conversación reciente
            return $recentSession->session_id;
        }

        // 3. Si no hay sesión reciente, generar una nueva
        $sessionId = 'session_'.uniqid().'_'.time();

        return $sessionId;
    }
}
