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
                $contexto .= "--- Menú {$menu->formato} ---\n";
                $contexto .= $menu->contexto_gpt . "\n\n";
            }
        } else {
            $contexto .= "\nNo hay menús procesados disponibles.\n";
        }

        return $contexto;
    }

    /**
     * Crear prompt para OpenAI
     */
    private function createPrompt(Comercio $comercio, string $contexto, string $message): string
    {
        return "Eres un asistente virtual especializado en restaurantes. Tu trabajo es ayudar a los clientes con información sobre el menú y el restaurante.

INFORMACIÓN DEL RESTAURANTE:
{$contexto}

INSTRUCCIONES:
- Responde de manera profesional y directa, SIN saludos como \"Hola\" o \"¡Hola!\"
- Ve directo al punto con la información solicitada
- PRIORIZA las respuestas de las preguntas frecuentes cuando sean relevantes
- Si la pregunta del cliente es similar a una pregunta frecuente, adapta esa respuesta
- Usa la información del menú para responder preguntas sobre platos, precios, ingredientes
- Si no tienes información específica, sé honesto y sugiere contactar al restaurante
- Mantén las respuestas concisas pero útiles
- Responde en español
- Si alguien pregunta sobre algo que está en las preguntas frecuentes (como 'comida árabe', 'café', etc.), usa la respuesta correspondiente como base
- NO uses expresiones como \"¡Hola!\", \"Bienvenido\", \"Te damos la bienvenida\" - ve directo a la información

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
            'max_tokens' => 300,
            'temperature' => 0.7,
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
