<?php

namespace App\Http\Controllers;

use App\Models\Comercio;
use Illuminate\Http\Request;

class CustomizationController extends Controller
{
    /**
     * Mostrar formulario de personalización
     */
    public function show($comercioId)
    {
        $comercio = Comercio::findOrFail($comercioId);
        return view('comercios.customize', compact('comercio'));
    }

    /**
     * Actualizar configuración de personalización
     */
    public function update(Request $request, $comercioId)
    {
        $comercio = Comercio::findOrFail($comercioId);
        
        $request->validate([
            'primary_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'secondary_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'accent_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'background_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'welcome_message' => 'nullable|string|max:500',
            'menu_description' => 'nullable|string|max:1000',
            'contact_info' => 'nullable|string|max:500',
            'chatbot_name' => 'required|string|max:50',
            'show_contact_info' => 'boolean',
            'enable_ai_responses' => 'boolean',
            'pedido_habilitado' => 'nullable|boolean',
            'page_title' => 'nullable|string|max:100',
            'page_description' => 'nullable|string|max:500',
            'favicon_url' => 'nullable|string|max:255'
        ]);

        $data = $request->all();
        $data['pedido_habilitado'] = $request->boolean('pedido_habilitado');
        $comercio->update($data);

        return redirect()
            ->route('comercios.customize', $comercio)
            ->with('success', 'Configuración de personalización actualizada correctamente.');
    }

    /**
     * Vista previa de la página pública
     */
    public function preview($comercioId)
    {
        $comercio = Comercio::findOrFail($comercioId);
        $menus = $comercio->menus()->where('procesado', true)->get();
        
        return view('menu.chat', compact('comercio', 'menus'))->with('preview', true);
    }

    /**
     * Resetear configuración a valores por defecto
     */
    public function reset($comercioId)
    {
        $comercio = Comercio::findOrFail($comercioId);
        
        $comercio->update([
            'primary_color' => '#3B82F6',
            'secondary_color' => '#6B7280',
            'accent_color' => '#10B981',
            'background_color' => '#FFFFFF',
            'welcome_message' => null,
            'menu_description' => null,
            'contact_info' => null,
            'chatbot_name' => 'Asistente IA',
            'show_contact_info' => true,
            'enable_ai_responses' => true,
            'pedido_habilitado' => true,
            'page_title' => null,
            'page_description' => null,
            'favicon_url' => null
        ]);

        return redirect()
            ->route('comercios.customize', $comercio)
            ->with('success', 'Configuración reseteada a valores por defecto.');
    }

