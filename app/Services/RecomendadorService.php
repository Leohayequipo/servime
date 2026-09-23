<?php

namespace App\Services;

use App\Models\Comercio;
use App\Models\Menu;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Recomendaciones calculadas UNA vez por carta, no en cada consulta.
 *
 * La idea: el trabajo de "con qué va bien un pancho" no cambia entre un cliente y el
 * siguiente, así que no hay por qué pagarlo mil veces. La IA lee la carta cuando se
 * procesa el menú y deja escrito, por plato, con qué otros platos DE ESA MISMA CARTA
 * conviene pedirlo. Después, en la mesa, todo se sirve desde esa tabla:
 *
 * - cuesta cero por consulta y contesta en milisegundos, no en ~2,7 segundos;
 * - no se puede quedar sin cupo ni caerse con OpenAI;
 * - no puede inventar, porque lo que se guarda pasó por `validar()` contra la carta real.
 *
 * Lo que NO hace: entender una pregunta libre. Para eso está el asistente con IA. Esto
 * responde lo que se previó, y por eso el texto que arma es corto y no finge saber más.
 */
class RecomendadorService
{
    use \App\Support\ArmaPayloadOpenAI;

    /**
     * Techo de salida. Generoso a propósito: en un modelo de razonamiento, los tokens
     * que piensa salen de acá, y si se queda corto la respuesta vuelve cortada y sin JSON.
     */
    private const MAX_TOKENS = 6000;

    /**
     * Platos por llamada. La carta entera viaja igual como catálogo para elegir.
     *
     * 25 y no 40 porque con lotes grandes el modelo contesta sólo por algunos y se saltea
     * el resto en silencio: con 40 devolvió 12 platos de 151.
     */
    private const LOTE = 25;

    /** Tope de platos por carta, para acotar el costo de una carta gigante. */
    private const MAX_PLATOS = 250;

    /** Complementos por plato. Más que esto no entra en una línea del menú. */
    private const MAX_COMPLEMENTOS = 3;

    public function __construct(private ChatbotService $chatbot) {}

    /**
     * El modelo que arma las recomendaciones (config/servime.php).
     *
     * No sale del plan del comercio, como el del chat: esto corre una vez por carta y lo
     * pagamos igual para todos, así que va el que mejor entienda la carta y no el más
     * barato por mensaje.
     */
    private function modelo(): string
    {
        return (string) config('servime.modelo_recomendaciones', 'gpt-4o-mini');
    }

