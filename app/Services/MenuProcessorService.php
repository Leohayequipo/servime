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
        $this->openaiModel = config('services.openai.model', 'gpt-4o');
    }

    /**
     * Procesa un menú extrayendo texto y enviándolo a OpenAI
     */
    public function processMenu(Menu $menu)
    {
        try {
            // Guardar ID del menú para logs
            $this->menuId = $menu->id;
            
            // Log detallado del menú que se va a procesar
            Log::info('🔍 INICIANDO PROCESAMIENTO DE MENÚ', [
                'menu_id' => $menu->id,
                'menu_nombre' => $menu->nombre,
                'archivo_original' => $menu->archivo_original,
                'formato' => $menu->formato,
                'comercio_id' => $menu->comercio_id,
                'archivo_path_completo' => storage_path('app/public/' . $menu->archivo_original),
                'archivo_existe' => file_exists(storage_path('app/public/' . $menu->archivo_original)),
                'tamaño_archivo' => file_exists(storage_path('app/public/' . $menu->archivo_original)) ? filesize(storage_path('app/public/' . $menu->archivo_original)) : 'N/A',
                'modelo_openai' => $this->openaiModel
            ]);
            
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
                // Guardar JSON procesado en archivo temporal para comparación
                $jsonProcesadoPath = storage_path('app/temp/json_procesado_antes_de_guardar_' . $menu->id . '_' . uniqid() . '.txt');
                if (!is_dir(storage_path('app/temp'))) {
                    mkdir(storage_path('app/temp'), 0755, true);
                }
                file_put_contents($jsonProcesadoPath, "=== JSON PROCESADO ANTES DE GUARDAR EN BD - MENÚ {$menu->id} ===\n\n");
                file_put_contents($jsonProcesadoPath, $gptResponse . "\n", FILE_APPEND);
                file_put_contents($jsonProcesadoPath, "\n=== TAMAÑO DEL JSON ===\n", FILE_APPEND);
                file_put_contents($jsonProcesadoPath, "Longitud: " . strlen($gptResponse) . " caracteres\n", FILE_APPEND);
                file_put_contents($jsonProcesadoPath, "Es JSON válido: " . (json_decode($gptResponse) !== null ? 'SÍ' : 'NO') . "\n", FILE_APPEND);
                
                // Validar que el JSON sea válido antes de guardar
                $jsonValidacion = json_decode($gptResponse, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('❌ JSON INVÁLIDO antes de guardar en BD', [
                        'menu_id' => $menu->id,
                        'json_error' => json_last_error_msg(),
                        'json_preview' => mb_substr($gptResponse, 0, 500, 'UTF-8')
                    ]);
                    throw new \Exception('JSON inválido antes de guardar: ' . json_last_error_msg());
                }
                
                Log::info('📝 Guardando JSON en base de datos', [
                    'menu_id' => $menu->id,
                    'tamaño_json' => strlen($gptResponse),
                    'es_json_valido' => true,
                    'archivo_temporal' => $jsonProcesadoPath,
                    'primeros_100_caracteres' => mb_substr($gptResponse, 0, 100, 'UTF-8')
                ]);
                
                // 4. Actualizar con la respuesta de GPT (ya procesada)
                $menu->update([
                    'contexto_gpt' => $gptResponse,
                    'procesado' => true
                ]);
                
                // Verificar qué se guardó realmente en la BD
                $menu->refresh();
                $jsonGuardado = $menu->contexto_gpt;
                
                // Guardar lo que realmente se guardó en BD
                $jsonGuardadoPath = storage_path('app/temp/json_guardado_en_bd_' . $menu->id . '_' . uniqid() . '.txt');
                file_put_contents($jsonGuardadoPath, "=== JSON GUARDADO EN BASE DE DATOS - MENÚ {$menu->id} ===\n\n");
                file_put_contents($jsonGuardadoPath, $jsonGuardado . "\n", FILE_APPEND);
                file_put_contents($jsonGuardadoPath, "\n=== COMPARACIÓN ===\n", FILE_APPEND);
                file_put_contents($jsonGuardadoPath, "Tamaño original: " . strlen($gptResponse) . " caracteres\n", FILE_APPEND);
                file_put_contents($jsonGuardadoPath, "Tamaño guardado: " . strlen($jsonGuardado ?? '') . " caracteres\n", FILE_APPEND);
                file_put_contents($jsonGuardadoPath, "Son iguales: " . ($gptResponse === $jsonGuardado ? 'SÍ' : 'NO') . "\n", FILE_APPEND);
                
                if ($gptResponse !== $jsonGuardado) {
                    Log::warning('⚠️ DIFERENCIA DETECTADA entre JSON procesado y guardado', [
                        'menu_id' => $menu->id,
                        'tamaño_original' => strlen($gptResponse),
                        'tamaño_guardado' => strlen($jsonGuardado ?? ''),
                        'archivo_procesado' => $jsonProcesadoPath,
                        'archivo_guardado' => $jsonGuardadoPath
                    ]);
                }
                
                // Log detallado de la respuesta procesada
                $responsePreview = mb_substr($gptResponse, 0, 500, 'UTF-8');
                Log::info('✅ MENÚ PROCESADO EXITOSAMENTE', [
                    'menu_id' => $menu->id,
                    'menu_nombre' => $menu->nombre,
                    'archivo_original' => $menu->archivo_original,
                    'tamaño_respuesta' => strlen($gptResponse),
                    'tamaño_guardado' => strlen($jsonGuardado ?? ''),
                    'formato' => $menu->formato,
                    'preview_respuesta' => $responsePreview . '...',
                    'archivos_temporales' => [
                        'procesado' => $jsonProcesadoPath,
                        'guardado' => $jsonGuardadoPath
                    ]
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
        // REFRESCAR el menú desde la BD para asegurar que tenemos la ruta correcta
        $menu->refresh();
        
        $filePath = storage_path('app/public/' . $menu->archivo_original);
        
        Log::info('📄 EXTRAYENDO TEXTO DEL ARCHIVO', [
            'menu_id' => $menu->id,
            'menu_nombre' => $menu->nombre,
            'archivo_original' => $menu->archivo_original,
            'file_path' => $filePath,
            'archivo_existe' => file_exists($filePath),
            'tamaño_archivo' => file_exists($filePath) ? filesize($filePath) : 'N/A',
            'formato' => $menu->formato,
            'comercio_id' => $menu->comercio_id,
            'hash_archivo' => file_exists($filePath) ? md5_file($filePath) : 'N/A'
        ]);
        
        if (!file_exists($filePath)) {
            Log::error('❌ ARCHIVO NO ENCONTRADO', [
                'menu_id' => $menu->id,
                'menu_nombre' => $menu->nombre,
                'archivo_original' => $menu->archivo_original,
                'file_path' => $filePath,
                'directorio_existe' => is_dir(dirname($filePath)),
                'archivos_en_directorio' => is_dir(dirname($filePath)) ? implode(', ', array_slice(scandir(dirname($filePath)), 0, 10)) : 'N/A'
            ]);
            throw new \Exception('Archivo no encontrado: ' . $filePath);
        }

        if ($menu->formato === 'pdf') {
            return $this->processPDFWithOpenAI($filePath);
        } else {
            return $this->processImageWithOpenAI($filePath);
        }
    }

    /**
     * Procesa PDFs usando estrategia híbrida con múltiples proveedores:
     * 1. Primero intenta con Gemini (procesa PDFs directamente, sin conversión)
     * 2. Si falla, intenta extraer texto directamente del PDF
     * 3. Si falla, convierte a imágenes y usa OpenAI Vision (para PDFs escaneados)
     */
    protected function processPDFWithOpenAI($filePath)
    {
        Log::info('📄 PROCESANDO PDF - Estrategia híbrida con múltiples proveedores', [
            'file_path' => $filePath,
            'archivo_existe' => file_exists($filePath)
        ]);
        
        // ESTRATEGIA 1: Intentar con Gemini (procesa PDFs directamente)
        try {
            $geminiProcessor = new GeminiPdfProcessorService();
            $result = $geminiProcessor->processPdfToMenu($filePath);
            
            if ($result['success'] && !empty($result['menu_data'])) {
                Log::info('✅ PDF procesado exitosamente con Gemini', [
                    'tamaño_respuesta' => strlen($result['menu_data']),
                    'provider' => 'gemini'
                ]);
                
                // Guardar respuesta de Gemini en archivo temporal
                $this->saveGeminiResponseToTemp($filePath, $result['menu_data']);
                
                return $result['menu_data'];
            }
        } catch (\Exception $e) {
            Log::warning('⚠️ No se pudo procesar PDF con Gemini, intentando con extracción de texto', [
                'error' => $e->getMessage(),
                'file_path' => $filePath
            ]);
        }
        
        // ESTRATEGIA 2: Intentar extraer texto directamente del PDF
        try {
            $pdfProcessor = new PdfProcessorService();
            $result = $pdfProcessor->processPdfToMenu($filePath);
            
            if ($result['success'] && !empty($result['menu_data'])) {
                // Guardar el texto extraído para referencia
                $extractedText = $result['extracted_text'] ?? '';
                $this->savedExtractedText = $extractedText;
                
                // Inicializar variables para archivos temporales
                $textoExtraidoFile = null;
                $respuestaOpenAIFile = null;
                
                // GUARDAR TEXTO EXTRAÍDO EN ARCHIVO TEMPORAL PARA ANÁLISIS
                if (!empty($extractedText)) {
                    $tempDir = storage_path('app/temp');
                    if (!is_dir($tempDir)) {
                        mkdir($tempDir, 0755, true);
                    }
                    
                    $textoExtraidoFile = $tempDir . '/texto_extraido_pdf_' . basename($filePath, '.pdf') . '_' . date('Y-m-d_His') . '_' . uniqid() . '.txt';
                    
                    $contenidoArchivo = "=== TEXTO EXTRAÍDO DEL PDF ===\n";
                    $contenidoArchivo .= "Fecha: " . date('Y-m-d H:i:s') . "\n";
                    $contenidoArchivo .= "Archivo PDF original: " . basename($filePath) . "\n";
                    $contenidoArchivo .= "Ruta completa: " . $filePath . "\n";
                    $contenidoArchivo .= "Tamaño del texto: " . strlen($extractedText) . " caracteres\n";
                    $contenidoArchivo .= "Tamaño del texto: " . number_format(strlen($extractedText) / 1024, 2) . " KB\n";
                    $contenidoArchivo .= "Método: Extracción directa de texto (PdfProcessorService)\n";
                    $contenidoArchivo .= "Estado: Texto extraído exitosamente, listo para procesar con OpenAI\n";
                    $contenidoArchivo .= "\n";
                    $contenidoArchivo .= "=== CONTENIDO DEL TEXTO EXTRAÍDO ===\n";
                    $contenidoArchivo .= "\n";
                    $contenidoArchivo .= $extractedText;
                    $contenidoArchivo .= "\n\n";
                    $contenidoArchivo .= "=== FIN DEL TEXTO EXTRAÍDO ===\n";
                    
                    file_put_contents($textoExtraidoFile, $contenidoArchivo);
                    
                    Log::info('💾 Texto extraído del PDF guardado en archivo temporal', [
                        'archivo_temporal' => $textoExtraidoFile,
                        'tamaño_texto' => strlen($extractedText),
                        'tiene_texto' => !empty($extractedText)
                    ]);
                }
                
                // GUARDAR RESPUESTA DE OPENAI EN ARCHIVO TEMPORAL PARA ANÁLISIS
                if (!empty($result['menu_data'])) {
                    $menuData = $result['menu_data'];
                    $tempDir = storage_path('app/temp');
                    if (!is_dir($tempDir)) {
                        mkdir($tempDir, 0755, true);
                    }
                    
                    $respuestaOpenAIFile = $tempDir . '/respuesta_openai_pdf_' . basename($filePath, '.pdf') . '_' . date('Y-m-d_His') . '_' . uniqid() . '.json';
                    
                    $contenidoRespuesta = [
                        'metadata' => [
                            'fecha' => date('Y-m-d H:i:s'),
                            'archivo_pdf_original' => basename($filePath),
                            'ruta_completa_pdf' => $filePath,
                            'metodo' => 'Extracción directa de texto + OpenAI (no Vision)',
                            'tamaño_respuesta' => strlen($menuData) . ' caracteres',
                            'tamaño_respuesta_kb' => number_format(strlen($menuData) / 1024, 2) . ' KB',
                            'archivo_texto_extraido' => isset($textoExtraidoFile) ? basename($textoExtraidoFile) : null,
                            'tiene_texto_extraido' => !empty($extractedText),
                            'tamaño_texto_extraido' => !empty($extractedText) ? strlen($extractedText) . ' caracteres' : null
                        ],
                        'respuesta_openai' => $menuData
                    ];
                    
                    // Intentar parsear como JSON para validar
                    $jsonData = json_decode($menuData, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $contenidoRespuesta['es_json_valido'] = true;
                        $contenidoRespuesta['estructura_json'] = [
                            'tiene_restaurante' => isset($jsonData['restaurante']),
                            'tiene_categorias' => isset($jsonData['categorias']),
                            'numero_categorias' => isset($jsonData['categorias']) ? count($jsonData['categorias']) : 0
                        ];
                    } else {
                        $contenidoRespuesta['es_json_valido'] = false;
                        $contenidoRespuesta['error_json'] = json_last_error_msg();
                    }
                    
                    file_put_contents($respuestaOpenAIFile, json_encode($contenidoRespuesta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                    
                    Log::info('💾 Respuesta de OpenAI guardada en archivo temporal', [
                        'archivo_temporal' => $respuestaOpenAIFile,
                        'tamaño_respuesta' => strlen($menuData),
                        'es_json_valido' => isset($contenidoRespuesta['es_json_valido']) ? $contenidoRespuesta['es_json_valido'] : 'desconocido'
                    ]);
                }
                
                Log::info('✅ PDF procesado exitosamente con extracción de texto directa', [
                    'tiene_texto_extraido' => !empty($result['extracted_text']),
                    'tamaño_respuesta' => strlen($result['menu_data']),
                    'archivo_texto_temp' => isset($textoExtraidoFile) ? basename($textoExtraidoFile) : null,
                    'archivo_respuesta_temp' => isset($respuestaOpenAIFile) ? basename($respuestaOpenAIFile) : null
                ]);
                
                return $result['menu_data'];
            }
        } catch (\Exception $e) {
            Log::warning('⚠️ No se pudo extraer texto del PDF (probablemente es escaneado), intentando con Vision', [
                'error' => $e->getMessage(),
                'file_path' => $filePath
            ]);
        }
        
        // ESTRATEGIA 3: Si falló la extracción de texto, convertir a imágenes y usar OpenAI Vision
        // (OpenAI Vision NO acepta PDFs directamente, solo imágenes)
        Log::info('📄 PDF no tiene texto extraíble, convirtiendo a imágenes para OpenAI Vision', [
            'file_path' => $filePath
        ]);
        
        try {
            $result = $this->processPdfDirectlyWithOpenAI($filePath);
            
            Log::info('✅ PDF procesado exitosamente con OpenAI Vision (convertido a imágenes)');
            return $result;
        } catch (\Exception $e) {
            Log::error('❌ Error procesando PDF con OpenAI Vision: ' . $e->getMessage(), [
                'exception_type' => get_class($e),
                'file_path' => $filePath
            ]);
            
            throw new \Exception('No se pudo procesar el PDF. Intentó con Gemini, extracción de texto y conversión a imágenes, todos fallaron: ' . $e->getMessage());
        }
    }

    /**
     * Guardar respuesta de Gemini en archivo temporal para análisis
     */
    private function saveGeminiResponseToTemp(string $filePath, string $menuData): void
    {
        try {
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            $respuestaGeminiFile = $tempDir . '/respuesta_gemini_pdf_' . basename($filePath, '.pdf') . '_' . date('Y-m-d_His') . '_' . uniqid() . '.json';
            
            $contenidoRespuesta = [
                'metadata' => [
                    'fecha' => date('Y-m-d H:i:s'),
                    'archivo_pdf_original' => basename($filePath),
                    'ruta_completa_pdf' => $filePath,
                    'metodo' => 'Procesamiento directo de PDF con Gemini (sin conversión)',
                    'provider' => 'gemini',
                    'tamaño_respuesta' => strlen($menuData) . ' caracteres',
                    'tamaño_respuesta_kb' => number_format(strlen($menuData) / 1024, 2) . ' KB'
                ],
                'respuesta_gemini' => $menuData
            ];
            
            // Intentar parsear como JSON para validar
            $jsonData = json_decode($menuData, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $contenidoRespuesta['es_json_valido'] = true;
                $contenidoRespuesta['estructura_json'] = [
                    'tiene_restaurante' => isset($jsonData['restaurante']),
                    'tiene_categorias' => isset($jsonData['categorias']),
                    'numero_categorias' => isset($jsonData['categorias']) ? count($jsonData['categorias']) : 0
                ];
            } else {
                $contenidoRespuesta['es_json_valido'] = false;
                $contenidoRespuesta['error_json'] = json_last_error_msg();
            }
            
            file_put_contents($respuestaGeminiFile, json_encode($contenidoRespuesta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            
            Log::info('💾 Respuesta de Gemini guardada en archivo temporal', [
                'archivo_temporal' => $respuestaGeminiFile,
                'tamaño_respuesta' => strlen($menuData),
                'es_json_valido' => isset($contenidoRespuesta['es_json_valido']) ? $contenidoRespuesta['es_json_valido'] : 'desconocido'
            ]);
        } catch (\Exception $e) {
            Log::warning('⚠️ No se pudo guardar respuesta de Gemini en archivo temporal: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene el número de páginas de un PDF
     */
    protected function getPdfPageCount($filePath): int
    {
        try {
            if (class_exists('Imagick')) {
                $imagick = new \Imagick();
                $imagick->setResolution(150, 150);
                try {
                    $imagick->readImage($filePath);
                    $pageCount = $imagick->getNumberImages();
                    $imagick->clear();
                    $imagick->destroy();
                    return $pageCount;
                } catch (\Exception $e) {
                    Log::warning('No se pudo leer PDF con Imagick para contar páginas: ' . $e->getMessage());
                }
            }
            // Si no se puede contar, asumir 1 página
            return 1;
        } catch (\Exception $e) {
            Log::warning('Error contando páginas del PDF: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Convierte una página específica del PDF a imagen
     */
    protected function convertPdfPageToImage($pdfPath, $pageNumber, $outputPath): bool
    {
        try {
            if (class_exists('Imagick')) {
                $imagick = new \Imagick();
                $imagick->setResolution(150, 150);
                try {
                    $imagick->readImage($pdfPath . '[' . ($pageNumber - 1) . ']');
                    $imagick->setImageFormat('png');
                    $imagick->setImageCompressionQuality(90);
                    $imagick->writeImage($outputPath);
                    $imagick->clear();
                    $imagick->destroy();
                    return file_exists($outputPath) && filesize($outputPath) > 0;
                } catch (\Exception $e) {
                    Log::error('Error convirtiendo página ' . $pageNumber . ' con Imagick: ' . $e->getMessage());
                    return false;
                }
            }
            return false;
        } catch (\Exception $e) {
            Log::error('Error convirtiendo página del PDF: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Procesa secciones de imagen por separado y combina los resultados
     */
    protected function processImageSectionsSeparately(array $sections, string $mimeType): string
    {
        // Aumentar el tiempo de ejecución para procesar múltiples secciones
        set_time_limit(600); // 10 minutos
        
        Log::info('Procesando imagen dividida en ' . count($sections) . ' secciones', [
            'modelo_usado' => $this->openaiModel
        ]);

        $jsonResults = []; // Array indexado por número de sección para preservar orden

        foreach ($sections as $section) {
            $sectionNumber = $section['number']; // Número original de la sección (1, 2, 3...)
            $sectionIndex = $sectionNumber - 1; // Índice para el array (0, 1, 2...)
            
            Log::info('Procesando sección ' . $sectionNumber . ' de ' . count($sections), [
                'modelo' => $this->openaiModel,
                'seccion_path' => $section['path'],
                'section_index' => $sectionIndex
            ]);
            
            try {
                $imageData = file_get_contents($section['path']);
                $base64Image = base64_encode($imageData);
                
                // Obtener dimensiones de la imagen para logging
                $imageInfo = getimagesize($section['path']);
                $imageWidth = $imageInfo[0] ?? 'N/A';
                $imageHeight = $imageInfo[1] ?? 'N/A';
                $imageSize = strlen($imageData);
                $base64Size = strlen($base64Image);
                
                Log::info('Enviando sección a OpenAI', [
                    'seccion' => $sectionNumber,
                    'dimensiones' => $imageWidth . 'x' . $imageHeight,
                    'tamaño_imagen_bytes' => $imageSize,
                    'tamaño_base64_bytes' => $base64Size,
                    'tamaño_base64_mb' => round($base64Size / 1024 / 1024, 2)
                ]);
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->openaiApiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(300)->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->openaiModel, // gpt-4o-mini o gpt-4.1
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => $this->buildExtractionPrompt($sectionNumber, $sections)
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => 'data:' . $mimeType . ';base64,' . $base64Image,
                                        'detail' => 'high'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'temperature' => 0,
                    'max_tokens' => 16384
                ]);
                

                if ($response->successful()) {
                    $data = $response->json();
                    $content = $data['choices'][0]['message']['content'] ?? null;
                    
                    if ($content) {
                        // Limpiar markdown si existe
                        $cleanContent = $content;
                        if (strpos($cleanContent, '```json') !== false) {
                            $cleanContent = preg_replace('/```json\s*/', '', $cleanContent);
                            $cleanContent = preg_replace('/```\s*$/', '', $cleanContent);
                            $cleanContent = trim($cleanContent);
                        } elseif (strpos($cleanContent, '```') !== false) {
                            $cleanContent = preg_replace('/```\s*/', '', $cleanContent);
                            $cleanContent = trim($cleanContent);
                        }
                        
                        // Guardar respuesta de OpenAI en archivo de texto para revisión
                        $txtPath = storage_path('app/temp/openai_response_section_' . $sectionNumber . '_' . uniqid() . '.txt');
                        if (!is_dir(storage_path('app/temp'))) {
                            mkdir(storage_path('app/temp'), 0755, true);
                        }
                        file_put_contents($txtPath, "=== RESPUESTA DE OPENAI - SECCIÓN {$sectionNumber} ===\n\n");
                        file_put_contents($txtPath, $content . "\n\n", FILE_APPEND);
                        file_put_contents($txtPath, "=== CONTENIDO LIMPIO ===\n\n", FILE_APPEND);
                        file_put_contents($txtPath, $cleanContent . "\n", FILE_APPEND);
                        Log::info('Respuesta de OpenAI guardada en archivo', [
                            'seccion' => $sectionNumber,
                            'archivo' => $txtPath
                        ]);
                        
                        // Validar que el JSON sea válido antes de agregarlo
                        $jsonTest = json_decode($cleanContent, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($jsonTest)) {
                            // Guardar JSON original antes de normalizar
                            $jsonOriginalPath = storage_path('app/temp/json_original_section_' . $sectionNumber . '_' . uniqid() . '.txt');
                            if (!is_dir(storage_path('app/temp'))) {
                                mkdir(storage_path('app/temp'), 0755, true);
                            }
                            file_put_contents($jsonOriginalPath, "=== JSON ORIGINAL DE GPT - SECCIÓN {$sectionNumber} ===\n\n");
                            file_put_contents($jsonOriginalPath, json_encode($jsonTest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
                            file_put_contents($jsonOriginalPath, "\n=== ESTRUCTURA ORIGINAL ===\n", FILE_APPEND);
                            file_put_contents($jsonOriginalPath, "Keys disponibles: " . implode(', ', array_keys($jsonTest)) . "\n", FILE_APPEND);
                            Log::info('JSON original guardado antes de normalizar', [
                                'seccion' => $sectionNumber,
                                'archivo' => $jsonOriginalPath
                            ]);
                            
                            // Normalizar cualquier estructura JSON al formato estándar
                            try {
                                $jsonTest = $this->normalizeMenuJsonStructure($jsonTest);
                                $cleanContent = json_encode($jsonTest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                                
                                Log::info('JSON normalizado exitosamente', [
                                    'seccion' => $sectionNumber,
                                    'estructura_original' => array_keys($jsonTest),
                                    'tiene_categorias' => isset($jsonTest['categorias'])
                                ]);
                            } catch (\Exception $normalizeError) {
                                Log::error('Error normalizando JSON de sección ' . $sectionNumber . ': ' . $normalizeError->getMessage());
                            }
                            
                            // Validar estructura básica después de normalizar
                            if (isset($jsonTest['categorias']) && is_array($jsonTest['categorias'])) {
                                // IMPORTANTE: Guardar en el índice correcto según el número de sección original
                                // Esto preserva el orden incluso si las secciones terminan en orden diferente
                                $jsonResults[$sectionIndex] = $cleanContent;
                                Log::info('Sección ' . $sectionNumber . ' procesada exitosamente', [
                                    'section_index' => $sectionIndex,
                                    'categorias_encontradas' => count($jsonTest['categorias']),
                                    'total_platos' => array_sum(array_map(function($cat) {
                                        return isset($cat['platos']) && is_array($cat['platos']) ? count($cat['platos']) : 0;
                                    }, $jsonTest['categorias']))
                                ]);
                            } else {
                                Log::warning('Sección ' . $sectionNumber . ' procesada pero sin estructura válida después de normalizar', [
                                    'keys_disponibles' => array_keys($jsonTest),
                                    'tiene_items' => isset($jsonTest['items']),
                                    'tiene_categorias' => isset($jsonTest['categorias']),
                                    'tiene_sections' => isset($jsonTest['sections'])
                                ]);
                            }
                        } else {
                            Log::warning('Sección ' . $sectionNumber . ' procesada pero JSON inválido: ' . json_last_error_msg());
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error procesando sección ' . $sectionNumber . ': ' . $e->getMessage());
            }
        }

        if (empty($jsonResults)) {
            throw new \Exception('No se pudo procesar ninguna sección de la imagen');
        }

        // Ordenar por clave (índice) para asegurar orden correcto antes de combinar
        ksort($jsonResults);
        
        // Reindexar array para que combineMenuJsons reciba índices consecutivos (0, 1, 2...)
        $jsonResults = array_values($jsonResults);

        // Combinar resultados usando método simplificado
        $combinedJson = $this->combineMenuJsons_SIMPLE($jsonResults);
        Log::info('Imagen procesada exitosamente por secciones', [
            'secciones_procesadas' => count($jsonResults),
            'total_secciones' => count($sections)
        ]);

        return $combinedJson;
    }

    /**
     * Construye el prompt de extracción flexible para páginas de PDF
     */
    protected function buildExtractionPromptForPage($pageNumber, $totalPages): string
    {
        $prompt = $this->buildExtractionPrompt($pageNumber, range(1, $totalPages));
        // Reemplazar "sección" por "página" en el prompt
        $prompt = str_replace('sección', 'página', $prompt);
        $prompt = str_replace('SECCIÓN', 'PÁGINA', $prompt);
        return $prompt;
    }

    /**
     * Construye el prompt de extracción flexible para secciones de imagen
     * Permite que GPT estructure el JSON según la organización visual del menú
     */
    protected function buildExtractionPrompt($sectionNumber, $sections): string
    {
        $totalSections = is_array($sections) ? count($sections) : 1;
        
        return 'Eres un experto en extracción de información de menús de restaurantes. Tu tarea es analizar esta imagen y extraer TODA la información del menú de forma precisa y completa.

Esta es la sección ' . $sectionNumber . ' de ' . $totalSections . ' de una imagen de menú.

🎯 OBJETIVO PRINCIPAL:
Extrae TODA la información que veas en esta imagen: nombres de platos, precios, descripciones, categorías, tags, y cualquier otro detalle relevante.

📋 LIBERTAD DE ESTRUCTURA:
Puedes estructurar el JSON de la manera que mejor refleje la organización visual del menú. Algunas opciones:
- Si el menú tiene categorías claras → agrupa por categorías
- Si tiene secciones visuales → respeta esas secciones
- Si tiene subcategorías → inclúyelas como mejor entiendas la jerarquía
- Si tiene items en lista plana → estructura como lista plana
- Si tiene combos/promos especiales → inclúyelos como mejor los entiendas

Lo importante es que captures TODA la información de forma precisa, no el formato exacto del JSON.

🚨🚨🚨 ATENCIÓN CRÍTICA - LEE ESTO PRIMERO 🚨🚨🚨:
Esta imagen tiene el ANCHO COMPLETO del menú original. Puede tener MÚLTIPLES COLUMNAS.
DEBES leer TODA la imagen de IZQUIERDA A DERECHA, columna por columna.
NO te enfoques solo en la columna izquierda. Escanea toda la imagen horizontalmente.
Si hay "Waffles" a la izquierda y "Postres" a la derecha, AMBOS deben aparecer en tu respuesta.
Lee cada línea COMPLETA de izquierda a derecha antes de pasar a la siguiente.

⚠️⚠️⚠️ LEE TODA LA IMAGEN COMPLETA - ESTO ES CRÍTICO ⚠️⚠️⚠️:
- Esta sección contiene el ANCHO COMPLETO de la imagen original
- La imagen puede tener MÚLTIPLES COLUMNAS (izquierda, centro, derecha)
- DEBES leer TODA la imagen de IZQUIERDA A DERECHA, sin saltarte ninguna columna
- Si ves texto en la parte izquierda Y texto en la parte derecha, AMBOS deben ser procesados
- NO te enfoques solo en una columna, escanea toda la imagen horizontalmente
- Ejemplo: Si hay "Waffles" a la izquierda y "Postres" a la derecha, AMBOS deben aparecer en el JSON
- Lee cada línea de IZQUIERDA A DERECHA antes de pasar a la siguiente línea

🚫 REGLAS ABSOLUTAS - NO HAY EXCEPCIONES:

1. TRANSCRIBE SOLO lo que VES escrito. Si no está escrito, NO existe.
2. NO uses tu conocimiento sobre restaurantes, menús, o comida.
3. NO asumas nombres de platos comunes (ej: si NO ves "Expresso", NO lo agregues).
4. NO uses patrones o lógica (ej: si hay "Café con leche", NO significa que debe haber "Expresso").
5. NO completes información faltante (si falta el precio, escribe "Precio no especificado", NO inventes uno).
6. NO cambies nombres (si dice "CAFÉ- CORTADO- LÁGRIMA", NO lo cambies a "CAFÉ EXPRESSO").
7. NO agregues categorías que no veas escritas como título o encabezado.
8. Si un plato está cortado o no se ve completo (falta nombre O precio), NO lo agregues.

🚨🚨🚨 PRECIOS - LA REGLA MÁS IMPORTANTE (LEE ESTO 3 VECES) 🚨🚨🚨:
- Los precios son CRÍTICOS - debes leerlos CARACTER POR CARACTER, sin confundir dígitos
- Copia el precio EXACTAMENTE como aparece escrito: "$3500", "$4900", "$6900", "$7500", "$8500", "$7900", "$9900"
- Si ves "$6900", escribe "$6900" - NO "$7500", NO "$7900", NO "$9600" (NO confundas 6 con 7)
- Si ves "$7500", escribe "$7500" - NO "$8000", NO "$8500" (NO redondees hacia arriba)
- Si ves "$8500", escribe "$8500" - NO "$9000", NO "$9500" (NO confundas 8 con 9)
- Si ves "$7900", escribe "$7900" - NO "$7000", NO "$9600" (NO confundas 9 con 0 o 6)
- Si ves "$9900", escribe "$9900" - NO "$9600", NO "$10000" (NO confundas 9 con 6)
- NO redondees, NO cambies, NO "corrijas", NO "mejores", NO "normalices" precios
- NO uses el precio de otro plato similar o de la misma categoría
- NO inventes precios basándote en patrones, lógica, o conocimiento previo
- NO asumas que si un café cuesta $3500, otro debe costar lo mismo
- **BUSCA CUIDADOSAMENTE cada precio** - pueden estar a la derecha, debajo, o en la misma línea del nombre
- **SI VES CUALQUIER NÚMERO O SÍMBOLO $ JUNTO A UN PLATO, ESE ES EL PRECIO** - Cópialo EXACTAMENTE
- **SOLO usa "Precio no especificado" si REALMENTE no ves ningún número, símbolo $, o indicador de precio junto al plato**
- NO uses "Precio no especificado" por defecto - primero busca cuidadosamente el precio
- EJEMPLOS ESPECÍFICOS DE ERRORES COMUNES A EVITAR:
  ❌ Si ves "$6900" → NO escribas "$7500" (confundiste 6 con 7)
  ❌ Si ves "$7500" → NO escribas "$8000" (redondeaste hacia arriba)
  ❌ Si ves "$8500" → NO escribas "$9000" (confundiste 8 con 9)
  ❌ Si ves "$7900" → NO escribas "$7000" o "$9600"
  ❌ Si ves "$9900" → NO escribas "$9600"

🚨🚨🚨 NOMBRES DE PLATOS - MUY IMPORTANTE 🚨🚨🚨:
- Copia EXACTAMENTE como aparece: "CAFÉ CON LECHE", "CAFÉ- CORTADO- LÁGRIMA", etc.
- NO cambies mayúsculas, guiones, espacios, o formato
- NO "corrijas" nombres (si dice "CAFÉ- CORTADO", NO lo cambies a "Café Cortado")
- Si NO ves un plato escrito, NO lo agregues aunque sea común en menús
- Un nombre de plato puede ocupar MÚLTIPLES LÍNEAS en la imagen
- Si ves "SALSA DE CHOCO O" en una línea y "MERMELADA" en la siguiente, es UN SOLO plato: "SALSA DE CHOCO O MERMELADA"
- NO dividas nombres de platos que están en múltiples líneas en platos separados
- Si un nombre continúa en la siguiente línea, únelo en UN SOLO nombre
- EJEMPLO: Si ves "SALSA DE CHOCO O" (línea 1) y "MERMELADA" (línea 2) con precio "$8200" → escribe: nombre="SALSA DE CHOCO O MERMELADA", precio="$8200" (UN SOLO plato)

✅ PROCESO DE TRANSCRIPCIÓN (RESPETA EL ORDEN - ESTO ES CRÍTICO):
1. ⚠️ LEE TODA LA IMAGEN COMPLETA - NO TE SALTEES NINGUNA PARTE:
   - Lee de ARRIBA HACIA ABAJO, línea por línea
   - Lee de IZQUIERDA A DERECHA en cada línea
   - Si la imagen tiene MÚLTIPLES COLUMNAS, lee TODAS las columnas (izquierda, centro, derecha)
   - NO te enfoques solo en una columna, lee TODA la imagen completa
   - Si ves texto en la parte izquierda Y texto en la parte derecha, ambos deben ser procesados
   - Ejemplo: Si hay "Waffles" a la izquierda y "Postres" a la derecha, AMBOS deben aparecer en el JSON
   - Escanea toda la imagen de izquierda a derecha antes de pasar a la siguiente línea
2. Para cada línea que veas, en el orden exacto que aparece (de arriba hacia abajo, y de izquierda a derecha):
   - Si es un título/encabezado → es una categoría (ejemplos: "CAFETERÍA", "Para acompañar", "Pastelería", "Waffles", "Postres", etc.)
   - Si es un nombre de plato seguido de precio → es un plato
   - Si hay texto descriptivo debajo del nombre del plato (ingredientes, preparación, etc.) → es la descripción, cópiala EXACTAMENTE
   - Si falta el nombre O el precio → NO lo agregues
3. ⚠️ DETECCIÓN DE CATEGORÍAS - MUY IMPORTANTE:
   - INSTRUCCIÓN CLAVE: Todo texto que esté visualmente unido al título de una sección del menú —incluye palabras entre paréntesis, subtítulos, aclaraciones, ingredientes base o variantes— debe ser considerado parte de la categoría.
   - Ejemplo: si aparece "VEGGIES (LENTEJA O QUINOA)", la categoría completa es "VEGGIES (LENTEJA O QUINOA)". Nunca descartes esos textos.
   - Si una categoría tiene texto adicional en la misma línea o visualmente asociado (paréntesis, guiones, subtítulos), inclúyelo TODO en el nombre de la categoría.
   - 🧠 REGLA DE JERARQUÍA VISUAL (CRÍTICO):
     * Interpreta las categorías por niveles:
       - Título grande = categoría principal
       - Títulos medianos o destacados = subcategorías dentro de la categoría principal
       - Títulos pequeños, variantes o estilos (como "Veggies (lenteja o quinoa)") también deben considerarse subcategorías si están dentro del área gráfica de la sección principal, aunque tengan otro diseño o fondo
     * Nunca crees una categoría principal nueva si no hay un título principal visualmente dominante que lo justifique
   - 🔧 REGLA VISUAL DE CONTENCIÓN (FUNDAMENTAL):
     * Si un grupo de ítems aparece debajo de una categoría principal (como "Hamburguesas") y está en la misma columna o bloque, entonces pertenece a esa categoría, aunque tenga un diseño distinto o un recuadro propio
     * Esto hace que "Veggies" sea subcategoría de "Hamburguesas", no categoría independiente
   - 🎯 EJEMPLO EXPLÍCITO:
     * En la imagen, "Hamburguesas" es la categoría principal
     * "Clásicas", "Especiales" y "Veggies (lenteja o quinoa)" son subcategorías de Hamburguesas
     * No deben convertirse en categorías principales separadas
   - Una categoría puede aparecer como: texto en mayúsculas, texto centrado, texto más grande, texto en negrita, o simplemente texto seguido de una lista de platos
   - Si ves texto que parece ser un encabezado o título (aunque no esté en mayúsculas), es probablemente una categoría
   - Ejemplos de categorías comunes: "Cafetería", "Para acompañar", "Pastelería", "Waffles", "Postres", "Milanesas", "Lomitos", "Hamburguesas", "Ensaladas", "Brunch", "Promos", "Licuados", etc.
   - NO te saltes ninguna categoría, incluso si no tiene un formato muy claro de título
   - Si dudas si algo es una categoría o un plato, pregúntate: ¿viene seguido de una lista de platos? Si la respuesta es sí, es una categoría
   - ⚠️ CRÍTICO: Si la imagen tiene múltiples columnas, busca categorías en TODAS las columnas (izquierda, centro, derecha)
   - Si ves "Waffles" a la izquierda y "Postres" a la derecha, AMBAS son categorías y deben aparecer en el JSON
   - NO ignores ninguna columna, lee TODA la imagen de izquierda a derecha
4. Transcribe carácter por carácter, sin interpretar
5. IMPORTANTE: Si hay descripción visible (texto adicional bajo el nombre del plato), cópiala EXACTAMENTE en el campo "descripcion"
6. ⚠️ DETECCIÓN DE TAGS/ETIQUETAS (MUY IMPORTANTE):
   - Si ves CUALQUIER símbolo, icono, emoji, texto pequeño, badge o indicador visual junto al nombre del plato (antes, después, arriba, abajo, al lado), inclúyelo en el campo "tags" como TEXTO
   - Si ves un emoji o icono, conviértelo a su significado en texto (ej: 🌱 → "VEGANO", ⚡ → "PICANTE")
   - ⚠️ DIFERENCIACIÓN CRÍTICA ENTRE TAGS:
     * Tags de GLUTEN/SIN TACC: "GF", "Sin gluten", "Gluten free", "Libre gluten", "Libre de gluten", "Sin TACC", "Celíaco", "Celiaco", iconos/logos de espiga tachada o símbolos de gluten
     * Tags VEGANOS: "V", "VG", "Vegano", "Veg", 🌱 (planta), iconos/logos de hoja o plantas
     * NO confundas logos/símbolos de gluten con logos/símbolos veganos
     * Si ves un logo/símbolo de espiga tachada o relacionado con gluten → es SIN_TACC/GLUTEN FREE, NO VEGANO
     * Si ves un logo/símbolo de hoja/planta → es VEGANO, NO gluten free
   - Tags específicos a buscar: VEGANO, VEGETARIANO, SIN TACC, GLUTEN FREE, LIBRE GLUTEN, CELIACO, SIN LACTOSA, SIN AZUCAR, LOW CARB, KETO, KOSHER, HALAL, DIABETICO FRIENDLY, BAJO SODIO, ALTO EN PROTEINAS, LIGHT, ORGANICO, RAW, CRUDIVEGANO
   - Captura CUALQUIER variación de estos tags (ej: "V", "VG", "GF", "Sin gluten", "Gluten free", "Libre gluten", "🌱", "Sin TACC", "Celíaco", etc.) y escríbelo tal cual aparece
   - Si hay múltiples tags, inclúyelos todos en el array
   - Si NO hay ningún tag visible, deja el array vacío: []
   - Los tags deben ser en TEXTO, no emojis ni iconos
7. ⚠️ ORDEN CRÍTICO - LA REGLA MÁS IMPORTANTE:
   - Las categorías y platos DEBEN aparecer en el JSON en el MISMO ORDEN EXACTO que aparecen en la imagen
   - Lee de ARRIBA HACIA ABAJO, línea por línea, y agrega cada categoría/plato al JSON en el mismo orden que lo lees
   - NO reordenes, NO agrupes por tipo, NO cambies el orden por NINGÚN motivo
   - Si lees "A" antes que "B" en la imagen, entonces "A" debe estar ANTES que "B" en el JSON
   - El orden del JSON debe ser un REFLEJO EXACTO del orden visual de la imagen

📝 FORMATO JSON - LIBERTAD DE ESTRUCTURA:

Puedes estructurar el JSON de la manera que mejor refleje la organización visual del menú. Algunos ejemplos de estructuras válidas:

OPCIÓN 1 - Con categorías y platos (recomendado si hay categorías claras):
{
  "restaurante": {"nombre": "", "descripcion": ""},
  "categorias": [
    {
      "nombre": "Nombre de la categoría",
      "platos": [
        {
          "nombre": "Nombre del plato",
          "precio": "$3500",
          "descripcion": "Descripción si existe",
          "tags": ["VEGANO", "SIN GLUTEN"]
        }
      ]
    }
  ]
}

OPCIÓN 2 - Con items planos (si no hay categorías claras):
{
  "items": [
    {
      "category": "Cafetería",
      "name": "Café con leche",
      "price": "$4900",
      "description": "",
      "tags": []
    }
  ]
}

OPCIÓN 3 - Con secciones y subsecciones:
{
  "sections": [
    {
      "title": "Cafetería",
      "subsections": [
        {
          "title": "Clásicos",
          "items": [...]
        }
      ]
    }
  ]
}

OPCIÓN 4 - Cualquier otra estructura que mejor refleje el menú:
Puedes usar la estructura que mejor entiendas del menú. Lo importante es que incluyas:
- Nombres de platos (exactos)
- Precios (exactos, con $ si aparece)
- Descripciones (si existen)
- Categorías/secciones (si existen)
- Tags/etiquetas (si existen)

⚠️ LO MÁS IMPORTANTE:
- Captura TODA la información que veas
- Copia nombres y precios EXACTAMENTE como aparecen
- Respeta el orden visual (arriba hacia abajo, izquierda a derecha)
- NO inventes nada que no veas escrito

Responde SOLO con JSON válido, sin texto adicional, sin markdown, sin explicaciones.';
    }

    /**
     * Normaliza cualquier estructura JSON de menú al formato estándar "categorias"
     * Acepta múltiples formatos y los convierte inteligentemente
     */
    protected function normalizeMenuJsonStructure(array $data): array
    {
        // Si ya está en el formato estándar, retornarlo
        if (isset($data['categorias']) && is_array($data['categorias'])) {
            return $data;
        }

        // Formato 1: items (array plano con category, name, price, etc.)
        if (isset($data['items']) && is_array($data['items'])) {
            return $this->convertItemsFormatToCategoriasFormat($data);
        }

        // Formato 2: sections (con subsections)
        if (isset($data['sections']) && is_array($data['sections'])) {
            return $this->convertSectionsFormatToCategoriasFormat($data);
        }

        // Formato 3: categories (en inglés)
        if (isset($data['categories']) && is_array($data['categories'])) {
            return $this->convertCategoriesFormatToCategoriasFormat($data);
        }

        // Formato 4: menu (con items dentro)
        if (isset($data['menu']) && is_array($data['menu'])) {
            if (isset($data['menu']['items'])) {
                return $this->convertItemsFormatToCategoriasFormat(['items' => $data['menu']['items']]);
            }
            if (isset($data['menu']['categories'])) {
                return $this->convertCategoriesFormatToCategoriasFormat(['categories' => $data['menu']['categories']]);
            }
        }

        // Si no reconoce el formato, intentar extraer información de cualquier estructura
        Log::warning('Formato JSON no reconocido, intentando extracción genérica', [
            'keys' => array_keys($data)
        ]);
        
        return $this->extractGenericMenuStructure($data);
    }

    /**
     * Convierte el formato "items" al formato "categorias" esperado
     */
    protected function convertItemsFormatToCategoriasFormat(array $data): array
    {
        if (!isset($data['items']) || !is_array($data['items'])) {
            return $data;
        }

        $categorias = [];
        $categoriasMap = []; // Para agrupar platos por categoría

        foreach ($data['items'] as $item) {
            $categoriaNombre = $item['category'] ?? 'Sin categoría';
            $subCategoria = $item['sub_category'] ?? '';
            
            // Si hay subcategoría, incluirla en el nombre de la categoría
            if (!empty($subCategoria)) {
                $categoriaNombre = $categoriaNombre . ' - ' . $subCategoria;
            }

            // Normalizar nombre de categoría (lowercase para agrupar)
            $categoriaKey = strtolower(trim($categoriaNombre));
            
            // Crear categoría si no existe
            if (!isset($categoriasMap[$categoriaKey])) {
                $categoriasMap[$categoriaKey] = [
                    'nombre' => $categoriaNombre,
                    'platos' => []
                ];
            }

            // Crear plato
            $precio = $item['price'] ?? '';
            // Agregar $ si no lo tiene y no está vacío
            if (!empty($precio) && strpos($precio, '$') === false) {
                $precio = '$' . $precio;
            }
            if (empty($precio)) {
                $precio = 'Precio no especificado';
            }

            $plato = [
                'nombre' => $item['name'] ?? '',
                'precio' => $precio,
                'descripcion' => $item['description'] ?? '',
                'tags' => $item['tags'] ?? [],
                'imagen' => ''
            ];

            // Agregar opciones a la descripción si existen
            if (isset($item['options']) && is_array($item['options']) && !empty($item['options'])) {
                $opcionesTexto = implode(', ', $item['options']);
                if (!empty($plato['descripcion'])) {
                    $plato['descripcion'] .= ' (' . $opcionesTexto . ')';
                } else {
                    $plato['descripcion'] = $opcionesTexto;
                }
            }

            $categoriasMap[$categoriaKey]['platos'][] = $plato;
        }

        // Convertir map a array
        $categorias = array_values($categoriasMap);

        return [
            'restaurante' => [
                'nombre' => $data['restaurante']['nombre'] ?? $data['restaurant']['name'] ?? '',
                'descripcion' => $data['restaurante']['descripcion'] ?? $data['restaurant']['description'] ?? ''
            ],
            'categorias' => $categorias
        ];
    }

    /**
     * Convierte formato "sections" al formato "categorias"
     */
    protected function convertSectionsFormatToCategoriasFormat(array $data): array
    {
        $categorias = [];

        foreach ($data['sections'] ?? [] as $section) {
            $sectionTitle = $section['title'] ?? $section['nombre'] ?? 'Sin categoría';
            
            // Si tiene subsections
            if (isset($section['subsections']) && is_array($section['subsections'])) {
                foreach ($section['subsections'] as $subsection) {
                    $subsectionTitle = $subsection['title'] ?? $subsection['nombre'] ?? '';
                    $categoriaNombre = !empty($subsectionTitle) ? $sectionTitle . ' - ' . $subsectionTitle : $sectionTitle;
                    
                    $platos = [];
                    foreach ($subsection['items'] ?? [] as $item) {
                        $platos[] = $this->normalizePlatoFromItem($item);
                    }
                    
                    if (!empty($platos)) {
                        $categorias[] = [
                            'nombre' => $categoriaNombre,
                            'platos' => $platos
                        ];
                    }
                }
            } else {
                // Sin subsections, items directos
                $platos = [];
                foreach ($section['items'] ?? [] as $item) {
                    $platos[] = $this->normalizePlatoFromItem($item);
                }
                
                if (!empty($platos)) {
                    $categorias[] = [
                        'nombre' => $sectionTitle,
                        'platos' => $platos
                    ];
                }
            }
        }

        return [
            'restaurante' => [
                'nombre' => $data['restaurante']['nombre'] ?? $data['restaurant']['name'] ?? '',
                'descripcion' => $data['restaurante']['descripcion'] ?? $data['restaurant']['description'] ?? ''
            ],
            'categorias' => $categorias
        ];
    }

    /**
     * Convierte formato "categories" (en inglés) al formato "categorias"
     */
    protected function convertCategoriesFormatToCategoriasFormat(array $data): array
    {
        $categorias = [];

        foreach ($data['categories'] ?? [] as $category) {
            $categoriaNombre = $category['name'] ?? $category['nombre'] ?? $category['title'] ?? 'Sin categoría';
            
            $platos = [];
            foreach ($category['items'] ?? $category['platos'] ?? $category['dishes'] ?? [] as $item) {
                $platos[] = $this->normalizePlatoFromItem($item);
            }
            
            if (!empty($platos) || !empty($categoriaNombre)) {
                $categorias[] = [
                    'nombre' => $categoriaNombre,
                    'platos' => $platos
                ];
            }
        }

        return [
            'restaurante' => [
                'nombre' => $data['restaurante']['nombre'] ?? $data['restaurant']['name'] ?? '',
                'descripcion' => $data['restaurante']['descripcion'] ?? $data['restaurant']['description'] ?? ''
            ],
            'categorias' => $categorias
        ];
    }

    /**
     * Normaliza un item/plato desde cualquier formato al formato estándar
     */
    protected function normalizePlatoFromItem(array $item): array
    {
        // Detectar nombre (múltiples variantes)
        $nombre = $item['nombre'] ?? $item['name'] ?? $item['title'] ?? $item['dish'] ?? '';
        
        // Detectar precio (múltiples variantes)
        $precio = $item['precio'] ?? $item['price'] ?? $item['cost'] ?? '';
        
        // Normalizar precio: agregar $ si no lo tiene y no está vacío
        if (!empty($precio) && strpos($precio, '$') === false && is_numeric(str_replace(['.', ','], '', $precio))) {
            $precio = '$' . $precio;
        }
        if (empty($precio) || $precio === 'null' || $precio === null) {
            $precio = 'Precio no especificado';
        }

        // Detectar descripción (múltiples variantes)
        $descripcion = $item['descripcion'] ?? $item['description'] ?? $item['desc'] ?? '';
        
        // Detectar tags (múltiples variantes)
        $tags = $item['tags'] ?? $item['tag'] ?? $item['labels'] ?? [];
        if (!is_array($tags)) {
            $tags = [];
        }

        // Agregar opciones a la descripción si existen
        $opciones = $item['options'] ?? $item['opciones'] ?? $item['variants'] ?? [];
        if (is_array($opciones) && !empty($opciones)) {
            $opcionesTexto = implode(', ', $opciones);
            if (!empty($descripcion)) {
                $descripcion .= ' (' . $opcionesTexto . ')';
            } else {
                $descripcion = $opcionesTexto;
            }
        }

        return [
            'nombre' => $nombre,
            'precio' => $precio,
            'descripcion' => $descripcion,
            'tags' => $tags,
            'imagen' => $item['imagen'] ?? $item['image'] ?? ''
        ];
    }

    /**
     * Extrae información genérica de cualquier estructura JSON
     */
    protected function extractGenericMenuStructure(array $data): array
    {
        $categorias = [];
        $items = [];

        // Intentar encontrar items en cualquier nivel
        if (isset($data['items']) && is_array($data['items'])) {
            $items = $data['items'];
        } elseif (isset($data['menu']['items']) && is_array($data['menu']['items'])) {
            $items = $data['menu']['items'];
        } else {
            // Buscar recursivamente arrays que parezcan items
            foreach ($data as $key => $value) {
                if (is_array($value) && !empty($value)) {
                    $firstItem = reset($value);
                    if (is_array($firstItem) && (isset($firstItem['name']) || isset($firstItem['nombre']) || isset($firstItem['title']))) {
                        $items = $value;
                        break;
                    }
                }
            }
        }

        // Si encontramos items, agruparlos por categoría
        if (!empty($items)) {
            $categoriasMap = [];
            
            foreach ($items as $item) {
                if (!is_array($item)) continue;
                
                $categoriaNombre = $item['category'] ?? $item['categoria'] ?? $item['section'] ?? 'Sin categoría';
                $categoriaKey = strtolower(trim($categoriaNombre));
                
                if (!isset($categoriasMap[$categoriaKey])) {
                    $categoriasMap[$categoriaKey] = [
                        'nombre' => $categoriaNombre,
                        'platos' => []
                    ];
                }
                
                $categoriasMap[$categoriaKey]['platos'][] = $this->normalizePlatoFromItem($item);
            }
            
            $categorias = array_values($categoriasMap);
        }

        return [
            'restaurante' => [
                'nombre' => $data['restaurante']['nombre'] ?? $data['restaurant']['name'] ?? '',
                'descripcion' => $data['restaurante']['descripcion'] ?? $data['restaurant']['description'] ?? ''
            ],
            'categorias' => $categorias
        ];
    }

    /**
     * Normaliza tags a una lista estándar
     */
    protected function normalizeTag($tag): ?string
    {
        if (empty($tag)) {
            return null;
        }
        
        $tagLower = strtolower(trim($tag));
        
        // Mapeo de variaciones a tags estándar
        $tagMap = [
            // Vegano
            'vegano' => 'VEGANO',
            'veg' => 'VEGANO',
            'v' => 'VEGANO',
            'vg' => 'VEGANO',
            '🌱' => 'VEGANO',
            
            // Vegetariano
            'vegetariano' => 'VEGETARIANO',
            'veget' => 'VEGETARIANO',
            
            // Sin TACC / Gluten Free
            'sin tacc' => 'SIN_TACC',
            'sin gluten' => 'SIN_TACC',
            'libre gluten' => 'SIN_TACC',
            'libre de gluten' => 'SIN_TACC',
            'gluten free' => 'SIN_TACC',
            'free gluten' => 'SIN_TACC',
            'glutenfree' => 'SIN_TACC',
            'gf' => 'SIN_TACC',
            'celiaco' => 'SIN_TACC',
            'celíaco' => 'SIN_TACC',
            'celiac' => 'SIN_TACC',
            
            // Sin Lactosa
            'sin lactosa' => 'SIN_LACTOSA',
            'lactose free' => 'SIN_LACTOSA',
            'lactosefree' => 'SIN_LACTOSA',
            'sin lacteos' => 'SIN_LACTOSA',
            
            // Sin Azúcar
            'sin azúcar' => 'SIN_AZUCAR',
            'sin azucar' => 'SIN_AZUCAR',
            'sugar free' => 'SIN_AZUCAR',
            'sugarfree' => 'SIN_AZUCAR',
            
            // Low Carb / Keto
            'low carb' => 'LOW_CARB',
            'lowcarb' => 'LOW_CARB',
            'keto' => 'KETO',
            
            // Kosher
            'kosher' => 'KOSHER',
            
            // Halal
            'halal' => 'HALAL',
            
            // Diabético Friendly
            'diabetico friendly' => 'DIABETICO_FRIENDLY',
            'diabético friendly' => 'DIABETICO_FRIENDLY',
            'diabetic friendly' => 'DIABETICO_FRIENDLY',
            'apto diabeticos' => 'DIABETICO_FRIENDLY',
            'apto diabéticos' => 'DIABETICO_FRIENDLY',
            
            // Bajo Sodio
            'bajo sodio' => 'BAJO_SODIO',
            'low sodium' => 'BAJO_SODIO',
            'bajo en sodio' => 'BAJO_SODIO',
            
            // Alto en Proteínas
            'alto en proteinas' => 'ALTO_EN_PROTEINAS',
            'alto en proteínas' => 'ALTO_EN_PROTEINAS',
            'high protein' => 'ALTO_EN_PROTEINAS',
            'rico en proteinas' => 'ALTO_EN_PROTEINAS',
            
            // Light / Bajo en Calorías
            'light' => 'LIGHT',
            'bajo en calorias' => 'LIGHT',
            'bajo en calorías' => 'LIGHT',
            'low calorie' => 'LIGHT',
            'low cal' => 'LIGHT',
            
            // Orgánico
            'organico' => 'ORGANICO',
            'orgánico' => 'ORGANICO',
            'organic' => 'ORGANICO',
            'bio' => 'ORGANICO',
            
            // Raw / Crudivegano
            'raw' => 'RAW',
            'crudivegano' => 'RAW',
            'crudo' => 'RAW',
        ];
        
        // Buscar coincidencia exacta
        if (isset($tagMap[$tagLower])) {
            return $tagMap[$tagLower];
        }
        
        // Buscar coincidencia parcial (contiene)
        foreach ($tagMap as $variation => $standard) {
            if (strpos($tagLower, $variation) !== false || strpos($variation, $tagLower) !== false) {
                return $standard;
            }
        }
        
        // Si no coincide con ningún tag estándar, retornar null (se filtrará)
        return null;
    }

    /**
     * Normaliza un array de tags a la lista estándar
     */
    protected function normalizeTags(array $tags): array
    {
        $normalized = [];
        foreach ($tags as $tag) {
            $normalizedTag = $this->normalizeTag($tag);
            if ($normalizedTag !== null && !in_array($normalizedTag, $normalized)) {
                $normalized[] = $normalizedTag;
            }
        }
        return $normalized;
    }

    /**
     * Valida y limpia un precio, detectando valores sospechosos
     */
    protected function validatePrice($precio): string
    {
        if (empty($precio) || $precio === 'Precio no especificado') {
            return 'Precio no especificado';
        }

        // Remover espacios extra
        $precio = trim($precio);
        
        // Detectar precios sospechosos comunes que OpenAI inventa
        $suspiciousPrices = ['$8000', '$9000', '$5900', '$30.00', '$30', '$3.50', '$800', '$900'];
        if (in_array($precio, $suspiciousPrices)) {
            Log::warning('Precio sospechoso detectado (posiblemente inventado): ' . $precio);
            return 'Precio no especificado';
        }

        return $precio;
    }

    /**
     * Combina múltiples JSONs de menú en uno solo (SIMPLIFICADO)
     * Solo une las categorías y platos de cada sección, sin lógica compleja
     */
    protected function combineMenuJsons(array $jsonStrings): string
    {
        // Si solo hay un JSON, retornarlo directamente
        if (count($jsonStrings) === 1) {
            return $jsonStrings[0];
        }
        
        Log::info('🔗 UNIENDO JSONs DE SECCIONES (método simplificado)', [
            'total_secciones' => count($jsonStrings)
        ]);
        
        $allCategorias = []; // [nombre_categoria_lower => categoria_completa]
        $categoriaOrder = []; // [nombre_categoria_lower => primera_seccion] para mantener orden
        
        // Procesar cada sección en orden
        foreach ($jsonStrings as $sectionIndex => $jsonString) {
            if (empty($jsonString)) continue;
            
            try {
                $data = json_decode($jsonString, true);
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                    Log::warning('JSON inválido en sección ' . ($sectionIndex + 1) . ': ' . json_last_error_msg());
                    continue;
                }
                
                // Normalizar estructura si es necesario
                $data = $this->normalizeMenuJsonStructure($data);
                
                if (!isset($data['categorias']) || !is_array($data['categorias'])) {
                    Log::warning('Sección ' . ($sectionIndex + 1) . ' sin categorías válidas');
                    continue;
                }
                
                // Procesar cada categoría de esta sección
                foreach ($data['categorias'] as $categoria) {
                    if (!is_array($categoria) || empty($categoria['nombre'])) continue;
                    
                    $nombreCategoria = trim($categoria['nombre']);
                    $nombreCategoriaLower = strtolower($nombreCategoria);
                    
                    // Si es la primera vez que vemos esta categoría, crearla
                    if (!isset($allCategorias[$nombreCategoriaLower])) {
                        $allCategorias[$nombreCategoriaLower] = [
                            'nombre' => $nombreCategoria,
                            'platos' => []
                        ];
                        $categoriaOrder[$nombreCategoriaLower] = $sectionIndex;
                        Log::info('✅ Nueva categoría encontrada', [
                            'categoria' => $nombreCategoria,
                            'seccion' => $sectionIndex + 1
                        ]);
                    }
                    
                    // Agregar platos de esta categoría (si existen)
                    if (isset($categoria['platos']) && is_array($categoria['platos'])) {
                        foreach ($categoria['platos'] as $plato) {
                            if (!is_array($plato) || empty($plato['nombre'])) continue;
                            
                            $nombrePlato = trim($plato['nombre']);
                            
                            // Verificar si el plato ya existe (evitar duplicados exactos)
                            $platoExiste = false;
                            foreach ($allCategorias[$nombreCategoriaLower]['platos'] as $platoExistente) {
                                if (strtolower(trim($platoExistente['nombre'] ?? '')) === strtolower($nombrePlato)) {
                                    $platoExiste = true;
                                    break;
                                }
                            }
                            
                            if (!$platoExiste) {
                                // Normalizar tags si existen
                                $tags = isset($plato['tags']) && is_array($plato['tags']) 
                                    ? $this->normalizeTags($plato['tags']) 
                                    : [];
                                
                                // Validar precio
                                $precio = $this->validatePrice($plato['precio'] ?? '');
                                
                                $allCategorias[$nombreCategoriaLower]['platos'][] = [
                                    'nombre' => $nombrePlato,
                                    'precio' => $precio,
                                    'descripcion' => trim($plato['descripcion'] ?? ''),
                                    'imagen' => $plato['imagen'] ?? '',
                                    'tags' => $tags
                                ];
                            }
                        }
                    }
                }
                
                Log::info('Sección ' . ($sectionIndex + 1) . ' procesada', [
                    'categorias_en_seccion' => count($data['categorias']),
                    'total_categorias_acumuladas' => count($allCategorias)
                ]);
                
            } catch (\Exception $e) {
                Log::error('Error procesando sección ' . ($sectionIndex + 1) . ' en combineMenuJsons: ' . $e->getMessage());
            }
        }
        
        // Ordenar categorías por orden de aparición (primera sección donde aparecieron)
        uksort($allCategorias, function($a, $b) use ($categoriaOrder) {
            $orderA = $categoriaOrder[$a] ?? 999;
            $orderB = $categoriaOrder[$b] ?? 999;
            if ($orderA !== $orderB) {
                return $orderA <=> $orderB;
            }
            // Si aparecieron en la misma sección, mantener orden alfabético
            return strcmp($a, $b);
        });
        
        // Construir JSON final
        $finalJson = [
            'restaurante' => [
                'nombre' => '',
                'descripcion' => ''
            ],
            'categorias' => array_values($allCategorias)
        ];
        
        $result = json_encode($finalJson, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        Log::info('✅ JSONs unidos exitosamente', [
            'total_categorias' => count($finalJson['categorias']),
            'total_platos' => array_sum(array_map(function($cat) {
                return count($cat['platos'] ?? []);
            }, $finalJson['categorias']))
        ]);
        
        return $result;
    }
    
    /**
     * MÉTODO ANTIGUO - NO USAR (mantenido por referencia)
     */
    protected function combineMenuJsons_OLD(array $jsonStrings): string
    {
        $restaurante = ['nombre' => '', 'descripcion' => ''];
        $seenPlatos = []; // Para detectar platos duplicados
        $platosConOrden = []; // Para preservar el orden de los platos: [categoria => [platos con orden]]
        $categoriasConOrden = []; // Array temporal: [timestamp => categoria_completa]
        
        // Primero, recopilar todas las categorías con su orden
        foreach ($jsonStrings as $index => $jsonString) {
            try {
                if (empty($jsonString)) {
                    Log::warning('JSON vacío en sección ' . ($index + 1));
                    continue;
                }

                $data = json_decode($jsonString, true);
                if (!$data || !is_array($data)) {
                    Log::warning('JSON inválido en sección ' . ($index + 1) . ': ' . substr($jsonString, 0, 100));
                    continue;
                }

                // Combinar información del restaurante (solo de la primera sección si está vacío)
                if (isset($data['restaurante']) && is_array($data['restaurante']) && !empty($data['restaurante']['nombre']) && empty($restaurante['nombre'])) {
                    $restaurante = $data['restaurante'];
                }

                // Combinar categorías preservando el orden
                if (isset($data['categorias']) && is_array($data['categorias'])) {
                    // Log del orden en que OpenAI devolvió las categorías en esta sección
                    $categoriasEnSeccion = array_map(function($cat) {
                        return $cat['nombre'] ?? '';
                    }, $data['categorias']);
                    Log::info('Categorías devueltas por OpenAI en sección ' . ($index + 1), [
                        'seccion_index' => $index,
                        'categorias_orden' => $categoriasEnSeccion
                    ]);
                    
                    foreach ($data['categorias'] as $categoriaIndex => $categoria) {
                        if (!is_array($categoria)) continue;
                        
                        $nombreCategoria = trim($categoria['nombre'] ?? '');
                        if (empty($nombreCategoria)) continue;

                        $nombreCategoriaLower = strtolower($nombreCategoria);
                        
                        // Calcular timestamp para esta categoría (orden absoluto)
                        // IMPORTANTE: $index es el índice de la sección (0, 1, 2...)
                        // $categoriaIndex es el índice dentro del array de categorías devuelto por OpenAI
                        $timestamp = $index * 10000 + $categoriaIndex; // Multiplicar por 10000 para dejar espacio para platos

                        // Buscar si la categoría ya existe en categoriasConOrden
                        // IMPORTANTE: Solo combinar si es la MISMA categoría en la MISMA posición jerárquica
                        // No combinar si una es subcategoría de otra
                        $categoriaExistenteKey = null;
                        foreach ($categoriasConOrden as $key => $catData) {
                            $nombreExistente = strtolower(trim($catData['categoria']['nombre'] ?? ''));
                            
                            // Comparación exacta (mismo nombre)
                            if ($nombreExistente === $nombreCategoriaLower) {
                                $categoriaExistenteKey = $key;
                                break;
                            }
                            
                            // Verificar si una podría ser subcategoría de la otra
                            // Si el nombre contiene el otro nombre + palabras de subcategoría, no combinar
                            $palabrasSubcategoria = ['clásica', 'clasica', 'especial', 'veggie', 'veggies', 'tradicional', 'gourmet', 'premium', 'clásicas', 'especiales'];
                            
                            // Caso 1: La categoría actual podría ser subcategoría de una existente
                            // Ej: existente="Hamburguesas", actual="Hamburguesas Clásicas"
                            foreach ($palabrasSubcategoria as $palabra) {
                                if (strpos($nombreCategoriaLower, $palabra) !== false && 
                                    strpos($nombreCategoriaLower, $nombreExistente) === 0) {
                                    // Es una subcategoría, no combinar
                                    Log::info('Categoría detectada como subcategoría, no combinando', [
                                        'categoria_padre' => $catData['categoria']['nombre'],
                                        'subcategoria' => $nombreCategoria,
                                        'seccion' => $index + 1
                                    ]);
                                    continue 2; // Continuar con la siguiente categoría existente
                                }
                            }
                            
                            // Caso 2: La categoría existente podría ser subcategoría de la actual
                            // Ej: existente="Hamburguesas Clásicas", actual="Hamburguesas"
                            foreach ($palabrasSubcategoria as $palabra) {
                                if (strpos($nombreExistente, $palabra) !== false && 
                                    strpos($nombreExistente, $nombreCategoriaLower) === 0) {
                                    // La existente es subcategoría, no combinar
                                    Log::info('Categoría existente es subcategoría, no combinando', [
                                        'categoria_actual' => $nombreCategoria,
                                        'subcategoria_existente' => $catData['categoria']['nombre'],
                                        'seccion' => $index + 1
                                    ]);
                                    continue 2; // Continuar con la siguiente categoría existente
                                }
                            }
                        }

                        if ($categoriaExistenteKey !== null) {
                            // La categoría ya existe, solo agregar platos
                            Log::info('Categoría existente encontrada, agregando platos', [
                                'categoria' => $nombreCategoria,
                                'seccion' => $index + 1,
                                'categoria_existente_key' => $categoriaExistenteKey
                            ]);
                            
                            // Agregar platos a la categoría existente, validando duplicados y precios
                            if (isset($categoria['platos']) && is_array($categoria['platos'])) {
                                // Inicializar platosConOrden si no existe
                                if (!isset($platosConOrden[$nombreCategoriaLower])) {
                                    $platosConOrden[$nombreCategoriaLower] = [];
                                }
                                
                                $platosAgregados = 0;
                                foreach ($categoria['platos'] as $platoIndex => $plato) {
                                    if (!is_array($plato)) continue;
                                    
                                    $nombrePlato = trim($plato['nombre'] ?? '');
                                    if (empty($nombrePlato)) continue;
                                    
                                    $precioOriginal = $plato['precio'] ?? '';
                                    
                                    // Validar precio
                                    $precio = $this->validatePrice($precioOriginal);
                                    
                                    // Log detallado si el precio cambió
                                    if ($precio !== $precioOriginal && !empty($precioOriginal)) {
                                        Log::warning('⚠️ PRECIO MODIFICADO por validatePrice', [
                                            'categoria' => $nombreCategoria,
                                            'plato' => $nombrePlato,
                                            'precio_original' => $precioOriginal,
                                            'precio_validado' => $precio,
                                            'seccion' => $index + 1
                                        ]);
                                    }
                                    
                                    // Crear clave única para detectar duplicados
                                    $platoKey = $nombreCategoriaLower . '|' . strtolower($nombrePlato);
                                    
                                    // Si el plato ya existe, no agregarlo (evitar duplicados)
                                    if (isset($seenPlatos[$platoKey])) {
                                        Log::info('Plato duplicado detectado y omitido', [
                                            'categoria' => $nombreCategoria,
                                            'plato' => $nombrePlato,
                                            'seccion' => $index + 1,
                                            'plato_key' => $platoKey
                                        ]);
                                        continue;
                                    }
                                    
                                    $seenPlatos[$platoKey] = true;
                                    
                                    // Normalizar tags si existen
                                    $tags = isset($plato['tags']) && is_array($plato['tags']) 
                                        ? $this->normalizeTags($plato['tags']) 
                                        : [];
                                    
                                    // Guardar plato con información de orden (sección y posición)
                                    $platoData = [
                                        'plato' => [
                                            'nombre' => $nombrePlato,
                                            'precio' => $precio,
                                            'descripcion' => trim($plato['descripcion'] ?? ''),
                                            'imagen' => $plato['imagen'] ?? '',
                                            'tags' => $tags // Tags normalizados
                                        ],
                                        'seccion' => $index, // Índice de la sección (0, 1, 2...)
                                        'posicion' => $platoIndex // Posición dentro de la categoría en esta sección
                                    ];
                                    
                                    $platosConOrden[$nombreCategoriaLower][] = $platoData;
                                    
                                    // Log detallado del plato agregado
                                    Log::info('✅ Plato agregado a categoría existente', [
                                        'categoria' => $nombreCategoria,
                                        'plato' => $nombrePlato,
                                        'precio_original' => $precioOriginal,
                                        'precio_final' => $precio,
                                        'seccion' => $index + 1,
                                        'posicion' => $platoIndex,
                                        'plato_key' => $platoKey
                                    ]);
                                    
                                    $platosAgregados++;
                                }
                                
                                Log::info('Platos agregados a categoría existente', [
                                    'categoria' => $nombreCategoria,
                                    'seccion' => $index + 1,
                                    'platos_agregados' => $platosAgregados,
                                    'total_platos_en_categoria' => count($platosConOrden[$nombreCategoriaLower])
                                ]);
                            } else {
                                Log::warning('Categoría existente sin platos en esta sección', [
                                    'categoria' => $nombreCategoria,
                                    'seccion' => $index + 1
                                ]);
                            }
                        } else {
                            // Nueva categoría - agregar platos primero
                            if (isset($categoria['platos']) && is_array($categoria['platos'])) {
                                foreach ($categoria['platos'] as $platoIndex => $plato) {
                                    if (!is_array($plato)) continue;
                                    
                                    $nombrePlato = trim($plato['nombre'] ?? '');
                                    if (empty($nombrePlato)) continue;
                                    
                                    $platoKey = $nombreCategoriaLower . '|' . strtolower($nombrePlato);
                                    
                                    if (isset($seenPlatos[$platoKey])) {
                                        Log::info('Plato duplicado detectado y omitido', [
                                            'categoria' => $nombreCategoria,
                                            'plato' => $nombrePlato,
                                            'seccion' => $index + 1
                                        ]);
                                        continue;
                                    }
                                    
                                    $seenPlatos[$platoKey] = true;
                                    
                                    // Guardar plato con información de orden
                                    if (!isset($platosConOrden[$nombreCategoriaLower])) {
                                        $platosConOrden[$nombreCategoriaLower] = [];
                                    }
                                    // Normalizar tags si existen
                                    $tags = isset($plato['tags']) && is_array($plato['tags']) 
                                        ? $this->normalizeTags($plato['tags']) 
                                        : [];
                                    
                                    $platosConOrden[$nombreCategoriaLower][] = [
                                        'plato' => [
                                            'nombre' => $nombrePlato,
                                            'precio' => $this->validatePrice($plato['precio'] ?? ''),
                                            'descripcion' => trim($plato['descripcion'] ?? ''),
                                            'imagen' => $plato['imagen'] ?? '',
                                            'tags' => $tags // Tags normalizados
                                        ],
                                        'seccion' => $index,
                                        'posicion' => $platoIndex
                                    ];
                                }
                            }
                            
                            // Agregar categoría a categoriasConOrden con su timestamp
                            // IMPORTANTE: NO incluir los platos originales aquí, solo el nombre
                            // Los platos se agregarán desde platosConOrden más adelante
                            $categoriasConOrden[$timestamp] = [
                                'categoria' => [
                                    'nombre' => $categoria['nombre'] ?? '',
                                    'platos' => [] // Inicializar vacío - se llenará desde platosConOrden
                                ],
                                'nombre_lower' => $nombreCategoriaLower
                            ];
                            
                            Log::info('Categoría nueva registrada', [
                                'categoria' => $nombreCategoria,
                                'seccion' => $index,
                                'posicion_en_seccion' => $categoriaIndex,
                                'timestamp' => $timestamp
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error combinando JSON de sección ' . ($index + 1) . ': ' . $e->getMessage(), [
                    'json_preview' => substr($jsonString ?? '', 0, 200)
                ]);
            }
        }

        // Ordenar categorías por timestamp (orden absoluto de aparición)
        ksort($categoriasConOrden);
        
        // Construir array final de categorías en el orden correcto
        // IMPORTANTE: Detectar y prevenir mezcla de categorías padre-hijo
        $combinedCategorias = [];
        $categoriasProcesadas = []; // Para rastrear qué categorías ya se procesaron
        
        foreach ($categoriasConOrden as $timestamp => $catData) {
            $categoria = $catData['categoria'];
            $nombreCategoria = trim($categoria['nombre'] ?? '');
            $nombreCategoriaLower = strtolower($nombreCategoria);
            
            // Verificar si esta categoría podría ser una subcategoría de otra ya procesada
            $palabrasSubcategoria = ['clásica', 'clasica', 'especial', 'veggie', 'veggies', 'tradicional', 'gourmet', 'premium', 'clásicas', 'especiales'];
            $esSubcategoria = false;
            $categoriaPadre = null;
            
            foreach ($categoriasProcesadas as $catProcesada) {
                $nombreProcesada = strtolower(trim($catProcesada['nombre'] ?? ''));
                
                // Verificar si la categoría actual es subcategoría de una procesada
                // Ejemplo: procesada="Hamburguesas", actual="Hamburguesas Clásicas"
                foreach ($palabrasSubcategoria as $palabra) {
                    if (strpos($nombreCategoriaLower, $palabra) !== false && 
                        strpos($nombreCategoriaLower, $nombreProcesada) === 0) {
                        // Es una subcategoría
                        $esSubcategoria = true;
                        $categoriaPadre = $catProcesada['nombre'];
                        Log::warning('Categoría detectada como subcategoría en combinación final', [
                            'categoria_padre' => $catProcesada['nombre'],
                            'subcategoria' => $nombreCategoria,
                            'timestamp' => $timestamp,
                            'accion' => 'Se mantiene como categoría separada para preservar jerarquía'
                        ]);
                        break 2;
                    }
                }
            }
            
            // Agregar la categoría al array final
            // Si es subcategoría, se mantiene separada (no se combina con la padre)
            // Esto preserva la estructura jerárquica
            $combinedCategorias[] = $categoria;
            $categoriasProcesadas[] = $categoria;
        }
        
        Log::info('Categorías ordenadas por timestamp', [
            'total_categorias' => count($combinedCategorias),
            'orden' => array_map(function($cat) {
                return $cat['nombre'] ?? '';
            }, $combinedCategorias)
        ]);
        
        // Ordenar platos dentro de cada categoría según el orden de las secciones (de arriba hacia abajo)
        foreach ($combinedCategorias as &$categoria) {
            $categoriaNombreLower = strtolower(trim($categoria['nombre'] ?? ''));
            
            // SIEMPRE usar platosConOrden si existe, nunca los platos originales
            // Los platos originales pueden tener datos incorrectos o mezclados
            if (isset($platosConOrden[$categoriaNombreLower]) && !empty($platosConOrden[$categoriaNombreLower])) {
                // Ordenar platos por sección y luego por posición dentro de la sección
                usort($platosConOrden[$categoriaNombreLower], function($a, $b) {
                    // Primero por sección (orden de arriba hacia abajo)
                    if ($a['seccion'] !== $b['seccion']) {
                        return $a['seccion'] <=> $b['seccion'];
                    }
                    // Si están en la misma sección, por posición dentro de la sección
                    return $a['posicion'] <=> $b['posicion'];
                });
                
                // Reemplazar los platos con los ordenados
                $categoria['platos'] = array_map(function($item) {
                    return $item['plato'];
                }, $platosConOrden[$categoriaNombreLower]);
                
                Log::info('Platos asignados desde platosConOrden', [
                    'categoria' => $categoria['nombre'] ?? '',
                    'total_platos' => count($categoria['platos']),
                    'primeros_3_platos' => array_slice(array_map(function($p) {
                        return $p['nombre'] . ' - ' . $p['precio'];
                    }, $categoria['platos']), 0, 3)
                ]);
            } else {
                // Si no hay platos en platosConOrden, vaciar los platos originales
                // Esto evita usar datos incorrectos o mezclados
                Log::warning('⚠️ Categoría sin platos en platosConOrden - VACIANDO platos originales', [
                    'categoria' => $categoria['nombre'] ?? '',
                    'platos_originales_count' => count($categoria['platos'] ?? []),
                    'platos_originales_preview' => array_slice(array_map(function($p) {
                        return ($p['nombre'] ?? '') . ' - ' . ($p['precio'] ?? '');
                    }, $categoria['platos'] ?? []), 0, 3)
                ]);
                $categoria['platos'] = [];
            }
        }
        unset($categoria); // Liberar referencia

        Log::info('JSON combinado', [
            'total_categorias' => count($combinedCategorias),
            'total_platos' => array_sum(array_map(function($cat) {
                return isset($cat['platos']) && is_array($cat['platos']) ? count($cat['platos']) : 0;
            }, $combinedCategorias))
        ]);

        return json_encode([
            'restaurante' => $restaurante,
            'categorias' => $combinedCategorias
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    
    /**
     * Combina múltiples JSONs de menú en uno solo (SIMPLIFICADO)
     * Solo une las categorías y platos de cada sección, sin lógica compleja
     */
    protected function combineMenuJsons_SIMPLE(array $jsonStrings): string
    {
        // Si solo hay un JSON, retornarlo directamente
        if (count($jsonStrings) === 1) {
            return $jsonStrings[0];
        }
        
        Log::info('🔗 UNIENDO JSONs DE SECCIONES (método simplificado)', [
            'total_secciones' => count($jsonStrings)
        ]);
        
        $allCategorias = []; // [nombre_categoria_lower => categoria_completa]
        $categoriaOrder = []; // [nombre_categoria_lower => primera_seccion] para mantener orden
        
        // Procesar cada sección en orden
        foreach ($jsonStrings as $sectionIndex => $jsonString) {
            if (empty($jsonString)) continue;
            
            try {
                $data = json_decode($jsonString, true);
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                    Log::warning('JSON inválido en sección ' . ($sectionIndex + 1) . ': ' . json_last_error_msg());
                    continue;
                }
                
                // Normalizar estructura si es necesario
                $data = $this->normalizeMenuJsonStructure($data);
                
                if (!isset($data['categorias']) || !is_array($data['categorias'])) {
                    Log::warning('Sección ' . ($sectionIndex + 1) . ' sin categorías válidas');
                    continue;
                }
                
                // Procesar cada categoría de esta sección
                foreach ($data['categorias'] as $categoria) {
                    if (!is_array($categoria) || empty($categoria['nombre'])) continue;
                    
                    $nombreCategoria = trim($categoria['nombre']);
                    $nombreCategoriaLower = strtolower($nombreCategoria);
                    
                    // Si es la primera vez que vemos esta categoría, crearla
                    if (!isset($allCategorias[$nombreCategoriaLower])) {
                        $allCategorias[$nombreCategoriaLower] = [
                            'nombre' => $nombreCategoria,
                            'platos' => []
                        ];
                        $categoriaOrder[$nombreCategoriaLower] = $sectionIndex;
                        Log::info('✅ Nueva categoría encontrada', [
                            'categoria' => $nombreCategoria,
                            'seccion' => $sectionIndex + 1
                        ]);
                    }
                    
                    // Agregar platos de esta categoría (si existen)
                    if (isset($categoria['platos']) && is_array($categoria['platos'])) {
                        foreach ($categoria['platos'] as $plato) {
                            if (!is_array($plato) || empty($plato['nombre'])) continue;
                            
                            $nombrePlato = trim($plato['nombre']);
                            
                            // Verificar si el plato ya existe (evitar duplicados exactos)
                            $platoExiste = false;
                            foreach ($allCategorias[$nombreCategoriaLower]['platos'] as $platoExistente) {
                                if (strtolower(trim($platoExistente['nombre'] ?? '')) === strtolower($nombrePlato)) {
                                    $platoExiste = true;
                                    break;
                                }
                            }
                            
                            if (!$platoExiste) {
                                // Normalizar tags si existen
                                $tags = isset($plato['tags']) && is_array($plato['tags']) 
                                    ? $this->normalizeTags($plato['tags']) 
                                    : [];
                                
                                // Validar precio
                                $precio = $this->validatePrice($plato['precio'] ?? '');
                                
                                $allCategorias[$nombreCategoriaLower]['platos'][] = [
                                    'nombre' => $nombrePlato,
                                    'precio' => $precio,
                                    'descripcion' => trim($plato['descripcion'] ?? ''),
                                    'imagen' => $plato['imagen'] ?? '',
                                    'tags' => $tags
                                ];
                            }
                        }
                    }
                }
                
                Log::info('Sección ' . ($sectionIndex + 1) . ' procesada', [
                    'categorias_en_seccion' => count($data['categorias']),
                    'total_categorias_acumuladas' => count($allCategorias)
                ]);
                
            } catch (\Exception $e) {
                Log::error('Error procesando sección ' . ($sectionIndex + 1) . ' en combineMenuJsons: ' . $e->getMessage());
            }
        }
        
        // Ordenar categorías por orden de aparición (primera sección donde aparecieron)
        uksort($allCategorias, function($a, $b) use ($categoriaOrder) {
            $orderA = $categoriaOrder[$a] ?? 999;
            $orderB = $categoriaOrder[$b] ?? 999;
            if ($orderA !== $orderB) {
                return $orderA <=> $orderB;
            }
            // Si aparecieron en la misma sección, mantener orden alfabético
            return strcmp($a, $b);
        });
        
        // Construir JSON final
        $finalJson = [
            'restaurante' => [
                'nombre' => '',
                'descripcion' => ''
            ],
            'categorias' => array_values($allCategorias)
        ];
        
        $result = json_encode($finalJson, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        Log::info('✅ JSONs unidos exitosamente', [
            'total_categorias' => count($finalJson['categorias']),
            'total_platos' => array_sum(array_map(function($cat) {
                return count($cat['platos'] ?? []);
            }, $finalJson['categorias']))
        ]);
        
        return $result;
    }

    /**
     * Procesa cada página del PDF por separado y combina los resultados
     */
    protected function processPdfPagesSeparately($pdfPath, $pageCount): string
    {
        Log::info('Iniciando procesamiento página por página', [
            'total_páginas' => $pageCount,
            'archivo' => basename($pdfPath)
        ]);

        // Crear directorio temp si no existe
        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $jsonResults = [];
        $tempFiles = [];

        try {
            // Procesar cada página
            for ($page = 1; $page <= $pageCount; $page++) {
                Log::info('Procesando página ' . $page . ' de ' . $pageCount);
                
                // Convertir página a imagen
                $imagePath = storage_path('app/temp/pdf_page_' . $page . '_' . uniqid() . '.png');
                $tempFiles[] = $imagePath;
                
                if (!$this->convertPdfPageToImage($pdfPath, $page, $imagePath)) {
                    Log::warning('No se pudo convertir página ' . $page . ' a imagen');
                    continue;
                }

                // Procesar imagen con OpenAI
                $pageJson = $this->processImagePageWithOpenAI($imagePath, $page, $pageCount);
                if ($pageJson) {
                    // IMPORTANTE: Guardar en el índice correcto según el número de página (0-indexed)
                    // Esto preserva el orden incluso si las páginas terminan en orden diferente
                    $pageIndex = $page - 1; // Convertir página (1, 2, 3...) a índice (0, 1, 2...)
                    $jsonResults[$pageIndex] = $pageJson;
                }
            }

            // NO limpiar archivos temporales - dejarlos en temp para revisión
            // foreach ($tempFiles as $tempFile) {
            //     if (file_exists($tempFile)) {
            //         unlink($tempFile);
            //     }
            // }
            Log::info('Archivos temporales guardados en temp para revisión', [
                'archivos' => count($tempFiles),
                'ruta' => storage_path('app/temp')
            ]);

            // Combinar resultados
            if (empty($jsonResults)) {
                throw new \Exception('No se pudo procesar ninguna página del PDF');
            }

            // Ordenar por clave (índice) para asegurar orden correcto antes de combinar
            ksort($jsonResults);
            
            // Reindexar array para que combineMenuJsons reciba índices consecutivos (0, 1, 2...)
            $jsonResults = array_values($jsonResults);

            $combinedJson = $this->combineMenuJsons_SIMPLE($jsonResults);
            Log::info('PDF procesado exitosamente página por página', [
                'páginas_procesadas' => count($jsonResults),
                'total_páginas' => $pageCount
            ]);

            return $combinedJson;

        } catch (\Exception $e) {
            // NO limpiar archivos temporales - dejarlos en temp para revisión
            // foreach ($tempFiles as $tempFile) {
            //     if (file_exists($tempFile)) {
            //         @unlink($tempFile);
            //     }
            // }
            Log::info('Páginas de PDF guardadas en temp (incluso con error)', [
                'archivos' => count($tempFiles),
                'ruta' => storage_path('app/temp')
            ]);
            throw $e;
        }
    }

    /**
     * Procesa una imagen de página individual con OpenAI
     */
    protected function processImagePageWithOpenAI($imagePath, $pageNumber, $totalPages): ?string
    {
        try {
            $imageData = file_get_contents($imagePath);
            $base64Image = base64_encode($imageData);
            $imageInfo = getimagesize($imagePath);
            $mimeType = $imageInfo['mime'] ?? 'image/png';

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
                'Content-Type' => 'application/json',
            ])->timeout(300)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->openaiModel,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $this->buildExtractionPromptForPage($pageNumber, $totalPages)
                            ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => 'data:' . $mimeType . ';base64,' . $base64Image,
                                        'detail' => 'high'
                                    ]
                                ]
                        ]
                    ]
                ],
                'max_tokens' => 16384,
                'temperature' => 0
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? null;
                
                if ($content) {
                    // Limpiar markdown si existe
                    $cleanContent = $content;
                    if (strpos($cleanContent, '```json') !== false) {
                        $cleanContent = preg_replace('/```json\s*/', '', $cleanContent);
                        $cleanContent = preg_replace('/```\s*$/', '', $cleanContent);
                        $cleanContent = trim($cleanContent);
                    } elseif (strpos($cleanContent, '```') !== false) {
                        $cleanContent = preg_replace('/```\s*/', '', $cleanContent);
                        $cleanContent = trim($cleanContent);
                    }
                    
                    // Guardar respuesta de OpenAI en archivo de texto para revisión
                    $txtPath = storage_path('app/temp/openai_response_page_' . $pageNumber . '_' . uniqid() . '.txt');
                    if (!is_dir(storage_path('app/temp'))) {
                        mkdir(storage_path('app/temp'), 0755, true);
                    }
                    file_put_contents($txtPath, "=== RESPUESTA DE OPENAI - PÁGINA {$pageNumber} de {$totalPages} ===\n\n");
                    file_put_contents($txtPath, $content . "\n\n", FILE_APPEND);
                    file_put_contents($txtPath, "=== CONTENIDO LIMPIO ===\n\n", FILE_APPEND);
                    file_put_contents($txtPath, $cleanContent . "\n", FILE_APPEND);
                    Log::info('Respuesta de OpenAI guardada en archivo', [
                        'pagina' => $pageNumber,
                        'archivo' => $txtPath
                    ]);
                    
                    // Normalizar cualquier estructura JSON al formato estándar
                    try {
                        $jsonTest = json_decode($cleanContent, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($jsonTest)) {
                            // Guardar JSON original antes de normalizar
                            $jsonOriginalPath = storage_path('app/temp/json_original_page_' . $pageNumber . '_' . uniqid() . '.txt');
                            if (!is_dir(storage_path('app/temp'))) {
                                mkdir(storage_path('app/temp'), 0755, true);
                            }
                            file_put_contents($jsonOriginalPath, "=== JSON ORIGINAL DE GPT - PÁGINA {$pageNumber} de {$totalPages} ===\n\n");
                            file_put_contents($jsonOriginalPath, json_encode($jsonTest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
                            file_put_contents($jsonOriginalPath, "\n=== ESTRUCTURA ORIGINAL ===\n", FILE_APPEND);
                            file_put_contents($jsonOriginalPath, "Keys disponibles: " . implode(', ', array_keys($jsonTest)) . "\n", FILE_APPEND);
                            Log::info('JSON original guardado antes de normalizar', [
                                'pagina' => $pageNumber,
                                'archivo' => $jsonOriginalPath
                            ]);
                            
                            $jsonTest = $this->normalizeMenuJsonStructure($jsonTest);
                            $cleanContent = json_encode($jsonTest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                            
                            Log::info('JSON de página normalizado exitosamente', [
                                'pagina' => $pageNumber,
                                'tiene_categorias' => isset($jsonTest['categorias'])
                            ]);
                        }
                    } catch (\Exception $normalizeError) {
                        Log::error('Error normalizando JSON de página ' . $pageNumber . ': ' . $normalizeError->getMessage());
                    }
                    
                    Log::info('Página ' . $pageNumber . ' procesada exitosamente');
                    return $cleanContent;
                }
            }

            Log::warning('Error procesando página ' . $pageNumber . ': ' . ($response->body() ?? 'Sin respuesta'));
            return null;

        } catch (\Exception $e) {
            Log::error('Error procesando página ' . $pageNumber . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Procesa PDF completo DIRECTAMENTE con OpenAI Vision (sin convertir a imágenes)
     */
    protected function processPdfDirectlyWithOpenAI($filePath)
    {
        if (empty($this->openaiApiKey) || $this->openaiApiKey === 'your_openai_api_key_here') {
            throw new \Exception('OpenAI API key no configurada');
        }

        try {
            // Verificar tamaño del archivo (OpenAI tiene límite de ~20MB)
            $fileSize = filesize($filePath);
            $maxSize = 20 * 1024 * 1024; // 20MB
            
            if ($fileSize > $maxSize) {
                Log::warning('PDF demasiado grande para OpenAI', [
                    'tamaño' => $fileSize,
                    'máximo' => $maxSize,
                    'archivo' => basename($filePath)
                ]);
                throw new \Exception('El PDF es demasiado grande. Por favor, reduce el tamaño a menos de 20MB o convierte solo la primera página a imagen.');
            }
            
            // Contar páginas del PDF
            $pageCount = $this->getPdfPageCount($filePath);
            Log::info('PDF detectado con ' . $pageCount . ' página(s)', [
                'archivo' => basename($filePath),
                'páginas' => $pageCount
            ]);
            
            // CONVERTIR PDF A IMÁGENES PRIMERO
            // OpenAI Vision solo acepta imágenes (PNG, JPEG), NO PDFs directamente
            Log::info('🎯 CONVIRTIENDO PDF A IMÁGENES para procesar con OpenAI Vision', [
                'total_páginas' => $pageCount,
                'archivo' => basename($filePath)
            ]);
            
            // Aumentar el tiempo de ejecución de PHP para permitir conversión y llamada a OpenAI
            set_time_limit(600); // 10 minutos
            
            // Crear directorio temporal si no existe
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            // Convertir cada página del PDF a imagen
            $imagePaths = [];
            $tempFilesToCleanup = [];
            
            for ($page = 1; $page <= $pageCount; $page++) {
                $imagePath = $tempDir . '/pdf_page_' . uniqid() . '_' . $page . '.png';
                $tempFilesToCleanup[] = $imagePath;
                
                Log::info('Convirtiendo página ' . $page . ' de ' . $pageCount . ' a imagen', [
                    'output_path' => $imagePath
                ]);
                
                if ($this->convertPdfPageToImage($filePath, $page, $imagePath)) {
                    if (file_exists($imagePath) && filesize($imagePath) > 0) {
                        $imagePaths[] = $imagePath;
                        Log::info('✅ Página ' . $page . ' convertida exitosamente', [
                            'tamaño_imagen' => filesize($imagePath)
                        ]);
                    } else {
                        Log::warning('⚠️ Página ' . $page . ' convertida pero archivo no válido');
                    }
                } else {
                    Log::error('❌ Error convirtiendo página ' . $page);
                }
            }
            
            if (empty($imagePaths)) {
                // Limpiar archivos temporales antes de fallar
                foreach ($tempFilesToCleanup as $tempFile) {
                    if (file_exists($tempFile)) {
                        @unlink($tempFile);
                    }
                }
                throw new \Exception('No se pudo convertir ninguna página del PDF a imagen. Asegúrate de tener ImageMagick o Ghostscript instalado.');
            }
            
            Log::info('✅ PDF convertido a ' . count($imagePaths) . ' imagen(es)', [
                'total_páginas' => $pageCount,
                'imágenes_generadas' => count($imagePaths)
            ]);
            
            // Preparar contenido para OpenAI: texto del prompt + todas las imágenes
            $contentArray = [
                [
                    'type' => 'text',
                    'text' => 'Eres un OCR (reconocimiento óptico de caracteres). Tu única tarea es TRANSCRIBIR LITERALMENTE el texto que ves en este PDF de menú.

⚠️ PROHIBICIONES ABSOLUTAS:
- NO uses tu conocimiento sobre menús de restaurantes
- NO inventes platos, categorías o precios
- NO asumas nombres de platos comunes
- NO completes información faltante
- NO traduzcas o modifiques el texto
- NO agregues categorías que no veas escritas en el PDF
- NO uses variaciones de nombres (ej: si dice "CAFÉ CON LECHE", NO lo cambies a "CAFÉ EXPRESSO")

✅ LO QUE DEBES HACER:
1. Lee el texto EXACTAMENTE como aparece en el PDF, carácter por carácter
2. Copia los nombres de platos EXACTAMENTE como están escritos (con mayúsculas, guiones, espacios, etc.)
3. **PRECIOS SON OBLIGATORIOS**: Busca CUIDADOSAMENTE cada precio junto a cada plato. Los precios pueden estar:
   - A la derecha del nombre del plato
   - Debajo del nombre del plato
   - En la misma línea pero separados
   - Con formato: $3500, $4.500, $4,500, 3500, etc.
4. **SI VES UN PRECIO, CÓPIALO EXACTAMENTE** - NO uses "Precio no especificado" a menos que REALMENTE no veas ningún número o símbolo de precio
5. Identifica las categorías SOLO si hay un título o encabezado visible que las nombre
6. Si hay descripción visible (texto adicional bajo el nombre del plato), cópiala EXACTAMENTE en el campo "descripcion"
7. Si NO hay descripción visible, deja el campo vacío: "" (NO escribas "Sin descripción" ni "Sin descripción")

🚨🚨🚨 PRECIOS - LEE ESTO CON MUCHA ATENCIÓN 🚨🚨🚨:
- Los precios son CRÍTICOS - debes leerlos CARACTER POR CARACTER, sin confundir dígitos
- Si ves "$6900", escribe "$6900" - NO "$7500", NO "$7900", NO "$9600"
- Si ves "$7500", escribe "$7500" - NO "$8000", NO "$8500"
- Si ves "$8500", escribe "$8500" - NO "$9000", NO "$9500"
- Si ves "$7900", escribe "$7900" - NO "$7000", NO "$9600"
- Si ves "$9900", escribe "$9900" - NO "$9600", NO "$10000"
- NO confundas el dígito 6 con 7, ni el 8 con 9
- NO redondees precios hacia arriba o abajo
- NO uses el precio de otro plato similar
- **BUSCA CUIDADOSAMENTE cada precio** - pueden estar a la derecha, debajo, o en la misma línea del nombre
- **SI VES CUALQUIER NÚMERO O SÍMBOLO $ JUNTO A UN PLATO, ESE ES EL PRECIO** - Cópialo EXACTAMENTE
- **SOLO usa "Precio no especificado" si REALMENTE no ves ningún número, símbolo $, o indicador de precio junto al plato**
- EJEMPLOS ESPECÍFICOS DE ERRORES COMUNES A EVITAR:
  ❌ Si ves "$6900" → NO escribas "$7500" (confundiste 6 con 7)
  ❌ Si ves "$7500" → NO escribas "$8000" (redondeaste hacia arriba)
  ❌ Si ves "$8500" → NO escribas "$9000" (confundiste 8 con 9)
  ❌ Si ves "$7900" → NO escribas "$7000" (confundiste 9 con 0 y 7 con 0)
  ❌ Si ves "$9900" → NO escribas "$9600" (confundiste 9 con 6)

EJEMPLO DE LO QUE NO DEBES HACER:
❌ Si en el PDF dice "CAFÉ CON LECHE: $4900", NO lo cambies a "CAFÉ EXPRESSO: $30.00" ni "$8000"
❌ Si NO ves "Cafés con leche deslactosada" en el PDF, NO la agregues
❌ Si ves "CAFÉ- CORTADO- LÁGRIMA", NO lo cambies a "CAFÉ EXPRESSO"
❌ Si ves "$3500", NO lo cambies a "$8000" o "$9000"

⚠️ DETECCIÓN DE TAGS/ETIQUETAS (MUY IMPORTANTE):
- Si ves CUALQUIER símbolo, icono, emoji, texto pequeño, badge o indicador visual junto al nombre del plato (antes, después, arriba, abajo, al lado), inclúyelo en el campo "tags" como TEXTO
- Si ves un emoji o icono, conviértelo a su significado en texto (ej: 🌱 → "VEGANO", ⚡ → "PICANTE", 🔥 → "PICANTE")
- Ejemplos de tags comunes: V, VG, VEGANO, VEGETARIANO, GF, GLUTEN FREE, SIN GLUTEN, SIN TACC, SIN LACTOSA, PICANTE, *, (V), [VEG], etc.
- NO limites a estos ejemplos: captura CUALQUIER indicador visual que veas y conviértelo a texto
- Si hay múltiples tags, inclúyelos todos en el array
- Si NO hay ningún tag visible, deja el array vacío: []
- Los tags deben ser en TEXTO, no emojis ni iconos

FORMATO JSON REQUERIDO:
{
  "restaurante": {
    "nombre": "",
    "descripcion": ""
  },
  "categorias": [
    {
      "nombre": "Nombre EXACTO de la categoría como aparece en el PDF",
      "platos": [
        {
          "nombre": "Nombre EXACTO del plato como aparece en el PDF",
          "precio": "Precio EXACTO como aparece en el PDF (ej: $3500, $4900)",
          "descripcion": "Descripción EXACTA si aparece escrita bajo el nombre del plato, o vacío si no hay",
          "tags": ["VEGANO", "SIN GLUTEN", "PICANTE"] // Array de tags/etiquetas en TEXTO (no emojis), o [] si no hay
        }
      ]
    }
  ]
}

IMPORTANTE: Transcribe SOLO lo que REALMENTE VES. Si no está escrito en el PDF, NO existe.

Responde SOLO con el JSON válido, sin texto adicional, sin explicaciones, sin comentarios, sin markdown.'
                ]
            ];
            
            // Agregar todas las imágenes convertidas al contenido
            foreach ($imagePaths as $imagePath) {
                $imageData = file_get_contents($imagePath);
                $base64Image = base64_encode($imageData);
                $contentArray[] = [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => 'data:image/png;base64,' . $base64Image,
                        'detail' => 'high'
                    ]
                ];
            }
            
            Log::info('Enviando ' . count($imagePaths) . ' imagen(es) a OpenAI Vision', [
                'total_páginas' => $pageCount,
                'tamaño_total_imágenes' => array_sum(array_map('filesize', $imagePaths))
            ]);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
                'Content-Type' => 'application/json',
            ])->timeout(600)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->openaiModel,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $contentArray
                    ]
                ],
                'max_tokens' => 16384,
                'temperature' => 0
            ]);
            
            // Guardar número de imágenes para el log
            $imagesProcessed = count($imagePaths);
            
            try {
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
                        Log::info('PDF procesado exitosamente con OpenAI Vision (convertido a imágenes)', [
                            'tamaño_respuesta' => strlen($content),
                            'finish_reason' => $finishReason,
                            'páginas_procesadas' => $imagesProcessed
                        ]);
                        return $content;
                    }
                }

                Log::error('Error en respuesta de OpenAI', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                throw new \Exception('Error al procesar con OpenAI: ' . $response->body());
                
            } finally {
                // Limpiar archivos temporales siempre, incluso si hay errores
                foreach ($tempFilesToCleanup as $tempFile) {
                    if (file_exists($tempFile)) {
                        @unlink($tempFile);
                        Log::debug('Archivo temporal eliminado: ' . $tempFile);
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('Error procesando PDF con OpenAI: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calcula el tamaño de una página A4 en píxeles basado en la resolución de la imagen
     */
    protected function calculateA4Height($imageWidth, $imageHeight): int
    {
        // Dimensiones A4 en mm: 210mm x 297mm
        $a4WidthMm = 210;
        $a4HeightMm = 297;
        
        // Calcular DPI aproximado basado en el ancho de la imagen
        // Asumimos que el ancho de la imagen corresponde aproximadamente al ancho de A4
        // Si la imagen es más ancha que A4, ajustamos
        $estimatedDpi = ($imageWidth / $a4WidthMm) * 25.4; // 25.4mm = 1 pulgada
        
        // Si el DPI calculado es muy bajo o muy alto, usar valores estándar
        if ($estimatedDpi < 100) {
            $estimatedDpi = 150; // DPI estándar para imágenes de baja resolución
        } elseif ($estimatedDpi > 400) {
            $estimatedDpi = 300; // DPI estándar para imágenes de alta resolución
        }
        
        // Calcular altura de A4 en píxeles
        $a4HeightPx = ($a4HeightMm / 25.4) * $estimatedDpi;
        
        // Redondear a entero
        return (int) round($a4HeightPx);
    }

    /**
     * Divide una imagen grande en secciones del tamaño de páginas A4
     */
    protected function divideImageIntoSections($imagePath, $maxHeight = null): array
    {
        try {
            if (!class_exists('Imagick')) {
                Log::warning('Imagick no disponible, no se puede dividir la imagen');
                return [];
            }

            $imagick = new \Imagick($imagePath);
            $width = $imagick->getImageWidth();
            $height = $imagick->getImageHeight();

            // Calcular altura de A4 basado en la resolución de la imagen
            $a4Height = $this->calculateA4Height($width, $height);
            
            // Si no se especifica maxHeight, usar el tamaño de A4
            if ($maxHeight === null) {
                $maxHeight = $a4Height;
            }

            // Si la imagen es más pequeña que una página A4, no dividir
            if ($height <= $a4Height) {
                $imagick->clear();
                $imagick->destroy();
                Log::info('Imagen no requiere división (menor o igual a A4)', [
                    'altura_imagen' => $height,
                    'altura_a4' => $a4Height
                ]);
                return [];
            }

            Log::info('Dividiendo imagen grande en secciones tipo A4', [
                'dimensiones_originales' => $width . 'x' . $height,
                'altura_a4_calculada' => $a4Height,
                'altura_máxima_por_sección' => $maxHeight,
                'secciones_estimadas' => ceil($height / $a4Height)
            ]);

            // Crear directorio temp si no existe
            if (!is_dir(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            $sections = [];
            $sectionNumber = 1;
            $currentY = 0;

            while ($currentY < $height) {
                // Usar altura de A4 para cada sección
                $sectionHeight = min($a4Height, $height - $currentY);
                
                // Crear nueva imagen para esta sección
                $sectionImage = clone $imagick;
                $sectionImage->cropImage($width, $sectionHeight, 0, $currentY);
                
                $sectionPath = storage_path('app/temp/image_section_' . $sectionNumber . '_' . uniqid() . '.png');
                $sectionImage->writeImage($sectionPath);
                $sectionImage->clear();
                $sectionImage->destroy();

                if (file_exists($sectionPath) && filesize($sectionPath) > 0) {
                    $sections[] = [
                        'path' => $sectionPath,
                        'number' => $sectionNumber,
                        'y_start' => $currentY,
                        'y_end' => $currentY + $sectionHeight,
                        'height' => $sectionHeight
                    ];
                    Log::info('Sección ' . $sectionNumber . ' creada', [
                        'path' => $sectionPath,
                        'y_start' => $currentY,
                        'y_end' => $currentY + $sectionHeight
                    ]);
                }

                $currentY += $sectionHeight;
                $sectionNumber++;
            }

            $imagick->clear();
            $imagick->destroy();

            return $sections;

        } catch (\Exception $e) {
            Log::error('Error dividiendo imagen: ' . $e->getMessage());
            return [];
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
            // Verificar tamaño del archivo (OpenAI tiene límite de ~20MB para imágenes)
            $fileSize = filesize($filePath);
            $maxSize = 20 * 1024 * 1024; // 20MB
            
            if ($fileSize > $maxSize) {
                Log::warning('Imagen demasiado grande para OpenAI', [
                    'tamaño' => $fileSize,
                    'máximo' => $maxSize,
                    'archivo' => basename($filePath)
                ]);
                throw new \Exception('La imagen es demasiado grande. Por favor, reduce el tamaño a menos de 20MB.');
            }
            
            // Determinar el tipo de imagen
            $imageInfo = getimagesize($filePath);
            
            if ($imageInfo === false) {
                throw new \Exception('No se pudo leer la información de la imagen');
            }
            
            $mimeType = $imageInfo['mime'];
            
            // PROCESAR IMAGEN COMPLETA DIRECTAMENTE (sin dividir en secciones)
            // Esto evita problemas de combinación y mejora la precisión
            Log::info('🎯 PROCESANDO IMAGEN COMPLETA DIRECTAMENTE (sin dividir en secciones)', [
                'dimensiones' => $imageInfo[0] . 'x' . $imageInfo[1],
                'tamaño_archivo_mb' => round($fileSize / 1024 / 1024, 2),
                'archivo' => basename($filePath),
                'modelo' => $this->openaiModel
            ]);
            
            // Procesar imagen completa directamente
            // Leer la imagen y convertir a base64
            $imageData = file_get_contents($filePath);
            $base64Image = base64_encode($imageData);
            
            // Calcular hash del archivo para verificación
            $fileHash = md5_file($filePath);
            $base64Hash = md5($base64Image);
            
            Log::info('Procesando imagen con OpenAI Vision', [
                'archivo_path' => $filePath,
                'archivo_hash' => $fileHash,
                'base64_hash' => $base64Hash,
                'tamaño_archivo' => $fileSize,
                'tamaño_base64' => strlen($base64Image),
                'tipo' => $mimeType,
                'dimensiones' => $imageInfo[0] . 'x' . $imageInfo[1]
            ]);
            
            // Aumentar el tiempo de ejecución de PHP para permitir que la llamada a OpenAI complete
            // OpenAI puede tardar más de 5 minutos con imágenes muy grandes
            set_time_limit(600); // 10 minutos
            
            // La imagen está lista para enviar a OpenAI
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
                'Content-Type' => 'application/json',
            ])->timeout(600)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->openaiModel,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Eres un OCR (reconocimiento óptico de caracteres). Tu única tarea es TRANSCRIBIR LITERALMENTE el texto que ves en esta imagen de menú.

⚠️ INSTRUCCIONES CRÍTICAS:

1. LEE LA IMAGEN de arriba hacia abajo, sección por sección
2. Si la imagen es muy larga y no puedes verla completa, procesa SOLO lo que REALMENTE VES
3. NO inventes NADA que no veas escrito explícitamente en la imagen
4. NO uses tu conocimiento previo sobre menús
5. NO agregues categorías que no existan en la imagen
6. NO cambies nombres de platos por variaciones comunes
7. Si no puedes ver toda la imagen, NO inventes contenido para "completar" el menú
8. Es MEJOR dejar el JSON incompleto que inventar contenido

⚠️ PROHIBICIONES ABSOLUTAS:
- NO inventes platos como "CAFÉ EXPRESSO AMERICANO" si no lo ves escrito
- NO agregues categorías como "Cafés con leche deslactosada" si no aparece en la imagen
- NO cambies precios (si dice $3500, NO lo cambies a $8000 o $9000 o $5900)
- NO uses nombres genéricos si en la imagen hay nombres específicos
- NO completes información que no veas
- NO uses patrones de otros platos para inventar nuevos platos
- NO asumas que si hay "CAFÉ CON LECHE" debe haber "CAFÉ EXPRESSO"
- NO inventes variaciones de platos que no veas escritas
- Si no ves un plato completo (nombre Y precio), NO lo agregues

🚨🚨🚨 PRECIOS - LEE ESTO CON MUCHA ATENCIÓN 🚨🚨🚨:
- Los precios son CRÍTICOS - debes leerlos CARACTER POR CARACTER, sin confundir dígitos
- Si ves "$6900", escribe "$6900" - NO "$7500", NO "$7900", NO "$9600"
- Si ves "$7500", escribe "$7500" - NO "$8000", NO "$8500"
- Si ves "$8500", escribe "$8500" - NO "$9000", NO "$9500"
- Si ves "$7900", escribe "$7900" - NO "$7000", NO "$9600"
- Si ves "$9900", escribe "$9900" - NO "$9600", NO "$10000"
- NO confundas el dígito 6 con 7, ni el 8 con 9
- NO redondees precios hacia arriba o abajo
- NO uses el precio de otro plato similar
- **BUSCA CUIDADOSAMENTE cada precio** - pueden estar a la derecha, debajo, o en la misma línea del nombre
- **SI VES CUALQUIER NÚMERO O SÍMBOLO $ JUNTO A UN PLATO, ESE ES EL PRECIO** - Cópialo EXACTAMENTE
- **SOLO usa "Precio no especificado" si REALMENTE no ves ningún número, símbolo $, o indicador de precio junto al plato**
- EJEMPLOS ESPECÍFICOS DE ERRORES COMUNES A EVITAR:
  ❌ Si ves "$6900" → NO escribas "$7500" (confundiste 6 con 7)
  ❌ Si ves "$7500" → NO escribas "$8000" (redondeaste hacia arriba)
  ❌ Si ves "$8500" → NO escribas "$9000" (confundiste 8 con 9)
  ❌ Si ves "$7900" → NO escribas "$7000" (confundiste 9 con 0 y 7 con 0)
  ❌ Si ves "$9900" → NO escribas "$9600" (confundiste 9 con 6)

🚨🚨🚨 NOMBRES DE PLATOS - MUY IMPORTANTE 🚨🚨🚨:
- Un nombre de plato puede ocupar MÚLTIPLES LÍNEAS en la imagen
- Si ves "SALSA DE CHOCO O" en una línea y "MERMELADA" en la siguiente, es UN SOLO plato: "SALSA DE CHOCO O MERMELADA"
- NO dividas nombres de platos que están en múltiples líneas en platos separados
- Si un nombre continúa en la siguiente línea, únelo en UN SOLO nombre
- EJEMPLOS ESPECÍFICOS:
  ✅ Si ves "SALSA DE CHOCO O" (línea 1) y "MERMELADA" (línea 2) → escribe: "SALSA DE CHOCO O MERMELADA" (UN SOLO plato)
  ❌ NO escribas dos platos: "SALSA DE CHOCO O" y "MERMELADA"
  ✅ Si ves "OPCIÓN 1: CAFÉ CON LECHE + 2 MEDIALUNAS + EXPR. DE NARANJA." → escribe el nombre COMPLETO en una sola línea
  ❌ NO dividas en múltiples platos

✅ LO QUE DEBES HACER:
1. Lee la imagen COMPLETA de arriba a abajo, de IZQUIERDA A DERECHA (lee TODAS las columnas)
2. Identifica TODAS las categorías que veas escritas (ej: "Cafetería", "Para acompañar", "Pastelería", "Waffles", "Postres")
3. Para cada categoría, lista TODOS los platos que veas escritos debajo de ella
4. Copia los nombres EXACTAMENTE como aparecen, UNIENDO texto que esté en múltiples líneas (ej: "CAFÉ- CORTADO- LÁGRIMA", "CAFÉ CON LECHE + MEDIALUNAS", "SALSA DE CHOCO O MERMELADA")
5. Copia los precios EXACTAMENTE como aparecen, CARACTER POR CARACTER, sin confundir dígitos (ej: "$6900", "$7500", "$8500", "$7900", "$9900")
6. Si hay subcategorías escritas (ej: "Cafés con otra onda"), inclúyelas como parte de la categoría principal

EJEMPLOS ESPECÍFICOS DE LO QUE DEBES EXTRAER:
✅ Si ves "CAFÉ- CORTADO- LÁGRIMA: $3500" → extrae exactamente eso
✅ Si ves "CAFÉ CON LECHE: $4900" → extrae exactamente eso
✅ Si ves "CAFÉ CON LECHE + MEDIALUNAS: $6900" → extrae exactamente eso
✅ Si ves "FLAN: $6900" → extrae exactamente "$6900" (NO "$7500")
✅ Si ves "FLAN CON CREMA O DULCE: $7500" → extrae exactamente "$7500" (NO "$8000")
✅ Si ves "FLAN MIXTO: $8500" → extrae exactamente "$8500" (NO "$9000")
✅ Si ves "ENSALADA DE FRUTA: $7900" → extrae exactamente "$7900" (NO "$7000")
✅ Si ves "ENSALADA DE FRUTA CON HELADO: $9900" → extrae exactamente "$9900" (NO "$9600")
✅ Si ves "SALSA DE CHOCO O" (línea 1) y "MERMELADA" (línea 2) con precio "$8200" → extrae: nombre="SALSA DE CHOCO O MERMELADA", precio="$8200" (UN SOLO plato)

EJEMPLOS DE LO QUE NO DEBES HACER:
❌ Si NO ves "CAFÉ EXPRESSO AMERICANO" escrito, NO lo agregues
❌ Si NO ves "Cafés con leche deslactosada" escrito, NO lo agregues
❌ Si ves "$3500" NO lo cambies a "$8000" o "$9000"
❌ Si ves "$6900" NO lo cambies a "$7500" o "$7900" (confundiste 6 con 7)
❌ Si ves "$7500" NO lo cambies a "$8000" (redondeaste hacia arriba)
❌ Si ves "$8500" NO lo cambies a "$9000" (confundiste 8 con 9)
❌ Si ves "$7900" NO lo cambies a "$7000" o "$9600"
❌ Si ves "$9900" NO lo cambies a "$9600"
❌ Si ves "CAFÉ- CORTADO- LÁGRIMA" NO lo cambies a "CAFÉ EXPRESSO"
❌ Si ves "SALSA DE CHOCO O" y "MERMELADA" en líneas separadas, NO los dividas en dos platos separados

⚠️ DETECCIÓN DE TAGS/ETIQUETAS (MUY IMPORTANTE):
- Si ves CUALQUIER símbolo, icono, emoji, texto pequeño, badge o indicador visual junto al nombre del plato (antes, después, arriba, abajo, al lado), inclúyelo en el campo "tags" como TEXTO
- Si ves un emoji o icono, conviértelo a su significado en texto (ej: 🌱 → "VEGANO", ⚡ → "PICANTE", 🔥 → "PICANTE")
- Ejemplos de tags comunes: V, VG, VEGANO, VEGETARIANO, GF, GLUTEN FREE, SIN GLUTEN, SIN TACC, SIN LACTOSA, PICANTE, *, (V), [VEG], etc.
- NO limites a estos ejemplos: captura CUALQUIER indicador visual que veas y conviértelo a texto
- Si hay múltiples tags, inclúyelos todos en el array
- Si NO hay ningún tag visible, deja el array vacío: []
- Los tags deben ser en TEXTO, no emojis ni iconos

FORMATO JSON REQUERIDO:
{
  "restaurante": {
    "nombre": "",
    "descripcion": ""
  },
  "categorias": [
    {
      "nombre": "Nombre EXACTO de la categoría como aparece en la imagen",
      "platos": [
        {
          "nombre": "Nombre EXACTO del plato como aparece en la imagen",
          "precio": "Precio EXACTO como aparece en la imagen",
          "descripcion": "Descripción EXACTA si aparece escrita bajo el nombre del plato, o vacío si no hay",
          "tags": ["VEGANO", "SIN GLUTEN", "PICANTE"] // Array de tags/etiquetas en TEXTO (no emojis), o [] si no hay
        }
      ]
    }
  ]
}

⚠️ REGLA FINAL ABSOLUTA: 
- Si no está escrito en la imagen, NO existe
- NO inventes, NO asumas, NO completes
- NO uses patrones para inventar contenido
- Si la respuesta se trunca, detente donde puedas - NO inventes para completar
- Es MEJOR un JSON incompleto que un JSON con contenido inventado

Responde SOLO con el JSON válido, sin texto adicional, sin explicaciones, sin comentarios, sin markdown.'
                            ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => 'data:' . $mimeType . ';base64,' . $base64Image,
                                        'detail' => 'high'
                                    ]
                                ]
                        ]
                    ]
                ],
                'max_tokens' => 16384,
                'temperature' => 0
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? null;
                
                // Verificar si la respuesta fue truncada
                $finishReason = $data['choices'][0]['finish_reason'] ?? null;
                if ($finishReason === 'length') {
                    Log::warning('⚠️ RESPUESTA TRUNCADA - El menú puede estar incompleto', [
                        'menu_id' => $this->menuId ?? null,
                        'tokens_usados' => $data['usage']['total_tokens'] ?? 'desconocido',
                        'tamaño_respuesta' => strlen($content ?? ''),
                        'mensaje' => 'La respuesta se cortó por límite de tokens. El JSON puede estar incompleto.'
                    ]);
                    // Si se truncó, validar que el JSON esté completo antes de retornar
                    if ($content) {
                        $jsonTest = json_decode($content, true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            Log::error('JSON inválido después de truncamiento - puede contener contenido inventado');
                            // Intentar extraer solo el JSON válido hasta donde se cortó
                            $lastBrace = strrpos($content, '}');
                            if ($lastBrace !== false) {
                                $content = substr($content, 0, $lastBrace + 1);
                                // Intentar cerrar el JSON correctamente
                                $openBraces = substr_count($content, '{') - substr_count($content, '}');
                                for ($i = 0; $i < $openBraces; $i++) {
                                    $content .= '}';
                                }
                                $openBrackets = substr_count($content, '[') - substr_count($content, ']');
                                for ($i = 0; $i < $openBrackets; $i++) {
                                    $content .= ']';
                                }
                            }
                        }
                    }
                }
                
                if ($content) {
                    // Limpiar el contenido de markdown si existe
                    $cleanContent = $content;
                    if (strpos($cleanContent, '```json') !== false) {
                        $cleanContent = preg_replace('/```json\s*/', '', $cleanContent);
                        $cleanContent = preg_replace('/```\s*$/', '', $cleanContent);
                        $cleanContent = trim($cleanContent);
                    } elseif (strpos($cleanContent, '```') !== false) {
                        $cleanContent = preg_replace('/```\s*/', '', $cleanContent);
                        $cleanContent = trim($cleanContent);
                    }
                    
                    // Normalizar cualquier estructura JSON al formato estándar
                    try {
                        $jsonTest = json_decode($cleanContent, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($jsonTest)) {
                            // Guardar JSON original antes de normalizar
                            $jsonOriginalPath = storage_path('app/temp/json_original_completo_' . uniqid() . '.txt');
                            if (!is_dir(storage_path('app/temp'))) {
                                mkdir(storage_path('app/temp'), 0755, true);
                            }
                            file_put_contents($jsonOriginalPath, "=== JSON ORIGINAL DE GPT - IMAGEN COMPLETA ===\n\n");
                            file_put_contents($jsonOriginalPath, json_encode($jsonTest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
                            file_put_contents($jsonOriginalPath, "\n=== ESTRUCTURA ORIGINAL ===\n", FILE_APPEND);
                            file_put_contents($jsonOriginalPath, "Keys disponibles: " . implode(', ', array_keys($jsonTest)) . "\n", FILE_APPEND);
                            
                            $jsonTest = $this->normalizeMenuJsonStructure($jsonTest);
                            $cleanContent = json_encode($jsonTest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                            
                            Log::info('JSON normalizado exitosamente (imagen completa)', [
                                'estructura_original' => array_keys($jsonTest),
                                'tiene_categorias' => isset($jsonTest['categorias']),
                                'archivo_original' => $jsonOriginalPath
                            ]);
                        }
                    } catch (\Exception $normalizeError) {
                        Log::error('Error normalizando JSON de imagen completa: ' . $normalizeError->getMessage());
                    }
                    
                    Log::info('Imagen procesada exitosamente con OpenAI Vision', [
                        'archivo_hash' => $fileHash ?? 'N/A',
                        'tamaño_respuesta' => strlen($content),
                        'tamaño_respuesta_limpia' => strlen($cleanContent),
                        'finish_reason' => $finishReason,
                        'preview_respuesta' => mb_substr($cleanContent, 0, 1000, 'UTF-8')
                    ]);
                    
                    return $cleanContent;
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
            ])->timeout(180)->post('https://api.openai.com/v1/chat/completions', [
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
                'temperature' => 0
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
