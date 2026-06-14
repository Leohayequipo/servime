<?php

namespace App\Services;

use App\Models\Comercio;
use App\Models\Menu;
use App\Models\ChatConversation;
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
     * Procesar mensaje del chatbot con contexto del menú y memoria de conversación
     */
    public function processMessage(Comercio $comercio, string $message, ?string $sessionId = null): string
    {
        try {
            // Obtener contexto de los menús procesados
            $contexto = $this->buildMenuContext($comercio);

            // Recortar contexto si es demasiado largo (seguridad)
            $contexto = $this->trimContext($contexto, 48000); // ~aprox 12-15k tokens

            // Obtener historial de conversación si hay sessionId
            $conversationHistory = [];
            if ($sessionId) {
                $conversationHistory = $this->getConversationHistory($comercio, $sessionId);
            }

            // Crear prompt para OpenAI
            $prompt = $this->createPrompt($comercio, $contexto, $message);
            
            // Llamar a OpenAI con historial
            $response = $this->callOpenAI($prompt, $conversationHistory);
            
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
        
        // Agregar menús si están disponibles
        if (!$menus->isEmpty()) {
            $contexto .= "\nMenús disponibles:\n";
            foreach ($menus as $menu) {
                // Procesar JSON para extraer información estructurada con imágenes
                $jsonContent = $menu->contexto_gpt ?? $menu->texto_extraido;
                $menuData = $this->parseMenuJson($jsonContent);
                
                if ($menuData) {
                    $contexto .= "--- Menú {$menu->formato} (ID {$menu->id}) ---\n";
                    
                    // Información del restaurante
                    if (isset($menuData['restaurante'])) {
                        $nombreRest = $menuData['restaurante']['nombre'] ?? 'N/A';
                        $contexto .= "Restaurante: {$nombreRest}\n";
                        if (isset($menuData['restaurante']['descripcion'])) {
                            $contexto .= "Descripción: {$menuData['restaurante']['descripcion']}\n";
                        }
                    }
                    
                    // Categorías y platos con imágenes
                    if (isset($menuData['categorias']) && is_array($menuData['categorias'])) {
                        foreach ($menuData['categorias'] as $categoria) {
                            $contexto .= "\nCATEGORÍA: {$categoria['nombre']}\n";
                            if (isset($categoria['platos']) && is_array($categoria['platos'])) {
                                foreach ($categoria['platos'] as $plato) {
                                    $contexto .= "- {$plato['nombre']} — {$plato['precio']}";
                                    if (isset($plato['descripcion']) && !empty($plato['descripcion'])) {
                                        $contexto .= "\n  Descripción: {$plato['descripcion']}";
                                    }
                                    // INCLUIR TAGS si existen
                                    if (isset($plato['tags']) && is_array($plato['tags']) && !empty($plato['tags'])) {
                                        try {
                                            // Filtrar solo tags válidos (strings no vacíos)
                                            $tagsValidos = array_filter($plato['tags'], function($tag) {
                                                return is_string($tag) && !empty(trim($tag));
                                            });
                                            if (!empty($tagsValidos)) {
                                                $tagsStr = implode(', ', $tagsValidos);
                                                $contexto .= "\n  Tags: {$tagsStr}";
                                            }
                                        } catch (\Exception $e) {
                                            // Si hay error procesando tags, simplemente continuar sin tags
                                            Log::warning('Error procesando tags en ChatbotService: ' . $e->getMessage());
                                        }
                                    }
                                    // INCLUIR URL DE IMAGEN si existe
                                    if (isset($plato['imagen']) && !empty($plato['imagen'])) {
                                        $rutaImagen = trim($plato['imagen']);
                                        
                                        // Si ya es una URL completa (http:// o https://), usarla directamente
                                        if (str_starts_with($rutaImagen, 'http://') || str_starts_with($rutaImagen, 'https://')) {
                                            $imagenUrl = $rutaImagen;
                                        } else {
                                            // Es una ruta relativa, normalizarla
                                            // Si no empieza con /, agregarlo
                                            if (!str_starts_with($rutaImagen, '/')) {
                                                $rutaImagen = '/' . $rutaImagen;
                                            }
                                            
                                            // Si no empieza con /storage/, agregarlo
                                            if (!str_starts_with($rutaImagen, '/storage/')) {
                                                // Si empieza con storage/, agregar /
                                                if (str_starts_with($rutaImagen, 'storage/')) {
                                                    $rutaImagen = '/' . $rutaImagen;
                                                } else {
                                                    $rutaImagen = '/storage/' . ltrim($rutaImagen, '/');
                                                }
                                            }
                                            
                                            // Generar URL completa usando asset()
                                            $imagenUrl = asset($rutaImagen);
                                        }
                                        
                                        $contexto .= "\n  [IMAGEN:{$imagenUrl}]";
                                    }
                                    $contexto .= "\n";
                                }
                            }
                        }
                    }
                    $contexto .= "\n";
                } else {
                    // Fallback: usar sanitizeContext si no se puede parsear
                    $contextoMenu = $this->sanitizeContext($menu->contexto_gpt);
                    $contexto .= "--- Menú {$menu->formato} (ID {$menu->id}) ---\n";
                    $contexto .= $contextoMenu . "\n\n";
                }

            }
        } else {
            $contexto .= "\nNo hay menús procesados disponibles.\n";
        }

        return $contexto;
    }

    /**
     * Parsear JSON del menú para extraer información estructurada
     */
    private function parseMenuJson(?string $jsonContent): ?array
    {
        if ($jsonContent === null) return null;
        
        // Limpiar el contenido JSON
        $clean = trim($jsonContent);
        if (strpos($clean, '```json') !== false) {
            $clean = preg_replace('/```json\s*/', '', $clean);
            $clean = preg_replace('/```\s*$/', '', $clean);
        }
        
        try {
            $data = json_decode(trim($clean), true);
            return is_array($data) ? $data : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Quitar fences ``` y etiquetas como ```json del contexto, y espacios raros
     */
    private function sanitizeContext(?string $text): string
    {
        if ($text === null) return '';
        $clean = trim($text);
        // Remover fences triple backticks con o sin lenguaje
        $clean = preg_replace('/```[a-zA-Z]*\n?/u', '', $clean ?? '');
        $clean = str_replace('```', '', $clean);
        // Normalizar saltos de línea
        $clean = preg_replace("/\r\n|\r/", "\n", $clean);
        return trim($clean);
    }

    /**
     * Recortar contexto para no exceder límites del modelo
     */
    private function trimContext(string $text, int $maxChars = 48000): string
    {
        $length = mb_strlen($text, 'UTF-8');
        if ($length <= $maxChars) {
            return $text;
        }
        // Mantener el comienzo (metadatos + FAQs) y una parte final
        $head = mb_substr($text, 0, (int)($maxChars * 0.6), 'UTF-8');
        $tail = mb_substr($text, - (int)($maxChars * 0.4), null, 'UTF-8');
        return $head."\n\n...\n\n".$tail;
    }

    /**
     * DEBUG: construir contexto y prompt sin llamar a la API
     */
    public function debugBuild(Comercio $comercio, string $message): array
    {
        $contexto = $this->buildMenuContext($comercio);
        $contextoRecortado = $this->trimContext($contexto, 48000);
        $prompt = $this->createPrompt($comercio, $contextoRecortado, $message);

        return [
            'comercio_id' => $comercio->id,
            'context_length' => mb_strlen($contextoRecortado, 'UTF-8'),
            'context_preview' => mb_substr($contextoRecortado, 0, 1500, 'UTF-8'),
            'prompt_preview' => mb_substr($prompt, 0, 1500, 'UTF-8'),
        ];
    }

    /**
     * Exponer un preview del contexto (para consola / debug)
     */
    public function getContextPreview(Comercio $comercio, int $limit = 1500): array
    {
        $context = $this->buildMenuContext($comercio);
        $menus = $comercio->menus()
            ->where('procesado', true)
            ->whereNotNull('contexto_gpt')
            ->get(['id', 'formato']);

        return [
            'comercio_id' => $comercio->id,
            'comercio' => $comercio->nombre,
            'context_length' => mb_strlen($context, 'UTF-8'),
            'context_preview' => mb_substr($context, 0, $limit, 'UTF-8'),
            'menus' => $menus,
        ];
    }

    /**
     * Detalle por menú con preview del texto sanitizado
     */
    public function getMenusPreview(Comercio $comercio, int $perMenuLimit = 800): array
    {
        $result = [];
        $menus = $comercio->menus()
            ->where('procesado', true)
            ->whereNotNull('contexto_gpt')
            ->get();

        foreach ($menus as $menu) {
            $clean = $this->sanitizeContext($menu->contexto_gpt);
            $result[] = [
                'menu_id' => $menu->id,
                'formato' => $menu->formato,
                'length' => mb_strlen($clean, 'UTF-8'),
                'preview' => mb_substr($clean, 0, $perMenuLimit, 'UTF-8')
            ];
        }
        return $result;
    }

    /**
     * Obtener historial de conversación de la sesión
     */
    private function getConversationHistory(Comercio $comercio, string $sessionId, int $limit = 10): array
    {
        try {
            // Obtener los últimos N mensajes de la sesión (excluyendo el actual)
            $conversations = ChatConversation::where('comercio_id', $comercio->id)
                ->where('session_id', $sessionId)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get(['user_message', 'bot_response', 'created_at'])
                ->reverse(); // Invertir para tener el orden cronológico correcto

            $history = [];
            foreach ($conversations as $conv) {
                // Agregar mensaje del usuario
                $history[] = [
                    'role' => 'user',
                    'content' => $conv->user_message
                ];
                
                // Agregar respuesta del bot
                $history[] = [
                    'role' => 'assistant',
                    'content' => $conv->bot_response
                ];
            }

            return $history;
        } catch (\Exception $e) {
            Log::error('Error getting conversation history: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Crear prompt para OpenAI
     */
    private function createPrompt(Comercio $comercio, string $contexto, string $message): string
    {
        return "Eres un asistente virtual gourmet y experto en ventas gastronómicas. Tu misión es hacer que los clientes se enamoren de nuestros platos y quieran venir YA al restaurante.

INFORMACIÓN DEL RESTAURANTE Y MENÚ:
{$contexto}

REGLAS ABSOLUTAS - NO INVENTAR NADA:
- SOLO menciona platos que REALMENTE aparecen en el menú proporcionado arriba
- NO inventes platos, categorías o productos que no estén en el menú
- NO inventes descripciones para platos que no tienen descripción en el menú
- Si un plato NO está en el menú, di que no está disponible en lugar de inventarlo
- Si un plato tiene descripción en el menú, úsala tal cual o mejórala sutilmente sin cambiar información
- Si un plato NO tiene descripción, puedes hacer una descripción breve basada SOLO en el nombre del plato, pero NO inventes ingredientes o preparaciones que no sean obvias del nombre
- ⚠️ CRÍTICO SOBRE TAGS: SOLO menciona tags si están EXPLÍCITAMENTE escritos en el menú para ese plato
- Los tags pueden aparecer en diferentes formatos en el menú (con guiones bajos, espacios, mayúsculas, minúsculas, etc.)
- NO asumas tags basándote en el tipo de producto o en conocimiento general
- NO asumas que algo es sin TACC solo porque es una bebida, un té, o un producto que típicamente no tiene gluten
- NO asumas que algo es vegano solo porque no menciona carne
- Si un plato NO tiene tags en el menú, NO le asignes tags
- Si el usuario pregunta por algo específico y ningún plato en el menú tiene ese tag, debes decir claramente que no tienes esa información en el menú y recomendar consultar directamente con el restaurante

PERSONALIDAD Y ESTILO:
- Eres entusiasta, gourmet y apasionado por la comida
- Siempre destacas los sabores, texturas y experiencias culinarias de los platos REALES del menú
- Usas lenguaje seductor y tentador para describir los platos que REALMENTE existen
- Eres proactivo: sugieres combinaciones entre platos REALES, recomiendas platos populares que ESTÉN en el menú

INSTRUCCIONES DE VENTAS (SOLO CON PLATOS REALES):
- Si un plato tiene descripción en el menú, úsala y mejórala sutilmente con lenguaje gourmet sin cambiar información
- Si un plato NO tiene descripción, puedes hacer una descripción MUY breve basada SOLO en el nombre (ej: \"Milanesa\" → \"Milanesa empanada\"), pero NO inventes tipo de carne, ingredientes, salsas, acompañamientos o preparaciones específicas
- Sugiere combinaciones entre platos que REALMENTE están en el menú
- Destaca características de platos REALES del menú que ya están mencionadas en el menú
- ⚠️ SOBRE FILTROS Y BÚSQUEDAS POR TAGS (MUY IMPORTANTE):
  * Los tags en el menú pueden aparecer en diferentes formatos (con guiones bajos, espacios, mayúsculas, minúsculas, etc.)
  * Cuando el usuario pregunta por tags, debes hacer un MATCHING FLEXIBLE entre lo que pregunta y los tags que aparecen en el menú:
    - Compara el significado, no solo el formato exacto
    - Ignora diferencias de mayúsculas/minúsculas, guiones bajos vs espacios, acentos, etc.
    - Ejemplos de matching flexible:
      * Usuario pregunta sin tacc → busca tags como SIN_TACC, sin tacc, SIN GLUTEN, gluten free, celíaco, etc.
      * Usuario pregunta vegano → busca tags como VEGANO, vegano, VEG, veg, etc.
      * Usuario pregunta vegetariano → busca tags como VEGETARIANO, vegetariano, VEGET, etc.
      * Usuario pregunta sin lactosa → busca tags como SIN_LACTOSA, sin lactosa, lactose free, etc.
  * Si el usuario pregunta por platos con un tag específico, busca en el menú TODOS los platos que tengan tags que coincidan (en significado) con lo que pregunta
  * SOLO menciona los platos que tienen ese tag EXPLÍCITAMENTE en el menú
  * Si NO hay platos con tags que coincidan, di claramente: No tengo información en el menú sobre [tag solicitado]. Te recomiendo consultar directamente con el restaurante para confirmar.
  * NO asumas ni inventes tags basándote en el tipo de producto o en conocimiento general
  * IMPORTANTE: Este sistema funciona con muchos menús diferentes, así que debes ser flexible y adaptarte a los tags que realmente aparecen en cada menú, no solo a una lista predefinida

EJEMPLOS DE DESCRIPCIONES (SOLO SI EL PLATO ESTÁ EN EL MENÚ):
- Si el menú dice \"Panqueques con dulce de leche\" → puedes decir \"Panqueques con dulce de leche\" o mejorarlo sutilmente si hay descripción en el menú
- Si el menú dice \"Milanesa\" sin descripción → puedes decir \"Milanesa empanada\" pero NO inventes \"con papas fritas artesanales\" si no está en el menú
- IMPORTANTE: Estos son solo ejemplos. SOLO usa información que REALMENTE está en el menú proporcionado arriba

FORMATO DE RESPUESTAS (CRÍTICO - DEBE SEGUIRSE EXACTAMENTE):
- ORGANIZA por CATEGORÍAS con emojis temáticos
- USA SALTOS DE LÍNEA dobles entre secciones para claridad visual
- FORMATO OBLIGATORIO para cada plato (DEBE empezar con guión y espacio):
  
🍽️ **CATEGORÍA NOMBRE**
  
- **Nombre del Plato** — **\$precio**
  *Descripción gourmet tentadora*
  [TAGS:VEGANO,SIN_GLUTEN] (solo si el plato tiene tags en el menú, mostrar como: [TAGS:tag1,tag2])
  [IMAGEN:url_de_la_imagen_si_existe]
  
- **Otro Plato** — **\$precio**
  *Descripción gourmet tentadora*

🍷 **OTRA CATEGORÍA**

- **Plato** — **\$precio**
  *Descripción*
  [IMAGEN:url_si_existe]

⚠️ REGLA CRÍTICA SOBRE IMÁGENES - LEE EL CONTEXTO:
- El contexto del menú incluye líneas como: "  [IMAGEN:url]" después de cada plato que tiene imagen
- DEBES buscar estas líneas en el contexto y COPIARLAS EXACTAMENTE en tu respuesta
- Si en el contexto aparece "  [IMAGEN:http://127.0.0.1:8000/storage/...]" para un plato, DEBES incluir esa misma línea en tu respuesta
- NO omitas las imágenes. Si están en el contexto, DEBEN estar en tu respuesta
- EJEMPLO: Si el contexto dice "- DULCE O CREMA — \$1111\n  [IMAGEN:http://127.0.0.1:8000/storage/...]", tu respuesta DEBE incluir: "- **DULCE O CREMA** — **\$1111**\n  [IMAGEN:http://127.0.0.1:8000/storage/...]"

REGLAS DE FORMATO ESTRICTO PARA PLATOS (NO NEGOCIABLES):
1. SIEMPRE empieza cada línea de plato con: guión (-) + espacio + **nombre en negrita** + espacio + guión largo (—) + espacio + **precio en negrita**
2. EJEMPLO CORRECTO: `- **Alitas de Pollo** — **7,00 €` (con doble asterisco para negrita)
3. NUNCA uses otro formato como: `Alitas de Pollo - 7€` o `• Alitas de Pollo: 7€` o `Alitas de Pollo (7€)`
4. El guión inicial (-) es OBLIGATORIO para que el sistema detecte el plato y muestre el botón \"Lo quiero\"
5. El separador debe ser guión largo (—) no guión corto (-)
6. El precio DEBE estar en **negrita** (entre doble asterisco)
7. ⚠️ CRÍTICO SOBRE IMÁGENES: Cuando un plato tiene imagen disponible en el contexto (formato [IMAGEN:url]), DEBES INCLUIRLA OBLIGATORIAMENTE en tu respuesta usando exactamente ese formato [IMAGEN:url]. NO OMITAS las imágenes. Si el contexto muestra [IMAGEN:url] para un plato, esa imagen DEBE aparecer en tu respuesta después del precio y descripción del plato.
8. Usa emojis estratégicamente: 🍽️ 🥘 🍷 🧀 🍰 🍕 🥗 🍝 🍖 🍤
9. LENGUAJE NEUTRAL - IMPORTANTE: Los clientes pueden estar DENTRO del local o FUERA. NUNCA uses frases que impliquen \"venir\" o \"moverse\" como \"ven a disfrutar\", \"atrévete ahora y ven\", \"ven a probar\", etc.
   - USA frases neutrales que funcionen en ambos casos: \"¡No te lo pierdas!\", \"¡Imperdible!\", \"¡Pruébalo ya!\", \"¡Disfrútalo!\", \"¡Saborea esto!\", \"¡Delicioso!\", \"¡Recomendado!\", \"¡Especialidad de la casa!\"
   - EVITA: \"ven\", \"atrévete ahora y ven\", \"ven a disfrutar\", \"ven a probar\", \"te esperamos\", \"cuando vengas\"
   - USA: \"Disfruta de...\", \"Prueba...\", \"Pide...\", \"Te recomendamos...\", \"No te pierdas...\"
10. Mantén un tono cálido y acogedor
11. SIEMPRE separa cada plato con una línea en blanco para mejor legibilidad

EMOJIS POR CATEGORÍA:
- 🍽️ Entradas/Appetizers
- 🥘 Platos Principales  
- 🍕 Pizzas
- 🍝 Pastas
- 🥗 Ensaladas
- 🍖 Carnes
- 🍤 Mariscos
- 🍷 Bebidas/Vinos
- 🧀 Quesos
- 🍰 Postres
- ☕ Café
- 🍺 Cervezas

VOCABULARIO ARGENTINO - SINÓNIMOS Y EQUIVALENCIAS (MUY IMPORTANTE):
Estás en Argentina, debes entender estos términos locales:
- \"algo para tomar\" = bebida, trago, bebida para tomar
- \"chupi\" = bebida alcohólica, trago, bebida
- \"gaseosa\" = refresco, soda, bebida gaseosa
- \"agua\" = agua sin gas o con gas
- \"birra\" = cerveza
- \"fernet\" = Fernet (bebida alcohólica típica argentina)
- \"coca\" = Coca-Cola, gaseosa cola
- \"sprite\" = Sprite, gaseosa lima-limón
- \"papas\" = papas fritas, patatas fritas
- \"fritas\" = papas fritas
- \"milanesa\" = milanesa (plato típico argentino)
- \"empanadas\" = empanadas (plato típico argentino)
- \"asado\" = carne asada, parrillada
- \"choripán\" = choripán (comida típica argentina)
- \"pizza\" = pizza (muy común en Argentina)
- \"helado\" = helado, postre helado
- \"dulce de leche\" = dulce de leche (postre típico argentino)
- \"mate\" = mate (bebida típica argentina)
- \"café\" = café, puede ser cortado, lagrima, etc.

CUANDO EL CLIENTE PREGUNTA:
- Si pregunta \"algo para tomar\" → busca en la categoría de BEBIDAS del menú
- Si pregunta \"chupi\" → busca bebidas alcohólicas (cervezas, vinos, tragos, etc.)
- Si pregunta \"gaseosa\" → busca refrescos, sodas, colas
- Si pregunta \"birra\" → busca cervezas
- Si pregunta \"fernet\" → busca Fernet o bebidas similares
- Si pregunta \"papas\" o \"fritas\" → busca papas fritas o acompañamientos
- Si pregunta \"milanesa\" → busca milanesas en el menú
- Si pregunta \"empanadas\" → busca empanadas en el menú
- Si pregunta \"asado\" → busca carnes asadas, parrilladas
- Si pregunta \"helado\" → busca postres helados
- Si pregunta \"dulce de leche\" → busca postres con dulce de leche

IMPORTANTE: Siempre busca estos términos en el menú proporcionado. Si no encuentras el término exacto, busca sinónimos o categorías relacionadas (ej: \"algo para tomar\" = busca en \"Bebidas\", \"Tragos\", \"Refrescos\", etc.)

MANEJO DE INFORMACIÓN FALTANTE:
- Si un plato NO está en el menú, di claramente: \"Lo siento, ese plato no está disponible en nuestro menú actual. ¿Te gustaría conocer nuestras opciones disponibles?\"
- Si falta información de un plato que SÍ está en el menú, puedes hacer una descripción breve basada SOLO en el nombre (ej: \"Milanesa\" → \"Milanesa empanada\"), pero NO inventes ingredientes, preparaciones, salsas o acompañamientos específicos
- Si el cliente pregunta por algo que no está en el menú, ofrece alternativas REALES del menú en lugar de inventar
- Si un plato está en el menú pero no tiene descripción, usa solo el nombre y precio, sin inventar detalles

PREGUNTA DEL CLIENTE: {$message}

REGLAS FINALES ABSOLUTAS ANTES DE RESPONDER:
1. REVISA el menú proporcionado arriba - SOLO menciona platos que REALMENTE aparecen ahí
2. Si el cliente pregunta por un plato que NO está en el menú, di que no está disponible y ofrece alternativas REALES
3. NO inventes platos, ingredientes, preparaciones o descripciones que no estén en el menú
4. Si un plato no tiene descripción en el menú, usa solo el nombre y precio, sin inventar detalles
5. FORMATO OBLIGATORIO: Cada plato DEBE empezar con \"- **Nombre** — **Precio**\" (guión + espacio + nombre en negrita + espacio + guión largo + espacio + precio en negrita). Sin este formato exacto, el botón \"Lo quiero\" NO aparecerá.
6. ⚠️ IMÁGENES OBLIGATORIAS: Si en el contexto aparece [IMAGEN:url] para un plato, DEBES incluirla en tu respuesta. Busca en el contexto las líneas que dicen [IMAGEN:url] y copia exactamente ese formato en tu respuesta después del precio y descripción del plato correspondiente.
7. LENGUAJE NEUTRAL: NUNCA uses frases como \"ven a disfrutar\", \"atrévete ahora y ven\", \"ven a probar\" porque el cliente puede estar DENTRO del local. Usa frases neutrales como \"Disfruta de...\", \"Prueba...\", \"Pide...\", \"No te lo pierdas\", \"Imperdible\".

RESPUESTA (con formato perfecto, entusiasmo, pero SOLO con información REAL del menú):";
    }

    /**
     * Llamar a la API de OpenAI con historial de conversación
     */
    private function callOpenAI(string $prompt, array $conversationHistory = []): string
    {
        // Verificar si la API key está configurada
        if (empty($this->openaiApiKey)) {
            throw new \Exception('OpenAI API key no configurada');
        }

        // Construir array de mensajes con historial + mensaje actual
        $messages = [];
        
        // Agregar historial de conversación si existe
        if (!empty($conversationHistory)) {
            $messages = array_merge($messages, $conversationHistory);
        }
        
        // Agregar el mensaje actual al final
        $messages[] = [
            'role' => 'user',
            'content' => $prompt
        ];

        $payload = [
            'model' => 'gpt-4o-mini',
            'messages' => $messages,
            'max_tokens' => 900,        // Más tokens para respuestas más detalladas
            'temperature' => 0.7,       // Más creatividad para descripciones gourmet
            'presence_penalty' => 0.3,  // Evita repeticiones
            'frequency_penalty' => 0.2, // Fomenta variedad en vocabulario
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->openaiApiKey,
            'Content-Type' => 'application/json',
        ])->timeout(20)->post($this->openaiBaseUrl . '/chat/completions', $payload);

        if ($response->successful()) {
            $data = $response->json();
            return $data['choices'][0]['message']['content'] ?? 'No pude procesar tu mensaje.';
        }

        // Log del error para debugging
        Log::error('Error OpenAI API', [
            'status' => $response->status(),
            'body'   => substr($response->body(), 0, 500),
        ]);

        throw new \Exception('Error al conectar con OpenAI: ' . $response->body());
    }
}
