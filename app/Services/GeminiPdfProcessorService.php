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
        // El modelo sale de config/services.php (ahí está por qué es ése).
        $this->geminiModel = config('services.gemini.model') ?: env('GEMINI_MODEL', 'gemini-3.6-flash');
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

            $fileSize = filesize($pdfPath);
            if ($fileSize > self::MAX_BYTES_ARCHIVO) {
                throw new \Exception('El PDF es demasiado grande. Tamaño: ' . number_format($fileSize / 1024 / 1024, 2) . ' MB. Máximo permitido: ' . number_format(self::MAX_BYTES_ARCHIVO / 1024 / 1024, 2) . ' MB');
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
            $response = $this->callGeminiAPI([
                ['text' => $prompt],
                ['inline_data' => ['mime_type' => $mimeType, 'data' => $base64Pdf]],
            ]);

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

            $fileSize = filesize($imagePath);
            if ($fileSize > self::MAX_BYTES_ARCHIVO) {
                throw new \Exception('La imagen es demasiado grande. Tamaño: ' . number_format($fileSize / 1024 / 1024, 2) . ' MB. Máximo permitido: ' . number_format(self::MAX_BYTES_ARCHIVO / 1024 / 1024, 2) . ' MB');
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

            $response = $this->callGeminiAPI([
                ['text' => $prompt],
                ['inline_data' => ['mime_type' => $mimeType, 'data' => $base64Image]],
            ]);

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
     * Lado mayor y calidad con que se mandan las fotos de una carta en varias partes.
     *
     * 2400 px se lee igual de bien que el original (la letra de una carta impresa sigue
     * nítida) y deja cada foto en ~0,5-1 MB: diez entran holgadas en el límite de Gemini.
     */
    private const FOTO_LADO_MAYOR = 2400;

    private const FOTO_CALIDAD_JPEG = 85;

    /** Gemini acepta ~20 MB por pedido, contando el base64. Se deja margen para el prompt. */
    private const MAX_BYTES_PEDIDO = 19 * 1024 * 1024;

    /**
     * Lo más que puede pesar un PDF o una foto que va sola en el pedido (~14,25 MB).
     *
     * El base64 convierte cada 3 bytes en 4. Antes se comparaba el archivo crudo contra
     * 20 MB, y uno de 16 MB pasaba ese control y llegaba a Gemini con 21.
     */
    private const MAX_BYTES_ARCHIVO = self::MAX_BYTES_PEDIDO / 4 * 3;

    /**
     * Procesar una carta subida como VARIAS fotos, todas en un solo pedido.
     *
     * Van juntas y no una por una porque son páginas de la misma carta: leídas por separado,
     * una categoría que sigue en la página siguiente salía dos veces, y una foto repetida
     * duplicaba platos. Gemini las recibe en orden, cada una con su número de página.
     *
     * @param  array<int, string>  $rutas  Rutas absolutas, en el orden de la carta.
     */
    public function processImagesToMenu(array $rutas): array
    {
        try {
            if (empty($this->geminiApiKey) || $this->geminiApiKey === 'your_gemini_api_key_here') {
                throw new \Exception('Gemini API key no configurada. Verifica que GEMINI_API_KEY esté en tu archivo .env');
            }

            if (count($rutas) === 0) {
                throw new \Exception('No hay fotos para procesar');
            }

            set_time_limit(600); // 10 minutos

            $total = count($rutas);
            $partes = [
                ['text' => $this->buildPrompt("conjunto de {$total} fotos")],
                ['text' => $this->instruccionesVariasFotos($total)],
            ];
            $bytes = 0;

            foreach (array_values($rutas) as $i => $ruta) {
                if (! file_exists($ruta)) {
                    throw new \Exception('Foto no encontrada: '.basename($ruta));
                }

                [$mimeType, $base64] = $this->prepararFoto($ruta);
                $bytes += strlen($base64);

                $partes[] = ['text' => 'Página '.($i + 1)." de {$total}:"];
                $partes[] = ['inline_data' => ['mime_type' => $mimeType, 'data' => $base64]];
            }

            if ($bytes > self::MAX_BYTES_PEDIDO) {
                throw new \Exception('Las fotos juntas pesan demasiado para procesarlas en un solo pedido ('
                    .number_format($bytes / 1024 / 1024, 1).' MB). Probá con menos fotos.');
            }

            $response = $this->callGeminiAPI($partes);

            if ($response['success']) {
                return [
                    'success' => true,
                    'menu_data' => $response['menu_data'],
                    'extracted_text' => "{$total} fotos procesadas juntas con Gemini",
                    'provider' => 'gemini',
                ];
            }

            throw new \Exception($response['error'] ?? 'Error desconocido al procesar con Gemini');

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al procesar las fotos con Gemini: '.$e->getMessage(),
                'provider' => 'gemini',
            ];
        }
    }

    /** Lo que cambia cuando la carta llega en varias fotos. Va aparte del prompt de siempre. */
    private function instruccionesVariasFotos(int $total): string
    {
        return "IMPORTANTE — LA CARTA LLEGA EN {$total} FOTOS:
- Son las páginas de UNA MISMA carta, en el orden en que se adjuntan (Página 1, Página 2...).
- Devolvé UN SOLO JSON con los platos de TODAS las páginas, en ese orden.
- Si una categoría empieza en una página y sigue en la siguiente, es la MISMA categoría: no la repitas, seguí agregando sus platos.
- Si el mismo plato aparece en dos fotos (por ejemplo, una página fotografiada dos veces), incluilo UNA sola vez.
- Las reglas de precios de arriba valen para cada página por separado.";
    }

    /**
     * Deja la foto lista para mandar: [mime, base64].
     *
     * Se manda tal cual si ya es liviana, derecha y en un formato que Gemini lee. Si no, se
     * carga con `BrandThemeService::cargar()` —que corrige la orientación EXIF de las fotos
     * de celular y corta antes de quedarse sin memoria— y se achica a JPEG. GIF siempre se
     * convierte: Gemini no lo acepta.
     */
    private function prepararFoto(string $ruta): array
    {
        $info = @getimagesize($ruta);

        if ($info === false) {
            throw new \Exception('No se pudo leer la foto: '.basename($ruta));
        }

        $orientacion = 1;
        if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $orientacion = (int) ((@exif_read_data($ruta) ?: [])['Orientation'] ?? 1);
        }

        $aceptado = in_array($info['mime'], ['image/jpeg', 'image/png', 'image/webp'], true);
        $liviana = filesize($ruta) <= 2 * 1024 * 1024 && max($info[0], $info[1]) <= self::FOTO_LADO_MAYOR;

        if ($aceptado && $liviana && $orientacion === 1) {
            return [$info['mime'], base64_encode(file_get_contents($ruta))];
        }

        $imagenes = app(BrandThemeService::class);
        $img = $imagenes->cargar($ruta);

        $ancho = imagesx($img);
        $alto = imagesy($img);
        $lado = max($ancho, $alto);

        if ($lado > self::FOTO_LADO_MAYOR) {
            $nuevoAncho = (int) round($ancho * self::FOTO_LADO_MAYOR / $lado);
            $achicada = $imagenes->reescalar($img, $nuevoAncho);
            imagedestroy($img);
            $img = $achicada;
        }

        ob_start();
        imagejpeg($img, null, self::FOTO_CALIDAD_JPEG);
        $jpeg = ob_get_clean();
        imagedestroy($img);

        return ['image/jpeg', base64_encode($jpeg)];
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
     *
     * Recibe las partes del pedido tal cual las espera Gemini: textos (`['text' => ...]`)
     * y adjuntos (`['inline_data' => ['mime_type' => ..., 'data' => base64]]`), en orden.
     * Antes recibía un solo adjunto; una carta en varias fotos necesita mandar todas en el
     * mismo pedido para que salga UNA carta y no una por foto.
     */
    private function callGeminiAPI(array $partes): array
    {
        try {
            // Construir URL para la API de Gemini
            $url = "{$this->geminiBaseUrl}/models/{$this->geminiModel}:generateContent?key={$this->geminiApiKey}";

            $payload = [
                'contents' => [
                    [
                        'parts' => $partes,
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'topK' => 40,
                    'topP' => 0.95,
                    // El máximo del modelo (evita cortes en JSON grandes). Incluye lo que el
                    // modelo razona: una carta de 127 platos usa ~10.000 en total.
                    'maxOutputTokens' => 65536,
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