    /**
     * Generar preguntas frecuentes con IA
     */
    public function generateQuestions(Request $request, $comercioId)
    {
        $comercio = Comercio::findOrFail($comercioId);
        
        try {
            // Preparar información del comercio para la IA
            $comercioInfo = [
                'nombre' => $comercio->nombre,
                'descripcion' => $comercio->descripcion,
                'telefono' => $comercio->telefono,
                'email' => $comercio->email,
                'direccion' => $comercio->direccion,
                'menus' => $comercio->menus()->where('procesado', true)->get()->map(function($menu) {
                    return [
                        'nombre' => $menu->nombre,
                        'contenido' => $menu->contexto_gpt
                    ];
                })
            ];

            // Crear el prompt para la IA
            $prompt = $this->createPromptForQuestions($comercioInfo);

            // Llamar a OpenAI usando HTTP (mismo método que el resto del proyecto)
            $openaiApiKey = config('services.openai.api_key');
            if (empty($openaiApiKey)) {
                throw new \Exception('OpenAI API key no configurada');
            }

            $response = \Http::withHeaders([
                'Authorization' => 'Bearer ' . $openaiApiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Eres un experto en crear preguntas frecuentes para restaurantes y comercios. Genera preguntas relevantes basadas en la información del negocio.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 2000,
                'temperature' => 0.7
            ]);

            if (!$response->successful()) {
                throw new \Exception('Error al conectar con OpenAI: ' . $response->body());
            }

            $data = $response->json();
            $aiResponse = $data['choices'][0]['message']['content'] ?? '';
            
            // Parsear la respuesta de la IA
            $generatedQuestions = $this->parseAIResponse($aiResponse);
            
            // Actualizar las preguntas del comercio
            $comercio->update([
                'quick_questions' => $generatedQuestions
            ]);

            // Si es una petición AJAX, devolver JSON
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Preguntas frecuentes generadas con IA exitosamente.',
                    'questions' => $generatedQuestions
                ]);
            }

            // Redirigir a la página de edición de menús o a crear uno nuevo
            $firstMenu = $comercio->menus()->first();
            if ($firstMenu) {
                return redirect()
                    ->route('menus.edit', [$comercio, $firstMenu])
                    ->with('success', 'Preguntas frecuentes generadas con IA exitosamente.');
            } else {
                return redirect()
                    ->route('menus.create', $comercio)
                    ->with('success', 'Preguntas frecuentes generadas con IA exitosamente.');
            }

        } catch (\Exception $e) {
            \Log::error('Error generating questions with AI: ' . $e->getMessage());
            
            // Si es una petición AJAX, devolver JSON con error
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al generar preguntas con IA. Por favor, inténtalo de nuevo.'
                ], 500);
            }
            
            // Redirigir a la página de edición de menús o a crear uno nuevo
            $firstMenu = $comercio->menus()->first();
            if ($firstMenu) {
                return redirect()
                    ->route('menus.edit', [$comercio, $firstMenu])
                    ->with('error', 'Error al generar preguntas con IA. Por favor, inténtalo de nuevo.');
            } else {
                return redirect()
                    ->route('menus.create', $comercio)
                    ->with('error', 'Error al generar preguntas con IA. Por favor, inténtalo de nuevo.');
            }
        }
    }

    /**
     * Crear prompt para la generación de preguntas
     */
    private function createPromptForQuestions($comercioInfo)
    {
        $prompt = "Genera 6-8 preguntas frecuentes para el siguiente comercio:\n\n";
        $prompt .= "NOMBRE: " . $comercioInfo['nombre'] . "\n";
        $prompt .= "DESCRIPCIÓN: " . ($comercioInfo['descripcion'] ?? 'No especificada') . "\n";
        $prompt .= "TELÉFONO: " . ($comercioInfo['telefono'] ?? 'No especificado') . "\n";
        $prompt .= "EMAIL: " . ($comercioInfo['email'] ?? 'No especificado') . "\n";
        $prompt .= "DIRECCIÓN: " . ($comercioInfo['direccion'] ?? 'No especificada') . "\n\n";

        if ($comercioInfo['menus']->count() > 0) {
            $prompt .= "MENÚS DISPONIBLES:\n";
            foreach ($comercioInfo['menus'] as $menu) {
                $prompt .= "- " . $menu['nombre'] . "\n";
                if ($menu['contenido']) {
                    $prompt .= "  Contenido: " . substr($menu['contenido'], 0, 200) . "...\n";
                }
            }
            $prompt .= "\n";
        }

        $prompt .= "Responde ÚNICAMENTE con un JSON válido en este formato exacto:\n";
        $prompt .= "[\n";
        $prompt .= "  {\n";
        $prompt .= "    \"icon\": \"🍽️\",\n";
        $prompt .= "    \"question\": \"¿Qué tenés en el menú?\",\n";
        $prompt .= "    \"answer\": \"Tenemos una variedad de platos...\",\n";
        $prompt .= "    \"auto_generated\": true,\n";
        $prompt .= "    \"category\": \"menu\"\n";
        $prompt .= "  }\n";
        $prompt .= "]\n\n";
        $prompt .= "Categorías disponibles: menu, precios, horarios, ubicacion, contacto, general\n";
        $prompt .= "Usa emojis apropiados para cada pregunta.";

        return $prompt;
    }

    /**
     * Parsear respuesta de la IA
     */
    private function parseAIResponse($aiResponse)
    {
        try {
            // Limpiar la respuesta
            $cleanResponse = trim($aiResponse);
            
            // Remover markdown si existe
            if (strpos($cleanResponse, '```json') !== false) {
                $cleanResponse = preg_replace('/```json\s*/', '', $cleanResponse);
                $cleanResponse = preg_replace('/```\s*$/', '', $cleanResponse);
            }
            
            $questions = json_decode($cleanResponse, true);
            
            if (!is_array($questions)) {
                throw new \Exception('Invalid JSON response from AI');
            }
            
            // Validar y limpiar cada pregunta
            $validQuestions = [];
            foreach ($questions as $question) {
                if (isset($question['question']) && !empty($question['question'])) {
                    $validQuestions[] = [
                        'icon' => $question['icon'] ?? '❓',
                        'question' => trim($question['question']),
                        'answer' => trim($question['answer'] ?? ''),
                        'auto_generated' => true,
                        'category' => $question['category'] ?? 'general'
                    ];
                }
            }
            
            return $validQuestions;
            
        } catch (\Exception $e) {
            \Log::error('Error parsing AI response: ' . $e->getMessage());
            
            // Fallback: preguntas por defecto
            return [
                [
                    'icon' => '🍽️',
                    'question' => '¿Qué tenés en el menú?',
                    'answer' => 'Tenemos una variedad de platos deliciosos disponibles.',
                    'auto_generated' => true,
                    'category' => 'menu'
                ],
                [
                    'icon' => '💰',
                    'question' => '¿Cuáles son los precios?',
                    'answer' => 'Los precios varían según el plato. Puedes consultar nuestro menú para más detalles.',
                    'auto_generated' => true,
                    'category' => 'precios'
                ],
                [
                    'icon' => '🕒',
                    'question' => '¿Cuáles son los horarios?',
                    'answer' => 'Estamos abiertos para servirte. Contactanos para conocer nuestros horarios.',
                    'auto_generated' => true,
                    'category' => 'horarios'
                ]
            ];
        }
    }

    /**
     * Mejorar respuesta específica con IA
     */
    public function improveAnswer(Request $request, $comercioId)
    {
        $comercio = Comercio::findOrFail($comercioId);
        
        $request->validate([
            'question_index' => 'nullable|integer|min:0',
            'question_text' => 'required|string|max:500',
            'current_answer' => 'required|string|max:1000'
        ]);

        try {
            $questionIndex = $request->question_index;
            $questionText = $request->input('question_text');
            $currentAnswer = $request->current_answer;
            $questions = $comercio->quick_questions;
            
            // Si es una pregunta existente, usar la pregunta del comercio
            if ($questionIndex !== null && isset($questions[$questionIndex])) {
                $question = $questions[$questionIndex];
                $questionText = $question['question'];
            }
            
            // Preparar información del comercio para la IA
            $comercioInfo = [
                'nombre' => $comercio->nombre,
                'descripcion' => $comercio->descripcion,
                'categoria' => isset($question) ? $question['category'] : 'general'
            ];

            // Crear prompt para mejorar la respuesta
            $prompt = $this->createPromptForAnswerImprovement($comercioInfo, $questionText, $currentAnswer);

            // Llamar a OpenAI
            $openaiApiKey = config('services.openai.api_key');
            if (empty($openaiApiKey)) {
                throw new \Exception('OpenAI API key no configurada');
            }

            $response = \Http::withHeaders([
                'Authorization' => 'Bearer ' . $openaiApiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Eres un experto en redacción de respuestas para restaurantes y comercios. Mejora las respuestas haciéndolas más atractivas, claras y útiles para los clientes.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 500,
                'temperature' => 0.7
            ]);

            if (!$response->successful()) {
                throw new \Exception('Error al conectar con OpenAI: ' . $response->body());
            }

            $data = $response->json();
            $improvedAnswer = trim($data['choices'][0]['message']['content'] ?? '');
            
            if (empty($improvedAnswer)) {
                throw new \Exception('No se pudo generar una respuesta mejorada');
            }
            
            // Si es una pregunta existente, actualizar en la base de datos
            if ($questionIndex !== null && isset($questions[$questionIndex])) {
                $questions[$questionIndex]['answer'] = $improvedAnswer;
                $comercio->update([
                    'quick_questions' => $questions
                ]);
            }

            // Devolver respuesta JSON para actualizar el frontend
            return response()->json(['improved_answer' => $improvedAnswer]);

        } catch (\Exception $e) {
            \Log::error('Error improving answer with AI: ' . $e->getMessage());
            
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Crear prompt para mejorar respuesta específica
     */
    private function createPromptForAnswerImprovement($comercioInfo, $question, $currentAnswer)
    {
        $prompt = "Mejora la siguiente respuesta para un restaurante/comercio:\n\n";
        $prompt .= "NOMBRE DEL NEGOCIO: " . $comercioInfo['nombre'] . "\n";
        $prompt .= "CATEGORÍA: " . $comercioInfo['categoria'] . "\n\n";
        $prompt .= "PREGUNTA: " . $question . "\n\n";
        $prompt .= "RESPUESTA ACTUAL: " . $currentAnswer . "\n\n";
        $prompt .= "INSTRUCCIONES:\n";
        $prompt .= "- Haz la respuesta más atractiva y profesional\n";
        $prompt .= "- Mantén un tono directo y útil, SIN saludos como \"Hola\" o \"¡Hola!\"\n";
        $prompt .= "- Ve directo al punto con la información\n";
        $prompt .= "- Incluye detalles específicos si es posible\n";
        $prompt .= "- Haz que sea útil para el cliente\n";
        $prompt .= "- Máximo 200 palabras\n";
        $prompt .= "- NO uses expresiones como \"¡Hola!\", \"Bienvenido\", \"Te damos la bienvenida\"\n\n";
        $prompt .= "Responde ÚNICAMENTE con la respuesta mejorada, sin explicaciones adicionales:";

        return $prompt;
    }
}
