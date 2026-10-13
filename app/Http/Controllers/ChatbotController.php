<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\Comercio;
use App\Models\Mesa;
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
                'tipo' => 'nullable|string|in:categoria,ver_mas,chip,chip_portada,chip_chat,tag',
                'chip_id' => 'nullable|string|max:16',
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
                $this->logConversation($comercio, $message, $limitResponse, false, $responseTime, $request, $sessionId, $modoRespuesta);

                return response()->json([
                    'response' => $limitResponse,
                    'timestamp' => now(),
                    'success' => true,
                ]);
            }

            // Usar el servicio de chatbot con OpenAI (pasar sessionId para memoria de conversación)
            $response = $this->chatbotService->processMessage($comercio, $message, $sessionId, $modoRespuesta, $scopeRespuesta);
            $responseTime = round((microtime(true) - $startTime) * 1000);

            // Sin texto no hay respuesta (ver la misma guarda en chatStream).
            $usoIA = trim($response) !== '';
            if (! $usoIA) {
                $response = $this->generateFallbackResponse($comercio, $message);
            }

            // Contabilizar el consumo de IA del mes (para la cuota del plan)
            if ($usoIA) {
                $comercio->registrarUsoIA();
            }

            // Platos del menú recomendados (datos exactos del JSON: foto/precio/botón).
            // Se calcula ANTES del log para poder guardar cuántos fueron: cero significa
            // que el bot no encontró nada, y eso alimenta la demanda insatisfecha.
            $platos = $this->chatbotService->detectarPlatosMencionados($comercio, $response);

            // Log de la conversación (usar session_id obtenido anteriormente)
            $this->logConversation($comercio, $message, $response, $usoIA, $responseTime, $request, $sessionId, $modoRespuesta, count($platos), $this->chatbotService->faltanteDeclarado(), array_column($platos, 'nombre'));

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
                $this->logConversation($comercio, $message ?? '', $fallbackResponse, false, $responseTime, $request, $sessionId, $modoRespuesta ?? null);

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
     * Igual que chat(), pero devuelve la respuesta en streaming (SSE) para que el texto
     * aparezca token a token. Emite eventos:
     *   {"type":"delta","content":"..."}  → fragmento de texto
     *   {"type":"done","platos":[...]}     → fin + platos detectados (foto/precio/botón)
     */
    public function chatStream(Request $request, Comercio $comercio): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $request->validate([
            'message' => 'required|string|max:500',
            'tipo' => 'nullable|string|in:categoria,ver_mas,chip,chip_portada,chip_chat,tag',
            'chip_id' => 'nullable|string|max:16',
            'scope' => 'nullable|string|max:500',
        ]);

        $message = $request->input('message');
        $modoRespuesta = $request->input('tipo');
        $scopeRespuesta = $modoRespuesta ? $request->input('scope') : null;
        $sessionId = $this->getSessionId($request, $comercio);
        $startTime = microtime(true);

        // Liberar el lock de sesión: así el stream (varios segundos) no bloquea
        // otras llamadas de la misma sesión (p.ej. el carrito).
        if ($request->hasSession()) {
            $request->session()->save();
        }

        $callback = function () use ($comercio, $message, $modoRespuesta, $scopeRespuesta, $sessionId, $startTime, $request) {
            // Desactivar buffering para que los tokens salgan al instante
            @ini_set('zlib.output_compression', '0');
            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            $emit = function (array $payload) {
                echo 'data: '.json_encode($payload, JSON_UNESCAPED_UNICODE)."\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            };

            // Abrir el stream cuanto antes. El relleno (~2KB) fuerza a Chrome a soltar
            // el buffer de olfateo del ReadableStream y entregar los tokens en vivo al JS
            // (si no, los junta y la respuesta se ve "de golpe").
            echo ':'.str_repeat(' ', 2048)."\n\n";
            flush();

            // Chequeos que no se pueden "streamear": IA deshabilitada o sin cuota
            if (! $comercio->enable_ai_responses || ! $comercio->puedeUsarIA()) {
                $limit = 'Por el momento el asistente no está disponible. '
                    .'Podés ver el menú completo en esta misma página. ¡Gracias por tu paciencia!';
                $emit(['type' => 'delta', 'content' => $limit]);
                $emit(['type' => 'done', 'platos' => []]);
                $this->logConversation($comercio, $message, $limit, false, (int) round((microtime(true) - $startTime) * 1000), $request, $sessionId, $modoRespuesta);

                return;
            }

            $full = '';
            $usoIA = true;
            try {
                $full = $this->chatbotService->streamMessage(
                    $comercio,
                    $message,
                    $sessionId,
                    $modoRespuesta,
                    $scopeRespuesta,
                    function ($delta) use ($emit) {
                        $emit(['type' => 'delta', 'content' => $delta]);
                    }
                );
            } catch (\Throwable $e) {
                \Log::error('Error en chatStream: '.$e->getMessage(), ['comercio_id' => $comercio->id]);
                // Si no llegó ni un token, contestó el texto de respaldo y no la IA. Si el
                // stream se cortó a mitad de camino la respuesta igual salió, así que eso
                // sigue contando como atendido.
                if ($full === '') {
                    $usoIA = false;
                    $full = $this->generateFallbackResponse($comercio, $message);
                    $emit(['type' => 'delta', 'content' => $full]);
                }
            }

            // El modelo también puede terminar BIEN y sin escribir nada: un GPT-5 que se
            // gasta el límite pensando. OpenAI no lo reporta como error, así que el cliente
            // veía la burbuja vacía y al comercio se le descontaba el mensaje de la cuota.
            // Sin texto no hay respuesta: se trata igual que una caída.
            if ($usoIA && trim($full) === '') {
                $usoIA = false;
                $full = $this->generateFallbackResponse($comercio, $message);
                $emit(['type' => 'delta', 'content' => $full]);
            }

            // Contabilizar el uso de IA del mes (cuota del plan). Solo si OpenAI respondió:
            // si se cae (o se queda sin crédito), el comercio no tiene por qué perder cuota
            // por consultas que nunca se atendieron.
            if ($usoIA) {
                $comercio->registrarUsoIA();
            }

            // Platos mencionados (datos exactos del JSON: foto/precio/botón)
            $platos = $this->chatbotService->detectarPlatosMencionados($comercio, $full);
            $emit(['type' => 'done', 'platos' => $platos]);

            // $usoIA y no true fijo: con esto la tarjeta "Sin responder" del panel se entera
            // de que el asistente está caído. Estaba hardcodeado y marcaba 0 mientras OpenAI
            // rechazaba todas las llamadas por falta de crédito.
            $this->logConversation($comercio, $message, $full, $usoIA, (int) round((microtime(true) - $startTime) * 1000), $request, $sessionId, $modoRespuesta, count($platos), $this->chatbotService->faltanteDeclarado(), array_column($platos, 'nombre'));
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
            'X-Content-Type-Options' => 'nosniff',
            'Connection' => 'keep-alive',
        ]);
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
            // Si el comercio cargó sus horarios, contestamos con eso: mandarlo a "contactar
            // al local" teniendo el dato es la clase de respuesta que hace desconfiar del chat.
            $abierto = $comercio->estaAbierto();
            if ($abierto === true) {
                return '¡Sí, estamos abiertos ahora! Podés ver el menú completo acá mismo.';
            }
            if ($abierto === false) {
                $proxima = $comercio->proximaApertura();

                return 'En este momento estamos cerrados'.($proxima ? ", abrimos {$proxima}" : '')
                    .'. Igual podés mirar el menú y dejar todo listo.';
            }

            return "Para información sobre horarios, te recomiendo contactar directamente con {$comercio->nombre}.";
        }

        return 'Gracias por tu mensaje. Estoy aquí para ayudarte con información sobre nuestro menú. ¿Hay algo específico que te gustaría saber?';
    }

    /**
     * Log de conversación en la base de datos
     */
    /**
     * Consumo de la llamada al modelo, para el `metadata` de la conversación.
     *
     * Va acá y no como parámetro de logConversation() porque el service es la misma
     * instancia de todo el request y ya tiene el dato: la firma tiene once parámetros
     * posicionales y sumarle otro más solo para esto la vuelve intratable.
     *
     * Devuelve vacío cuando la respuesta no salió del modelo (cuota agotada, fallback):
     * ahí no hay consumo que registrar y la clave no se agrega.
     *
     * @return array<string, mixed>
     */
    /**
     * Qué chip se tocó, para el `metadata` de la conversación.
     *
     * Se guarda el id Y el texto que tenía en ese momento. El id identifica la versión
     * —editar la frase o la pregunta genera uno nuevo, para que la analítica pueda comparar
     * variantes—, y el texto hace que el histórico se explique solo: si el comercio cambia
     * o borra el chip, el reporte igual puede decir "Algo dulce: 15 clicks" en vez de
     * mostrar un id huérfano.
     *
     * @return array<string, mixed>
     */
    private function chipDelClick(Request $request, Comercio $comercio): array
    {
        $id = $request->input('chip_id');

        if (! $id) {
            return [];
        }

        // El label se resuelve del lado del servidor y no se toma del request: el cliente
        // podría mandar cualquier cosa, y el reporte quedaría con texto inventado.
        $label = '';
        foreach (['chat', 'portada'] as $superficie) {
            foreach ($comercio->chipsDe($superficie) as $chip) {
                if (($chip['id'] ?? null) === $id) {
                    $label = $chip['label'];
                    break 2;
                }
            }
        }

        return ['chip' => array_filter(['id' => $id, 'label' => $label])];
    }

    private function consumoDelModelo(): array
    {
        $consumo = $this->chatbotService->ultimoConsumo();

        return $consumo ? ['consumo' => $consumo] : [];
    }

    private function logConversation(Comercio $comercio, string $message, string $response, bool $usedOpenAI, int $responseTime, Request $request, ?string $sessionId = null, ?string $modoRespuesta = null, ?int $platosSugeridos = null, ?string $falta = null, ?array $platosOfrecidos = null): void
    {
        try {
            // Si no se pasó session_id, obtenerlo
            if (! $sessionId) {
                $sessionId = $this->getSessionId($request, $comercio);
            }

            ChatConversation::create([
                'comercio_id' => $comercio->id,
                // Desde qué mesa se hizo la consulta (la puso ResolverMesa al escanear el QR).
                'mesa_id' => Mesa::idDeSesion($comercio->id),
                'session_id' => $sessionId,
                'user_message' => $message,
                // Todo lo que no sea 'escrito' lo armó la interfaz al tocar un botón, y los
                // widgets que analizan TEXTO lo excluyen (scope `escritas()`): son frases
                // idénticas entre sí y dominan cualquier conteo. Medido antes de marcar los
                // dos últimos: 21 de 103 mensajes eran botones, y una sola frase de chip
                // ocupaba cuatro casilleros del top de palabras.
                //   categoria → chip de categoría del chat      ver_mas → "Ver más opciones"
                //   chip      → atajo de la portada (histórico) tag     → filtro de tags del chat
                //   chip_portada / chip_chat → atajos configurables, separados por superficie
                //   porque son dos momentos distintos y hay que poder compararlos.
                'origen' => in_array($modoRespuesta, ['categoria', 'ver_mas', 'chip', 'chip_portada', 'chip_chat', 'tag'], true) ? $modoRespuesta : 'escrito',
                // Idioma en el que se atendió: explica por qué la misma pregunta aparece
                // en varias formas, y le dice al comercio cuántos clientes no hablan español.
                'idioma' => app()->getLocale(),
                // 0 = el bot no pudo recomendar nada: candidato a demanda insatisfecha
                'platos_sugeridos' => $platosSugeridos,
                // Y CUÁLES fueron: el marcador [[PLATOS]] se borra de la respuesta antes de
                // guardarla, así que sin esto el historial del panel no permitía ver qué
                // ofreció el asistente ni si el plato existía de verdad.
                'platos_ofrecidos' => ! empty($platosOfrecidos) ? array_values($platosOfrecidos) : null,
                // Lo que pidió y el menú no tiene (lo declara el modelo)
                'falta' => $falta,
                'bot_response' => $response,
                'used_openai' => $usedOpenAI,
                'response_time_ms' => $responseTime,
                'user_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => array_merge([
                    'comercio_name' => $comercio->nombre,
                    'message_length' => strlen($message),
                    'response_length' => strlen($response),
                    'timestamp' => now()->toISOString(),
                    'session_id_source' => $request->header('X-Session-ID') ? 'header' : 'inferred',
                ], $this->consumoDelModelo(), $this->chipDelClick($request, $comercio)),
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
