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
            // DEBUG: Log del mensaje recibido
            \Log::info('Chatbot Debug - Mensaje recibido', [
                'comercio_id' => $comercio->id,
                'message' => $message,
                'message_length' => strlen($message)
            ]);
            
            // Primero verificar si es una pregunta frecuente configurada
            $frequentAnswer = $this->checkFrequentQuestion($comercio, $message);
            
            // DEBUG: Log del resultado de verificación
            \Log::info('Chatbot Debug - Verificación pregunta frecuente', [
                'comercio_id' => $comercio->id,
                'found_frequent_answer' => !empty($frequentAnswer),
                'frequent_answer' => $frequentAnswer ? substr($frequentAnswer, 0, 100) . '...' : null
            ]);
            
            if ($frequentAnswer) {
                $responseTime = round((microtime(true) - $startTime) * 1000);
                
                // Log de la conversación con respuesta frecuente
                $this->logConversation($comercio, $message, $frequentAnswer, false, $responseTime, $request);
                
                return response()->json([
                    'response' => $frequentAnswer,
                    'timestamp' => now(),
                    'success' => true
                ]);
            }
            
            // Verificar si las respuestas con IA están habilitadas
            if (!$comercio->enable_ai_responses) {
                throw new \Exception('Respuestas con IA deshabilitadas');
            }
            
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

    /**
     * Verificar si el mensaje coincide con una pregunta frecuente
     */
    private function checkFrequentQuestion(Comercio $comercio, string $message): ?string
    {
        $questions = $comercio->quick_questions;
        
        // DEBUG: Log de las preguntas frecuentes
        \Log::info('Chatbot Debug - Preguntas frecuentes disponibles', [
            'comercio_id' => $comercio->id,
            'total_questions' => count($questions),
            'questions' => array_map(function($q) {
                return [
                    'question' => $q['question'] ?? 'N/A',
                    'answer' => !empty($q['answer']) ? 'Tiene respuesta' : 'Sin respuesta',
                    'category' => $q['category'] ?? 'N/A'
                ];
            }, $questions)
        ]);
        
        if (empty($questions)) {
            \Log::info('Chatbot Debug - No hay preguntas frecuentes configuradas');
            return null;
        }
        
        // Normalizar el mensaje para comparación (asegurar UTF-8)
        $normalizedMessage = mb_strtolower(trim($message), 'UTF-8');
        
        \Log::info('Chatbot Debug - Comparando mensaje', [
            'original_message' => $message,
            'normalized_message' => $normalizedMessage
        ]);
        
        foreach ($questions as $index => $question) {
            if (!isset($question['question']) || !isset($question['answer'])) {
                \Log::info('Chatbot Debug - Pregunta incompleta', [
                    'index' => $index,
                    'has_question' => isset($question['question']),
                    'has_answer' => isset($question['answer'])
                ]);
                continue;
            }
            
            $questionText = mb_strtolower(trim($question['question']), 'UTF-8');
            
            \Log::info('Chatbot Debug - Comparando con pregunta', [
                'index' => $index,
                'question_text' => $questionText,
                'exact_match' => $normalizedMessage === $questionText,
                'similarity' => similar_text($normalizedMessage, $questionText)
            ]);
            
            // Verificar coincidencia exacta PRIMERO (más estricto)
            if ($normalizedMessage === $questionText) {
                \Log::info('Chatbot Debug - Coincidencia EXACTA encontrada', [
                    'index' => $index,
                    'question' => $question['question']
                ]);
                
                // Si la respuesta está vacía, usar IA como fallback
                if (empty(trim($question['answer']))) {
                    \Log::info('Chatbot Debug - Respuesta vacía en coincidencia exacta, usando IA como fallback');
                    return null;
                }
                
                \Log::info('Chatbot Debug - Devolviendo respuesta frecuente EXACTA', [
                    'answer_length' => strlen(trim($question['answer']))
                ]);
                
                return trim($question['answer']);
            }
            
            // Si no hay coincidencia exacta, verificar coincidencia muy similar
            if (similar_text($normalizedMessage, $questionText) > 80) {
                \Log::info('Chatbot Debug - Coincidencia SIMILAR encontrada', [
                    'index' => $index,
                    'question' => $question['question'],
                    'similarity' => similar_text($normalizedMessage, $questionText)
                ]);
                
                // Si la respuesta está vacía, usar IA como fallback
                if (empty(trim($question['answer']))) {
                    \Log::info('Chatbot Debug - Respuesta vacía en coincidencia similar, usando IA como fallback');
                    return null;
                }
                
                \Log::info('Chatbot Debug - Devolviendo respuesta frecuente SIMILAR', [
                    'answer_length' => strlen(trim($question['answer']))
                ]);
                
                return trim($question['answer']);
            }
        }
        
        \Log::info('Chatbot Debug - No se encontró coincidencia');
        return null;
    }

    /**
     * Verificar si dos preguntas son similares
     */
    private function isSimilarQuestion(string $message, string $question): bool
    {
        // Palabras clave que indican similitud
        $keywords = [
            'menu' => ['menu', 'platos', 'comida', 'carta'],
            'precio' => ['precio', 'cuesta', 'vale', 'costo'],
            'horario' => ['horario', 'abierto', 'cierra', 'atencion'],
            'ubicacion' => ['ubicacion', 'direccion', 'donde', 'lugar'],
            'contacto' => ['contacto', 'telefono', 'llamar', 'comunicar']
        ];
        
        foreach ($keywords as $category => $words) {
            $messageHasKeyword = false;
            $questionHasKeyword = false;
            
            foreach ($words as $word) {
                if (mb_strpos($message, $word, 0, 'UTF-8') !== false) {
                    $messageHasKeyword = true;
                }
                if (mb_strpos($question, $word, 0, 'UTF-8') !== false) {
                    $questionHasKeyword = true;
                }
            }
            
            // Si ambos tienen la misma palabra clave, son similares
            if ($messageHasKeyword && $questionHasKeyword) {
                return true;
            }
        }
        
        return false;
    }
}
