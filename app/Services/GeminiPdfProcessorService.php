<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class GeminiPdfProcessorService
{
    private $geminiApiKey;
    private $geminiModel;
    private $geminiBaseUrl;

    public function __construct()
    {
        // Intentar leer desde config primero, si no desde env directamente
        $this->geminiApiKey = config('services.gemini.api_key') ?: env('GEMINI_API_KEY');
        // Para procesar PDFs, usar gemini-2.5-flash (modelo estable, rápido y soporta 1M tokens)
        $this->geminiModel = config('services.gemini.model') ?: env('GEMINI_MODEL', 'gemini-2.5-flash');
        $this->geminiBaseUrl = config('services.gemini.base_url') ?: env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta');
    }

    /**
     * Procesar PDF directamente con Gemini
     * Gemini puede procesar PDFs nativamente sin necesidad de convertirlos a imágenes
     */
    public function processPdfToMenu(string $pdfPath): array
    {
        try {
            if (empty($this->geminiApiKey) || $this->geminiApiKey === 'your_gemini_api_key_here') {
                Log::error('❌ Gemini API key no configurada');
                throw new \Exception('Gemini API key no configurada. Verifica que GEMINI_API_KEY esté en tu archivo .env');
            }

            if (!file_exists($pdfPath)) {
                throw new \Exception('Archivo PDF no encontrado: ' . $pdfPath);
            }

            // Verificar tamaño del archivo (Gemini tiene límite de ~20MB)
            $fileSize = filesize($pdfPath);
            $maxSize = 20 * 1024 * 1024; // 20MB
            
            if ($fileSize > $maxSize) {
                throw new \Exception('El PDF es demasiado grande. Tamaño: ' . number_format($fileSize / 1024 / 1024, 2) . ' MB. Máximo permitido: 20 MB');
            }

            // Aumentar el tiempo de ejecución de PHP
            set_time_limit(600); // 10 minutos

            // Leer el PDF y convertir a base64
            $pdfData = file_get_contents($pdfPath);
            $base64Pdf = base64_encode($pdfData);
            $mimeType = 'application/pdf';

            // Construir el prompt
            $prompt = $this->buildPrompt('PDF');

            // Llamar a la API de Gemini
            $response = $this->callGeminiAPI($prompt, $base64Pdf, $mimeType);

            if ($response['success']) {

                return [
                    'success' => true,
                    'menu_data' => $response['menu_data'],
                    'extracted_text' => 'PDF procesado directamente con Gemini (sin extracción de texto previa)',
                    'provider' => 'gemini'
                ];
            } else {
                throw new \Exception($response['error'] ?? 'Error desconocido al procesar con Gemini');
            }

        } catch (\Exception $e) {
            // Log simplificado - solo errores críticos (el error se maneja arriba)

            return [
                'success' => false,
                'message' => 'Error al procesar el PDF con Gemini: ' . $e->getMessage(),
                'provider' => 'gemini'
            ];
        }
    }

    /**
     * Procesar una IMAGEN de menú directamente con Gemini.
     * Gemini procesa imágenes nativamente (jpg/png/webp), igual que los PDFs.
     * Se usa como estrategia primaria para imágenes porque OpenAI Vision tiende
     * a inventar precios y a concatenar variantes/tamaños de un mismo plato.
     */
    public function processImageToMenu(string $imagePath): array
    {
        try {
            if (empty($this->geminiApiKey) || $this->geminiApiKey === 'your_gemini_api_key_here') {
                throw new \Exception('Gemini API key no configurada. Verifica que GEMINI_API_KEY esté en tu archivo .env');
            }

            if (!file_exists($imagePath)) {
                throw new \Exception('Archivo de imagen no encontrado: ' . $imagePath);
            }

            // Verificar tamaño del archivo (Gemini tiene límite de ~20MB)
            $fileSize = filesize($imagePath);
            $maxSize = 20 * 1024 * 1024; // 20MB
            if ($fileSize > $maxSize) {
                throw new \Exception('La imagen es demasiado grande. Tamaño: ' . number_format($fileSize / 1024 / 1024, 2) . ' MB. Máximo permitido: 20 MB');
            }

            // Detectar el mime type real de la imagen
            $imageInfo = @getimagesize($imagePath);
            if ($imageInfo === false || empty($imageInfo['mime'])) {
                throw new \Exception('No se pudo leer la información de la imagen');
            }
            $mimeType = $imageInfo['mime'];

            // Aumentar el tiempo de ejecución de PHP
            set_time_limit(600); // 10 minutos

            $imageData = file_get_contents($imagePath);
            $base64Image = base64_encode($imageData);

            $prompt = $this->buildPrompt('imagen');

            $response = $this->callGeminiAPI($prompt, $base64Image, $mimeType);

            if ($response['success']) {
                return [
                    'success' => true,
                    'menu_data' => $response['menu_data'],
                    'extracted_text' => 'Imagen procesada directamente con Gemini',
                    'provider' => 'gemini'
                ];
            }

            throw new \Exception($response['error'] ?? 'Error desconocido al procesar con Gemini');

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al procesar la imagen con Gemini: ' . $e->getMessage(),
                'provider' => 'gemini'
            ];
        }
    }

    /**
     * Construir el prompt para Gemini
     *
     * @param string $tipo Etiqueta del documento a analizar ("PDF" o "imagen")
     */
    private function buildPrompt(string $tipo = 'PDF'): string
    {
        return "Analiza este {$tipo} de menú de restaurante y extráelo en formato JSON estructurado.

INSTRUCCIONES CRÍTICAS:
- SOLO extrae información que REALMENTE APARECE en el {$tipo}
- NO inventes platos, precios, descripciones o categorías que no estén en el {$tipo}

🚨🚨🚨 DETECCIÓN DE PRECIOS - LEE ESTO CON MUCHA ATENCIÓN 🚨🚨🚨:
- **PRECIOS SON OBLIGATORIOS Y CRÍTICOS** - cada plato DEBE tener SU precio específico y correcto
- **REGLA FUNDAMENTAL**: Cada plato tiene UN SOLO precio asociado. NO uses el mismo precio para múltiples platos a menos que REALMENTE aparezca así en el PDF
- **LECTURA PRECISA**: Lee cada plato de forma INDIVIDUAL y busca SU precio específico. No asumas que platos similares tienen el mismo precio
- Los precios pueden estar en DIFERENTES POSICIONES RELATIVAS AL PLATO:
  * En la MISMA línea que el nombre del plato (a la derecha)
  * En la línea INMEDIATA SIGUIENTE al nombre del plato (debajo)
  * Alineados verticalmente a la derecha en una columna
- Los precios pueden tener estos formatos: \$3500, \$4.500, \$10.200, \$4,500, \$9100, etc.
- **ASOCIACIÓN UNO-A-UNO**: Si ves una lista de platos y luego una columna de precios, asocia cada precio a su plato correspondiente POR POSICIÓN (el primer precio al primer plato, el segundo al segundo, etc.)
- **VERIFICACIÓN**: Antes de asignar un precio, VERIFICA visualmente en el PDF que ese precio realmente pertenece a ese plato específico
- **FORMATO CON PUNTO**: Si ves \$10.200 significa \$10,200 (diez mil doscientos), el punto es separador de miles
- **SI VES CUALQUIER NÚMERO CON \$ JUNTO A UN PLATO, ESE ES SU PRECIO** - Cópialo EXACTAMENTE tal como aparece
- **NO REPITAS PRECIOS**: Si muchos platos tienen el mismo precio en el PDF, está bien. Pero NO copies el precio de un plato a otro si no está claramente asociado
- **SOLO usa \"Precio no especificado\" si REALMENTE no hay ningún número, símbolo \$, o indicador de precio asociado al plato específico**
- Si hay múltiples precios cerca de un plato, usa el que esté VISUALMENTE MÁS CERCANO y ALINEADO con ese plato específico

- Si hay descripción visible (texto adicional bajo el nombre del plato), cópiala EXACTAMENTE en el campo \"descripcion\"
- Si NO hay descripción visible, deja el campo vacío: \"\" (NO escribas \"Sin descripción\")
- Mantén los nombres de platos EXACTAMENTE como aparecen en el PDF, sin modificar
- Identifica las categorías que realmente existen en el PDF (títulos en mayúsculas o nombres de secciones)

FORMATO JSON REQUERIDO:
{
  \"restaurante\": {
    \"nombre\": \"Nombre del restaurante\",
    \"descripcion\": \"Descripción si existe\"
  },
  \"categorias\": [
    {
      \"nombre\": \"Nombre de la categoría\",
      \"platos\": [
        {
          \"nombre\": \"Nombre EXACTO del plato como aparece en el PDF\",
          \"precio\": \"Precio EXACTO como aparece en el PDF (ej: \$3500, \$4900) o 'Precio no especificado' SOLO si REALMENTE no hay precio\",
          \"descripcion\": \"Descripción EXACTA si aparece escrita bajo el nombre del plato, o vacío \\\"\\\" si no hay\"
        }
      ]
    }
  ]
}