    /**
     * Una llamada a /chat/completions pidiendo JSON, con el body que acepta cada familia
     * de modelos (los GPT-5 rechazan `temperature` y `max_tokens` con HTTP 400).
     *
     * @return array<string, mixed>
     */
    private function pedirJson(string $system, string $user, string $apiKey, int $maxTokens = self::MAX_TOKENS): array
    {
        $payload = $this->payloadOpenAI(
            $this->modelo(),
            [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            $maxTokens,
            false,
            ['temperature' => 0, 'presence_penalty' => 0, 'frequency_penalty' => 0],
        ) + ['response_format' => ['type' => 'json_object']];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(180)->post(config('services.openai.base_url', 'https://api.openai.com/v1').'/chat/completions', $payload);

            if (! $response->successful()) {
                Log::error('Recomendador: error OpenAI', [
                    'status' => $response->status(),
                    'modelo' => $this->modelo(),
                    'cuerpo' => mb_substr($response->body(), 0, 300),
                ]);

                return [];
            }

            return (array) json_decode($response->json('choices.0.message.content', '{}'), true);
        } catch (\Throwable $e) {
            Log::error('Recomendador: excepción', ['msg' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Genera las recomendaciones de un menú y las guarda.
     *
     * @return array{platos:int, error?:string}
     */
    public function generarYGuardar(Menu $menu): array
    {
        $resultado = $this->generar($menu);

        if (isset($resultado['error'])) {
            return ['platos' => 0, 'error' => $resultado['error']];
        }

        $menu->update(['recomendaciones' => $resultado]);

        return ['platos' => count($resultado['platos'])];
    }

    /**
     * Le pide a la IA las duplas de la carta y devuelve la estructura ya validada.
     *
     * @return array{modelo:string, generado_en:string, platos:array<string, array{con:array<int,string>, motivo:string}>}|array{error:string}
     */
    public function generar(Menu $menu): array
    {
        $apiKey = config('services.openai.api_key');

        if (empty($apiKey)) {
            return ['error' => 'OpenAI API key no configurada'];
        }

        $catalogo = $this->catalogo($menu);

        if (count($catalogo) < 2) {
            return ['error' => 'La carta no tiene suficientes platos'];
        }

        // Qué es cada categoría de la carta (principal, guarnición, bebida, postre). Es una
        // sola llamada —las categorías son ~15, no 151 platos— y es lo que después permite
        // prohibir por código que un principal "acompañe" a otro principal.
        $roles = $this->rolesDeCategorias($catalogo, $apiKey);

        $recomendaciones = [];

        foreach (array_chunk($catalogo, self::LOTE, true) as $lote) {
            $crudo = $this->pedirALaIA($catalogo, $lote, $apiKey);
            $recomendaciones += $this->validar($crudo, $catalogo, $roles);
        }

        return [
            'modelo' => $this->modelo(),
            'generado_en' => now()->toIso8601String(),
            'roles' => $roles,
            'platos' => $recomendaciones,
        ];
    }

    /**
     * Los complementos de un plato, con su dato exacto de la carta (precio, foto).
     *
     * Devuelve los platos y no una copia del texto: el precio tiene que salir siempre del
     * menú vigente, o el día que el comercio lo cambie la recomendación queda mintiendo.
     *
     * @return array{platos:array<int,array>, parecidos:array<int,array>, motivo:string}
     */
    public function paraPlato(Comercio $comercio, string $nombre): array
    {
        $menu = $this->menuConRecomendaciones($comercio);
        $guardadas = $menu?->recomendaciones['platos'] ?? [];
        $fila = $guardadas[$this->clave($nombre)] ?? null;

        if (! $fila) {
            return ['platos' => [], 'parecidos' => [], 'motivo' => ''];
        }

        return [
            'platos' => $this->platosPorNombre($comercio, $fila['con'] ?? []),
            'parecidos' => $this->platosPorNombre($comercio, $fila['parecidos'] ?? []),
            'motivo' => (string) ($fila['motivo'] ?? ''),
        ];
    }

    /**
     * La respuesta del menú cuando NO hay IA: busca en la carta y suma los complementos.
     *
     * Usa la misma búsqueda en PHP que hoy se le sirve al modelo antes de cada consulta
     * (`ChatbotService::buscarEnElMenu()`), así que encuentra plurales, acentos, jerga y
     * categorías igual que el asistente. Lo que no hace es interpretar la pregunta.
     *
     * @return array{texto:string, platos:array<int,array>}
     */
    public function responder(Comercio $comercio, string $mensaje): array
    {
        // Un "hola" no es un plato, y contestarle "no encontré eso en la carta" es la
        // primera impresión del menú. Va antes que la búsqueda porque "buenas" y "hola"
        // no tienen por qué coincidir con nada.
        if ($this->esSaludo($mensaje)) {
            return [
                'texto' => __('¡Hola! Mirá la carta acá abajo o escribí lo que estás buscando.'),
                'platos' => $this->masPedidos($comercio),
            ];
        }

        $encontrados = $this->chatbot->buscarEnElMenu($comercio, $mensaje, 3);

        if (empty($encontrados)) {
            // "Algo para tomar" no es un plato y la búsqueda por nombre no lo encuentra
            // nunca. Pero el rol de cada categoría ya está calculado, así que un pedido
            // genérico se puede resolver por ahí: bebida, postre, algo para comer.
            if ($porRol = $this->porRolPedido($comercio, $mensaje)) {
                return $porRol;
            }

            $sugeridos = $this->masPedidos($comercio);

            return [
                'texto' => empty($sugeridos)
                    ? __('No encontré eso en la carta. Podés mirar el menú completo acá abajo.')
                    : __('No encontré eso en la carta. Te dejo algunas de las opciones más pedidas.'),
                'platos' => $sugeridos,
            ];
        }

        $principal = $encontrados[0]['nombre'];
        $complemento = $this->paraPlato($comercio, $principal);

        $platos = $this->platosPorNombre($comercio, array_column($encontrados, 'nombre'));
        $texto = __('Esto tenemos:');

        if (! empty($complemento['platos'])) {
            $nombres = collect($complemento['platos'])->pluck('nombre');
            $texto .= ' '.__(':plato va bien con :con.', [
                'plato' => $principal,
                'con' => $nombres->join(', ', ' '.__('y').' '),
            ]);
        }

        // Primero lo que buscó, después con qué acompañarlo y al final las alternativas:
        // la tarjeta de lo que el cliente pidió tiene que ser la primera.
        foreach (array_merge($complemento['platos'], $complemento['parecidos']) as $p) {
            if (! in_array($p['nombre'], array_column($platos, 'nombre'), true)) {
                $platos[] = $p;
            }
        }

        return ['texto' => $texto, 'platos' => $platos];
    }

    /**
     * ¿Esta carta ya tiene recomendaciones calculadas?
     */
    public function tieneRecomendaciones(Comercio $comercio): bool
    {
        return ! empty($this->menuConRecomendaciones($comercio)?->recomendaciones['platos'] ?? []);
    }

    /**
     * Las recomendaciones del menú vigente, listas para la vista.
     *
     * La clave es el nombre EXACTO del plato tal como lo muestra la carta: la
     * normalización se resuelve acá y no en el Blade, que si no termina con una copia
     * de `clave()` que se separa de esta en el primer arreglo.
     *
     * @return array<string, array{con:array<int,string>, parecidos:array<int,string>}>
     */
    public function mapaParaMenu(Comercio $comercio): array
    {
        $guardadas = $this->menuConRecomendaciones($comercio)?->recomendaciones['platos'] ?? [];

        if (empty($guardadas)) {
            return [];
        }

        $mapa = [];

        foreach ($this->chatbot->platosDelMenu($comercio) as $plato) {
            $fila = $guardadas[$this->clave($plato['nombre'])] ?? null;

            if (! empty($fila['con']) || ! empty($fila['parecidos'])) {
                $mapa[$plato['nombre']] = [
                    'con' => $fila['con'] ?? [],
                    'parecidos' => $fila['parecidos'] ?? [],
                ];
            }
        }

        return $mapa;
    }

    // ---- Interno ----

    private function menuConRecomendaciones(Comercio $comercio): ?Menu
    {
        return $comercio->menus()
            ->whereNotNull('recomendaciones')
            ->orderByDesc('activo')
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Platos de la carta: clave normalizada => nombre exacto y categoría.
     *
     * La categoría no sobra. Mandando sólo los nombres, el modelo no tiene cómo saber qué
     * es dulce, qué es una bebida y qué una guarnición: en la primera corrida contra una
     * carta real metió "Papas Fritas" en 121 de 121 platos, budines y ensalada de frutas
     * incluidos. Con la categoría al lado, elige dentro del mismo momento del día.
     *
     * @return array<string, array{nombre:string, categoria:string}>
     */
    private function catalogo(Menu $menu): array
    {
        $catalogo = [];

        foreach ($menu->platos() as $plato) {
            $nombre = trim((string) ($plato['nombre'] ?? ''));

            if ($nombre === '' || count($catalogo) >= self::MAX_PLATOS) {
                continue;
            }

            $catalogo[$this->clave($nombre)] = [
                'nombre' => $nombre,
                'categoria' => trim((string) ($plato['categoria'] ?? '')),
            ];
        }

        return $catalogo;
    }

    /**
     * Cómo se le describe un plato al modelo: "Nombre (Categoría)".
     *
     * @param  array<string, array{nombre:string, categoria:string}>  $platos
     */
    private function listar(array $platos): string
    {
        return collect($platos)
            ->map(fn ($p) => $p['categoria'] !== '' ? "{$p['nombre']} ({$p['categoria']})" : $p['nombre'])
            ->implode("\n- ");
    }

    /**
     * @param  array<string, array{nombre:string, categoria:string}>  $catalogo
     * @param  array<string, array{nombre:string, categoria:string}>  $lote
     * @return array<int, array{plato?:string, con?:array, motivo?:string}>
     */
    private function pedirALaIA(array $catalogo, array $lote, string $apiKey): array
    {
        $system = 'Sos el encargado de un bar/restaurante de Argentina y conocés la carta de memoria. '
            .'Para cada plato que te pidan devolvé DOS listas, siempre con nombres EXACTOS de la carta: '
            .'"con" = hasta '.self::MAX_COMPLEMENTOS.' platos que el cliente pediría JUNTO con él en la misma mesa '
            .'(una bebida, una guarnición, un postre), y "parecidos" = hasta '.self::MAX_COMPLEMENTOS.' alternativas '
            .'del mismo tipo, para el que todavía está eligiendo (otras hamburguesas si es una hamburguesa). '
            .'REGLAS: nunca el mismo plato; en "con" no pongas otro plato principal ni algo de la misma categoría, '
            .'eso va en "parecidos"; respetá el momento del día (a un desayuno no le va una guarnición de almuerzo) '
            .'y no mezcles dulce con salado; variá, no repitas el mismo acompañamiento en todos los platos; '
            .'si no hay nada que combine de verdad, dejá la lista vacía. '
            .'El motivo va en español rioplatense, máximo 6 palabras, sin signos de exclamación. '
            .'Devolvé una entrada por CADA plato pedido, en el mismo orden, aunque sus listas queden vacías. '
            .'Respondé SOLO un JSON con la forma '
            .'{"recomendaciones":[{"plato":"nombre exacto","con":["nombre exacto"],"parecidos":["nombre exacto"],"motivo":"texto corto"}]}';

        $user = "CARTA COMPLETA, con su categoría (elegí solo de acá):\n- ".$this->listar($catalogo)
            ."\n\nRECOMENDÁ PARA ESTOS PLATOS:\n- ".$this->listar($lote);

        $data = $this->pedirJson($system, $user, $apiKey);

        return is_array($data['recomendaciones'] ?? null) ? $data['recomendaciones'] : [];
    }

    /**
     * Qué papel juega cada categoría de la carta en una mesa.
     *
     * Sin esto, el modelo ofrece un lomito para acompañar una empanada y otra hamburguesa
     * para acompañar la hamburguesa: son platos principales, no acompañamientos. Se
     * pregunta por CATEGORÍA y no por plato porque son quince contra ciento cincuenta.
     *
     * @param  array<string, array{nombre:string, categoria:string}>  $catalogo
     * @return array<string, string> categoría => principal|guarnicion|bebida|postre|otro
     */
    private function rolesDeCategorias(array $catalogo, string $apiKey): array
    {
        $categorias = collect($catalogo)->pluck('categoria')->filter()->unique()->values();

        if ($categorias->isEmpty()) {
            return [];
        }

        // Los ejemplos no sobran: sin ellos marcó "Fritos" —papas y aros de cebolla— como
        // plato principal, y con eso las papas dejaban de poder acompañar una hamburguesa.
        $system = 'Clasificá cada categoría de la carta de un bar según su papel en la mesa. '
            .'Valores posibles, en minúscula y sin acentos: '
            .'principal (una comida completa: hamburguesas, pizzas, milanesas, sandwiches, ensaladas grandes, desayunos), '
            .'guarnicion (lo que se pide PARA ACOMPAÑAR otra cosa: papas fritas, aros de cebolla, fritos, pan, dips, ensalada de guarnición), '
            .'bebida, postre (tortas, budines, helados, dulces de la vitrina), otro. '
            .'Ante la duda entre principal y guarnicion, mirá si alguien pediría SOLO eso para almorzar. '
            .'Respondé SOLO un JSON {"categorias":[{"nombre":"...","rol":"..."}]} '
            .'con TODAS las categorías recibidas, con el nombre EXACTO que recibiste.';

        $data = $this->pedirJson($system, '- '.$categorias->implode("\n- "), $apiKey, 2000);
        $roles = [];

        foreach ((array) ($data['categorias'] ?? []) as $fila) {
            $nombre = trim((string) ($fila['nombre'] ?? ''));
            $rol = trim((string) ($fila['rol'] ?? ''));

            // Minúsculas y sin acentos antes de comparar: el modelo contesta
            // "Principal" y "guarnición", y con la lista cruda no entraba ninguna.
            $rol = strtr(mb_strtolower($rol, 'UTF-8'), ['ó' => 'o', 'í' => 'i', 'á' => 'a', 'é' => 'e', 'ú' => 'u']);

            if ($nombre !== '' && in_array($rol, ['principal', 'guarnicion', 'bebida', 'postre', 'otro'], true)) {
                $roles[$nombre] = $rol;
            }
        }

        return $roles;
    }

    /**
     * Deja pasar sólo lo que existe en la carta.
     *
     * Es la parte que hace confiable a todo esto: el modelo se equivoca —ya lo vimos
     * declarando faltantes que estaban en el menú— y acá lo que no matchea con un plato
     * real se descarta antes de guardarse. Lo que queda escrito es lo que se muestra.
     *
     * @param  array<int, array>  $crudo
     * @param  array<string, array{nombre:string, categoria:string}>  $catalogo
     * @param  array<string, string>  $roles
     * @return array<string, array{con:array<int,string>, motivo:string}>
     */
    private function validar(array $crudo, array $catalogo, array $roles = []): array
    {
        $validas = [];

        // Con una carta bien categorizada, un plato de la MISMA categoría no es un
        // acompañamiento sino un parecido: a la hamburguesa le ofrecía otras tres
        // hamburguesas, y a la empanada, empanadas. El prompt lo pide y acá se exige.
        // No se aplica si la carta tiene una o dos categorías enormes, donde "misma
        // categoría" no significa nada y dejaría todo afuera.
        $porCategoria = collect($catalogo)->pluck('categoria')->filter()->unique()->count() >= 3;

        foreach ($crudo as $fila) {
            $clave = $this->clave((string) ($fila['plato'] ?? ''));

            if (! isset($catalogo[$clave])) {
                continue;
            }

            $con = [];
            $parecidos = [];
            $esPrincipal = fn (string $k) => ($roles[$catalogo[$k]['categoria']] ?? '') === 'principal';

            // Las dos listas se mezclan a propósito y se reparten acá: el modelo se
            // equivoca de columna, y quien decide qué es acompañamiento y qué alternativa
            // es la categoría de la carta, que no opina.
            $propuestos = array_merge((array) ($fila['con'] ?? []), (array) ($fila['parecidos'] ?? []));

            foreach ($propuestos as $nombre) {
                $claveCon = $this->clave((string) $nombre);

                // Ni platos inventados, ni el mismo plato, ni repetidos.
                if (! isset($catalogo[$claveCon]) || $claveCon === $clave
                    || in_array($catalogo[$claveCon]['nombre'], $con, true)
                    || in_array($catalogo[$claveCon]['nombre'], $parecidos, true)) {
                    continue;
                }

                // Un plato de la misma categoría, o dos principales, NO son un
                // acompañamiento: son una alternativa. Sirven igual —ayudan a elegir
                // entre seis hamburguesas— pero van en otra lista y con otro título. Bajo
                // "Va bien con", una segunda hamburguesa se lee como "pedite dos".
                $esAlternativa = ($porCategoria && $catalogo[$claveCon]['categoria'] === $catalogo[$clave]['categoria'])
                    || ($esPrincipal($clave) && $esPrincipal($claveCon));

                if ($esAlternativa) {
                    if (count($parecidos) < self::MAX_COMPLEMENTOS) {
                        $parecidos[] = $catalogo[$claveCon]['nombre'];
                    }

                    continue;
                }

                if (count($con) < self::MAX_COMPLEMENTOS) {
                    $con[] = $catalogo[$claveCon]['nombre'];
                }
            }

            if (empty($con) && empty($parecidos)) {
                continue;
            }

            $validas[$clave] = [
                'con' => $con,
                'parecidos' => $parecidos,
                'motivo' => mb_substr(trim(str_replace(["\n", "\r"], ' ', (string) ($fila['motivo'] ?? ''))), 0, 80),
            ];
        }

        return $validas;
    }

    /**
     * ¿El mensaje es un saludo y nada más?
     *
     * Con "hola, tienen hamburguesas?" NO alcanza con que empiece con hola: ahí lo que
     * importa es la pregunta, así que sólo se toma como saludo el mensaje corto que no
     * trae nada más.
     */
    private function esSaludo(string $mensaje): bool
    {
        $texto = trim(strtr(mb_strtolower($mensaje, 'UTF-8'), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', '¡' => '', '!' => '', '.' => '', ',' => '']));

        $saludos = ['hola', 'holis', 'buenas', 'buen dia', 'buenas tardes', 'buenas noches', 'hey', 'que tal', 'hello', 'hi'];

        return in_array($texto, $saludos, true);
    }

    /**
     * Pedidos genéricos ("algo para tomar", "un postre"), resueltos por el rol de la
     * categoría en vez de por el nombre del plato.
     *
     * @return array{texto:string, platos:array<int,array>}|null
     */
    private function porRolPedido(Comercio $comercio, string $mensaje): ?array
    {
        $texto = strtr(mb_strtolower($mensaje, 'UTF-8'), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']);

        $pedidos = [
            'bebida' => ['tomar', 'beber', 'bebida', 'sed', 'trago', 'refresco'],
            'postre' => ['postre', 'dulce', 'torta', 'helado'],
            'principal' => ['comer', 'almorzar', 'cenar', 'hambre', 'comida'],
        ];

        foreach ($pedidos as $rol => $palabras) {
            foreach ($palabras as $palabra) {
                if (! str_contains($texto, $palabra)) {
                    continue;
                }

                $platos = $this->platosConRol($comercio, $rol);

                if (! empty($platos)) {
                    return ['texto' => __('Te dejo algunas opciones:'), 'platos' => $platos];
                }
            }
        }

        return null;
    }

    /**
     * Hasta tres platos de las categorías que tienen ese rol en la carta.
     *
     * @return array<int, array>
     */
    private function platosConRol(Comercio $comercio, string $rol): array
    {
        $roles = $this->menuConRecomendaciones($comercio)?->recomendaciones['roles'] ?? [];
        $categorias = array_keys(array_filter($roles, fn ($r) => $r === $rol));

        if (empty($categorias)) {
            return [];
        }

        $platos = array_values(array_filter(
            $this->chatbot->platosDelMenu($comercio),
            fn ($p) => in_array($p['categoria'] ?? '', $categorias, true)
        ));

        return array_slice($platos, 0, 3);
    }

    /**
     * Los platos marcados como estrella, o los primeros de la carta.
     *
     * @return array<int, array>
     */
    private function masPedidos(Comercio $comercio): array
    {
        $platos = $this->chatbot->platosDelMenu($comercio);
        $estrella = array_values(array_filter($platos, fn ($p) => ! empty($p['estrella'])));

        return array_slice($estrella ?: $platos, 0, 3);
    }

    /**
     * @param  array<int, string>  $nombres
     * @return array<int, array>
     */
    private function platosPorNombre(Comercio $comercio, array $nombres): array
    {
        $porClave = [];

        foreach ($this->chatbot->platosDelMenu($comercio) as $plato) {
            $porClave[$this->clave($plato['nombre'])] = $plato;
        }

        $resultado = [];

        foreach ($nombres as $nombre) {
            $plato = $porClave[$this->clave($nombre)] ?? null;

            if ($plato) {
                $resultado[] = $plato;
            }
        }

        return $resultado;
    }

    /**
     * Nombre normalizado, para que "Coca-Cola 500ml" y "coca cola 500 ml" sean lo mismo.
     */
    private function clave(string $nombre): string
    {
        $n = mb_strtolower(trim($nombre), 'UTF-8');
        $n = strtr($n, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
        $n = (string) preg_replace('/[^a-z0-9]+/', ' ', $n);

        return trim((string) preg_replace('/\s+/', ' ', $n));
    }
}
