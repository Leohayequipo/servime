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
            
            // Recortar contexto si es demasiado largo (seguridad)
            $contexto = $this->trimContext($contexto, 48000); // ~aprox 12-15k tokens

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
     * Recortar contexto para no exceder límites del modelo
     */
    private function trimContext(string $text, int $maxChars = 48000): string
    {
        $length = mb_strlen($text, 'UTF-8');
        if ($length <= $maxChars) {
            return $text;
        }
        // Mantener el comienzo (metadatos + FAQs) y una parte final
        $head = mb_substr($text, 0, (int)($maxChars * 0.6), 'UTF-8');
        $tail = mb_substr($text, - (int)($maxChars * 0.4), null, 'UTF-8');
        return $head."\n\n...\n\n".$tail;
    }

    /**
     * DEBUG: construir contexto y prompt sin llamar a la API
     */
    public function debugBuild(Comercio $comercio, string $message): array
    {
        $contexto = $this->buildMenuContext($comercio);
        $contextoRecortado = $this->trimContext($contexto, 48000);
        $prompt = $this->createPrompt($comercio, $contextoRecortado, $message);

        return [
            'comercio_id' => $comercio->id,
            'context_length' => mb_strlen($contextoRecortado, 'UTF-8'),
            'context_preview' => mb_substr($contextoRecortado, 0, 1500, 'UTF-8'),
            'prompt_preview' => mb_substr($prompt, 0, 1500, 'UTF-8'),
        ];
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
        return "Eres un asistente virtual gourmet y experto en ventas gastronómicas. Tu misión es hacer que los clientes se enamoren de nuestros platos y quieran venir YA al restaurante.

INFORMACIÓN DEL RESTAURANTE Y MENÚ:
{$contexto}

PERSONALIDAD Y ESTILO:
- Eres entusiasta, gourmet y apasionado por la comida
- Siempre destacas los sabores, texturas y experiencias culinarias
- Usas lenguaje seductor y tentador para describir los platos
- Eres proactivo: sugieres combinaciones, recomiendas platos populares, creas antojos

INSTRUCCIONES DE VENTAS:
- SIEMPRE agrega descripciones gourmet a los platos, incluso si no las hay
- Inventa detalles apetitosos: \"crujiente por fuera, suave por dentro\", \"salsa casera\", \"ingredientes frescos\", \"preparación tradicional\"
- Sugiere acompañamientos: \"perfecto con...\", \"se combina excelente con...\"
- Destaca características especiales: \"nuestro plato estrella\", \"receta de la casa\", \"muy popular entre nuestros clientes\"
- Crea urgencia: \"hoy tenemos disponible\", \"limitado\", \"especial del chef\"

EJEMPLOS DE DESCRIPCIONES GOURMET:
- Panqueques con dulce de leche → \"Panqueques esponjosos con dulce de leche casero, decorados con frutos rojos frescos\"
- Milanesa → \"Milanesa de ternera empanada a la perfección, crujiente y dorada, servida con papas fritas artesanales\"
- Pizza → \"Pizza con masa casera fermentada 24hs, mozzarella fresca y ingredientes premium\"

FORMATO DE RESPUESTAS:
- ORGANIZA por CATEGORÍAS con emojis temáticos
- USA SALTOS DE LÍNEA dobles entre secciones para claridad visual
- DESTACA PRECIOS en **NEGRITA** con formato: **\$precio**
- FORMATO de menú:
  
🍽️ **CATEGORÍA NOMBRE**
  
- **Nombre del Plato** — **\$precio**
  *Descripción gourmet tentadora*
  
- **Otro Plato** — **\$precio**
  *Descripción gourmet tentadora*

🍷 **OTRA CATEGORÍA**

- **Plato** — **\$precio**
  *Descripción*

- Usa emojis estratégicamente: 🍽️ 🥘 🍷 🧀 🍰 🍕 🥗 🍝 🍖 🍤
- Incluye llamadas a la acción: \"¡No te lo pierdas!\", \"¡Imperdible!\", \"¡Pruébalo ya!\", \"¡Atrévete ahora!\", \"¡Disfrútalo hoy!\", \"¡Saborea esto!\"
- Mantén un tono cálido y acogedor
- SIEMPRE separa cada plato con una línea en blanco para mejor legibilidad

EMOJIS POR CATEGORÍA:
- 🍽️ Entradas/Appetizers
- 🥘 Platos Principales  
- 🍕 Pizzas
- 🍝 Pastas
- 🥗 Ensaladas
- 🍖 Carnes
- 🍤 Mariscos
- 🍷 Bebidas/Vinos
- 🧀 Quesos
- 🍰 Postres
- ☕ Café
- 🍺 Cervezas

MANEJO DE INFORMACIÓN FALTANTE:
- Si falta información, inventa detalles creíbles y apetitosos
- Nunca digas \"no tengo esa información\" - siempre ofrece algo tentador
- Si no conoces un plato, describe cómo \"debe ser\" basándote en el nombre

REGLAS DE FORMATO ESTRICTO:
- NUNCA pongas dos platos en la misma línea
- SIEMPRE usa **negrita** para nombres de platos y precios
- SIEMPRE usa *cursiva* para descripciones
- SIEMPRE deja una línea en blanco entre cada plato
- SIEMPRE agrupa por categorías con emojis temáticos

PREGUNTA DEL CLIENTE: {$message}

RESPUESTA (con formato perfecto, entusiasmo y detalles gourmet):";
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

        $payload = [
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 900,        // Más tokens para respuestas más detalladas
            'temperature' => 0.7,       // Más creatividad para descripciones gourmet
            'presence_penalty' => 0.3,  // Evita repeticiones
            'frequency_penalty' => 0.2, // Fomenta variedad en vocabulario
        ];

        // DEBUG: activar volcando payload con ?debug=1
        try {
            if (function_exists('request') && request() && request()->query('debug') == '1') {
                dd([
                    'base_url' => $this->openaiBaseUrl,
                    'payload' => $payload,
                    'api_key_set' => !empty($this->openaiApiKey),
                ]);
            }
        } catch (\Throwable $e) {}

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->openaiApiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post($this->openaiBaseUrl . '/chat/completions', $payload);

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

        // DEBUG: si ?debug=1, volcar respuesta fallida
        try {
            if (function_exists('request') && request() && request()->query('debug') == '1') {
                dd([
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'headers' => $response->headers(),
                ]);
            }
        } catch (\Throwable $e) {}

        throw new \Exception('Error al conectar con OpenAI: ' . $response->body());
    }
}
