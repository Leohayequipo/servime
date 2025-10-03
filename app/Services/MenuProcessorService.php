<?php

namespace App\Services;

use App\Models\Menu;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MenuProcessorService
{
    protected $openaiApiKey;
    protected $openaiModel;

    public function __construct()
    {
        $this->openaiApiKey = env('OPENAI_API_KEY');
        $this->openaiModel = env('OPENAI_MODEL', 'gpt-4o-mini');
    }

    /**
     * Procesa un menú extrayendo texto y enviándolo a OpenAI
     */
    public function processMenu(Menu $menu)
    {
        try {
            // 1. Extraer texto del archivo
            $extractedText = $this->extractTextFromFile($menu);
            
            // El texto se extrajo correctamente
            
            if (empty($extractedText)) {
                throw new \Exception('No se pudo extraer texto del archivo');
            }

            // 2. Actualizar el menú con el texto extraído
            $menu->update([
                'texto_extraido' => $extractedText,
                'procesado' => false
            ]);

            // 3. La respuesta ya viene procesada desde OpenAI Vision
            $gptResponse = $extractedText;
            
            if ($gptResponse) {
                // 4. Actualizar con la respuesta de GPT
                $menu->update([
                    'contexto_gpt' => $gptResponse,
                    'procesado' => true
                ]);
                
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Error procesando menú: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Extrae texto de archivos PDF o imágenes
     */
    protected function extractTextFromFile(Menu $menu)
    {
        $filePath = storage_path('app/public/' . $menu->archivo_original);
        
        if (!file_exists($filePath)) {
            throw new \Exception('Archivo no encontrado');
        }

        if ($menu->formato === 'pdf') {
            return $this->processPDFWithOpenAI($filePath);
        } else {
            return $this->processImageWithOpenAI($filePath);
        }
    }

    /**
     * Procesa PDFs con OpenAI (requiere conversión a imagen)
     */
    protected function processPDFWithOpenAI($filePath)
    {
        // Para PDFs, necesitaríamos convertirlos a imagen primero
        // Por ahora, retornamos un mensaje indicando que necesitamos implementar esto
        return "PDF detectado: " . basename($filePath) . ". Funcionalidad de procesamiento de PDFs pendiente de implementar.";
    }

    /**
     * Procesa imágenes directamente con OpenAI Vision
     */
    protected function processImageWithOpenAI($filePath)
    {
        if (empty($this->openaiApiKey) || $this->openaiApiKey === 'your_openai_api_key_here') {
            throw new \Exception('OpenAI API key no configurada');
        }

        try {
            // Leer la imagen y convertir a base64
            $imageData = file_get_contents($filePath);
            $base64Image = base64_encode($imageData);
            
            // Determinar el tipo de imagen
            $imageInfo = getimagesize($filePath);
            $mimeType = $imageInfo['mime'];
            
            // La imagen está lista para enviar a OpenAI
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Analiza esta imagen de un menú de restaurante y extrae todo el texto que veas. Organiza la información en formato JSON con: nombre del restaurante, categorías de platos, platos con precios, descripciones, y cualquier información relevante que encuentres.'
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => 'data:' . $mimeType . ';base64,' . $base64Image
                                ]
                            ]
                        ]
                    ]
                ],
                'max_tokens' => 2000,
                'temperature' => 0.3
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? null;
                
                return $content;
            }
            
            Log::error('Error en API de OpenAI Vision: ' . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::error('Error procesando imagen con OpenAI: ' . $e->getMessage());
            throw new \Exception('Error procesando imagen: ' . $e->getMessage());
        }
    }

    /**
     * Procesa el texto con OpenAI
     */
    protected function processWithOpenAI($text, $format)
    {
        if (empty($this->openaiApiKey) || $this->openaiApiKey === 'your_openai_api_key_here') {
            Log::warning('OpenAI API key no configurada');
            return $this->getMockResponse($format);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->openaiModel,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Eres un experto en análisis de menús de restaurantes. Analiza el texto del menú y extrae la información estructurada en formato JSON con: nombre del restaurante, categorías de platos, platos con precios, descripciones, y cualquier información relevante.'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Analiza este menú en formato {$format}:\n\n{$text}"
                    ]
                ],
                'max_tokens' => 2000,
                'temperature' => 0.3
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? null;
            }

            Log::error('Error en API de OpenAI: ' . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::error('Error conectando con OpenAI: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Respuesta mock para testing
     */
    protected function getMockResponse($format)
    {
        return json_encode([
            'restaurante' => 'Restaurante de Prueba',
            'categorias' => [
                [
                    'nombre' => 'Entradas',
                    'platos' => [
                        [
                            'nombre' => 'Ensalada César',
                            'precio' => '$8.99',
                            'descripcion' => 'Lechuga romana, crutones, queso parmesano'
                        ]
                    ]
                ],
                [
                    'nombre' => 'Platos Principales',
                    'platos' => [
                        [
                            'nombre' => 'Hamburguesa Clásica',
                            'precio' => '$12.99',
                            'descripcion' => 'Carne de res, lechuga, tomate, cebolla'
                        ]
                    ]
                ]
            ],
            'formato_original' => $format,
            'procesado_el' => now()->toISOString()
        ], JSON_PRETTY_PRINT);
    }
}
