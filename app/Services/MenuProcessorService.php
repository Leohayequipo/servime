<?php

namespace App\Services;

use App\Models\Menu;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MenuProcessorService
{
    protected $openaiApiKey;
    protected $openaiModel;
    protected $savedExtractedText = ''; // Para guardar texto extraído de PDFs
    protected $menuId = null; // Para logs de truncamiento

    public function __construct()
    {
        // Usar config() en lugar de env() para funcionar con config:cache en producción
        $this->openaiApiKey = config('services.openai.api_key');
        $this->openaiModel = config('services.openai.model', 'gpt-4o-mini');
    }

    /**
     * Procesa un menú extrayendo texto y enviándolo a OpenAI
     */
    public function processMenu(Menu $menu)
    {
        try {
            // Guardar ID del menú para logs
            $this->menuId = $menu->id;
            
            // 1. Extraer texto del archivo
            $extractedText = $this->extractTextFromFile($menu);
            
            // El texto se extrajo correctamente
            
            if (empty($extractedText)) {
                throw new \Exception('No se pudo extraer texto del archivo');
            }

            // 2. Guardar el texto extraído primero (si existe texto sin procesar)
            // Para PDFs con texto extraíble, savedExtractedText contiene el texto sin procesar
            // Para imágenes/PDFs escaneados, no hay texto extraído, solo la respuesta procesada de OpenAI
            $textoExtraido = !empty($this->savedExtractedText) ? $this->savedExtractedText : ($menu->formato === 'pdf' ? 'PDF procesado con OpenAI Vision' : 'Imagen procesada con OpenAI Vision');
            
            $menu->update([
                'texto_extraido' => $textoExtraido,
                'procesado' => false
            ]);
            
            // Limpiar el texto guardado después de usarlo
            $this->savedExtractedText = '';

            // 3. La respuesta ya viene procesada desde OpenAI Vision (PDFs o imágenes)
            // Para PDFs con texto extraíble, processPdfToMenu ya procesa con OpenAI
            // Para PDFs escaneados/imágenes, processPdfAsImageWithOpenAI/processImageWithOpenAI ya procesan
            // Entonces extractedText es el JSON procesado, no texto sin procesar
            $gptResponse = $extractedText;
            
            if (!empty($gptResponse)) {
                // 4. Actualizar con la respuesta de GPT (ya procesada)
                $menu->update([
                    'contexto_gpt' => $gptResponse,
                    'procesado' => true
                ]);
                
                Log::info('Menú procesado exitosamente', [
                    'menu_id' => $menu->id,
                    'tamaño_respuesta' => strlen($gptResponse),
                    'formato' => $menu->formato
                ]);
                
                return true;
            }

            // Si no hay respuesta procesada, queda marcado como procesado = false
            Log::warning('Menú no procesado - respuesta vacía', [
                'menu_id' => $menu->id,
                'formato' => $menu->formato
            ]);
            
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
     * Procesa PDFs extrayendo texto y enviándolo a OpenAI
     */
    protected function processPDFWithOpenAI($filePath)
    {
        try {
            $pdfProcessor = new PdfProcessorService();
            
            // Verificar si es un PDF válido
            if (!$pdfProcessor->isValidPdf($filePath)) {
                throw new \Exception('El archivo no es un PDF válido');
            }
            
            // Procesar PDF y extraer menú
            $result = $pdfProcessor->processPdfToMenu($filePath);
            
            if ($result['success']) {
                // Guardar el texto extraído sin procesar en el menú antes de retornar
                // Esto se hace aquí porque necesitamos el texto extraído para guardarlo
                // El JSON procesado se guardará en contexto_gpt
                $this->savedExtractedText = $result['extracted_text'] ?? '';
                
                // Retornar el JSON procesado (menu_data)
                return $result['menu_data'];
            } else {
                throw new \Exception($result['message'] . ' ' . ($result['suggestion'] ?? ''));
            }
            
        } catch (\Exception $e) {
            Log::error('Error procesando PDF: ' . $e->getMessage());
            
            // Fallback: intentar convertir PDF a imagen y procesar
            try {
                Log::info('Intentando convertir PDF a imagen...');
                $pdfToImage = new \App\Services\PdfToImageService();
                $imagePath = $pdfToImage->convertPdfToImage($filePath);
                
                if ($imagePath && file_exists($imagePath)) {
                    Log::info('PDF preparado para procesamiento, enviando a OpenAI...');
                    $result = $this->processPdfAsImageWithOpenAI($imagePath);
                    
                    // Limpiar archivo temporal
                    $pdfToImage->cleanupTempFile($imagePath);
                    
                    Log::info('PDF procesado exitosamente como imagen');
                    return $result;
                } else {
                    throw new \Exception('No se pudo preparar el PDF para procesamiento');
                }
            } catch (\Exception $imageError) {
                Log::error('Error procesando PDF como imagen: ' . $imageError->getMessage());
                
                // Si es un PDF escaneado, dar instrucciones claras al usuario
                if (strpos($imageError->getMessage(), 'PDFs escaneados requieren conversión') !== false) {
                    throw new \Exception('PDFs escaneados no pueden ser procesados directamente. Por favor, convierte el PDF a imagen (PNG/JPG) usando cualquier herramienta online y súbelo como imagen.');
                }
                
                throw new \Exception('No se pudo procesar el PDF. Error de extracción de texto: ' . $e->getMessage() . ' | Error procesando como imagen: ' . $imageError->getMessage());
            }
        }
    }

    /**
     * Procesa PDFs como imágenes con OpenAI Vision (método especial)
     */
    protected function processPdfAsImageWithOpenAI($filePath)
    {
        if (empty($this->openaiApiKey) || $this->openaiApiKey === 'your_openai_api_key_here') {
            throw new \Exception('OpenAI API key no configurada');
        }

        try {
            // Leer el PDF y convertir a base64
            $pdfData = file_get_contents($filePath);
            $base64Pdf = base64_encode($pdfData);
            
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
                                'text' => 'Analiza este PDF escaneado de un menú de restaurante y extráelo en formato JSON estructurado.

INSTRUCCIONES CRÍTICAS:
- SOLO extrae información que REALMENTE VES en el PDF
- NO inventes platos, precios, descripciones o categorías que no estén visibles
- Si no ves un precio, NO inventes uno - pon exactamente "Precio no especificado"
- Si no hay descripción visible, NO inventes una - deja el campo descripción vacío o pon "Sin descripción"
- Mantén los nombres de platos EXACTAMENTE como aparecen en el PDF, sin modificar
- Identifica las categorías que realmente existen en el PDF
- Si hay información del restaurante visible, inclúyela al inicio
- Si NO hay información del restaurante, deja el campo restaurante con nombre vacío

FORMATO JSON REQUERIDO:
{
  "restaurante": {
    "nombre": "Nombre del restaurante",
    "descripcion": "Descripción si existe"
  },
  "categorias": [
    {
      "nombre": "Nombre de la categoría",
      "platos": [
        {
          "nombre": "Nombre del plato",
          "precio": "Precio",
          "descripcion": "Descripción del plato"
        }
      ]
    }
  ]
}

REGLA FINAL: NO INVENTES NADA. Solo extrae lo que REALMENTE VES en el PDF. Si no está visible en el PDF, no lo incluyas.

Responde SOLO con el JSON válido, sin texto adicional, sin explicaciones, sin comentarios.'
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => 'data:application/pdf;base64,' . $base64Pdf
                                ]
                            ]
                        ]
                    ]
                ],
                'max_tokens' => 16000,
                'temperature' => 0.3
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? null;
                
                // Verificar si la respuesta fue truncada
                $finishReason = $data['choices'][0]['finish_reason'] ?? null;
                if ($finishReason === 'length') {
                    Log::warning('Respuesta de OpenAI truncada por límite de tokens', [
                        'menu_id' => $this->menuId ?? null,
                        'tokens_usados' => $data['usage']['total_tokens'] ?? 'desconocido'
                    ]);
                }
                
                if ($content) {
                    Log::info('PDF procesado exitosamente con OpenAI Vision', [
                        'tamaño_respuesta' => strlen($content),
                        'finish_reason' => $finishReason
                    ]);
                    return $content;
                }
            }

            Log::error('Error en respuesta de OpenAI', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            throw new \Exception('Error al procesar con OpenAI: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('Error procesando PDF con OpenAI: ' . $e->getMessage());
            throw $e;
        }
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
            
            if ($imageInfo === false) {
                throw new \Exception('No se pudo leer la información de la imagen');
            }
            
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
                                'text' => 'Analiza esta imagen de un menú de restaurante y extráelo en formato JSON estructurado.

INSTRUCCIONES CRÍTICAS:
- SOLO extrae información que REALMENTE VES en la imagen
- NO inventes platos, precios, descripciones o categorías que no estén visibles
- Si no ves un precio, NO inventes uno - pon exactamente "Precio no especificado"
- Si no hay descripción visible, NO inventes una - deja el campo descripción vacío o pon "Sin descripción"
- Mantén los nombres de platos EXACTAMENTE como aparecen en la imagen, sin modificar
- Identifica las categorías que realmente existen en la imagen
- Si hay información del restaurante visible, inclúyela al inicio
- Si NO hay información del restaurante, deja el campo restaurante con nombre vacío

FORMATO JSON REQUERIDO:
{
  "restaurante": {
    "nombre": "Nombre del restaurante",
    "descripcion": "Descripción si existe"
  },
  "categorias": [
    {
      "nombre": "Nombre de la categoría",
      "platos": [
        {
          "nombre": "Nombre del plato",
          "precio": "Precio",
          "descripcion": "Descripción del plato"
        }
      ]
    }
  ]
}

REGLA FINAL: NO INVENTES NADA. Solo extrae lo que REALMENTE VES en la imagen. Si no está visible en la imagen, no lo incluyas.

Responde SOLO con el JSON válido, sin texto adicional, sin explicaciones, sin comentarios.'
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
                'max_tokens' => 16000,
                'temperature' => 0.3
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? null;
                
                // Verificar si la respuesta fue truncada
                $finishReason = $data['choices'][0]['finish_reason'] ?? null;
                if ($finishReason === 'length') {
                    Log::warning('Respuesta de OpenAI truncada por límite de tokens', [
                        'menu_id' => $this->menuId ?? null,
                        'tokens_usados' => $data['usage']['total_tokens'] ?? 'desconocido'
                    ]);
                }
                
                if ($content) {
                    Log::info('Imagen procesada exitosamente con OpenAI Vision', [
                        'tamaño_respuesta' => strlen($content),
                        'finish_reason' => $finishReason
                    ]);
                }
                
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
