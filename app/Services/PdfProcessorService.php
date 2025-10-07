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
            // Extraer texto del PDF
            $extractedText = $this->extractTextFromPdf($pdfPath);
            
            if (empty($extractedText)) {
                return [
                    'success' => false,
                    'message' => 'No se pudo extraer texto del PDF. Puede ser una imagen escaneada.',
                    'suggestion' => 'Intenta subir el menú como imagen directamente.'
                ];
            }

            // Procesar con OpenAI
            $menuData = $this->processWithOpenAI($extractedText);
            
            return [
                'success' => true,
                'menu_data' => $menuData,
                'extracted_text' => $extractedText
            ];

        } catch (\Exception $e) {
            Log::error('Error procesando PDF: ' . $e->getMessage());
            
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
            $parser = new Parser();
            $pdf = $parser->parseFile($pdfPath);
            $text = $pdf->getText();
            
            // Limpiar el texto extraído
            $text = $this->cleanExtractedText($text);
            
            // Verificar si se extrajo texto válido
            if (empty(trim($text))) {
                throw new \Exception('El PDF no contiene texto extraíble');
            }
            
            return $text;
            
        } catch (\Exception $e) {
            Log::error('Error extrayendo texto del PDF: ' . $e->getMessage());
            
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
        $prompt = "Analiza el siguiente texto de un menú de restaurante y extráelo en formato JSON estructurado. 

INSTRUCCIONES:
- Identifica las categorías (entradas, platos principales, postres, bebidas, etc.)
- Para cada plato, extrae: nombre, precio, descripción (si existe)
- Usa el formato JSON exacto que se muestra abajo
- Si no hay precio visible, pon 'Precio no especificado'
- Mantén los nombres de platos tal como aparecen en el texto
- Si hay información del restaurante, inclúyela al inicio

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
          \"nombre\": \"Nombre del plato\",
          \"precio\": \"Precio\",
          \"descripcion\": \"Descripción del plato\"
        }
      ]
    }
  ]
}

TEXTO DEL MENÚ:
{$text}

Responde SOLO con el JSON, sin texto adicional.";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->openaiApiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post($this->openaiBaseUrl . '/chat/completions', [
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 4000,
            'temperature' => 0.1,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['choices'][0]['message']['content'] ?? 'Error procesando con OpenAI';
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
