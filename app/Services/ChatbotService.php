<?php

namespace App\Services;

use App\Http\Middleware\SetLocale;
use App\Models\Cart;
use App\Models\ChatConversation;
use App\Models\Comercio;
use App\Support\ArmaPayloadOpenAI;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotService
{
    use ArmaPayloadOpenAI;

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

    /**
     * Consumo de la última llamada al modelo.
     *
     * La API lo devuelve en cada respuesta y hasta ahora se descartaba, así que no había
     * forma de saber cuánto cuesta realmente cada comercio: la cuota cuenta MENSAJES, y un
     * menú de 291 platos consume el doble que uno de 42 gastando lo mismo de cuota. Los
     * tokens de caché además dicen si el descuento del prompt caching está entrando de
     * verdad en producción, que hasta ahora solo se podía medir a mano.
     *
     * @var array<string, mixed>
     */
    private array $ultimoConsumo = [];

    /**
     * Qué puede pedirle el comercio que el asistente empuje ("Qué querés que destaque").
     *
     * Fuente ÚNICA de la lista: la usan el selector de la pantalla de edición, la validación
     * del controller y el prompt. Antes estaba escrita en los tres lados por separado, y
     * agregar un foco sin tocar la validación devolvía un 422 que el auto-guardado se comía
     * en silencio.
     *
     * Es una instrucción de venta, no un filtro: el foco NO decide qué platos existen ni
     * los busca por su cuenta. Eso es deliberado — el modelo tiene el menú completo delante
     * y lo resuelve mejor que cualquier lista de palabras nuestra, que se rompe distinto en
     * cada carta (probado: "Vino Malbec" caía en bebidas, "Timbal de Vegetales" en veganas).
     *
     * `veganas` y `sin_tacc` van SEPARADOS y no juntos como estaban. Un menú real trae una
     * categoría "CELIACOS" con bife de chorizo y lomo: son sin TACC y no tienen nada de
     * vegano. Pedirle al asistente "ofrecé opciones veganas o sin TACC" lo habilitaba a
     * cruzarlas, y son información de salud.
     */
    public const FOCOS = [
        'promos' => ['label' => 'Promos del día', 'prompt' => 'las promos del día'],
        'compartir' => ['label' => 'Para compartir', 'prompt' => 'opciones para compartir'],
        'veganas' => ['label' => 'Veganas/vegetarianas', 'prompt' => 'opciones veganas o vegetarianas'],
        'sin_tacc' => ['label' => 'Sin TACC/celíacos', 'prompt' => 'opciones sin TACC (aptas celíacos)'],
        'bebidas' => ['label' => 'Bebidas/tragos', 'prompt' => 'una bebida o trago para acompañar'],
        'postres' => ['label' => 'Postres', 'prompt' => 'un postre para cerrar'],
        'combos' => ['label' => 'Combos', 'prompt' => 'combos'],
    ];

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
            $this->ultimoConsumo = [];

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
        // En la vista previa del editor, sólo el menú que se está editando.
        if ($enEdicion = \App\Support\VistaPrevia::menu($comercio)) {
            return collect([$enEdicion]);
        }

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
                        // La categoría es parte de cómo lo busca el cliente: pide "cerveza"
                        // y el plato se llama "Corona Sin Alcohol", dentro de "Cervezas 0.0%".
                        'categoria' => trim((string) ($cat['nombre'] ?? '')),
                        'estrella' => ! empty($p['estrella']),
                    ];
                }
            }
        }

        return $this->traducirDescripciones($comercio, $platos);
    }

    /**
     * Cómo lo pide el cliente -> cómo puede figurar en la carta.
     *
     * El menú nunca dice "birra". Sin esto, la búsqueda no encuentra nada, el modelo queda
     * solo y contesta que no hay cerveza teniendo la categoría "Cervezas 0.0%".
     * Es a propósito una lista corta de habla rioplatense: no reemplaza al modelo
     * entendiendo lenguaje natural, solo le sirve en bandeja los casos más comunes.
     */
    private const SINONIMOS = [
        'birra' => ['cerveza'],
        'chupi' => ['cerveza', 'vino'],
        'gaseosa' => ['coca', 'pepsi', 'sprite', 'seven'],
        'refresco' => ['coca', 'pepsi', 'sprite'],
        'sanguche' => ['sandwich', 'sandwiches'],
        'sanguchito' => ['sandwich', 'sandwiches'],
        'jugo' => ['exprimido', 'jugo'],
        'licuado' => ['licuado', 'batido'],
        'fritas' => ['papas'],
        'celiaco' => ['tacc'],
        'celiaca' => ['tacc'],
        'gluten' => ['tacc'],
        'vegano' => ['vegano', 'veggie', 'veg'],
        'vegetariano' => ['vegetariano', 'veggie', 'veg'],

        // Inglés: el menú está en castellano, pero el cliente extranjero escribe en el
        // suyo. Con el asistente encendido el modelo lo resuelve; cuando contesta el
        // buscador solo (plan sin IA), sin esto no encuentra nada.
        'burger' => ['hamburguesa'],
        'fries' => ['papas'],
        'coffee' => ['cafe'],
        'beer' => ['cerveza'],
        'water' => ['agua'],
        'juice' => ['jugo', 'exprimido'],
        'salad' => ['ensalada'],
        'chicken' => ['pollo'],
        'dessert' => ['postre', 'torta'],
        'breakfast' => ['desayuno'],
    ];

    /**
     * Palabras que no sirven para buscar un plato: aparecen en cualquier pregunta.
     *
     * Incluye adjetivos y verbos de OPINIÓN, y no solo muletillas, porque los comercios le
     * ponen a los platos nombres con esas mismas palabras. Preguntando "¿con cuál queda
     * mejor?" se buscaba "mejor", aparecía un desayuno llamado "Des. El Mejor!" y el
     * asistente lo recomendaba como acompañamiento de un lomito.
     */
    private const PALABRAS_VACIAS = [
        'tienen', 'tiene', 'tenes', 'tenés', 'hay', 'quiero', 'quisiera', 'algo', 'alguna',
        'alguno', 'para', 'como', 'cual', 'cuales', 'esta', 'este', 'estan', 'sobre', 'menu',
        'menú', 'carta', 'plato', 'platos', 'opcion', 'opciones', 'porfa', 'favor', 'gracias',
        'hola', 'buenas', 'dame', 'traeme', 'venden', 'sirven', 'ustedes', 'nosotros',
        // Opinión y comparación
        'mejor', 'mejores', 'peor', 'bueno', 'buena', 'buenos', 'rico', 'rica', 'ricos',
        'ricas', 'sabroso', 'delicioso', 'ideal', 'favorito', 'especial', 'recomendas',
        'recomendás', 'recomienda', 'recomendable', 'sugeris', 'sugerís', 'decis', 'decís',
        'queda', 'quedan', 'gusta', 'gustan', 'conviene', 'combina', 'acompaña', 'prefiero',
        'preferis', 'elijo', 'elegir', 'probar', 'pruebo', 'comer', 'tomar', 'pedir',
        // Cantidad y tamaño: casi nunca son el plato en sí
        'grande', 'grandes', 'chico', 'chica', 'mediano', 'porcion', 'porción', 'completo',
        'completa', 'simple', 'doble', 'solo', 'sola',
    ];

    /**
     * Busca en el menú, con PHP, los platos que coinciden con lo que escribió el cliente.
     *
     * El modelo chico (gpt-4o-mini) no encuentra de forma confiable un plato suelto dentro
     * de un menú largo: preguntándole "tienen rabas?" contestaba que no, teniendo
     * "- Rabas — $26.000" en el contexto, y a la vez se las ofrecía a quien preguntaba por
     * calamares. Es una limitación conocida del modelo, no del prompt (gpt-4o sí acierta).
     *
     * En vez de confiar en que preste atención, se le entrega el resultado ya buscado. Es
     * determinístico, cuesta unos pocos tokens y funciona igual en cualquier modelo.
     *
     * @return array<int, array{nombre:string, precio:string}>
     */
    /**
     * ¿El menú tiene algo que coincida con este texto?
     *
     * Lo usa el panel para descartar los faltantes que el modelo declaró mal: si el
     * asistente anotó "rabas" y las rabas están en la carta, eso no es demanda del cliente
     * sino una falla nuestra (ver AnalyticsInsightsService::demandaInsatisfecha()).
     */
    public function tieneEnElMenu(Comercio $comercio, string $texto): bool
    {
        return ! empty($this->platosQueCoinciden($comercio, $texto, 1));
    }

    /**
     * La misma búsqueda que se le sirve al modelo, para quien NO lo usa.
     *
     * La usa el buscador de la carta (`BuscadorMenuController`): plurales, acentos, jerga y
     * categorías ya están resueltos acá, y duplicar esa lógica en otro lado sería
     * garantizar que las dos se separen con el primer arreglo.
     *
     * @return array<int, array{nombre:string, precio:string}>
     */
    public function buscarEnElMenu(Comercio $comercio, string $texto, int $max = 5): array
    {
        return $this->platosQueCoinciden($comercio, $texto, $max);
    }

    private function platosQueCoinciden(Comercio $comercio, string $mensaje, int $max = 5): array
    {
        $normalizar = function (string $t): string {
            $t = mb_strtolower($t, 'UTF-8');
            $t = strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);

            return (string) preg_replace('/[^a-z0-9 ]+/', ' ', $t);
        };

        // Se buscan palabras de 4+ letras y se comparan por raíz, para que "rabas" matchee
        // "Rabas" y "empanada" matchee "Empanadas".
        $raiz = fn (string $p) => mb_strlen($p) > 4 && str_ends_with($p, 's') ? mb_substr($p, 0, -1) : $p;

        $buscadas = collect(preg_split('/\s+/', $normalizar($mensaje), -1, PREG_SPLIT_NO_EMPTY))
            ->filter(fn ($p) => mb_strlen($p) >= 4 && ! in_array($p, self::PALABRAS_VACIAS, true))
            // Cada palabra vale también por sus sinónimos: "birra" busca "cerveza".
            ->flatMap(fn ($p) => array_merge([$p], self::SINONIMOS[$p] ?? self::SINONIMOS[$raiz($p)] ?? []))
            ->map($raiz)
            ->unique();

        if ($buscadas->isEmpty()) {
            return [];
        }

        // Se busca en el nombre y también en el de la categoría: el cliente pide "cerveza"
        // y el plato se llama "Corona Sin Alcohol", dentro de "Cervezas 0.0%".
        $enPalabras = function (string $texto) use ($normalizar, $raiz): array {
            $tokens = preg_split('/\s+/', $normalizar($texto), -1, PREG_SPLIT_NO_EMPTY) ?: [];

            return array_map($raiz, $tokens);
        };

        /**
         * ¿Alguna palabra del plato coincide con la que buscó el cliente?
         *
         * Además de la palabra completa se aceptan dos cosas, que aparecieron probando el
         * buscador sin asistente: el ARRANQUE de una palabra ("hamburg" → hamburguesa,
         * porque la gente escribe hasta que aparece el resultado) y UN ERROR DE TIPEO
         * ("hamburgesas" → hamburguesa). Con el modelo encendido esto no hacía falta: él
         * tiene la carta entera delante y resuelve el tipeo solo.
         *
         * La tolerancia depende del largo. En palabras cortas una letra ya es otra palabra
         * ("coca" y "boca"), así que abajo de 5 letras se exige exacto.
         */
        $coincide = function (string $buscada, array $tokens): bool {
            $largo = mb_strlen($buscada);
            $tolerancia = $largo >= 8 ? 2 : ($largo >= 5 ? 1 : 0);

            foreach ($tokens as $token) {
                if ($token === $buscada) {
                    return true;
                }

                if ($largo >= 4 && str_starts_with($token, $buscada)) {
                    return true;
                }

                if ($tolerancia > 0
                    && abs(mb_strlen($token) - $largo) <= $tolerancia
                    && levenshtein($token, $buscada) <= $tolerancia) {
                    return true;
                }
            }

            return false;
        };

        // Se separan los dos tipos de coincidencia porque no valen lo mismo: preguntando
        // por "tortas" pesa más un plato que se llame así que uno de la categoría
        // "Panadería y tortas" (que devolvía ocho medialunas y tapaba las tortas de verdad).
        $porNombre = [];
        $porCategoria = [];

        foreach ($this->platosDelMenu($comercio) as $plato) {
            $nombre = $enPalabras($plato['nombre']);
            $categoria = $enPalabras((string) ($plato['categoria'] ?? ''));
            $fila = ['nombre' => $plato['nombre'], 'precio' => $plato['precio']];

            foreach ($buscadas as $b) {
                if ($coincide($b, $nombre)) {
                    $porNombre[] = $fila;
                    break;
                }
                if ($coincide($b, $categoria)) {
                    $porCategoria[] = $fila;
                    break;
                }
            }
        }

        // Tope bajo por defecto: alcanza para que el modelo sepa que el plato EXISTE, que es
        // para lo único que está esta búsqueda. Cuantos más le pasemos, más tienta a
        // nombrarlos a todos y más tarjetas le caen encima al cliente. El chat pide 6 (lo
        // máximo que muestra un pedido específico) y en "ver más" 25, porque ahí el cliente
        // pidió justamente la lista entera.
        return array_slice(array_merge($porNombre, $porCategoria), 0, $max);
    }

    /**
     * Traduce las descripciones de los platos al idioma del cliente (cacheado).
     *
     * Solo la DESCRIPCIÓN: el nombre queda en el idioma del menú porque el cliente se lo
     * tiene que poder pedir al camarero, y porque es la clave con la que se resuelven las
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
     * Consumo de la última llamada al modelo, para guardarlo junto a la conversación.
     * Vacío si la respuesta no salió del modelo (fallback) o si la API no informó nada.
     *
     * @return array<string, mixed>
     */
    public function ultimoConsumo(): array
    {
        return $this->ultimoConsumo;
    }

    /**
     * Normaliza el `usage` que devuelve OpenAI.
     *
     * `cached_tokens` es la parte del prompt que entró por el prompt caching (se cobra más
     * barato) y `reasoning_tokens` es lo que los modelos GPT-5 gastan pensando antes de
     * escribir: no se ve en la respuesta pero se paga como salida.
     *
     * @param  array<string, mixed>|null  $usage
     */
    private function registrarConsumo(?array $usage, string $model): void
    {
        if (! is_array($usage) || empty($usage)) {
            return;
        }

        $this->ultimoConsumo = [
            'modelo' => $model,
            'tokens_entrada' => (int) ($usage['prompt_tokens'] ?? 0),
            'tokens_cacheados' => (int) ($usage['prompt_tokens_details']['cached_tokens'] ?? 0),
            'tokens_salida' => (int) ($usage['completion_tokens'] ?? 0),
            'tokens_razonamiento' => (int) ($usage['completion_tokens_details']['reasoning_tokens'] ?? 0),
        ];
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
    /**
     * Cuántas consultas anteriores se le recuerdan al modelo.
     *
     * Cada una son DOS entradas (la del cliente y la respuesta completa del bot), así que
     * el costo sube rápido: con 10 medimos ~1.275 tokens de entrada extra por mensaje, en
     * cada mensaje. Con 5 alcanza de sobra para que siga el hilo de una charla en la mesa
     * —nadie retoma algo que preguntó diez mensajes atrás— y se paga la mitad.
     */
    private const HISTORIAL_MENSAJES = 5;

    private function getConversationHistory(Comercio $comercio, string $sessionId, int $limit = self::HISTORIAL_MENSAJES): array
    {
        try {
            // Obtener los últimos N mensajes de la sesión (excluyendo el actual)
            $conversations = ChatConversation::where('comercio_id', $comercio->id)
                ->where('session_id', $sessionId)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get(['user_message', 'bot_response', 'platos_ofrecidos', 'created_at'])
                ->reverse(); // Invertir para tener el orden cronológico correcto

            $history = [];
            foreach ($conversations as $conv) {
                // Agregar mensaje del usuario
                $history[] = [
                    'role' => 'user',
                    'content' => $conv->user_message,
                ];

                // La respuesta se guarda sin el marcador, y el texto ya no nombra los platos
                // (las tarjetas los muestran). Sin volver a ponérselo, el modelo no sabe qué
                // le mostró al cliente: "Ver más" repetía las nueve tortas que ya había dado.
                $respuesta = $conv->bot_response;
                if (! empty($conv->platos_ofrecidos)) {
                    $respuesta .= "\n[[PLATOS: ".implode(' | ', $conv->platos_ofrecidos).']]';
                }

                $history[] = [
                    'role' => 'assistant',
                    'content' => $respuesta,
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
     * Nombres de los platos marcados como estrella en el menú activo.
     *
     * @return array<int, string>
     */
    public function platosEstrella(Comercio $comercio): array
    {
        return array_values(array_map(
            fn ($plato) => $plato['nombre'],
            array_filter($this->platosDelMenu($comercio), fn ($plato) => ! empty($plato['estrella']))
        ));
    }

    /**
     * Bloque de indicaciones personalizadas del local (tono, focos de venta, estrellas, detalles).
     */
    private function indicacionesLocal(Comercio $comercio): string
    {
        // Sin `return` temprano cuando no hay chatbot_config: las estrellas ya no viven ahí
        // sino en el menú, y un comercio que marcó estrellas sin tocar la personalidad las
        // perdía del prompt.
        $c = is_array($comercio->chatbot_config) ? $comercio->chatbot_config : [];

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

        // Las estrellas salen del MENÚ, no de una lista de nombres en chatbot_config: así
        // no puede quedar una apuntando a un plato renombrado o borrado, que le pedía al
        // modelo recomendar algo que no está en la carta.
        $estrellas = $this->platosEstrella($comercio);
        if (! empty($estrellas)) {
            $lineas[] = 'Recomendá primero, cuando encajen con lo que pide el cliente, estos platos estrella: '.implode(', ', $estrellas).'.';
        }

        if (! empty($c['focos']) && is_array($c['focos'])) {
            $f = array_values(array_filter(array_map(
                fn ($x) => self::FOCOS[$x]['prompt'] ?? null,
                $c['focos']
            )));
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
    /**
     * Qué día y hora es, y si el local está abierto. Va en el bloque VARIABLE (cambia en
     * cada request, no puede entrar en el prefijo cacheado).
     *
     * Sin esto el asistente recomendaba una cena a las 10 de la mañana y esquivaba las
     * preguntas de horario aunque el comercio los tuviera cargados: no los veía por ningún
     * lado. `estaAbierto()` devuelve null cuando no hay horarios, y ahí no se dice nada —
     * mejor callarse que afirmar que está abierto sin saberlo.
     */
    private function ahoraMismo(Comercio $comercio): string
    {
        $dias = ['Dom', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
        $ahora = now();
        $dia = $dias[(int) $ahora->dayOfWeekIso] ?? $ahora->englishDayOfWeek;

        $texto = "\nAHORA MISMO: es {$dia} y son las {$ahora->format('H:i')}. Tenelo en cuenta al recomendar "
            .'(a la mañana un desayuno, a la tarde una merienda, a la noche una cena): no ofrezcas algo '
            ."propio de otro momento del día salvo que el cliente lo pida.\n";

        $abierto = $comercio->estaAbierto();
        if ($abierto === true) {
            $texto .= "El local está ABIERTO ahora. Si preguntan por el horario, decilo con esta info.\n";
        } elseif ($abierto === false) {
            $proxima = $comercio->proximaApertura();
            $texto .= 'El local está CERRADO ahora'.($proxima ? ", abre {$proxima}" : '')
                .". Si preguntan por el horario, decilo con esta info; podés igual mostrarles el menú.\n";
        }

        return $texto;
    }

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

        return "\nDATO DE CONTEXTO — YA HAY ESTO EN EL PEDIDO DE LA MESA: {$items}.\n".
            'Es SOLO contexto, NO es lo que tenés que recomendar ahora. No lo vuelvas a ofrecer ni lo nombres, '
            .'salvo que el cliente pregunte específicamente qué sumar, qué tomar o algo para acompañar: recién ahí '
            .'usalo para elegir mejor, recomendando DENTRO del tipo que te pide lo que mejor marida con eso. '
            ."Nunca dejes que lo que ya tiene en el pedido te desvíe del tipo de plato que te está pidiendo en este mensaje.\n"
            .'⚠️ El pedido es DE LA MESA, no de una persona: un café, un flan y una porción de queso juntos son tres '
            .'personas distintas, no una mala combinación. NUNCA opines sobre si lo que ya pidieron combina entre sí, '
            ."ni intentes que lo nuevo pegue con todo lo anterior.\n";
    }

    /**
     * Prompt completo (system + user) concatenado. Solo para debug/preview.
     */
    private function createPrompt(Comercio $comercio, string $contexto, string $message): string
    {
        return $this->promptSistema($comercio, $contexto)."\n\n".$this->promptUsuario($comercio, $message);
    }

    /**
     * Bloque ESTÁTICO del prompt, para el rol `system`. Nada dinámico (carrito, pregunta,
     * modo, idioma concreto) puede entrar acá.
     *
     * **El menú va PRIMERO, antes de las reglas.** Antes iba al final y el modelo negaba
     * platos que tenía delante: preguntándole "tienen rabas?" contestaba que no, con
     * "- Rabas — $26.000" en el contexto (verificado también con gpt-4o, no es cosa de
     * mini). Con el menú solo, sin reglas, las encontraba y hasta decía el precio: lo que
     * lo rompía eran 6.000 caracteres de reglas defensivas leídas ANTES del dato.
     *
     * Eso cuesta el prefijo compartido entre comercios para el prompt caching, que igual
     * era menor: el menú es estable por comercio, así que cada uno sigue cacheando el suyo,
     * que es la parte grande.
     */
    private function promptSistema(Comercio $comercio, string $contexto): string
    {
        $indicaciones = $this->indicacionesLocal($comercio);

        return "Sos el anfitrión gastronómico de este restaurante: conocés el menú al detalle, recomendás como un buen camarero y sabés maridar como un sommelier. Tu misión es que el cliente encuentre EXACTAMENTE lo que busca y arme un pedido que lo deje feliz, siempre con platos REALES del menú.

═══════════ EL MENÚ ═══════════
{$contexto}
{$indicaciones}
═══════════ FIN DEL MENÚ ═══════════

BUSCAR ANTES DE NEGAR — REGLA #0:
- Cuando el cliente nombra algo, recorré el menú COMPLETO antes de contestar. Puede estar con otro nombre, dentro de una categoría que no esperabas, o escrito distinto (plural, mayúsculas, con o sin acento).
- Compará por SIGNIFICADO, no por letra: \"birra\" es cerveza, \"papas\" son papas fritas, \"gaseosa\" es cualquier refresco del menú.
- Recién si de verdad no aparece, decilo y ofrecé lo más parecido que SÍ tengas.

⚠️ REGLA #1 — IDENTIFICÁ QUÉ CLASE DE PEDIDO ES. Hay dos y se responden al revés:

(a) PEDIDO ESPECÍFICO — el cliente dice QUÉ TIPO quiere: \"algo dulce\", \"un postre\", \"una bebida\", \"algo salado\", \"una entrada\", \"algo sin TACC\", \"una pizza\".
    → SOLO platos de ESE tipo, nada de otro tipo ni para acompañar. Dulce → SOLO postres, helados, tortas; NUNCA un salado. Salado → NUNCA un postre. Para tomar → SOLO bebidas. Un momento puntual (entrada, principal, guarnición) → SOLO ese momento.
    → Esta regla manda sobre la venta cruzada, sobre los platos estrella del local y sobre lo que ya tenga en el carrito. Si el menú no tiene nada de ese tipo, decilo; y si ofrecés otra cosa, avisá que es de otro tipo (nunca lo cambies en silencio).

(b) PEDIDO ABIERTO O DE GRUPO — NO dice el tipo: \"qué me recomendás\", \"tengo hambre\", \"somos 4\", \"venimos 6\", \"armanos algo para compartir\".
    → Acá SÍ armá una propuesta COMPLETA: algo principal o para compartir + una bebida + un postre para cerrar. No te limites a un solo tipo: es lo que más lo ayuda y lo que mejor vende.
    → Si dice CUÁNTAS PERSONAS son, priorizá las promos o combos dimensionados para esa cantidad y ajustá las cantidades a la mesa (4 personas → una pizza grande y una docena de empanadas, no una porción). Si la promo ya incluye bebida, no sumes otra: decí qué incluye.

SOLO INFORMACIÓN REAL DEL MENÚ — NO INVENTES NADA:
- Mencioná ÚNICAMENTE platos que aparecen en el menú de arriba.
- Si el plato TIENE descripción en el menú, usala y mejorala sutilmente con lenguaje gourmet, sin cambiar la información.
- Si NO tiene descripción, podés hacer una muy breve basada SOLO en el nombre (\"Milanesa\" → \"milanesa empanada\"). NO inventes tipo de carne, ingredientes, salsas, guarniciones ni preparaciones.
- TAGS (sin TACC, vegano, vegetariano, sin lactosa...): mencionalos SOLO si están escritos explícitamente en el menú para ese plato. NUNCA los deduzcas del tipo de producto ni de tu conocimiento general: una bebida o un té no es sin TACC porque sí, y algo que no menciona carne no es vegano porque sí.
- Al buscar por tag compará por SIGNIFICADO, no por formato: \"sin tacc\" también matchea SIN_TACC, sin gluten, gluten free, celíaco. Cada menú usa sus propias etiquetas, así que adaptate a las que ves. Devolvé TODOS los platos del menú que coincidan.
- Si ningún plato tiene el tag que piden, decí claramente que no tenés esa información en el menú y que conviene confirmarlo con el restaurante. NO adivines.

IDIOMA (IMPORTANTE):
- Más abajo, junto a la pregunta, se te indica EN QUÉ IDIOMA responder. Respetalo siempre y no le avises al cliente que cambiaste de idioma: simplemente escribí en el suyo.
- ⚠️ EXCEPCIÓN QUE NO SE NEGOCIA: los NOMBRES DE LOS PLATOS van SIEMPRE tal como figuran en el menú, en su idioma original, aunque estés escribiendo en otro idioma. El cliente tiene que poder pedirle ese plato al camarero, que no habla su idioma. Traducí la descripción, nunca el nombre.
  Ejemplo en inglés: \"The Milanesa napolitana is a classic here — breaded beef with ham, tomato and melted cheese.\" (el nombre queda en español, la explicación va en inglés).
- Las categorías sí podés nombrarlas en el idioma del cliente (\"POSTRES\" → \"desserts\").
- Los tags de alergias y dietas (sin TACC, vegano, sin lactosa) mencionalos con el término del menú y, si hace falta, aclarás entre paréntesis. Nunca cambies su significado: es información de salud.

CRITERIO DE ANFITRIÓN — TENÉS PERMISO PARA DECIR QUE NO:
- Si te piden una OPINIÓN (\"¿esto va bien con aquello?\", \"¿cuál me conviene?\", \"¿es buena idea?\"), contestá la pregunta con criterio propio. No listes el menú: opiná y recomendá UNA cosa, explicando en una frase por qué.
- Si la combinación no funciona, decilo con amabilidad y ofrecé la que sí. Un buen camarero no te deja pedir algo que no vas a disfrutar; para eso te preguntan. Ejemplos de criterio: un café con leche es de desayuno o merienda y no acompaña un plato salado fuerte; algo dulce cierra la comida, no la abre; una milanesa pide una bebida fresca, no una infusión.
- NO adules. Contestar \"¡excelente idea!\" a cualquier cosa te hace perder credibilidad y el cliente termina pidiendo algo que no le va a gustar.
- Si te preguntan por algo que el menú no tiene pero es una combinación rara o inventada, aclará que no existe como plato y llevá la conversación a lo que sí tenés.
- El criterio es para lo que el cliente está por elegir, NO para lo que ya tiene pedido: eso puede ser de varias personas de la misma mesa.

SI EL MENÚ NO LO DICE, VOS NO LO SABÉS:
- Tamaño, peso, porciones, cuánta gente come, tiempo de cocción, si es picante, si viene frío o caliente, alérgenos: si no está escrito en el menú, NO lo estimes ni lo deduzcas por el tipo de plato. Decí que en la carta no figura y que lo confirmen con el local.
- Vale para \"¿de qué tamaño son las empanadas?\" igual que para \"¿tiene maní?\". Improvisar un tamaño genera un reclamo en la mesa; improvisar un alérgeno es un problema de salud.
- Quedar corto es preferible: \"eso no lo tengo en la carta, consultalo con el camarero\" es una respuesta perfectamente buena.

CUÁNTOS PLATOS — SEGÚN LO QUE PIDE (van en el marcador del final, ver más abajo):
- Pedido específico (caso a de la REGLA #1: \"café\", \"hamburguesas\", \"algo dulce\", \"cervezas\"): TODOS los que de verdad respondan, hasta 6, primero los platos estrella y los que mejor encajan. Si hay más de 6, elegí los 6 mejores: debajo hay un botón \"Ver más opciones\" para el resto. No te quedes con dos si el menú tiene seis.
- Pedido abierto o de grupo (caso b): una propuesta de mesa de 3 o 4 platos.
- Opinión (\"¿esto va bien con aquello?\"): 1, el que recomendás.
- Si pide ver TODOS los de algo: todos los que coincidan.
- Saludo, horarios o una pregunta que no es de platos: ninguno.

CÓMO ESCRIBIR — EL TEXTO NO ES LA LISTA:
- ⚠️ DEBAJO DE TU RESPUESTA, la app le muestra al cliente una TARJETA por cada plato del marcador, con el NOMBRE, la FOTO, los INGREDIENTES y el PRECIO. Todo eso YA LO VE: si lo repetís en el texto, lee dos veces lo mismo.
  → NO enumeres en el texto los platos que van en el marcador. Nombrá como mucho UNO, el que destacás, y decí por qué.
  → NO describas de qué está hecho el plato. NO escribas precios.
  → Usá el texto para lo que la tarjeta NO puede decir: cuál elegir si duda, para qué momento es, qué tiene de especial.
  → Ejemplo. Mal: \"Podés elegir Cafe con leche, Cafe cortado o la PROMO Cafe con leche + 2 Med. jyq\" (el cliente lee tres nombres y abajo ve las mismas tres tarjetas). Bien: \"Para la merienda tenés varias opciones ☕ Si venís con hambre, la PROMO Cafe con leche + 2 Med. jyq es la más completa.\"
- 1 o 2 frases, no más, aunque el marcador lleve seis platos. Si piden \"ver más\" o el menú completo, el texto sigue siendo una línea: los platos van en el marcador.
- Sos entusiasta y gourmet, pero en prosa natural y cálida: nunca un catálogo ni una lista con guiones.
- El plato que nombres, con su NOMBRE EXACTO del menú. NO pegues imágenes ni links.
- No nombres platos de paso, ni para descartarlos, ni como ejemplo de sabores que no estás recomendando de verdad: el cliente los busca abajo y no los encuentra.
- VENTA SUGERIDA: según la REGLA #1. Caso (b) → mesa completa con bebida y postre. Caso (a) → NADA de otro tipo, ni siquiera como cierre o \"broche de oro\": si pide qué tomar, recomendá SOLO bebidas (la que mejor marida con lo que ya tiene); si pide un postre, SOLO postres. El maridaje existe para elegir mejor DENTRO del tipo que pidió, no para agregar otros tipos. Recién si el pedido es abierto (caso b) sumás algo de otra categoría.
- Entendés el habla argentina cotidiana (\"birra\", \"chupi\", \"algo para tomar\", \"papas\", \"fritas\", \"una coca\") y la traducís a lo que realmente hay en el menú.
- LENGUAJE NEUTRAL: el cliente puede estar DENTRO o FUERA del local. NUNCA uses \"ven\", \"te esperamos\", \"cuando vengas\". Usá \"Probá...\", \"Pedí...\", \"Disfrutá...\", \"Te recomiendo...\", \"No te lo pierdas\".
- Un emoji de categoría queda bien, con moderación (🍕 pizzas, 🍝 pastas, 🥗 ensaladas, 🍖 carnes, 🍤 mariscos, 🍷 vinos, 🍺 cervezas, 🍰 postres, ☕ café).

CÓMO CERRAR LA RESPUESTA (OBLIGATORIO):
Primero una intro breve en prosa, natural, como le hablarías al cliente.
Y en la ÚLTIMA LÍNEA, SIEMPRE este marcador, que el cliente NO ve:
[[PLATOS: Nombre exacto del menú | Otro nombre exacto del menú]]
- Copiá cada nombre EXACTAMENTE como figura en el menú, con su tamaño o variante y hasta el último detalle (si el menú dice \"Coca Cola 354 ml\", va \"Coca Cola 354 ml\", no \"Coca Cola\"; si dice \"Muzzarella (Grande)\", va así y no \"Pizza Muzzarella grande\").
- SIEMPRE en el idioma original del menú, aunque estés respondiendo en inglés o portugués: si traducís el nombre, el plato no se encuentra y el cliente se queda sin poder pedirlo.
- Solo los platos que estás recomendando de verdad, cuántos según \"CUÁNTOS PLATOS\", del que más le recomendás al que menos: las tarjetas salen en ese orden. NO lo que ya tiene en el pedido, ni platos nombrados al pasar o para descartar.
- Si no recomendás ninguno (un saludo, una consulta de horarios), cerrá con [[PLATOS: ]] vacío.
- Es la ÚNICA lista: el texto de arriba no la repite, ni con guiones ni dentro de las frases ni como resumen final.
Ese marcador es lo ÚNICO que se convierte en tarjetas con foto, precio y botón \"Lo quiero\": un plato que nombrás en el texto y no está acá, el cliente no lo puede pedir. Si te olvidás el marcador, no puede pedir nada.

Y SI el cliente pidió algo que el menú NO tiene —después de haberlo buscado de verdad (REGLA #0)—, agregá en la línea siguiente un segundo marcador, que tampoco ve:
[[FALTA: lo que pidió y no está]]
- En 1 a 3 palabras y en el idioma del menú: \"sushi\", \"opciones veganas\", \"cerveza sin alcohol\".
- Va IGUAL aunque le hayas ofrecido alternativas: sirve para que el local sepa qué le piden y no tiene.
- Si el menú SÍ lo tenía, NO pongas este marcador.";
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
     *
     * Tiene que quedar CORTO. Acá vivían el checklist "ANTES DE ENVIAR" y las reglas del
     * formato de salida, que son idénticas siempre: ~700 tokens repetidos en cada consulta
     * que además, por ir al final, quedaban fuera del prefijo que cachea OpenAI. Se
     * mudaron al bloque estático y quedó solo un recordatorio de una línea, porque lo
     * último que lee el modelo pesa más.
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
                ."- Si el cliente pide ver TODAS/más opciones, poné en el marcador TODOS los platos de la categoría \"{$scope}\" (sin límite). Si no, hasta 6 bien elegidos de esa misma categoría. En los dos casos el texto es una línea y no los enumera: las tarjetas ya los muestran.\n"
                ."- Si esa categoría no existe en el menú, decilo con amabilidad y ofrecé ver otra categoría real.\n";
        } elseif ($this->modoRespuesta === 'ver_mas') {
            // El problema de "Ver más" no era que mostrara poco: es que mostraba LO MISMO.
            // Arrancaba de cero, volvía a elegir los mejores (los que ya había dicho) y
            // sumaba uno, con el mismo párrafo de introducción. El cliente tocaba esperando
            // descubrir algo y releía lo que ya tenía arriba.
            $modoExtra = "\n\n⚠️ MODO VER MÁS — ESTA REGLA MANDA SOBRE TODAS LAS ANTERIORES:\n"
                ."El cliente ya vio tu respuesta anterior y tocó \"Ver más opciones\" sobre: \"{$scope}\".\n"
                ."- NO REPITAS los platos que ya nombraste antes en esta conversación. El cliente los tiene arriba en la pantalla: repetirlos hace que el botón no sirva para nada.\n"
                ."- Mostrá SOLO los que le faltan ver, y sin volver a explicar de qué se trata: una línea corta de texto, sin enumerarlos, y los platos nuevos en el marcador (las tarjetas ya los muestran).\n"
                ."- Si ya no queda ninguno por mostrar, decilo en UNA frase (\"esas son todas las que tenemos\") y ofrecele una categoría REAL del menú que le pueda interesar, para que la charla siga en algún lado.\n"
                ."- El marcador [[PLATOS: ...]] va SOLO con los platos nuevos de ESTE mensaje. Si repetís ahí los anteriores, al cliente le vuelven a aparecer las mismas tarjetas y no entiende si ya los agregó o no. Si no hay ninguno nuevo, cerrá con [[PLATOS: ]] vacío.\n"
                ."- NO vuelques el menú completo ni agregues cosas sin relación con \"{$scope}\".\n";
        }

        $idioma = $this->idiomaRespuesta();
        $anclaje = $this->anclajeIdioma();

        // La búsqueda ya la hizo PHP: el modelo no tiene que encontrar nada, solo usarlo.
        // En "ver más" se busca por la consulta ORIGINAL (el mensaje es el texto sintético
        // del botón, que no dice nada) y se traen todas: es justo lo que el cliente pidió.
        $enVerMas = $this->modoRespuesta === 'ver_mas';
        // Fuera de "ver más" el tope es 6, lo máximo que muestra un pedido específico. Se
        // probó con 8 para darle margen y gpt-4o-mini metía las 8 en el marcador: lo que
        // recibe en esta lista lo recita entero.
        $coinciden = $this->platosQueCoinciden($comercio, $enVerMas ? $scope : $message, $enVerMas ? 25 : 6);
        $encontrados = '';
        if (! empty($coinciden)) {
            $lista = implode("\n", array_map(
                fn ($p) => '- '.$p['nombre'].($p['precio'] !== '' ? ' — '.$p['precio'] : ''),
                $coinciden
            ));
            // El "si claramente no es lo que pidió, ignoralo" es la válvula de escape para
            // los falsos positivos de la búsqueda: coincide una palabra suelta del nombre y
            // no el plato. Sin eso el modelo lo mete a la fuerza porque se lo damos por hecho.
            // "Es referencia, no una lista para recitar" no es un detalle de redacción: sin
            // eso el modelo nombraba los seis lomitos que le pasábamos, y el cliente que
            // preguntaba si un lomito iba bien con café se comía ocho tarjetas de golpe.
            $encontrados = $enVerMas
                // Acá sí es la lista: el cliente tocó el botón justamente para verla entera.
                ? "\n\n✅ BÚSQUEDA YA HECHA — ESTO ES TODO lo que hay en el menú para \"{$scope}\":\n{$lista}\n"
                    ."Recorré esa lista una por una y poné en el marcador TODAS las que no hayas recomendado en tus respuestas anteriores. No te quedes con dos: el cliente tocó el botón para ver el resto.\n"
                    ."Recién si TODAS las de la lista ya aparecieron antes, decí que esas son todas y ofrecé otra categoría real del menú.\n"
                : "\n\n✅ BÚSQUEDA YA HECHA EN EL MENÚ — estos platos coinciden con lo que escribió:\n{$lista}\n"
                    ."Es información de referencia, NO una lista para recitar:\n"
                    ."- Si preguntó una OPINIÓN (si algo combina, cuál conviene, si es buena idea): contestá la pregunta y en el marcador va UN solo plato, el que recomendás. Esta lista no se usa.\n"
                    ."- Si buscó un tipo de plato puntual: al marcador van todos los que de verdad respondan, hasta 6.\n"
                    ."- Si es un pedido abierto o de grupo: 3 o 4, armando la mesa.\n"
                    ."Si es lo que pidió: existe, NO digas que no lo tenés ni uses [[FALTA]] para eso.\n"
                    ."Si claramente NO es lo que pidió (coincidió una palabra suelta del nombre), ignoralo.\n";
        }

        return "{$carrito}{$this->ahoraMismo($comercio)}
IDIOMA DE LA RESPUESTA: {$idioma}. {$anclaje} (los nombres de los platos NO se traducen).

PREGUNTA DEL CLIENTE: {$message}
{$encontrados}{$modoExtra}
Respondé en PROSA, con 1 o 2 frases. En el TEXTO nombrá como mucho UN plato (el que destacás); en el MARCADOR van TODOS los que recomendás, cuántos según \"CUÁNTOS PLATOS\": la app los muestra como tarjetas con nombre, foto y precio. Antes de decir que algo no está, buscalo en el menú. Cerrá con [[PLATOS: ...]] y, si de verdad no lo tenés, con [[FALTA: ...]].";
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
     * Arma el body de /chat/completions según lo que acepta CADA familia de modelos.
     *
     * Los GPT-5 son modelos de razonamiento y rechazan con HTTP 400 cuatro parámetros que
     * los GPT-4 sí aceptan: `max_tokens` (piden `max_completion_tokens`), `temperature`
     * distinta del default, `presence_penalty` y `frequency_penalty`. Verificado uno por
     * uno contra la API: mandar el payload viejo a gpt-5.6-luna devuelve 400 en TODAS las
     * consultas, o sea que cambiar el modelo del plan sin esto deja el chat muerto.
     *
     * Los tres parámetros que se pierden estaban puestos para variar el vocabulario; en las
     * pruebas contra el menú real las respuestas siguieron saliendo bien sin ellos.
     *
     * @param  array<int, array{role:string, content:string}>  $messages
     * @return array<string, mixed>
     */
    /**
     * Llamar a la API de OpenAI con historial de conversación
     */
    private function callOpenAI(string $system, string $userMessage, array $conversationHistory = [], string $model = 'gpt-4o-mini', int $maxTokens = 900): string
    {
        // Verificar si la API key está configurada
        if (empty($this->openaiApiKey)) {
            throw new \Exception('OpenAI API key no configurada');
        }

        $payload = $this->payloadOpenAI($model, $this->armarMensajes($system, $userMessage, $conversationHistory), $maxTokens);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->openaiApiKey,
            'Content-Type' => 'application/json',
        ])->timeout(20)->post($this->openaiBaseUrl.'/chat/completions', $payload);

        if ($response->successful()) {
            $data = $response->json();
            $this->registrarConsumo($data['usage'] ?? null, $model);

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
        $this->ultimoConsumo = [];

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

        $payload = $this->payloadOpenAI($model, $this->armarMensajes($system, $userMessage, $conversationHistory), $maxTokens, true);

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

                // El chunk del consumo llega al final, con `choices` vacío. Solo aparece
                // porque el payload pide `stream_options.include_usage`.
                if (isset($json['usage'])) {
                    $this->registrarConsumo($json['usage'], $model);
                }

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
