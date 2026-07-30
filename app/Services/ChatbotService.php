<?php

namespace App\Services;

use App\Http\Middleware\SetLocale;
use App\Models\Cart;
use App\Models\ChatConversation;
use App\Models\Comercio;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotService
{
    private $openaiApiKey;

    private $openaiBaseUrl;

    private ?string $sessionId = null;

    /**
     * Modo de acotación cuando el cliente usa un atajo de la UI:
     * 'categoria' = tocó un filtro de categoría; 'ver_mas' = pidió listar todo lo que coincide.
     */
    private ?string $modoRespuesta = null;

    /** A qué acotar la respuesta: nombre de la categoría, o la consulta original en "ver más". */
    private ?string $scopeRespuesta = null;

    /** Último mensaje del cliente en este request (lo usa la detección de platos). */
    private ?string $ultimoMensaje = null;

    /**
     * Platos que el modelo DECLARÓ recomendar en el marcador [[PLATOS: ...]] de su
     * respuesta. Si viene vacío, la detección cae al match por texto.
     *
     * @var array<int, string>
     */
    private array $platosDeclarados = [];

    /** Lo que el cliente pidió y el menú no tiene, declarado por el modelo en [[FALTA: ...]]. */
    private ?string $faltaDeclarada = null;

    public function __construct()
    {
        $this->openaiApiKey = config('services.openai.api_key');
        $this->openaiBaseUrl = config('services.openai.base_url', 'https://api.openai.com/v1');
    }

    /**
     * Procesar mensaje del chatbot con contexto del menú y memoria de conversación
     */
    public function processMessage(Comercio $comercio, string $message, ?string $sessionId = null, ?string $modoRespuesta = null, ?string $scopeRespuesta = null): string
    {
        try {
            $this->sessionId = $sessionId;
            $this->modoRespuesta = $modoRespuesta;
            $this->scopeRespuesta = $scopeRespuesta;
            $this->ultimoMensaje = $message;
            $this->platosDeclarados = [];
            $this->faltaDeclarada = null;

            // Obtener contexto de los menús procesados
            $contexto = $this->buildMenuContext($comercio);

            // Recortar contexto si es demasiado largo (seguridad)
            $contexto = $this->trimContext($contexto, 48000); // ~aprox 12-15k tokens

            // Obtener historial de conversación si hay sessionId
            $conversationHistory = [];
            if ($sessionId) {
                $conversationHistory = $this->getConversationHistory($comercio, $sessionId);
            }

            // Llamar a OpenAI con historial (modelo y límite de tokens según el plan)
            $response = $this->callOpenAI(
                $this->promptSistema($comercio, $contexto),
                $this->promptUsuario($comercio, $message),
                $conversationHistory,
                $comercio->modeloIA(),
                $comercio->maxTokensChat()
            );

            // Guardar la declaración del modelo y devolver solo la prosa: el marcador
            // no se le muestra al cliente ni se guarda en el historial.
            $sep = $this->separarMarcador($response);
            $this->platosDeclarados = $sep['nombres'];
            $this->faltaDeclarada = $sep['falta'];

            return $sep['texto'];

        } catch (\Exception $e) {
            Log::error('Error en ChatbotService: '.$e->getMessage());

            return 'Lo siento, hubo un error al procesar tu mensaje. Inténtalo de nuevo.';
        }
    }

    /**
     * Construir contexto de los menús y preguntas frecuentes
     */
    /**
     * Menú(s) para el contexto: el activo, o el último procesado si no hay activo.
     */
    private function menusParaContexto(Comercio $comercio)
    {
        $base = $comercio->menus()->where('procesado', true)->whereNotNull('contexto_gpt');
        $menus = (clone $base)->where('activo', true)->get();
        if ($menus->isEmpty()) {
            $menus = (clone $base)->orderBy('created_at', 'desc')->limit(1)->get();
        }

        return $menus;
    }

    /**
     * Lista plana de platos del menú activo: nombre, precio, descripción e imagen (URL resuelta).
     *
     * @return array<int, array{nombre:string, precio:string, descripcion:string, imagen:?string}>
     */
    public function platosDelMenu(Comercio $comercio): array
    {
        $platos = [];
        foreach ($this->menusParaContexto($comercio) as $menu) {
            $data = $this->parseMenuJson($menu->contexto_gpt ?? $menu->texto_extraido);
            if (! $data || ! isset($data['categorias']) || ! is_array($data['categorias'])) {
                continue;
            }
            foreach ($data['categorias'] as $cat) {
                foreach (($cat['platos'] ?? []) as $p) {
                    if (empty($p['nombre'])) {
                        continue;
                    }
                    $platos[] = [
                        'nombre' => trim($p['nombre']),
                        'precio' => trim((string) ($p['precio'] ?? '')),
                        'descripcion' => trim((string) ($p['descripcion'] ?? '')),
                        'imagen' => ! empty($p['imagen']) ? $this->resolverImagenUrl(trim($p['imagen'])) : null,
                    ];
                }
            }
        }

        return $this->traducirDescripciones($comercio, $platos);
    }

    /**
     * Traduce las descripciones de los platos al idioma del cliente (cacheado).
     *
     * Solo la DESCRIPCIÓN: el nombre queda en el idioma del menú porque el cliente se lo
     * tiene que poder pedir al mozo, y porque es la clave con la que se resuelven las
     * tarjetas contra el marcador [[PLATOS: ...]].
     *
     * @param  array<int, array>  $platos
     * @return array<int, array>
     */
    private function traducirDescripciones(Comercio $comercio, array $platos): array
    {
        $descripciones = array_filter(array_column($platos, 'descripcion'));
        if (empty($descripciones)) {
            return $platos;
        }

        $traducciones = app(MenuTranslatorService::class)->descripciones($comercio, $descripciones);

        foreach ($platos as &$plato) {
            if ($plato['descripcion'] !== '') {
                $plato['descripcion'] = $traducciones[$plato['descripcion']] ?? $plato['descripcion'];
            }
        }

        return $platos;
    }

    /**
     * Separa el marcador "[[PLATOS: Nombre | Nombre]]" de la prosa de la respuesta.
     *
     * El marcador es cómo el modelo declara QUÉ está recomendando, para no tener que
     * adivinarlo con un match de texto sobre la prosa (que fallaba en las dos direcciones:
     * perdía tarjetas cuando parafraseaba el nombre y las inventaba cuando nombraba un
     * plato al pasar). Nunca llega al cliente.
     *
     * También saca "[[FALTA: sushi]]", con lo que el cliente pidió y el menú no tiene.
     * Eso alimenta la métrica de demanda insatisfecha del panel, y va declarado por el
     * modelo en vez de inferido de "no recomendó nada": el prompt le pide ofrecer
     * alternativas, así que igual termina recomendando algo y el conteo de platos no
     * sirve como señal (medido: detectaba 1 de cada 3 casos).
     *
     * @return array{texto:string, nombres:array<int, string>, falta:?string}
     */
    private function separarMarcador(string $respuesta): array
    {
        // El cierre "]]" es opcional: si max_tokens cortó la respuesta a mitad del
        // marcador, igual queremos limpiarlo y aprovechar los nombres que llegaron.
        $nombres = [];
        if (preg_match('/\[\[\s*PLATOS\s*:(.*?)(?:\]\]|$)/is', $respuesta, $m)) {
            $nombres = array_values(array_filter(
                array_map('trim', explode('|', $m[1])),
                fn ($n) => $n !== ''
            ));
        }

        $falta = null;
        if (preg_match('/\[\[\s*FALTA\s*:(.*?)(?:\]\]|$)/is', $respuesta, $f)) {
            $falta = trim($f[1]);
            $falta = $falta !== '' ? mb_substr($falta, 0, 60) : null;
        }

        // Se corta desde el PRIMER marcador en adelante: así no se filtra ninguno,
        // ni siquiera si el modelo manda FALTA sin PLATOS.
        $texto = trim(preg_replace('/\[\[\s*(?:PLATOS|FALTA)\s*:.*$/is', '', $respuesta));

        return ['texto' => $texto, 'nombres' => $nombres, 'falta' => $falta];
    }

    /**
     * Resuelve los nombres declarados por el modelo contra los platos reales del menú,
     * en el orden en que los declaró. Lo que no exista en el menú se descarta: es la red
     * de seguridad contra un plato inventado.
     */
    private function platosDesdeDeclaracion(Comercio $comercio, array $nombres): array
    {
        $porNombre = [];
        foreach ($this->platosDelMenu($comercio) as $p) {
            $porNombre[$this->normalizarTexto($p['nombre'])] = $p;
        }

        $platos = [];
        $vistos = [];
        $descartados = [];
        foreach ($nombres as $nombre) {
            $plato = $this->resolverNombreDeclarado($nombre, $porNombre);
            if ($plato === null) {
                $descartados[] = $nombre;

                continue;
            }
            $clave = $this->normalizarTexto($plato['nombre']);
            if (isset($vistos[$clave])) {
                continue;
            }
            $vistos[$clave] = true;
            $platos[] = $plato;
        }

        if (! empty($descartados)) {
            Log::info('Chatbot: platos declarados que no existen en el menú', [
                'comercio_id' => $comercio->id,
                'descartados' => $descartados,
            ]);
        }

        return $platos;
    }

    /**
     * Lo que el cliente pidió y el menú no tiene, si el modelo lo declaró.
     * Lo consume el controller para guardarlo y alimentar el panel de analytics.
     */
    public function faltanteDeclarado(): ?string
    {
        return $this->faltaDeclarada;
    }

    /**
     * Busca a qué plato del menú corresponde un nombre declarado por el modelo.
     *
     * Primero match exacto. Si falla, el modelo suele haber agregado un prefijo
     * ("Pizza Muzzarella (Grande)" por "Muzzarella (Grande)"): aceptamos el nombre del
     * menú contenido en lo declarado, y ante varios candidatos gana el más largo
     * ("Empanada de Jamón y Queso" → "Jamón y Queso", no el adicional "Jamón").
     * Si hay empate en el más largo, no adivinamos: devolvemos null.
     *
     * @param  array<string, array>  $porNombre  platos del menú indexados por nombre normalizado
     */
    private function resolverNombreDeclarado(string $declarado, array $porNombre): ?array
    {
        $clave = $this->normalizarTexto($declarado);
        if ($clave === '') {
            return null;
        }
        if (isset($porNombre[$clave])) {
            return $porNombre[$clave];
        }

        $candidatos = [];
        foreach ($porNombre as $key => $plato) {
            if (mb_strlen($key) >= 3 && str_contains($clave, $key)) {
                $candidatos[] = ['len' => mb_strlen($key), 'plato' => $plato];
            }
        }
        if (empty($candidatos)) {
            return null;
        }

        usort($candidatos, fn ($a, $b) => $b['len'] <=> $a['len']);
        if (count($candidatos) > 1 && $candidatos[0]['len'] === $candidatos[1]['len']) {
            return null; // empate: ambiguo, mejor ninguna tarjeta que la equivocada
        }

        return $candidatos[0]['plato'];
    }

    /**
     * Platos a mostrar como tarjeta (foto, precio, botón "Lo quiero").
     *
     * Si el modelo declaró los platos en el marcador, se usa esa declaración: es su
     * decisión explícita. Si no (modelo que se olvidó el marcador, o respuesta de
     * fallback), se cae al match por nombre sobre la prosa.
     */
    public function detectarPlatosMencionados(Comercio $comercio, string $respuesta): array
    {
        if (! empty($this->platosDeclarados)) {
            return $this->platosDesdeDeclaracion($comercio, $this->platosDeclarados);
        }

        // Por si nos llaman con una respuesta cruda que todavía trae el marcador.
        $sep = $this->separarMarcador($respuesta);
        if (! empty($sep['nombres'])) {
            return $this->platosDesdeDeclaracion($comercio, $sep['nombres']);
        }

        return $this->detectarPorTexto($comercio, $sep['texto']);
    }

    /**
     * Fallback: detecta qué platos del menú aparecen en la prosa (match por nombre
     * normalizado), en orden de aparición. Devuelve el dato EXACTO del JSON.
     */
    private function detectarPorTexto(Comercio $comercio, string $respuesta): array
    {
        $platos = $this->platosDelMenu($comercio);
        if (empty($platos)) {
            return [];
        }
        $hay = $this->normalizarTexto($respuesta);

        // Lo que el cliente YA tiene en el carrito no vuelve a salir como tarjeta: el bot lo
        // nombra para maridar ("tu Milanesa pide un Flan") y terminaba apareciendo con foto y
        // botón "Lo quiero", como si fuera lo recomendado. Pedís algo dulce y ves un salado.
        // Excepción: si el propio cliente lo nombró ("quiero otra milanesa"), sí corresponde.
        $enCarrito = $this->nombresEnCarrito($comercio);
        $mensaje = $this->normalizarTexto($this->ultimoMensaje ?? '');

        $encontrados = [];
        foreach ($platos as $p) {
            $needle = $this->normalizarTexto($p['nombre']);
            if (mb_strlen($needle) < 3 || isset($encontrados[$needle])) {
                continue;
            }
            if (in_array($needle, $enCarrito, true) && ! str_contains($mensaje, $needle)) {
                continue;
            }
            // Un nombre de UNA sola palabra genérica ("Clásica", "Especial", "Casera"...)
            // colisiona con esa palabra usada como adjetivo ("una opción clásica"), así que
            // no lo tomamos como mención: sería un falso positivo.
            if ($this->esNombreGenerico($needle)) {
                continue;
            }
            // Match por palabra COMPLETA (no substring): evita que "jamón" matchee dentro
            // de "acompañar" o una pizza "Napolitana" dentro de otra palabra.
            if (preg_match('/\b'.preg_quote($needle, '/').'\b/', $hay, $m, PREG_OFFSET_CAPTURE)) {
                $pos = $m[0][1];
                $encontrados[$needle] = [
                    'pos' => $pos,
                    'end' => $pos + strlen($needle),
                    'len' => strlen($needle),
                    'plato' => $p,
                ];
            }
        }

        // Descartar el match contenido dentro de otro más largo en la misma posición:
        // "Jamón" dentro de "Jamón y Queso" deja solo el plato específico.
        $encontrados = array_filter($encontrados, function ($a) use ($encontrados) {
            foreach ($encontrados as $b) {
                if ($b['len'] > $a['len'] && $a['pos'] >= $b['pos'] && $a['end'] <= $b['end']) {
                    return false;
                }
            }

            return true;
        });

        uasort($encontrados, fn ($a, $b) => $a['pos'] <=> $b['pos']);

        return array_values(array_map(fn ($e) => $e['plato'], $encontrados));
    }

    /**
     * Nombres (normalizados) de los platos que el cliente ya tiene en el carrito de esta sesión.
     *
     * @return array<int, string>
     */
    private function nombresEnCarrito(Comercio $comercio): array
    {
        if (empty($this->sessionId)) {
            return [];
        }

        $cart = Cart::where('comercio_id', $comercio->id)
            ->where('session_id', $this->sessionId)
            ->with('items')
            ->first();

        if (! $cart) {
            return [];
        }

        return $cart->items->map(fn ($i) => $this->normalizarTexto($i->nombre))->all();
    }

    /**
     * ¿El nombre es una sola palabra genérica de carta? Estas colisionan con el mismo
     * término usado como adjetivo en la prosa del bot y generan tarjetas equivocadas.
     * Los nombres de varias palabras ya son específicos y no se filtran.
     */
    private function esNombreGenerico(string $needle): bool
    {
        if (str_contains($needle, ' ')) {
            return false;
        }

        static $genericos = [
            'clasica', 'clasico', 'especial', 'casera', 'casero', 'simple', 'completa',
            'completo', 'tradicional', 'grande', 'chica', 'chico', 'mediana', 'mediano',
            'doble', 'familiar', 'individual', 'comun', 'mixta', 'mixto', 'premium',
            'super', 'basica', 'basico', 'deluxe', 'surtida', 'surtido', 'combinado',
        ];

        return in_array($needle, $genericos, true);
    }

    private function normalizarTexto(string $t): string
    {
        $t = mb_strtolower($t, 'UTF-8');
        $t = strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
        $t = preg_replace('/[^a-z0-9 ]+/', ' ', $t);

        return trim(preg_replace('/\s+/', ' ', $t));
    }

    /**
     * Resuelve la URL de una imagen de plato (mismo criterio que el contexto).
     */
    private function resolverImagenUrl(string $ruta): string
    {
        if (str_starts_with($ruta, 'http://') || str_starts_with($ruta, 'https://')) {
            return $ruta;
        }
        if (! str_starts_with($ruta, '/')) {
            $ruta = '/'.$ruta;
        }
        if (! str_starts_with($ruta, '/storage/')) {
            $ruta = str_starts_with($ruta, 'storage/') ? '/'.$ruta : '/storage/'.ltrim($ruta, '/');
        }

        return asset($ruta);
    }

    private function buildMenuContext(Comercio $comercio): string
    {
        $menus = $this->menusParaContexto($comercio);

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
        if (! $menus->isEmpty()) {
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
                                    if (isset($plato['descripcion']) && ! empty($plato['descripcion'])) {
                                        $contexto .= "\n  Descripción: {$plato['descripcion']}";
                                    }
                                    // INCLUIR TAGS si existen
                                    if (isset($plato['tags']) && is_array($plato['tags']) && ! empty($plato['tags'])) {
                                        try {
                                            // Filtrar solo tags válidos (strings no vacíos)
                                            $tagsValidos = array_filter($plato['tags'], function ($tag) {
                                                return is_string($tag) && ! empty(trim($tag));
                                            });
                                            if (! empty($tagsValidos)) {
                                                $tagsStr = implode(', ', $tagsValidos);
                                                $contexto .= "\n  Tags: {$tagsStr}";
                                            }
                                        } catch (\Exception $e) {
                                            // Si hay error procesando tags, simplemente continuar sin tags
                                            Log::warning('Error procesando tags en ChatbotService: '.$e->getMessage());
                                        }
                                    }
                                    // INCLUIR URL DE IMAGEN si existe
                                    if (isset($plato['imagen']) && ! empty($plato['imagen'])) {
                                        $rutaImagen = trim($plato['imagen']);

                                        // Si ya es una URL completa (http:// o https://), usarla directamente
                                        if (str_starts_with($rutaImagen, 'http://') || str_starts_with($rutaImagen, 'https://')) {
                                            $imagenUrl = $rutaImagen;
                                        } else {
                                            // Es una ruta relativa, normalizarla
                                            // Si no empieza con /, agregarlo
                                            if (! str_starts_with($rutaImagen, '/')) {
                                                $rutaImagen = '/'.$rutaImagen;
                                            }

                                            // Si no empieza con /storage/, agregarlo
                                            if (! str_starts_with($rutaImagen, '/storage/')) {
                                                // Si empieza con storage/, agregar /
                                                if (str_starts_with($rutaImagen, 'storage/')) {
                                                    $rutaImagen = '/'.$rutaImagen;
                                                } else {
                                                    $rutaImagen = '/storage/'.ltrim($rutaImagen, '/');
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
                    $contexto .= $contextoMenu."\n\n";
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
        if ($jsonContent === null) {
            return null;
        }

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
        if ($text === null) {
            return '';
        }
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
        $head = mb_substr($text, 0, (int) ($maxChars * 0.6), 'UTF-8');
        $tail = mb_substr($text, -(int) ($maxChars * 0.4), null, 'UTF-8');

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
                'preview' => mb_substr($clean, 0, $perMenuLimit, 'UTF-8'),
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
                    'content' => $conv->user_message,
                ];

                // Agregar respuesta del bot
                $history[] = [
                    'role' => 'assistant',
                    'content' => $conv->bot_response,
                ];
            }

            return $history;
        } catch (\Exception $e) {
            Log::error('Error getting conversation history: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Crear prompt para OpenAI
     */
    /**
     * Bloque de indicaciones personalizadas del local (tono, focos de venta, estrellas, detalles).
     */
    private function indicacionesLocal(Comercio $comercio): string
    {
        $c = $comercio->chatbot_config;
        if (! is_array($c)) {
            return '';
        }

        $lineas = [];

        $tonos = [
            'cercano_divertido' => 'cercano y divertido',
            'calido_atento' => 'cálido y atento',
            'formal_elegante' => 'formal y elegante',
            'canchero' => 'canchero y relajado',
        ];
        if (! empty($c['tono']) && isset($tonos[$c['tono']])) {
            $lineas[] = 'Hablá en un tono '.$tonos[$c['tono']].'.';
        }

        // El trato solo aplica cuando se responde EN ESPAÑOL: si el cliente escribe en
        // inglés o portugués, forzar el voseo lo arrastraría de vuelta al español.
        $tratos = [
            'voseo' => 'Cuando respondas en español, tratá al cliente de "vos" (voseo argentino).',
            'tuteo' => 'Cuando respondas en español, tratá al cliente de "tú".',
            'neutro' => 'Cuando respondas en español, usá un trato neutral (ni vos ni tú marcado).',
        ];
        if (! empty($c['trato']) && isset($tratos[$c['trato']])) {
            $lineas[] = $tratos[$c['trato']];
        }

        $emojis = [
            'moderado' => 'Usá emojis con moderación.',
            'minimo' => 'Usá muy pocos emojis.',
            'ninguno' => 'No uses emojis.',
        ];
        if (! empty($c['emojis']) && isset($emojis[$c['emojis']])) {
            $lineas[] = $emojis[$c['emojis']];
        }

        if (! empty($c['estrellas']) && is_array($c['estrellas'])) {
            $est = implode(', ', array_filter(array_map('trim', $c['estrellas'])));
            if ($est !== '') {
                $lineas[] = 'Recomendá primero, cuando encajen con lo que pide el cliente, estos platos estrella: '.$est.'.';
            }
        }

        $focosMap = [
            'promos' => 'las promos del día',
            'compartir' => 'opciones para compartir',
            'veganas' => 'opciones veganas o sin TACC',
            'bebidas' => 'una bebida o trago para acompañar',
            'postres' => 'un postre para cerrar',
            'combos' => 'combos',
        ];
        if (! empty($c['focos']) && is_array($c['focos'])) {
            $f = array_values(array_filter(array_map(fn ($x) => $focosMap[$x] ?? null, $c['focos'])));
            if (! empty($f)) {
                $lineas[] = 'Cuando tenga sentido, ofrecé o destacá: '.implode(', ', $f).'.';
            }
        }

        if (! empty($c['detalles'])) {
            $lineas[] = trim($c['detalles']);
        }

        if (empty($lineas)) {
            return '';
        }

        return "\nINDICACIONES DE ESTE LOCAL (respetalas siempre):\n- ".implode("\n- ", $lineas)."\n";
    }

    /**
     * Lee el carrito de la sesión actual para que el bot recomiende en base a lo ya elegido (maridajes, etc.).
     */
    private function carritoActual(Comercio $comercio): string
    {
        if (empty($this->sessionId)) {
            return '';
        }

        $cart = Cart::where('comercio_id', $comercio->id)
            ->where('session_id', $this->sessionId)
            ->with('items')
            ->first();

        if (! $cart || $cart->items->isEmpty()) {
            return '';
        }

        $items = $cart->items
            ->map(fn ($i) => $i->qty > 1 ? "{$i->nombre} (x{$i->qty})" : $i->nombre)
            ->implode(', ');

        return "\nDATO DE CONTEXTO — EL CLIENTE YA AGREGÓ A SU PEDIDO: {$items}.\n".
            'Es SOLO contexto, NO es lo que tenés que recomendar ahora. No lo vuelvas a ofrecer ni lo nombres, '
            .'salvo que el cliente pregunte específicamente qué sumar, qué tomar o algo para acompañar: recién ahí '
            .'usalo para elegir mejor, recomendando DENTRO del tipo que te pide lo que mejor marida con eso. '
            ."Nunca dejes que lo que ya tiene en el pedido te desvíe del tipo de plato que te está pidiendo en este mensaje.\n";
    }

    /**
     * Prompt completo (system + user) concatenado. Solo para debug/preview.
     */
    private function createPrompt(Comercio $comercio, string $contexto, string $message): string
    {
        return $this->promptSistema($comercio, $contexto)."\n\n".$this->promptUsuario($comercio, $message);
    }

    /**
     * Bloque ESTÁTICO del prompt, para el rol `system`.
     *
     * El orden importa para el prompt caching de OpenAI, que funciona por prefijo:
     * las reglas van PRIMERO porque son idénticas para todos los comercios (prefijo
     * compartido que queda caliente entre todos), y el menú va al FINAL porque es
     * propio de cada uno. Nada dinámico (carrito, pregunta, modo) puede entrar acá.
     */
    private function promptSistema(Comercio $comercio, string $contexto): string
    {
        $indicaciones = $this->indicacionesLocal($comercio);

        return "Sos el anfitrión gastronómico de este restaurante: conocés el menú al detalle, recomendás como un buen mozo y sabés maridar como un sommelier. Tu misión es que el cliente encuentre EXACTAMENTE lo que busca y arme un pedido que lo deje feliz, siempre con platos REALES del menú.

⚠️ REGLA #1 — IDENTIFICÁ QUÉ CLASE DE PEDIDO ES. Hay dos y se responden al revés:

(a) PEDIDO ESPECÍFICO — el cliente dice QUÉ TIPO quiere: \"algo dulce\", \"un postre\", \"una bebida\", \"algo salado\", \"una entrada\", \"algo sin TACC\", \"una pizza\".
    → SOLO platos de ESE tipo, nada de otro tipo ni para acompañar. Dulce → SOLO postres, helados, tortas; NUNCA un salado. Salado → NUNCA un postre. Para tomar → SOLO bebidas. Un momento puntual (entrada, principal, guarnición) → SOLO ese momento.
    → Esta regla manda sobre la venta cruzada, sobre los platos estrella del local y sobre lo que ya tenga en el carrito. Si el menú no tiene nada de ese tipo, decilo; y si ofrecés otra cosa, avisá que es de otro tipo (nunca lo cambies en silencio).

(b) PEDIDO ABIERTO O DE GRUPO — NO dice el tipo: \"qué me recomendás\", \"tengo hambre\", \"somos 4\", \"venimos 6\", \"armanos algo para compartir\".
    → Acá SÍ armá una propuesta COMPLETA: algo principal o para compartir + una bebida + un postre para cerrar. No te limites a un solo tipo: es lo que más lo ayuda y lo que mejor vende.
    → Si dice CUÁNTAS PERSONAS son, priorizá las promos o combos dimensionados para esa cantidad y ajustá las cantidades a la mesa (4 personas → una pizza grande y una docena de empanadas, no una porción). Si la promo ya incluye bebida, no sumes otra: decí qué incluye.

SOLO INFORMACIÓN REAL DEL MENÚ — NO INVENTES NADA:
- Mencioná ÚNICAMENTE platos que aparecen en el menú provisto. Si el cliente pide algo que no está, decilo claro (\"ese plato no está disponible en nuestro menú actual\") y ofrecé alternativas REALES.
- Si el plato TIENE descripción en el menú, usala y mejorala sutilmente con lenguaje gourmet, sin cambiar la información.
- Si NO tiene descripción, podés hacer una muy breve basada SOLO en el nombre (\"Milanesa\" → \"milanesa empanada\"). NO inventes tipo de carne, ingredientes, salsas, guarniciones ni preparaciones.
- TAGS (sin TACC, vegano, vegetariano, sin lactosa...): mencionalos SOLO si están escritos explícitamente en el menú para ese plato. NUNCA los deduzcas del tipo de producto ni de tu conocimiento general: una bebida o un té no es sin TACC porque sí, y algo que no menciona carne no es vegano porque sí.
- Al buscar por tag compará por SIGNIFICADO, no por formato: \"sin tacc\" también matchea SIN_TACC, sin gluten, gluten free, celíaco. Cada menú usa sus propias etiquetas, así que adaptate a las que ves. Devolvé TODOS los platos del menú que coincidan.
- Si ningún plato tiene el tag que piden, decí claramente que no tenés esa información en el menú y que conviene confirmarlo con el restaurante. NO adivines.

IDIOMA (IMPORTANTE):
- Más abajo, junto a la pregunta, se te indica EN QUÉ IDIOMA responder. Respetalo siempre y no le avises al cliente que cambiaste de idioma: simplemente escribí en el suyo.
- ⚠️ EXCEPCIÓN QUE NO SE NEGOCIA: los NOMBRES DE LOS PLATOS van SIEMPRE tal como figuran en el menú, en su idioma original, aunque estés escribiendo en otro idioma. El cliente tiene que poder pedirle ese plato al mozo, que no habla su idioma. Traducí la descripción, nunca el nombre.
  Ejemplo en inglés: \"The Milanesa napolitana is a classic here — breaded beef with ham, tomato and melted cheese.\" (el nombre queda en español, la explicación va en inglés).
- Las categorías sí podés nombrarlas en el idioma del cliente (\"POSTRES\" → \"desserts\").
- Los tags de alergias y dietas (sin TACC, vegano, sin lactosa) mencionalos con el término del menú y, si hace falta, aclarás entre paréntesis. Nunca cambies su significado: es información de salud.

CÓMO ESCRIBIR:
- Sos entusiasta y gourmet: destacá sabores y texturas, pero de platos REALES. Escribí en prosa natural, cálida y breve, no como catálogo ni con listas de guiones.
- Recomendá POCOS platos (2 o 3) bien elegidos. Si piden \"ver más\", \"todas las opciones\" o el menú completo, ahí SÍ listá todas las que coincidan con su consulta, sin límite.
- Nombrá cada plato con su NOMBRE EXACTO del menú. La app le agrega sola la FOTO, el PRECIO y el botón \"Lo quiero\": NO escribas precios, NO pegues imágenes ni links.
- Cada plato que nombrás se le convierte en una tarjeta con botón de compra. Por eso no nombres platos de paso, ni para descartarlos, ni como ejemplo de sabores que no estás recomendando de verdad.
- VENTA SUGERIDA: según la REGLA #1. Caso (b) → mesa completa con bebida y postre. Caso (a) → NADA de otro tipo, ni siquiera como cierre o \"broche de oro\": si pide qué tomar, recomendá SOLO bebidas (la que mejor marida con lo que ya tiene); si pide un postre, SOLO postres. El maridaje existe para elegir mejor DENTRO del tipo que pidió, no para agregar otros tipos. Recién si el pedido es abierto (caso b) sumás algo de otra categoría.
- Entendés el habla argentina cotidiana (\"birra\", \"chupi\", \"algo para tomar\", \"papas\", \"fritas\", \"una coca\") y la traducís a lo que realmente hay en el menú.
- LENGUAJE NEUTRAL: el cliente puede estar DENTRO o FUERA del local. NUNCA uses \"ven\", \"te esperamos\", \"cuando vengas\". Usá \"Probá...\", \"Pedí...\", \"Disfrutá...\", \"Te recomiendo...\", \"No te lo pierdas\".
- Un emoji de categoría queda bien, con moderación (🍕 pizzas, 🍝 pastas, 🥗 ensaladas, 🍖 carnes, 🍤 mariscos, 🍷 vinos, 🍺 cervezas, 🍰 postres, ☕ café).

INFORMACIÓN DEL RESTAURANTE Y MENÚ:
{$contexto}
{$indicaciones}";
    }

    /**
     * Idioma en el que tiene que responder el bot, en español para el prompt.
     *
     * Se resuelve por código y se le dice al modelo de forma EXPLÍCITA, en vez de
     * confiar en que lo infiera: como todo el prompt está escrito en español, el
     * modelo tiende a responder en español aunque el cliente escriba en otro idioma.
     * Medido: inferir solo funcionaba 1 de cada 4 veces.
     */
    private function idiomaRespuesta(): string
    {
        return SetLocale::NOMBRES[app()->getLocale()] ?? 'español';
    }

    /** Frase de refuerzo escrita en el idioma destino (ver SetLocale::ANCLAJES). */
    private function anclajeIdioma(): string
    {
        return SetLocale::ANCLAJES[app()->getLocale()] ?? SetLocale::ANCLAJES['es'];
    }

    /**
     * Bloque VARIABLE del prompt, para el rol `user`: lo que cambia en cada mensaje
     * (carrito, pregunta, modo de la UI). Va después del historial.
     */
    private function promptUsuario(Comercio $comercio, string $message): string
    {
        $carrito = $this->carritoActual($comercio);
        $scope = $this->scopeRespuesta ?: $message;
        $modoExtra = '';
        if ($this->modoRespuesta === 'categoria') {
            $modoExtra = "\n\n⚠️ MODO CATEGORÍA — ESTA REGLA MANDA SOBRE TODAS LAS ANTERIORES:\n"
                ."El cliente está mirando SOLO la categoría \"{$scope}\" del menú (tocó ese filtro, no escribió una pregunta libre).\n"
                ."- Recomendá/mostrá ÚNICAMENTE platos que pertenezcan a la categoría \"{$scope}\" del menú. NO menciones NI sugieras nada de otras categorías.\n"
                ."- PROHIBIDA la venta cruzada: NO sumes una bebida, guarnición, entrada ni postre de otra categoría. Ignorá cualquier regla anterior que pida acompañamientos.\n"
                ."- Si el cliente pide ver TODAS/más opciones, listá TODOS los platos de la categoría \"{$scope}\" (sin límite). Si no, presentá unas pocas bien elegidas de esa misma categoría (3 a 5).\n"
                ."- Si esa categoría no existe en el menú, decilo con amabilidad y ofrecé ver otra categoría real.\n";
        } elseif ($this->modoRespuesta === 'ver_mas') {
            $modoExtra = "\n\n⚠️ MODO VER MÁS — ESTA REGLA MANDA SOBRE TODAS LAS ANTERIORES:\n"
                ."El cliente pidió ver TODAS las opciones que coincidan con su consulta anterior: \"{$scope}\".\n"
                ."- Listá ÚNICAMENTE los platos del menú que coincidan con esa consulta (mismo tipo/categoría de producto).\n"
                ."- NO vuelques el menú completo. NO agregues platos ni categorías que no tengan relación con \"{$scope}\".\n"
                ."- PROHIBIDA la venta cruzada con otras categorías. Sin límite de cantidad: mostrá TODAS las que coincidan.\n";
        }

        $idioma = $this->idiomaRespuesta();
        $anclaje = $this->anclajeIdioma();

        return "{$carrito}
⚠️ IDIOMA DE LA RESPUESTA: {$idioma}. {$anclaje}
Los nombres de los platos son la ÚNICA excepción: van tal cual figuran en el menú, sin traducir, porque el cliente se los tiene que poder pedir al mozo.

PREGUNTA DEL CLIENTE: {$message}

ANTES DE ENVIAR, VERIFICÁ:
0. IDIOMA: ¿está tu respuesta ENTERA en {$idioma}? {$anclaje} Si redactaste en otro idioma por costumbre, REESCRIBILA COMPLETA. Los nombres de los platos quedan en el idioma del menú.
1. Todos los platos que nombraste están en el menú provisto, escritos con su nombre EXACTO.
2. Si fue pedido ESPECÍFICO (caso a): ¿se te coló algo de otro tipo? Si pidió dulce y nombraste un salado (o al revés), o pidió bebida y nombraste comida, borralo y reescribí.
3. Si fue pedido ABIERTO o de GRUPO (caso b): ¿la propuesta está completa (algo principal o para compartir, una bebida y un postre, salvo que la promo ya traiga bebida)?
4. Sin precios, sin links, sin listas con guiones, y sin ingredientes ni tags inventados.
{$modoExtra}
FORMATO DE SALIDA (OBLIGATORIO):
Primero escribí tu respuesta en prosa, natural, como le hablarías al cliente. En la prosa nombrá los platos como se dicen naturalmente (\"una Coca Cola bien fría\", \"la muzza grande\").
Y en la ÚLTIMA LÍNEA, cerrá SIEMPRE con este marcador, que el cliente NO ve:
[[PLATOS: Nombre exacto del menú | Otro nombre exacto del menú]]
- Copiá cada nombre EXACTAMENTE como figura en el menú, con su tamaño o variante y hasta el último detalle (si el menú dice \"Coca Cola 354 ml\", va \"Coca Cola 354 ml\", no \"Coca Cola\"; si dice \"Muzzarella (Grande)\", va así y no \"Pizza Muzzarella grande\").
- El marcador va SIEMPRE en el idioma original del menú, incluso si le estás respondiendo en inglés o portugués. NUNCA traduzcas los nombres acá: si los traducís, el plato no se encuentra en el menú y el cliente se queda sin poder pedirlo.
- Listá SOLO los platos que estás recomendando de verdad, en el orden en que los recomendás. NO incluyas lo que el cliente ya tiene en el pedido, ni platos que nombraste al pasar o para descartar, ni sabores de ejemplo.
- Si no estás recomendando ningún plato (un saludo, una pregunta de horarios), cerrá con [[PLATOS: ]] vacío.
- El marcador es la ÚNICA lista: no repitas los platos como lista con guiones dentro de la prosa, ni cierres con un resumen tipo \"te sugiero: - X - Y\". En la prosa van dentro de las frases y nada más.

Y SI el cliente pidió algo que el menú NO tiene, agregá en la línea siguiente un segundo marcador (tampoco lo ve el cliente):
[[FALTA: lo que pidió y no está]]
- Poné SOLO de qué se trata, en 1 a 3 palabras y en el idioma del menú: \"sushi\", \"sin TACC\", \"delivery\", \"opciones veganas\". No la frase completa del cliente.
- Va IGUAL aunque le hayas ofrecido alternativas: sirve para que el local sepa qué le están pidiendo y no tiene.
- Si el menú sí tenía lo que pidió, NO pongas este marcador.
- Ese marcador se convierte en las tarjetas con foto, precio y botón \"Lo quiero\". Si te lo olvidás, el cliente no puede pedir nada.

RESPUESTA (cálida, breve y que recomiende, SOLO con información REAL del menú):";
    }

    /**
     * Arma el array de mensajes: el bloque estático como `system` PRIMERO (para que sea
     * prefijo cacheable), después el historial, y al final el mensaje variable.
     *
     * @return array<int, array{role:string, content:string}>
     */
    private function armarMensajes(string $system, string $userMessage, array $conversationHistory): array
    {
        $messages = [['role' => 'system', 'content' => $system]];

        if (! empty($conversationHistory)) {
            $messages = array_merge($messages, $conversationHistory);
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        return $messages;
    }

    /**
     * Llamar a la API de OpenAI con historial de conversación
     */
    private function callOpenAI(string $system, string $userMessage, array $conversationHistory = [], string $model = 'gpt-4o-mini', int $maxTokens = 900): string
    {
        // Verificar si la API key está configurada
        if (empty($this->openaiApiKey)) {
            throw new \Exception('OpenAI API key no configurada');
        }

        $messages = $this->armarMensajes($system, $userMessage, $conversationHistory);

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $maxTokens, // Según el plan del comercio
            'temperature' => 0.7,       // Más creatividad para descripciones gourmet
            'presence_penalty' => 0.3,  // Evita repeticiones
            'frequency_penalty' => 0.2, // Fomenta variedad en vocabulario
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->openaiApiKey,
            'Content-Type' => 'application/json',
        ])->timeout(20)->post($this->openaiBaseUrl.'/chat/completions', $payload);

        if ($response->successful()) {
            $data = $response->json();

            return $data['choices'][0]['message']['content'] ?? 'No pude procesar tu mensaje.';
        }

        // Log del error para debugging
        Log::error('Error OpenAI API', [
            'status' => $response->status(),
            'body' => substr($response->body(), 0, 500),
        ]);

        throw new \Exception('Error al conectar con OpenAI: '.$response->body());
    }

    /**
     * Igual que processMessage, pero en streaming: llama a $onDelta con cada fragmento
     * de texto a medida que OpenAI lo genera, y devuelve la respuesta completa.
     */
    public function streamMessage(Comercio $comercio, string $message, ?string $sessionId, ?string $modoRespuesta, ?string $scopeRespuesta, callable $onDelta): string
    {
        $this->sessionId = $sessionId;
        $this->modoRespuesta = $modoRespuesta;
        $this->scopeRespuesta = $scopeRespuesta;
        $this->ultimoMensaje = $message;
        $this->platosDeclarados = [];
        $this->faltaDeclarada = null;

        $contexto = $this->trimContext($this->buildMenuContext($comercio), 48000);
        $conversationHistory = $sessionId ? $this->getConversationHistory($comercio, $sessionId) : [];

        [$filtrado, $vaciar] = $this->filtrarMarcador($onDelta);

        $completa = $this->callOpenAIStream(
            $this->promptSistema($comercio, $contexto),
            $this->promptUsuario($comercio, $message),
            $conversationHistory,
            $comercio->modeloIA(),
            $comercio->maxTokensChat(),
            $filtrado
        );

        $vaciar(); // por si quedó un "[" retenido que nunca abrió marcador

        $sep = $this->separarMarcador($completa);
        $this->platosDeclarados = $sep['nombres'];
        $this->faltaDeclarada = $sep['falta'];

        return $sep['texto'];
    }

    /**
     * Envuelve el callback de streaming para que el marcador [[PLATOS: ...]] no se emita
     * al cliente. Como llega partido en tokens ("[", "[PLATOS", ...), retiene un "["
     * final hasta saber si abre el marcador o es un corchete de la prosa.
     *
     * Devuelve [callback filtrado, flush]. Hay que llamar al flush al terminar el stream
     * para soltar un "[" retenido que resultó ser prosa.
     *
     * @return array{0: callable, 1: callable}
     */
    private function filtrarMarcador(callable $onDelta): array
    {
        $pendiente = '';
        $enMarcador = false;

        $filtrado = function (string $delta) use (&$pendiente, &$enMarcador, $onDelta) {
            if ($enMarcador) {
                return; // desde que abre el marcador, ya no se emite nada más
            }

            $pendiente .= $delta;

            if (($pos = strpos($pendiente, '[[')) !== false) {
                $enMarcador = true;
                $previo = substr($pendiente, 0, $pos);
                $pendiente = '';
                if ($previo !== '') {
                    $onDelta($previo);
                }

                return;
            }

            // Un "[" al final podría ser el comienzo del marcador: lo retenemos un token.
            if (str_ends_with($pendiente, '[')) {
                $emitir = substr($pendiente, 0, -1);
                $pendiente = '[';
            } else {
                $emitir = $pendiente;
                $pendiente = '';
            }

            if ($emitir !== '') {
                $onDelta($emitir);
            }
        };

        $vaciar = function () use (&$pendiente, &$enMarcador, $onDelta) {
            if (! $enMarcador && $pendiente !== '') {
                $onDelta($pendiente);
            }
            $pendiente = '';
        };

        return [$filtrado, $vaciar];
    }

    /**
     * Llamada a OpenAI con stream:true. Parsea el SSE de OpenAI y emite cada token
     * por $onDelta. Devuelve el texto completo acumulado.
     */
    private function callOpenAIStream(string $system, string $userMessage, array $conversationHistory, string $model, int $maxTokens, callable $onDelta): string
    {
        if (empty($this->openaiApiKey)) {
            throw new \Exception('OpenAI API key no configurada');
        }

        $messages = $this->armarMensajes($system, $userMessage, $conversationHistory);

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $maxTokens,
            'temperature' => 0.7,
            'presence_penalty' => 0.3,
            'frequency_penalty' => 0.2,
            'stream' => true,
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->openaiApiKey,
            'Content-Type' => 'application/json',
        ])->withOptions(['stream' => true])->timeout(60)->post($this->openaiBaseUrl.'/chat/completions', $payload);

        if ($response->status() !== 200) {
            Log::error('Error OpenAI API (stream)', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);

            throw new \Exception('Error al conectar con OpenAI (stream)');
        }

        $body = $response->toPsrResponse()->getBody();
        $buffer = '';
        $full = '';

        while (! $body->eof()) {
            $buffer .= $body->read(2048);

            // El SSE de OpenAI manda una línea "data: {...}" por token, separadas por \n
            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $pos));
                $buffer = substr($buffer, $pos + 1);

                if ($line === '' || ! str_starts_with($line, 'data:')) {
                    continue;
                }

                $data = trim(substr($line, 5));
                if ($data === '[DONE]') {
                    return $full;
                }

                $json = json_decode($data, true);
                $delta = $json['choices'][0]['delta']['content'] ?? '';
                if ($delta !== '') {
                    $full .= $delta;
                    $onDelta($delta);
                }
            }
        }

        return $full;
    }
}