🎯 ESTRATEGIA DE PROCESAMIENTO:
1. Lee TODO el PDF primero para entender la estructura y el diseño
2. Identifica las categorías (títulos en mayúsculas, nombres de secciones)
3. Para cada categoría, procesa plato por plato:
   a. Identifica el nombre del plato
   b. Busca SU precio específico (en la misma línea, línea siguiente, o columna alineada)
   c. VERIFICA visualmente que el precio pertenece a ese plato específico
   d. NO uses precios de otros platos
4. Si los precios están en una columna separada, asócialos POR POSICIÓN VERTICAL: el primer precio de la columna va al primer plato de la lista, el segundo al segundo, etc.
5. 🍕 VARIANTES Y TAMAÑOS (MUY IMPORTANTE - NO CONCATENES PRECIOS):
   - Si un plato tiene MÚLTIPLES TAMAÑOS o VARIANTES con precios distintos (ej: pizza \"Chica \$3000 / Mediana \$4500 / Grande \$6000\", o \"SOLO\", \"JAMÓN Y QUESO\", \"COMPLETO\"), NO pongas todos los precios juntos en un solo plato
   - Crea UN PLATO SEPARADO por cada tamaño/variante, con SU precio individual y el tamaño en el nombre. Ejemplo: \"Pizza Muzzarella (Chica)\" → \$3000, \"Pizza Muzzarella (Mediana)\" → \$4500, \"Pizza Muzzarella (Grande)\" → \$6000
   - PROHIBIDO concatenar precios como \"\$3000 \$4500 \$6000\" o \"\$3000/\$4500/\$6000\" en el campo precio de un solo plato
   - Cuando veas varios números de precio en la misma fila que un plato, casi siempre significa que hay varios tamaños: sepáralos en platos distintos
6. **DOBLE VERIFICACIÓN**: Antes de finalizar, revisa que cada plato tenga SU precio correcto, no un precio genérico o copiado de otro plato

REGLA FINAL: 
- NO INVENTES NADA. Solo extrae lo que REALMENTE aparece en el PDF
- Cada plato tiene SU precio específico - NO copies precios entre platos
- VERIFICA visualmente cada asociación plato-precio antes de asignarla
- Si no estás 100% seguro de qué precio pertenece a qué plato, revisa el PDF nuevamente
- Los precios están en el PDF, solo necesitas encontrarlos y asociarlos CORRECTAMENTE a cada plato

Responde SOLO con el JSON válido, sin texto adicional, sin explicaciones, sin comentarios, sin markdown.";
    }

    /**
     * Llamar a la API de Gemini
     */
    private function callGeminiAPI(string $prompt, string $base64Pdf, string $mimeType): array
    {
        try {
            // Construir URL para la API de Gemini
            $url = "{$this->geminiBaseUrl}/models/{$this->geminiModel}:generateContent?key={$this->geminiApiKey}";

            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $prompt
                            ],
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $base64Pdf
                                ]
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 65536, // Máximo para gemini-2.5-flash (evita cortes en JSON grandes)
                ]
            ];

            // Enviando PDF a Gemini API (sin log - ya se logueó el inicio arriba)

            $response = Http::timeout(600)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                // Extraer el contenido de la respuesta
                $content = null;
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    $content = $data['candidates'][0]['content']['parts'][0]['text'];
                }

                if ($content) {
                    // Limpiar markdown si existe
                    $content = $this->cleanResponse($content);
                    
                    $finishReason = $data['candidates'][0]['finishReason'] ?? 'unknown';
                    
                    // Verificar si la respuesta se cortó
                    if ($finishReason === 'MAX_TOKENS') {
                        // Intentar arreglar JSON incompleto si termina a mitad de un objeto
                        $content = $this->fixIncompleteJson($content);
                    }

                    return [
                        'success' => true,
                        'menu_data' => $content
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => 'Respuesta de Gemini sin contenido válido'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => 'Error de API: ' . $response->body()
                ];
            }

        } catch (\Exception $e) {
            // Error se maneja arriba en processPdfToMenu
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Limpiar la respuesta de Gemini (remover markdown, etc)
     */
    private function cleanResponse(string $content): string
    {
        // Limpiar markdown si existe
        if (strpos($content, '```json') !== false) {
            $content = preg_replace('/```json\s*/', '', $content);
            $content = preg_replace('/```\s*$/', '', $content);
            $content = trim($content);
        } elseif (strpos($content, '```') !== false) {
            $content = preg_replace('/```\s*/', '', $content);
            $content = trim($content);
        }
        
        // Remover caracteres de control que pueden causar errores en JSON
        // Pero preservar espacios, tabs y saltos de línea normales
        $content = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/u', '', $content);
        
        // Normalizar saltos de línea
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        
        // Escapar caracteres especiales que puedan estar mal codificados
        $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');

        return trim($content);
    }
    
    /**
     * Intentar arreglar JSON incompleto (si fue cortado por MAX_TOKENS)
     */
    private function fixIncompleteJson(string $content): string
    {
        // Remover espacios en blanco al final
        $content = rtrim($content);
        
        // Si termina con una coma, quitarla
        $content = rtrim($content, ',');
        
        // Buscar el último objeto/array completo y cerrar el JSON correctamente
        // Si termina con un objeto/array abierto, intentar cerrarlo
        $depth = 0;
        $lastValidPos = strlen($content) - 1;
        
        // Contar llaves y corchetes para ver si está balanceado
        $openBraces = substr_count($content, '{');
        $closeBraces = substr_count($content, '}');
        $openBrackets = substr_count($content, '[');
        $closeBrackets = substr_count($content, ']');
        
        // Si faltan cierres, agregarlos
        $missingBraces = $openBraces - $closeBraces;
        $missingBrackets = $openBrackets - $closeBrackets;
        
        // Quitar comas al final antes de agregar cierres
        while (substr($content, -1) === ',' || substr($content, -1) === ' ' || substr($content, -1) === "\n") {
            $content = rtrim($content, ", \n");
        }
        
        // Agregar los cierres faltantes
        for ($i = 0; $i < $missingBrackets; $i++) {
            $content .= ']';
        }
        for ($i = 0; $i < $missingBraces; $i++) {
            $content .= '}';
        }
        
        return $content;
    }
}

