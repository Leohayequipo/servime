<?php

namespace App\Services;

use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PdfProcessorService
{
    private $openaiApiKey;
    private $openaiBaseUrl;

    public function __construct()
    {
        $this->openaiApiKey = config('services.openai.api_key');
        $this->openaiBaseUrl = config('services.openai.base_url', 'https://api.openai.com/v1');
    }

    /**
     * Procesar PDF y extraer menú usando OpenAI
     */
    public function processPdfToMenu(string $pdfPath): array
    {
        try {
            Log::info('🚀 Iniciando procesamiento de PDF con extracción de texto directa', [
                'pdf_path' => $pdfPath
            ]);
            
            // Extraer texto del PDF
            $extractedText = $this->extractTextFromPdf($pdfPath);
            
            if (empty($extractedText)) {
                Log::warning('⚠️ Texto extraído está vacío', [
                    'pdf_path' => $pdfPath
                ]);
                return [
                    'success' => false,
                    'message' => 'No se pudo extraer texto del PDF. Puede ser una imagen escaneada.',
                    'suggestion' => 'Intenta subir el menú como imagen directamente.'
                ];
            }

            Log::info('✅ Texto extraído exitosamente, procesando con OpenAI', [
                'longitud_texto' => strlen($extractedText)
            ]);

            // Procesar con OpenAI
            $menuData = $this->processWithOpenAI($extractedText);
            
            Log::info('✅ PDF procesado exitosamente con extracción de texto directa', [
                'longitud_respuesta' => strlen($menuData)
            ]);
            
            return [
                'success' => true,
                'menu_data' => $menuData,
                'extracted_text' => $extractedText
            ];

        } catch (\Exception $e) {
            Log::error('❌ Error procesando PDF con extracción de texto: ' . $e->getMessage(), [
                'exception_type' => get_class($e),
                'pdf_path' => $pdfPath,
                'exception_trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al procesar el PDF: ' . $e->getMessage(),
                'suggestion' => 'Verifica que el PDF tenga texto seleccionable.'
            ];
        }
    }

    /**
     * Extraer texto de un archivo PDF
     */
    private function extractTextFromPdf(string $pdfPath): string
    {
        try {
            Log::info('🔍 Intentando extraer texto directamente del PDF', [
                'pdf_path' => $pdfPath,
                'archivo_existe' => file_exists($pdfPath),
                'tamaño_archivo' => file_exists($pdfPath) ? filesize($pdfPath) : 'N/A'
            ]);
            
            $parser = new Parser();
            $pdf = $parser->parseFile($pdfPath);
            $text = $pdf->getText();
            
            Log::info('📝 Texto extraído del PDF (antes de limpiar)', [
                'longitud_texto_bruto' => strlen($text),
                'primeros_200_caracteres' => mb_substr($text, 0, 200, 'UTF-8')
            ]);
            
            // Limpiar el texto extraído
            $text = $this->cleanExtractedText($text);
            
            Log::info('📝 Texto extraído del PDF (después de limpiar)', [
                'longitud_texto_limpio' => strlen($text),
                'texto_vacio' => empty(trim($text)),
                'primeros_200_caracteres' => mb_substr($text, 0, 200, 'UTF-8')
            ]);
            
            // Verificar si se extrajo texto válido
            if (empty(trim($text))) {
                Log::warning('⚠️ El PDF no contiene texto extraíble después de la limpieza', [
                    'longitud_texto_bruto' => strlen($pdf->getText()),
                    'longitud_texto_limpio' => strlen($text)
                ]);
                throw new \Exception('El PDF no contiene texto extraíble');
            }
            
            Log::info('✅ Texto extraído exitosamente del PDF', [
                'longitud_final' => strlen($text)
            ]);
            
            return $text;
            
        } catch (\Exception $e) {
            Log::error('❌ Error extrayendo texto del PDF: ' . $e->getMessage(), [
                'exception_type' => get_class($e),
                'exception_trace' => $e->getTraceAsString()
            ]);
            
            // Si es un error específico de smalot/pdfparser, sugerir procesamiento como imagen
            if (strpos($e->getMessage(), 'Invalid object reference') !== false || 
                strpos($e->getMessage(), 'corrupted') !== false ||
                strpos($e->getMessage(), 'invalid') !== false) {
                throw new \Exception('PDF con formato no soportado por extracción de texto. Se recomienda procesar como imagen.');
            }
            
            throw $e;
        }
    }

    /**
     * Limpiar texto extraído del PDF
     */
    private function cleanExtractedText(string $text): string
    {
        // Normalizar saltos de línea
        $text = preg_replace("/\r\n|\r/", "\n", $text);
        
        // Remover espacios excesivos
        $text = preg_replace('/[ \t]+/', ' ', $text);
        
        // Remover líneas vacías múltiples
        $text = preg_replace('/\n\s*\n/', "\n\n", $text);
        
        return trim($text);
    }

    /**
     * Procesar texto extraído con OpenAI
     */
    private function processWithOpenAI(string $text): string
    {
        // Aumentar el tiempo de ejecución de PHP para permitir que la llamada a OpenAI complete
        set_time_limit(600); // 10 minutos
        
        $prompt = "Analiza el siguiente texto de un menú de restaurante y extráelo en formato JSON estructurado. 

INSTRUCCIONES CRÍTICAS:
- SOLO extrae información que REALMENTE APARECE en el texto
- NO inventes platos, precios, descripciones o categorías que no estén en el texto

🚨🚨🚨 DETECCIÓN DE PRECIOS - LEE ESTO CON MUCHA ATENCIÓN 🚨🚨🚨:
- **PRECIOS SON OBLIGATORIOS Y CRÍTICOS** - debes encontrar TODOS los precios
- Lee el texto LINEA POR LINEA y busca patrones de precios
- Los precios pueden estar en DIFERENTES POSICIONES:
  * En la MISMA línea que el nombre del plato (a la derecha o separados)
  * En la línea INMEDIATA SIGUIENTE al nombre del plato
  * En líneas cercanas al plato (dentro de 2-3 líneas)
- Los precios pueden tener estos formatos: \$3500, \$4.500, \$10.200, \$4,500, 3500, etc.
- **BUSCA PATRONES**: Si ves un nombre de plato seguido de líneas con números que empiezan con \$, esos SON los precios
- **LECTURA CONTEXTUAL**: Si hay un bloque de precios (como \$8900, \$9500, \$5500) después de una lista de platos, esos precios corresponden a esos platos en orden
- **FORMATO CON PUNTO**: Si ves \$10.200 significa \$10,200 (diez mil doscientos), el punto es separador de miles
- **SI VES CUALQUIER NÚMERO CON \$ EN LAS PROXIMIDADES DE UN PLATO, ESE ES SU PRECIO** - Cópialo EXACTAMENTE
- **SOLO usa \"Precio no especificado\" si REALMENTE no hay ningún número, símbolo \$, o indicador de precio cerca del plato (ni arriba, ni abajo, ni a los lados)**
- Si hay múltiples precios cerca de un plato, usa el que esté más cercano o el que tenga sentido contextualmente

- Si hay descripción visible (texto adicional bajo el nombre del plato), cópiala EXACTAMENTE en el campo \"descripcion\"
- Si NO hay descripción visible, deja el campo vacío: \"\" (NO escribas \"Sin descripción\")
- Mantén los nombres de platos EXACTAMENTE como aparecen en el texto, sin modificar
- Identifica las categorías que realmente existen en el texto (títulos en mayúsculas o nombres de secciones)
- Si hay información del restaurante visible, inclúyela al inicio
- Si NO hay información del restaurante, deja el campo restaurante con nombre vacío

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
          \"nombre\": \"Nombre EXACTO del plato como aparece en el texto\",
          \"precio\": \"Precio EXACTO como aparece en el texto (ej: $3500, $4900) o 'Precio no especificado' SOLO si REALMENTE no hay precio\",
          \"descripcion\": \"Descripción EXACTA si aparece escrita bajo el nombre del plato, o vacío \\\"\\\" si no hay\"
        }
      ]
    }
  ]
}

