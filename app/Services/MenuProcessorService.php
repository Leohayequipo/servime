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
                // 4. Actualizar con la respuesta de GPT (ya procesada)
                $menu->update([
                    'contexto_gpt' => $gptResponse,
                    'procesado' => true
                ]);
                
                // Log detallado de la respuesta procesada
                $responsePreview = mb_substr($gptResponse, 0, 500, 'UTF-8');
                Log::info('✅ MENÚ PROCESADO EXITOSAMENTE', [
                    'menu_id' => $menu->id,
                    'menu_nombre' => $menu->nombre,
                    'archivo_original' => $menu->archivo_original,
                    'tamaño_respuesta' => strlen($gptResponse),
                    'formato' => $menu->formato,
                    'preview_respuesta' => $responsePreview . '...'
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
            Log::error('Error procesando PDF con extracción de texto: ' . $e->getMessage());
            
            // Fallback: procesar PDF completo directamente con OpenAI Vision (sin convertir a imagen)
            // Esto procesa TODAS las páginas del PDF, no solo la primera
            // OpenAI Vision puede procesar PDFs completos mejor que imágenes muy grandes
            try {
                Log::info('Procesando PDF completo directamente con OpenAI Vision (todas las páginas)...');
                $result = $this->processPdfAsImageWithOpenAI($filePath);
                
                Log::info('PDF procesado exitosamente con OpenAI Vision');
                return $result;
            } catch (\Exception $visionError) {
                Log::error('Error procesando PDF con OpenAI Vision: ' . $visionError->getMessage());
                
                throw new \Exception('No se pudo procesar el PDF. Error de extracción de texto: ' . $e->getMessage() . ' | Error procesando con OpenAI Vision: ' . $visionError->getMessage());
            }
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
                    'model' => $this->openaiModel,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => 'Eres SOLO un OCR (reconocimiento óptico de caracteres). NO eres un asistente inteligente. NO interpretes. NO completes. NO inventes.

Esta es la sección ' . $sectionNumber . ' de ' . count($sections) . ' de una imagen de menú.

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

⚠️ PRECIOS - LA REGLA MÁS IMPORTANTE (LEE ESTO 3 VECES):
- Copia el precio EXACTAMENTE como aparece escrito: "$3500", "$4900", "$6900"
- Si ves "$3500", escribe "$3500" - NUNCA "$8000", "$9000", "$5900", "$30.00", "$30", "$3.50", "$800", "$900"
- NO redondees, NO cambies, NO "corrijas", NO "mejores", NO "normalices" precios
- NO uses el precio de otro plato similar o de la misma categoría
- NO inventes precios basándote en patrones, lógica, o conocimiento previo
- NO asumas que si un café cuesta $3500, otro debe costar lo mismo
- Si el precio está borroso, cortado, o no se ve claramente, escribe: "Precio no especificado"
- Si NO ves el precio escrito, NO inventes uno - escribe "Precio no especificado"
- Si dudas sobre un precio, escribe "Precio no especificado" en lugar de inventar

⚠️ NOMBRES DE PLATOS:
- Copia EXACTAMENTE como aparece: "CAFÉ CON LECHE", "CAFÉ- CORTADO- LÁGRIMA", etc.
- NO cambies mayúsculas, guiones, espacios, o formato
- NO "corrijas" nombres (si dice "CAFÉ- CORTADO", NO lo cambies a "Café Cortado")
- Si NO ves un plato escrito, NO lo agregues aunque sea común en menús

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
6. ⚠️ ORDEN CRÍTICO - LA REGLA MÁS IMPORTANTE:
   - Las categorías y platos DEBEN aparecer en el JSON en el MISMO ORDEN EXACTO que aparecen en la imagen
   - Lee de ARRIBA HACIA ABAJO, línea por línea, y agrega cada categoría/plato al JSON en el mismo orden que lo lees
   - NO reordenes, NO agrupes por tipo, NO cambies el orden por NINGÚN motivo
   - Si lees "A" antes que "B" en la imagen, entonces "A" debe estar ANTES que "B" en el JSON
   - El orden del JSON debe ser un REFLEJO EXACTO del orden visual de la imagen

FORMATO JSON (OBLIGATORIO - RESPETA EL ORDEN):
{
  "restaurante": {"nombre": "", "descripcion": ""},
  "categorias": [
    {
      "nombre": "Nombre EXACTO como aparece",
      "platos": [
        {
          "nombre": "Nombre EXACTO como aparece",
          "precio": "Precio EXACTO como aparece (ej: $3500)",
          "descripcion": "Descripción EXACTA si aparece escrita bajo el nombre del plato, o vacío si no hay"
        }
      ]
    }
  ]
}

⚠️ ORDEN OBLIGATORIO - ESTO ES CRÍTICO (LEE 5 VECES):
- Las categorías en el array "categorias" DEBEN estar en el ORDEN EXACTO que aparecen en la imagen, leyendo de ARRIBA HACIA ABAJO, línea por línea
- Los platos dentro de cada categoría DEBEN estar en el ORDEN EXACTO que aparecen en la imagen, leyendo de ARRIBA HACIA ABAJO
- NO reordenes las categorías alfabéticamente - NUNCA
- NO agrupes categorías similares - NUNCA
- NO cambies el orden por ningún motivo - NUNCA
- NO uses lógica o conocimiento previo para ordenar - NUNCA
- Si en la imagen aparece primero "Cafetería" y luego "Para acompañar", ese DEBE ser el orden en el JSON, sin excepciones
- Si lees la imagen de arriba hacia abajo y encuentras "A" primero y luego "B", entonces en el JSON "A" debe estar ANTES de "B"
- El orden del JSON debe ser un REFLEJO EXACTO del orden visual de la imagen, sin modificaciones

⚠️ RECUERDA: Si no está escrito en esta sección, NO existe. NO inventes, NO asumas, NO completes, NO uses conocimiento previo.

📋 EJEMPLO DE ORDEN CORRECTO:
Si en la imagen lees de arriba hacia abajo:
1. "CAFETERÍA" (categoría)
2. "Café con leche - $50" (plato)
3. "Café cortado - $45" (plato)
4. "PARA ACOMPAÑAR" (categoría)
5. "Medialunas - $30" (plato)

Entonces el JSON DEBE ser:
{
  "categorias": [
    {"nombre": "CAFETERÍA", "platos": [{"nombre": "Café con leche", "precio": "$50"}, {"nombre": "Café cortado", "precio": "$45"}]},
    {"nombre": "PARA ACOMPAÑAR", "platos": [{"nombre": "Medialunas", "precio": "$30"}]}
  ]
}

NO hagas esto (INCORRECTO):
{
  "categorias": [
    {"nombre": "PARA ACOMPAÑAR", ...},  // ❌ INCORRECTO: Esta categoría aparece después en la imagen
    {"nombre": "CAFETERÍA", ...}         // ❌ INCORRECTO: Esta categoría aparece primero en la imagen
  ]
}

Responde SOLO con el JSON válido, sin texto adicional, sin markdown, sin explicaciones.'
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
                            // Validar estructura básica
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
                                Log::warning('Sección ' . $sectionNumber . ' procesada pero sin estructura válida');
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

        // Combinar resultados
        $combinedJson = $this->combineMenuJsons($jsonResults);
        Log::info('Imagen procesada exitosamente por secciones', [
            'secciones_procesadas' => count($jsonResults),
            'total_secciones' => count($sections)
        ]);

        return $combinedJson;
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
     * Combina múltiples JSONs de menú en uno solo, preservando orden y validando precios
     */
    protected function combineMenuJsons(array $jsonStrings): string
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
                        $categoriaExistenteKey = null;
                        foreach ($categoriasConOrden as $key => $catData) {
                            if (strtolower(trim($catData['categoria']['nombre'] ?? '')) === $nombreCategoriaLower) {
                                $categoriaExistenteKey = $key;
                                break;
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
                                    
                                    // Validar precio
                                    $precio = $this->validatePrice($plato['precio'] ?? '');
                                    
                                    // Crear clave única para detectar duplicados
                                    $platoKey = $nombreCategoriaLower . '|' . strtolower($nombrePlato);
                                    
                                    // Si el plato ya existe, no agregarlo (evitar duplicados)
                                    if (isset($seenPlatos[$platoKey])) {
                                        Log::info('Plato duplicado detectado y omitido', [
                                            'categoria' => $nombreCategoria,
                                            'plato' => $nombrePlato,
                                            'seccion' => $index + 1
                                        ]);
                                        continue;
                                    }
                                    
                                    $seenPlatos[$platoKey] = true;
                                    
                                    // Guardar plato con información de orden (sección y posición)
                                    $platosConOrden[$nombreCategoriaLower][] = [
                                        'plato' => [
                                            'nombre' => $nombrePlato,
                                            'precio' => $precio,
                                            'descripcion' => trim($plato['descripcion'] ?? ''),
                                            'imagen' => $plato['imagen'] ?? ''
                                        ],
                                        'seccion' => $index, // Índice de la sección (0, 1, 2...)
                                        'posicion' => $platoIndex // Posición dentro de la categoría en esta sección
                                    ];
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
                                    $platosConOrden[$nombreCategoriaLower][] = [
                                        'plato' => [
                                            'nombre' => $nombrePlato,
                                            'precio' => $this->validatePrice($plato['precio'] ?? ''),
                                            'descripcion' => trim($plato['descripcion'] ?? ''),
                                            'imagen' => $plato['imagen'] ?? ''
                                        ],
                                        'seccion' => $index,
                                        'posicion' => $platoIndex
                                    ];
                                }
                            }
                            
                            // Agregar categoría a categoriasConOrden con su timestamp
                            $categoriasConOrden[$timestamp] = [
                                'categoria' => $categoria,
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
        $combinedCategorias = [];
        foreach ($categoriasConOrden as $timestamp => $catData) {
            $combinedCategorias[] = $catData['categoria'];
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
            
            // Inicializar array de platos si no existe
            if (!isset($categoria['platos'])) {
                $categoria['platos'] = [];
            }
            
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
            } else {
                // Si no hay platos en platosConOrden, mantener los platos originales de la categoría
                // Esto puede pasar si la categoría fue agregada pero no tiene platos aún
                Log::info('Categoría sin platos en platosConOrden, manteniendo platos originales', [
                    'categoria' => $categoria['nombre'] ?? '',
                    'platos_originales' => count($categoria['platos'] ?? [])
                ]);
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

            $combinedJson = $this->combineMenuJsons($jsonResults);
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
                                'text' => 'Eres SOLO un OCR (reconocimiento óptico de caracteres). NO eres un asistente inteligente. NO interpretes. NO completes. NO inventes.

Esta es la página ' . $pageNumber . ' de ' . $totalPages . ' de un menú.

🚨🚨🚨 ATENCIÓN CRÍTICA - LEE ESTO PRIMERO 🚨🚨🚨:
Esta imagen tiene el ANCHO COMPLETO del menú original. Puede tener MÚLTIPLES COLUMNAS.
DEBES leer TODA la imagen de IZQUIERDA A DERECHA, columna por columna.
NO te enfoques solo en la columna izquierda. Escanea toda la imagen horizontalmente.
Si hay "Waffles" a la izquierda y "Postres" a la derecha, AMBOS deben aparecer en tu respuesta.
Lee cada línea COMPLETA de izquierda a derecha antes de pasar a la siguiente.
Si solo lees una columna, tu respuesta será INCORRECTA e INCOMPLETA.

🚫 REGLAS ABSOLUTAS - NO HAY EXCEPCIONES:

1. TRANSCRIBE SOLO lo que VES escrito. Si no está escrito, NO existe.
2. NO uses tu conocimiento sobre restaurantes, menús, o comida.
3. NO asumas nombres de platos comunes (ej: si NO ves "Expresso", NO lo agregues).
4. NO uses patrones o lógica (ej: si hay "Café con leche", NO significa que debe haber "Expresso").
5. NO completes información faltante (si falta el precio, escribe "Precio no especificado", NO inventes uno).
6. NO cambies nombres (si dice "CAFÉ- CORTADO- LÁGRIMA", NO lo cambies a "CAFÉ EXPRESSO").
7. NO agregues categorías que no veas escritas como título o encabezado.
8. Si un plato está cortado o no se ve completo (falta nombre O precio), NO lo agregues.

⚠️ PRECIOS - LA REGLA MÁS IMPORTANTE (LEE ESTO 3 VECES):
- Copia el precio EXACTAMENTE como aparece escrito: "$3500", "$4900", "$6900"
- Si ves "$3500", escribe "$3500" - NUNCA "$8000", "$9000", "$5900", "$30.00", "$30", "$3.50", "$800", "$900"
- NO redondees, NO cambies, NO "corrijas", NO "mejores", NO "normalices" precios
- NO uses el precio de otro plato similar o de la misma categoría
- NO inventes precios basándote en patrones, lógica, o conocimiento previo
- NO asumas que si un café cuesta $3500, otro debe costar lo mismo
- Si el precio está borroso, cortado, o no se ve claramente, escribe: "Precio no especificado"
- Si NO ves el precio escrito, NO inventes uno - escribe "Precio no especificado"
- Si dudas sobre un precio, escribe "Precio no especificado" en lugar de inventar

⚠️ NOMBRES DE PLATOS:
- Copia EXACTAMENTE como aparece: "CAFÉ CON LECHE", "CAFÉ- CORTADO- LÁGRIMA", etc.
- NO cambies mayúsculas, guiones, espacios, o formato
- NO "corrijas" nombres (si dice "CAFÉ- CORTADO", NO lo cambies a "Café Cortado")
- Si NO ves un plato escrito, NO lo agregues aunque sea común en menús

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
6. ⚠️ ORDEN CRÍTICO - LA REGLA MÁS IMPORTANTE:
   - Las categorías y platos DEBEN aparecer en el JSON en el MISMO ORDEN EXACTO que aparecen en la imagen
   - Lee de ARRIBA HACIA ABAJO, línea por línea, y agrega cada categoría/plato al JSON en el mismo orden que lo lees
   - NO reordenes, NO agrupes por tipo, NO cambies el orden por NINGÚN motivo
   - Si lees "A" antes que "B" en la imagen, entonces "A" debe estar ANTES que "B" en el JSON
   - El orden del JSON debe ser un REFLEJO EXACTO del orden visual de la imagen

FORMATO JSON (OBLIGATORIO - RESPETA EL ORDEN):
{
  "restaurante": {"nombre": "", "descripcion": ""},
  "categorias": [
    {
      "nombre": "Nombre EXACTO como aparece",
      "platos": [
        {
          "nombre": "Nombre EXACTO como aparece",
          "precio": "Precio EXACTO como aparece (ej: $3500)",
          "descripcion": "Descripción EXACTA si aparece escrita bajo el nombre del plato, o vacío si no hay"
        }
      ]
    }
  ]
}

⚠️ ORDEN OBLIGATORIO - ESTO ES CRÍTICO (LEE 5 VECES):
- Las categorías en el array "categorias" DEBEN estar en el ORDEN EXACTO que aparecen en la imagen, leyendo de ARRIBA HACIA ABAJO, línea por línea
- Los platos dentro de cada categoría DEBEN estar en el ORDEN EXACTO que aparecen en la imagen, leyendo de ARRIBA HACIA ABAJO
- NO reordenes las categorías alfabéticamente - NUNCA
- NO agrupes categorías similares - NUNCA
- NO cambies el orden por ningún motivo - NUNCA
- NO uses lógica o conocimiento previo para ordenar - NUNCA
- Si en la imagen aparece primero "Cafetería" y luego "Para acompañar", ese DEBE ser el orden en el JSON, sin excepciones
- Si lees la imagen de arriba hacia abajo y encuentras "A" primero y luego "B", entonces en el JSON "A" debe estar ANTES de "B"
- El orden del JSON debe ser un REFLEJO EXACTO del orden visual de la imagen, sin modificaciones

⚠️ RECUERDA: Si no está escrito en esta página, NO existe. NO inventes, NO asumas, NO completa, NO uses conocimiento previo.

Responde SOLO con el JSON válido, sin texto adicional, sin markdown, sin explicaciones.'
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
     * Procesa PDFs como imágenes con OpenAI Vision (método especial)
     * Si el PDF tiene múltiples páginas, procesa cada página por separado
     */
    protected function processPdfAsImageWithOpenAI($filePath)
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
            
            // Si tiene más de 1 página, procesar cada página por separado
            if ($pageCount > 1) {
                Log::info('Procesando PDF página por página (múltiples páginas detectadas)');
                return $this->processPdfPagesSeparately($filePath, $pageCount);
            }
            
            // Si tiene 1 página, procesar directamente
            // Leer el PDF y convertir a base64
            $pdfData = file_get_contents($filePath);
            $base64Pdf = base64_encode($pdfData);
            
            Log::info('Procesando PDF con OpenAI Vision (1 página)', [
                'tamaño_archivo' => $fileSize,
                'tamaño_base64' => strlen($base64Pdf)
            ]);
            
            // Aumentar el tiempo de ejecución de PHP para permitir que la llamada a OpenAI complete
            // OpenAI puede tardar más de 5 minutos con PDFs grandes o imágenes muy grandes
            set_time_limit(600); // 10 minutos
            
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
3. Copia los precios EXACTAMENTE como aparecen (con el símbolo $ y los números)
4. Identifica las categorías SOLO si hay un título o encabezado visible que las nombre
5. Si no ves un precio, escribe: "Precio no especificado"
6. Si hay descripción visible (texto adicional bajo el nombre del plato), cópiala EXACTAMENTE en el campo "descripcion"
7. Si NO hay descripción visible, deja el campo vacío: ""

⚠️ SOBRE PRECIOS (MUY IMPORTANTE):
- Los precios son CRÍTICOS - copia EXACTAMENTE como aparecen: "$3500", "$4900", "$6900"
- NO redondees, NO cambies, NO "corrijas" precios
- Si ves "$3500", escribe "$3500", NO "$8000" ni "$9000" ni "$30.00"
- NO uses tu conocimiento para "corregir" precios
- Si no ves un precio claramente, escribe: "Precio no especificado"

EJEMPLO DE LO QUE NO DEBES HACER:
❌ Si en el PDF dice "CAFÉ CON LECHE: $4900", NO lo cambies a "CAFÉ EXPRESSO: $30.00" ni "$8000"
❌ Si NO ves "Cafés con leche deslactosada" en el PDF, NO la agregues
❌ Si ves "CAFÉ- CORTADO- LÁGRIMA", NO lo cambies a "CAFÉ EXPRESSO"
❌ Si ves "$3500", NO lo cambies a "$8000" o "$9000"

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
          "descripcion": "Descripción EXACTA si aparece escrita bajo el nombre del plato, o vacío si no hay"
        }
      ]
    }
  ]
}

