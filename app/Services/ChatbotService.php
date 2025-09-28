<?php

namespace App\Services;

use App\Models\Comercio;
use App\Models\Menu;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotService
{
    private $openaiApiKey;
    private $openaiBaseUrl;

    public function __construct()
    {
        $this->openaiApiKey = config('services.openai.api_key');
        $this->openaiBaseUrl = config('services.openai.base_url', 'https://api.openai.com/v1');
    }

    /**
     * Procesar mensaje del chatbot con contexto del menú
     */
    public function processMessage(Comercio $comercio, string $message): string
    {
        try {
            // Obtener contexto de los menús procesados
            $contexto = $this->buildMenuContext($comercio);

            // DEBUG: Log de contexto (recortado) para ver qué se está enviando
            try {
                Log::info('CHAT_CONTEXT_PREVIEW', [
                    'comercio_id' => $comercio->id,
                    'comercio_nombre' => $comercio->nombre,
                    'context_length' => mb_strlen($contexto, 'UTF-8'),
                    'context_preview' => mb_substr($contexto, 0, 1500, 'UTF-8')
                ]);
            } catch (\Throwable $e) {
                // no romper si falla el log
            }
            
            // Crear prompt para OpenAI
            $prompt = $this->createPrompt($comercio, $contexto, $message);
            
            // Llamar a OpenAI
            $response = $this->callOpenAI($prompt);
            
            return $response;
            
        } catch (\Exception $e) {
            Log::error('Error en ChatbotService: ' . $e->getMessage());
            return "Lo siento, hubo un error al procesar tu mensaje. Inténtalo de nuevo.";
        }
    }

    /**
     * Construir contexto de los menús y preguntas frecuentes
     */
    private function buildMenuContext(Comercio $comercio): string
    {
        $menus = $comercio->menus()
            ->where('procesado', true)
            ->whereNotNull('contexto_gpt')
            ->get();

        $contexto = "Información del restaurante: {$comercio->nombre}\n";
        if ($comercio->descripcion) {
            $contexto .= "Descripción: {$comercio->descripcion}\n";
        }
        
        // Agregar información de contacto si está disponible
        if ($comercio->telefono) {
            $contexto .= "Teléfono: {$comercio->telefono}\n";
        }
        if ($comercio->email) {
            $contexto .= "Email: {$comercio->email}\n";
        }
        if ($comercio->direccion) {
            $contexto .= "Dirección: {$comercio->direccion}\n";
        }
        
        // Agregar preguntas frecuentes como contexto
        $questions = $comercio->quick_questions;
        if (!empty($questions)) {
            $contexto .= "\nPREGUNTAS FRECUENTES Y SUS RESPUESTAS:\n";
            foreach ($questions as $question) {
                if (!empty($question['question']) && !empty($question['answer'])) {
                    $contexto .= "Pregunta: {$question['question']}\n";
                    $contexto .= "Respuesta: {$question['answer']}\n\n";
                }
            }
        }
        
        // Agregar menús si están disponibles
        if (!$menus->isEmpty()) {
            $contexto .= "\nMenús disponibles:\n";
            foreach ($menus as $menu) {
                $contextoMenu = $this->sanitizeContext($menu->contexto_gpt);
                $contexto .= "--- Menú {$menu->formato} (ID {$menu->id}) ---\n";
                $contexto .= $contextoMenu . "\n\n";

                // DEBUG: Log por menú para inspección
                try {
                    Log::info('CHAT_CONTEXT_MENU', [
                        'comercio_id' => $comercio->id,
                        'menu_id' => $menu->id,
                        'formato' => $menu->formato,
                        'length' => mb_strlen($contextoMenu, 'UTF-8'),
                        'preview' => mb_substr($contextoMenu, 0, 600, 'UTF-8')
                    ]);
                } catch (\Throwable $e) {
                    // ignorar
                }
            }
        } else {
            $contexto .= "\nNo hay menús procesados disponibles.\n";
        }

        return $contexto;
    }

    /**
     * Quitar fences ``` y etiquetas como ```json del contexto, y espacios raros
     */
    private function sanitizeContext(?string $text): string
    {
        if ($text === null) return '';
        $clean = trim($text);
        // Remover fences triple backticks con o sin lenguaje
        $clean = preg_replace('/```[a-zA-Z]*\n?/u', '', $clean ?? '');
        $clean = str_replace('```', '', $clean);
        // Normalizar saltos de línea
        $clean = preg_replace("/\r\n|\r/", "\n", $clean);
        return trim($clean);
    }

    /**
     * Exponer un preview del contexto (para consola / debug)
     */
    public function getContextPreview(Comercio $comercio, int $limit = 1500): array
    {
        $context = $this->buildMenuContext($comercio);
        $menus = $comercio->menus()
            ->where('procesado', true)
            ->whereNotNull('contexto_gpt')
            ->get(['id', 'formato']);

        return [
            'comercio_id' => $comercio->id,
            'comercio' => $comercio->nombre,
            'context_length' => mb_strlen($context, 'UTF-8'),
            'context_preview' => mb_substr($context, 0, $limit, 'UTF-8'),
            'menus' => $menus,
        ];
    }

    /**
     * Detalle por menú con preview del texto sanitizado
     */
    public function getMenusPreview(Comercio $comercio, int $perMenuLimit = 800): array
    {
        $result = [];
        $menus = $comercio->menus()
            ->where('procesado', true)
            ->whereNotNull('contexto_gpt')
            ->get();

        foreach ($menus as $menu) {
            $clean = $this->sanitizeContext($menu->contexto_gpt);
            $result[] = [
                'menu_id' => $menu->id,
                'formato' => $menu->formato,
                'length' => mb_strlen($clean, 'UTF-8'),
                'preview' => mb_substr($clean, 0, $perMenuLimit, 'UTF-8')
            ];
        }
        return $result;
    }

    /**
     * Crear prompt para OpenAI
     */
    private function createPrompt(Comercio $comercio, string $contexto, string $message): string
    {
        return "Eres un asistente virtual especializado en restaurantes. Tu trabajo es ayudar a los clientes con información sobre el menú y el restaurante.

INFORMACIÓN DEL RESTAURANTE Y MENÚ:
{$contexto}

INSTRUCCIONES IMPORTANTES:
- Responde de manera profesional y directa, SIN saludos como \"Hola\" o \"¡Hola!\"; ve directo al punto.
- El contexto puede incluir MENÚS en formato JSON (por ejemplo con claves como 'categorías', 'platos', 'precio', 'descripción'). Debes INTERPRETAR ese JSON y convertirlo en texto legible.
- Si el usuario pide \"listar/mostrar el menú\", DEVUELVE un listado por CATEGORÍAS con cada PLATO y su PRECIO (y descripción si está disponible). No inventes categorías ni platos: usa solo lo que esté en el contexto.
- Si la pregunta del cliente es similar a una pregunta frecuente, adapta esa respuesta priorizándola.
- Usa la información del menú para responder preguntas sobre platos, precios e ingredientes. Si un dato no está en el contexto, indícalo.
- Mantén las respuestas concisas pero útiles y SIEMPRE en español.
- NO uses expresiones como \"¡Hola!\", \"Bienvenido\", \"Te damos la bienvenida\".

PREGUNTA DEL CLIENTE: {$message}

RESPUESTA:";
    }

    /**
     * Llamar a la API de OpenAI
     */
    private function callOpenAI(string $prompt): string
    {
        // Verificar si la API key está configurada
        if (empty($this->openaiApiKey)) {
            throw new \Exception('OpenAI API key no configurada');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->openaiApiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post($this->openaiBaseUrl . '/chat/completions', [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 800,
            'temperature' => 0.2,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['choices'][0]['message']['content'] ?? 'No pude procesar tu mensaje.';
        }

        // Log del error para debugging
        Log::error('Error OpenAI API', [
            'status' => $response->status(),
            'body' => $response->body(),
            'headers' => $response->headers()
        ]);

        throw new \Exception('Error al conectar con OpenAI: ' . $response->body());
    }
}