TEXTO DEL MENÚ:
{$text}

🎯 ESTRATEGIA DE PROCESAMIENTO:
1. Lee TODO el texto primero para entender la estructura
2. Identifica las categorías (títulos en mayúsculas, nombres de secciones)
3. Para cada categoría, busca los platos y sus precios
4. Si los precios están en bloques separados, asócialos a los platos por posición y contexto
5. Si un plato tiene múltiples variantes (ej: \"SOLO\", \"JAMÓN Y QUESO\", \"COMPLETO\"), créalos como platos separados con sus respectivos precios

REGLA FINAL: NO INVENTES NADA. Solo extrae lo que REALMENTE aparece en el texto arriba. Si no está en el texto, no lo incluyas. Busca CUIDADOSAMENTE todos los precios - están en el texto, solo necesitas encontrarlos.

Responde SOLO con el JSON válido, sin texto adicional, sin explicaciones, sin comentarios.";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->openaiApiKey,
            'Content-Type' => 'application/json',
        ])->timeout(600)->post($this->openaiBaseUrl . '/chat/completions', [
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 16000,
            'temperature' => 0.1,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? 'Error procesando con OpenAI';
            
            // Limpiar markdown si existe
            if (strpos($content, '```json') !== false) {
                $content = preg_replace('/```json\s*/', '', $content);
                $content = preg_replace('/```\s*$/', '', $content);
                $content = trim($content);
            } elseif (strpos($content, '```') !== false) {
                $content = preg_replace('/```\s*/', '', $content);
                $content = trim($content);
            }
            
            // Verificar si la respuesta fue truncada
            $finishReason = $data['choices'][0]['finish_reason'] ?? null;
            if ($finishReason === 'length') {
                Log::warning('Respuesta de OpenAI truncada por límite de tokens', [
                    'tokens_usados' => $data['usage']['total_tokens'] ?? 'desconocido',
                    'tamaño_respuesta' => strlen($content)
                ]);
            } else {
                Log::info('PDF procesado exitosamente con OpenAI', [
                    'tamaño_respuesta' => strlen($content),
                    'finish_reason' => $finishReason
                ]);
            }
            
            return $content;
        }

        Log::error('Error OpenAI API', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        throw new \Exception('Error al procesar con OpenAI: ' . $response->body());
    }

    /**
     * Verificar si un archivo es un PDF válido
     */
    public function isValidPdf(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        return $mimeType === 'application/pdf';
    }
}