IMPORTANTE: Transcribe SOLO lo que REALMENTE VES. Si no está escrito en el PDF, NO existe.

Responde SOLO con el JSON válido, sin texto adicional, sin explicaciones, sin comentarios, sin markdown.'
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
                'max_tokens' => 16384,
                'temperature' => 0
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
            $imageHeight = $imageInfo[1];
            
            // Calcular altura de A4 para esta imagen
            $a4Height = $this->calculateA4Height($imageInfo[0], $imageHeight);
            
            // Si la imagen es más alta que una página A4, dividirla en secciones tipo A4
            if ($imageHeight > $a4Height) {
                Log::info('Imagen más alta que A4 detectada, dividiendo en secciones tipo A4', [
                    'altura' => $imageHeight,
                    'altura_a4' => $a4Height,
                    'archivo' => basename($filePath),
                    'modelo' => $this->openaiModel
                ]);
                
                $sections = $this->divideImageIntoSections($filePath);
                
                if (!empty($sections)) {
                    try {
                        $result = $this->processImageSectionsSeparately($sections, $mimeType);
                        // NO limpiar archivos temporales - dejarlos en temp para revisión
                        // foreach ($sections as $section) {
                        //     if (file_exists($section['path'])) {
                        //         @unlink($section['path']);
                        //     }
                        // }
                        Log::info('Secciones de imagen guardadas en temp para revisión', [
                            'secciones' => count($sections),
                            'ruta' => storage_path('app/temp')
                        ]);
                        return $result;
                    } catch (\Exception $e) {
                        // NO limpiar archivos temporales - dejarlos en temp para revisión
                        // foreach ($sections as $section) {
                        //     if (file_exists($section['path'])) {
                        //         @unlink($section['path']);
                        //     }
                        // }
                        Log::info('Secciones de imagen guardadas en temp (incluso con error)', [
                            'secciones' => count($sections),
                            'ruta' => storage_path('app/temp')
                        ]);
                        throw $e;
                    }
                }
            }
            
            // Si no se dividió, procesar normalmente
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

