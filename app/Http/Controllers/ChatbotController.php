<?php

namespace App\Http\Controllers;

use App\Models\Comercio;
use App\Models\ChatConversation;
use App\Services\ChatbotService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChatbotController extends Controller
{
    private $chatbotService;

    public function __construct(ChatbotService $chatbotService)
    {
        $this->chatbotService = $chatbotService;
    }

    /**
     * Procesar mensaje del chatbot con OpenAI
     */
    public function chat(Request $request, $comercioId): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:500'
        ]);

        $comercio = Comercio::findOrFail($comercioId);
        $message = $request->input('message');
        $startTime = microtime(true);
        
        try {
            // Usar el servicio de chatbot con OpenAI
            $response = $this->chatbotService->processMessage($comercio, $message);
            $responseTime = round((microtime(true) - $startTime) * 1000);
            
            // Log de la conversación
            $this->logConversation($comercio, $message, $response, true, $responseTime, $request);
            
            return response()->json([
                'response' => $response,
                'timestamp' => now(),
                'success' => true
            ]);
            
        } catch (\Exception $e) {
            // Log del error para debugging
            \Log::error('Error en ChatbotController: ' . $e->getMessage(), [
                'comercio_id' => $comercioId,
                'message' => $message,
                'exception' => $e
            ]);
            
            // Fallback a respuesta básica si OpenAI falla
            $fallbackResponse = $this->generateFallbackResponse($comercio, $message);
            $responseTime = round((microtime(true) - $startTime) * 1000);
            
            // Log de la conversación con fallback
            $this->logConversation($comercio, $message, $fallbackResponse, false, $responseTime, $request);
            
            return response()->json([
                'response' => $fallbackResponse,
                'timestamp' => now(),
                'success' => false,
                'error' => 'OpenAI no disponible, usando respuesta básica'
            ]);
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
            return "Tenemos varios menús disponibles. Puedes verlos en la página. ¿Te interesa algún tipo de comida en particular?";
        }
        
        if (str_contains($message, 'precio') || str_contains($message, 'cuesta')) {
            return "Los precios están disponibles en nuestros menús. ¿Hay algún plato específico que te interese?";
        }
        
        if (str_contains($message, 'horario') || str_contains($message, 'abierto')) {
            return "Para información sobre horarios, te recomiendo contactar directamente con {$comercio->nombre}.";
        }
        
        return "Gracias por tu mensaje. Estoy aquí para ayudarte con información sobre nuestro menú. ¿Hay algo específico que te gustaría saber?";
    }

    /**
     * Log de conversación en la base de datos
     */
    private function logConversation(Comercio $comercio, string $message, string $response, bool $usedOpenAI, int $responseTime, Request $request): void
    {
        try {
            ChatConversation::create([
                'comercio_id' => $comercio->id,
                'session_id' => $this->getSessionId($request),
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
                    'timestamp' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error logging conversation: ' . $e->getMessage());
        }
    }

    /**
     * Generar o obtener session ID
     */
    private function getSessionId(Request $request): string
    {
        // Intentar obtener session ID del header o generar uno nuevo
        $sessionId = $request->header('X-Session-ID');
        
        if (!$sessionId) {
            $sessionId = 'session_' . uniqid() . '_' . time();
        }
        
        return $sessionId;
    }
}