✅ LO QUE DEBES HACER:
1. Lee la imagen COMPLETA de arriba a abajo
2. Identifica TODAS las categorías que veas escritas (ej: "Cafetería", "Para acompañar", "Pastelería")
3. Para cada categoría, lista TODOS los platos que veas escritos debajo de ella
4. Copia los nombres EXACTAMENTE como aparecen (ej: "CAFÉ- CORTADO- LÁGRIMA", "CAFÉ CON LECHE + MEDIALUNAS")
5. Copia los precios EXACTAMENTE como aparecen (ej: "$3500", "$4900", "$6900")
6. Si hay subcategorías escritas (ej: "Cafés con otra onda"), inclúyelas como parte de la categoría principal

EJEMPLOS ESPECÍFICOS DE LO QUE DEBES EXTRAER:
✅ Si ves "CAFÉ- CORTADO- LÁGRIMA: $3500" → extrae exactamente eso
✅ Si ves "CAFÉ CON LECHE: $4900" → extrae exactamente eso
✅ Si ves "CAFÉ CON LECHE + MEDIALUNAS: $6900" → extrae exactamente eso
✅ Si ves "CAFÉ TOP: $8900" → extrae exactamente eso
✅ Si ves "MEDIALUNA: $1000" → extrae exactamente eso

EJEMPLOS DE LO QUE NO DEBES HACER:
❌ Si NO ves "CAFÉ EXPRESSO AMERICANO" escrito, NO lo agregues
❌ Si NO ves "Cafés con leche deslactosada" escrito, NO lo agregues
❌ Si ves "$3500" NO lo cambies a "$8000" o "$9000"
❌ Si ves "CAFÉ- CORTADO- LÁGRIMA" NO lo cambies a "CAFÉ EXPRESSO"

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
          "descripcion": "Descripción EXACTA si aparece escrita bajo el nombre del plato, o vacío si no hay"
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
