<?php

namespace App\Http\Controllers;

use App\Jobs\ClassifyIntentsJob;
use App\Models\ChatConversation;
use App\Models\Comercio;
use App\Models\IntentClassificationRun;
use App\Models\Menu;
use App\Models\SiteVisit;
use App\Models\TrackingEvent;
use App\Services\AnalyticsInsightsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /** Periodos ofrecidos en el historial, en dias. 0 = todo el historial. */
    public const PERIODOS = [7, 30, 90, 365, 0];

    /** Cuantos hilos mostrar por vez. */
    public const CANTIDADES = [25, 50, 100];

    /**
     * Minutos de inactividad que separan dos visitas de un mismo dispositivo.
     *
     * El `session_id` que manda el front vive en localStorage y no vence nunca: es un id
     * de VISITANTE, no de sesión. Sin este corte, alguien que vuelve a los 10 días arrastra
     * una "sesión" de 10 días y rompe el promedio de tiempo en el menú.
     */
    private const GAP_VISITA_MINUTOS = 30;

    /**
     * Dashboard principal de analytics
     */
    public function dashboard(Request $request)
    {
        $user = auth()->user();
        $esAdmin = $user->isSuperAdmin();

        // Scope: super admin ve todo; el comercio común solo lo suyo (evita fuga de datos)
        $comercios = Comercio::withCount(['menus', 'chatConversations'])
            ->when(! $esAdmin, fn ($q) => $q->where('user_id', $user->id))
            ->get();

        // Usuario común necesita la feature 'analytics' en alguno de sus comercios
        if (! $esAdmin && ! $comercios->contains(fn ($c) => $c->tieneFeature('analytics'))) {
            abort(403, 'Las analíticas están disponibles a partir del plan Pro.');
        }

        $comercioIds = $comercios->pluck('id');
        $conv = ChatConversation::query()
            ->when(! $esAdmin, fn ($q) => $q->whereIn('comercio_id', $comercioIds));

        $stats = [
            'total_comercios' => $comercios->count(),
            'total_menus' => $esAdmin ? Menu::count() : Menu::whereIn('comercio_id', $comercioIds)->count(),
            'total_conversations' => (clone $conv)->count(),
            'conversations_today' => (clone $conv)->whereDate('created_at', today())->count(),
            'openai_usage' => (clone $conv)->usedOpenAI()->count(),
            'fallback_usage' => (clone $conv)->where('used_openai', false)->count(),
        ];

        // Seguimiento de uso de la clasificación con IA (solo super admin)
        $iaRuns = $esAdmin
            ? IntentClassificationRun::with('comercio')->latest()->take(20)->get()
            : collect();
        $iaRunsMensajes = $esAdmin ? (int) IntentClassificationRun::sum('mensajes_procesados') : 0;

        // Analytics de la web pública: de dónde entran a la landing. Solo super admin:
        // es tráfico del sitio de ServiME, no de los comercios.
        $web = null;
        $webVisitas = null;

        if ($esAdmin) {
            $dias = (int) $request->input('dias', 30);
            $dias = in_array($dias, [7, 30, 90, 365], true) ? $dias : 30;

            // Por defecto solo personas. ?bots=1 muestra también lo que no confirmó por JS.
            $incluirBots = $request->boolean('bots');

            $web = SiteVisit::resumen($dias, $incluirBots);
            $webVisitas = SiteVisit::where('created_at', '>=', now()->subDays($dias))
                ->when(! $incluirBots, fn ($q) => $q->humanas())
                ->latest()
                ->paginate(25)
                ->withQueryString();
        }

        return view('analytics.dashboard', compact(
            'comercios', 'stats', 'iaRuns', 'iaRunsMensajes', 'web', 'webVisitas'
        ));
    }

    /**
     * Analytics específicos de un comercio
     */
    public function comercio(Request $request, Comercio $comercio)
    {
        if (! auth()->user()->isSuperAdmin() && $comercio->user_id !== auth()->id()) {
            abort(403);
        }

        if (! auth()->user()->isSuperAdmin() && ! $comercio->tieneFeature('analytics')) {
            abort(403, 'Las analíticas están disponibles a partir del plan Pro.');
        }

        $comercioId = $comercio->id;

        // Historial agrupado por hilo (session_id).
        //
        // La busqueda va del lado del SERVIDOR y sobre todo el periodo: la lista se corta
        // en 25 hilos, asi que un filtro en el navegador solo habria buscado dentro de
        // esos 25 y no serviria para encontrar una conversacion mas vieja.
        $q = trim((string) $request->query('q', ''));
        $soloSinResolver = $request->boolean('sin_resolver');

        // Periodo y cantidad EXPLICITOS, en vez de un tope fijo escondido: antes la lista
        // mostraba 25 hilos de 79 sin decirlo, y para ver algo mas viejo habia que
        // adivinar una palabra para buscar. Se validan contra una lista blanca para que
        // nadie pida "todo, 100000" por la URL.
        $periodo = (int) $request->query('periodo', 30);   // dias; 0 = todo el historial
        $periodo = in_array($periodo, self::PERIODOS, true) ? $periodo : 30;

        $cantidad = (int) $request->query('cantidad', 25);
        $cantidad = in_array($cantidad, self::CANTIDADES, true) ? $cantidad : 25;

        $base = ChatConversation::forComercio($comercioId);
        if ($periodo > 0) {
            $base = $base->where('created_at', '>=', now()->subDays($periodo));
        }

        if ($q !== '') {
            // Se buscan las SESIONES que tengan algun mensaje que coincida y despues se
            // traen todos sus mensajes: si no, el hilo aparecia cortado. El tope de 200
            // sesiones evita cargar en memoria un historial entero si el termino es muy
            // comun; igual solo se muestran las que entren en $cantidad.
            $sesiones = (clone $base)
                ->where(fn ($w) => $w->where('user_message', 'like', '%'.$q.'%')
                    ->orWhere('bot_response', 'like', '%'.$q.'%'))
                ->orderByDesc('created_at')
                ->distinct()
                ->take(200)
                ->pluck('session_id');

            $base = ChatConversation::forComercio($comercioId)->whereIn('session_id', $sesiones);
        }

        $threads = $base
            ->orderBy('created_at')
            ->get()
            ->groupBy('session_id')
            ->map(fn ($msgs) => [
                'session_id' => $msgs->first()->session_id,
                'ip' => $msgs->first()->user_ip,
                'total' => $msgs->count(),
                'inicio' => $msgs->min('created_at'),
                'fin' => $msgs->max('created_at'),
                'mensajes' => $msgs->values(),
                // Lo que hace útil el historial: poder distinguir de un vistazo los hilos
                // que hay que revisar (el asistente no encontró nada) de los que salieron
                // bien, y en qué idioma se atendió.
                'sin_resolver' => $msgs->contains(fn ($m) => $m->platos_sugeridos === 0 || $m->falta),
                'falta' => $msgs->pluck('falta')->filter()->unique()->values()->all(),
                'idiomas' => $msgs->pluck('idioma')->filter()->unique()->reject(fn ($i) => $i === 'es')->values()->all(),
                // El primer mensaje escrito por el cliente resume mejor el hilo que el
                // primero a secas, que puede ser un tap de categoría.
                'titulo' => optional($msgs->firstWhere('origen', 'escrito') ?? $msgs->first())->user_message,
                // Un adelanto de la respuesta: sin esto cada fila era una sola línea de
                // texto y una fecha, y el "historial de conversaciones" no mostraba
                // ninguna conversación hasta abrir el hilo.
                // Se sacan los asteriscos de Markdown: el chat los renderiza en negrita,
                // pero acá se mostraban crudos ("**Tiramisú**").
                'respuesta' => $this->sinMarkdown(
                    optional($msgs->firstWhere('origen', 'escrito') ?? $msgs->first())->bot_response
                ),
            ])
            ->when($soloSinResolver, fn ($c) => $c->where('sin_resolver', true))
            ->sortByDesc('fin');

        // Cuantos hay en total, para avisar cuando la lista quedo recortada
        $threadsTotal = $threads->count();
        $threads = $threads->take($cantidad)->values();

        $stats = [
            'total_conversations' => ChatConversation::forComercio($comercioId)->count(),
            'conversations_today' => ChatConversation::forComercio($comercioId)
                ->whereDate('created_at', today())->count(),
            // Se cuentan las que el asistente NO pudo responder, no las que sí.
            // "Con IA" era igual al total salvo cuando algo fallaba, así que la tarjeta
            // repetía un número y no avisaba nada. Esto arranca en 0 y solo crece cuando
            // hay un problema, que es cuando el comerciante tiene que mirar.
            //
            // recent(30) como todo el resto del panel: sin eso la tarjeta arrastraba una
            // caída de hace meses, ya resuelta, y el encabezado seguía diciendo "últimos
            // 30 días". No se notaba solo porque ningún comercio llevaba tanto tiempo.
            'sin_responder' => ChatConversation::forComercio($comercioId)
                ->recent(30)
                ->where('used_openai', false)->count(),
            // Métrica de infraestructura: la ve el super admin, no el comercio.
            'avg_response_time' => ChatConversation::forComercio($comercioId)
                ->recent(30)
                ->whereNotNull('response_time_ms')
                ->avg('response_time_ms'),
        ];

        // Un número solo no dice nada: "159 consultas" ¿contra qué? Se agrega la
        // comparación con los 30 días anteriores y la serie diaria para el sparkline.
        $stats['ultimos_30'] = ChatConversation::forComercio($comercioId)->recent(30)->count();
        $stats['previos_30'] = ChatConversation::forComercio($comercioId)
            ->whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])
            ->count();
        $stats['serie'] = $this->serieDiaria($comercioId, 14);

        // Preguntas más frecuentes
        $frequentQuestions = $this->getFrequentQuestions($comercioId);

        // Estadísticas por día
        $dailyStats = $this->getDailyStats($comercioId);

        // Métricas de comportamiento (eventos del menú público) + palabras
        $metrics = $this->getEventMetrics($comercioId);
        $topWords = $this->getTopWords($comercioId);

        // Intención de las consultas + sugerencias accionables
        $insights = new AnalyticsInsightsService;
        $intents = $insights->intents($comercioId);
        $suggestions = $insights->suggestions($comercioId, $metrics, $intents);
        // Consultas donde el bot no encontró nada: lo que le piden y no tiene
        $demandaInsatisfecha = $insights->demandaInsatisfecha($comercioId);

        // Última clasificación con IA (para el botón Premium)
        $ultimaClasificacion = IntentClassificationRun::where('comercio_id', $comercioId)->latest()->first();

        return view('analytics.comercio', compact(
            'comercio',
            'threads',
            'threadsTotal',
            'q',
            'soloSinResolver',
            'periodo',
            'cantidad',
            'stats',
            'frequentQuestions',
            'dailyStats',
            'metrics',
            'topWords',
            'intents',
            'suggestions',
            'demandaInsatisfecha',
            'ultimaClasificacion'
        ));
    }

    /**
     * Dispara la reclasificación de intención con IA (Premium, gateado en la ruta).
     */
    public function reclassifyIntents(Comercio $comercio)
    {
        if (! auth()->user()->isSuperAdmin() && $comercio->user_id !== auth()->id()) {
            abort(403);
        }

        ClassifyIntentsJob::dispatch($comercio);

        return back()->with('success', 'Reclasificación con IA encolada. En unos minutos vas a ver las intenciones actualizadas.');
    }

    /**
     * Métricas derivadas de tracking_events (últimos 30 días).
     */
    private function getEventMetrics($comercioId): array
    {
        $events = TrackingEvent::forComercio($comercioId)
            ->recent(30)
            ->get(['event', 'data', 'session_id', 'user_agent', 'created_at']);

        // Elección IA vs Clásico
        $choices = $events->where('event', 'menu_choice');
        $menuChoice = [
            'ia' => $choices->filter(fn ($e) => ($e->data['choice'] ?? null) === 'ia')->count(),
            'classic' => $choices->filter(fn ($e) => ($e->data['choice'] ?? null) === 'classic')->count(),
        ];

        // Por dónde entran: el botón grande, un chip sugerido (y cuál) o el menú completo.
        // Va en clicks y no en visitas a propósito: la pregunta acá no es cuánta gente
        // entró, sino qué puerta se usa, para decidir qué chips dejar en la portada.
        // Los eventos viejos no traen 'desde' (el atributo es nuevo) y quedan afuera.
        $entradas = [
            'cta' => $choices->filter(fn ($e) => ($e->data['desde'] ?? null) === 'cta')->count(),
            'chip' => $choices->filter(fn ($e) => ($e->data['desde'] ?? null) === 'chip')->count(),
            'completo' => $choices->filter(fn ($e) => ($e->data['desde'] ?? null) === 'completo')->count(),
        ];
        $topChips = $choices->filter(fn ($e) => ($e->data['desde'] ?? null) === 'chip' && ! empty($e->data['label']))
            ->groupBy(fn ($e) => $e->data['label'])
            ->map->count()->sortDesc()->take(6);

        // Promos más clickeadas.
        //
        // La etiqueta se limpia de emoji porque el evento guarda el textContent del botón,
        // y los chips de promo tenían un 🔥 adelante. Sin limpiarlo, los clicks anteriores
        // ("🔥 Promociones del Día") y los nuevos ("Promociones del Día") aparecían como
        // dos promos distintas, y encima con un emoji suelto en el panel.
        $topPromos = $events->where('event', 'promo_click')
            ->groupBy(fn ($e) => $this->sinEmoji($e->data['promo'] ?? '') ?: '—')
            ->map->count()->sortDesc()->take(6);

        // Platos más deseados ("Lo quiero")
        $topDishes = $events->where('event', 'dish_want')
            ->groupBy(fn ($e) => $e->data['nombre'] ?? '—')
            ->map->count()->sortDesc()->take(8);

        // Visitas reales: los eventos de cada visitante se cortan por inactividad, así que
        // el que vuelve otro día suma otra visita en vez de estirar la primera.
        $visitas = $this->agruparVisitas($events);

        // Cuántas visitas contienen al menos un evento que cumpla la condición.
        $visitasCon = fn (callable $cumple) => $visitas->filter(fn ($evs) => $evs->contains($cumple))->count();

        // Embudo por visita: visita -> ve la elección -> elige -> muestra interés.
        //
        // 'vieron_eleccion' es un paso propio porque solo quien PASÓ por esa pantalla puede
        // elegir: si alguien entra directo a /chat o /classic (un QR que apunta ahí, un
        // favorito, un link compartido) contaba en 'visitas' y nunca podía elegir, así que
        // el ratio de elección salía por el piso. Medido en local: 2% contra los 20% reales.
        // 'armaron_pedido' cierra el embudo: tocar "Lo quiero" es un click sobre un plato,
        // abrir el resumen es irse a pedirlo. Se exige al menos un ítem para que el paso
        // sea de verdad un subconjunto del anterior (el botón se puede abrir vacío).
        $funnel = [
            'visitas' => $visitasCon(fn ($e) => $e->event === 'page_view'),
            'vieron_eleccion' => $visitasCon(fn ($e) => $e->event === 'page_view' && ($e->data['view'] ?? null) === 'choose'),
            'eligieron' => $visitasCon(fn ($e) => $e->event === 'menu_choice'),
            'interesados' => $visitasCon(fn ($e) => $e->event === 'dish_want'),
            'armaron_pedido' => $visitasCon(fn ($e) => $e->event === 'order_summary' && (int) ($e->data['items'] ?? 0) > 0),
        ];

        // Micrófono: por visita, no por evento — dictar tres frases seguidas sigue siendo
        // una persona usando la función. Sirve para saber si la feature vale la pena.
        $vozVisitas = $visitasCon(fn ($e) => $e->event === 'mic_start');

        // Heatmap por día de semana (0=Dom..6=Sáb) x hora
        $heatmap = array_fill(0, 7, array_fill(0, 24, 0));
        $heatmapMax = 0;
        foreach ($events->where('event', 'page_view') as $e) {
            $dow = (int) $e->created_at->dayOfWeek;
            $hour = (int) $e->created_at->format('G');
            $heatmap[$dow][$hour]++;
            $heatmapMax = max($heatmapMax, $heatmap[$dow][$hour]);
        }

        // Duración: por visita, no por visitante (ver GAP_VISITA_MINUTOS)
        $duraciones = $visitas->map(
            fn ($evs) => $evs->max('created_at')->getTimestamp() - $evs->min('created_at')->getTimestamp()
        );
        $visitaAvgSeconds = $duraciones->isNotEmpty() ? (int) round($duraciones->avg()) : 0;

        // Por visitante: dispositivo y recurrencia (recurrente = volvió al menos una vez)
        $devices = ['movil' => 0, 'desktop' => 0, 'tablet' => 0];
        $visitors = ['nuevos' => 0, 'recurrentes' => 0];
        foreach ($visitas->groupBy(fn ($evs, $clave) => $this->visitanteDeClave($clave)) as $delVisitante) {
            $devices[$this->clasificarDispositivo($delVisitante->first()->first()->user_agent)]++;
            $visitors[$delVisitante->count() >= 2 ? 'recurrentes' : 'nuevos']++;
        }

        $totalVisitantes = array_sum($visitors);
        $totalVisitas = $visitas->count();

        return compact('menuChoice', 'entradas', 'topChips', 'topPromos', 'topDishes', 'funnel',
            'vozVisitas', 'heatmap', 'heatmapMax', 'devices', 'visitors', 'visitaAvgSeconds',
            'totalVisitantes', 'totalVisitas');
    }

    /**
     * Parte los eventos en visitas: los de un mismo `session_id` separados por más de
     * GAP_VISITA_MINUTOS de inactividad cuentan como visitas distintas.
     *
     * Los eventos sin `session_id` (la columna es nullable) se descartan: no se pueden
     * atribuir a nadie, y antes caían todos juntos en un mismo balde falso.
     *
     * Devuelve las visitas con clave "{session_id}#{n}" (n arranca en 0 por visitante),
     * ordenadas por visitante y, dentro de cada uno, cronológicamente.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\TrackingEvent>  $events
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, \App\Models\TrackingEvent>>
     */
    private function agruparVisitas($events)
    {
        $visitas = collect();

        foreach ($events->groupBy('session_id') as $sessionId => $delVisitante) {
            if (empty($sessionId)) {
                continue;
            }

            $n = 0;
            $anterior = null;

            foreach ($delVisitante->sortBy('created_at') as $e) {
                // Comparar timestamps y no diffInMinutes(): en Carbon 3 el diff viene con
                // signo y un orden invertido daría negativo, o sea, nunca cortaría.
                if ($anterior !== null && $e->created_at->getTimestamp() - $anterior > self::GAP_VISITA_MINUTOS * 60) {
                    $n++;
                }

                $visitas[$sessionId.'#'.$n] ??= collect();
                $visitas[$sessionId.'#'.$n]->push($e);
                $anterior = $e->created_at->getTimestamp();
            }
        }

        return $visitas;
    }

    /**
     * Saca el session_id de la clave "{session_id}#{n}" que arma agruparVisitas().
     */
    private function visitanteDeClave(string $clave): string
    {
        return substr($clave, 0, (int) strrpos($clave, '#'));
    }

    /**
     * Clasifica el dispositivo a partir del user-agent (móvil/tablet/desktop).
     */
    private function clasificarDispositivo(?string $ua): string
    {
        $ua = strtolower((string) $ua);
        if (str_contains($ua, 'ipad') || str_contains($ua, 'tablet')) {
            return 'tablet';
        }
        if (str_contains($ua, 'mobi') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            return 'movil';
        }

        return 'desktop';
    }

    // Stopwords en español (normalizadas, sin acentos)
    private const STOPWORDS = [
        'de', 'la', 'el', 'que', 'los', 'las', 'una', 'uno', 'con', 'para', 'por', 'del',
        'tienen', 'tiene', 'tenes', 'hay', 'cual', 'cuanto', 'cuanta', 'como', 'mas', 'pero',
        'eso', 'esta', 'este', 'son', 'algo', 'mi', 'tu', 'su', 'me', 'te', 'se', 'lo', 'al',
        'un', 'es', 'en', 'yo', 'ya', 'si', 'no', 'o', 'y', 'a', 'quiero', 'puedo', 'dame',
        'hola', 'gracias', 'buenas', 'recomendame', 'queria', 'quisiera',
    ];

    /**
     * Términos más usados en los mensajes del chat (últimos 30 días).
     * Combina palabras sueltas y bigramas (frases de 2 palabras), con
     * normalización de acentos y plurales para agrupar variantes.
     */
    private function getTopWords($comercioId, int $limit = 18)
    {
        $mensajes = ChatConversation::forComercio($comercioId)->recent(30)->escritas()->pluck('user_message');

        // Cada término se agrupa por su raíz (clave) pero se muestra la forma real
        // más frecuente, para no mostrar palabras cortadas (gratis, país, etc.).
        $unigramas = [];   // raiz => count
        $bigramas = [];    // raiz => count
        $formas = [];      // raiz => [forma_original => count]

        $registrar = function (string $key, string $forma, array &$bucket) use (&$formas) {
            $bucket[$key] = ($bucket[$key] ?? 0) + 1;
            $formas[$key][$forma] = ($formas[$key][$forma] ?? 0) + 1;
        };

        foreach ($mensajes as $msg) {
            $tokens = $this->tokenizar((string) $msg);

            // Palabras sueltas: contenido (>=4 letras, no stopword)
            foreach ($tokens as $t) {
                if (mb_strlen($t) >= 4 && ! in_array($t, self::STOPWORDS, true)) {
                    $registrar($this->singularizar($t), $t, $unigramas);
                }
            }

            // Bigramas: pares consecutivos (no ambos stopword, ambos >=3 letras)
            for ($i = 0, $n = count($tokens) - 1; $i < $n; $i++) {
                $a = $tokens[$i];
                $b = $tokens[$i + 1];
                if (mb_strlen($a) < 3 || mb_strlen($b) < 3) {
                    continue;
                }
                if (in_array($a, self::STOPWORDS, true) && in_array($b, self::STOPWORDS, true)) {
                    continue;
                }
                $raiz = $this->singularizar($a).' '.$this->singularizar($b);
                $registrar($raiz, $a.' '.$b, $bigramas);
            }
        }

        // Etiqueta = forma original más frecuente de cada raíz
        $conteo = [];
        foreach ($unigramas as $raiz => $c) {
            $conteo[$this->formaMasComun($formas[$raiz])] = $c;
        }
        // Solo bigramas que se repiten (evita ruido de frases únicas)
        foreach ($bigramas as $raiz => $c) {
            if ($c >= 2) {
                $conteo[$this->formaMasComun($formas[$raiz])] = $c;
            }
        }

        arsort($conteo);

        return collect($conteo)->take($limit);
    }

    /**
     * Devuelve la forma (texto original) más frecuente de un grupo.
     */
    private function formaMasComun(array $formas): string
    {
        arsort($formas);

        return (string) array_key_first($formas);
    }

    /**
     * Pasa a minúsculas, saca acentos/ñ y parte en palabras.
     */
    private function tokenizar(string $texto): array
    {
        $texto = mb_strtolower($texto, 'UTF-8');
        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);

        return preg_split('/[^a-z]+/', $texto, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * Stemming muy simple de plurales (postres -> postre, pizzas -> pizza).
     */
    private function singularizar(string $palabra): string
    {
        if (mb_strlen($palabra) > 4 && str_ends_with($palabra, 's')) {
            return mb_substr($palabra, 0, -1);
        }

        return $palabra;
    }

    /**
     * Obtener preguntas más frecuentes
     */
    private function getFrequentQuestions($comercioId, $limit = 10)
    {
        // escritas(): sin los mensajes que arma la interfaz al tocar un atajo, que son
        // idénticos entre sí y quedaban como la "pregunta más frecuente".
        $convs = ChatConversation::forComercio($comercioId)
            ->recent(30)
            ->escritas()
            ->get(['user_message', 'idioma', 'platos_sugeridos', 'falta']);

        // Agrupa por texto normalizado (así "¿Tienen postres?" y "tienen postres"
        // cuentan juntos), pero guarda la forma original para mostrarla.
        $conteo = [];
        $formas = [];
        $idiomas = [];
        $resueltas = [];  // cuántas veces el bot logró recomendar algo
        $conDato = [];    // cuántas tienen el contador cargado (filas viejas no lo tienen)

        foreach ($convs as $c) {
            $key = $this->normalizarFrase((string) $c->user_message);
            // Los saludos no son preguntas: ocupaban lugar sin decir nada.
            if ($key === '' || $this->esSaludo($key)) {
                continue;
            }
            $original = trim((string) $c->user_message);
            $conteo[$key] = ($conteo[$key] ?? 0) + 1;
            $formas[$key][$original] = ($formas[$key][$original] ?? 0) + 1;

            if ($c->idioma) {
                $idiomas[$key][$c->idioma] = true;
            }
            if ($c->platos_sugeridos !== null) {
                $conDato[$key] = ($conDato[$key] ?? 0) + 1;
                if ($c->platos_sugeridos > 0) {
                    $resueltas[$key] = ($resueltas[$key] ?? 0) + 1;
                }
            }
        }

        arsort($conteo);

        return collect($conteo)->take($limit)->map(function ($freq, $key) use ($formas, $idiomas, $resueltas, $conDato) {
            $total = $conDato[$key] ?? 0;
            $ok = $resueltas[$key] ?? 0;

            return (object) [
                'user_message' => $this->formaMasComun($formas[$key]),
                'frequency' => $freq,
                // null = no hay dato (conversaciones anteriores a la columna)
                'resuelta' => $total > 0 ? $ok / $total >= 0.5 : null,
                'idiomas' => array_keys($idiomas[$key] ?? []),
            ];
        })->values();
    }

    /**
     * ¿La frase es solo un saludo o una despedida? No es una pregunta frecuente.
     * Recibe el texto YA normalizado por normalizarFrase().
     */
    private function esSaludo(string $norm): bool
    {
        static $saludos = [
            'hola', 'holaa', 'holaaa', 'buenas', 'buenos dias', 'buenas tardes', 'buenas noches',
            'hey', 'hi', 'hello', 'ola', 'oi', 'ciao', 'como estas', 'que tal', 'test', 'prueba',
            'gracias', 'muchas gracias', 'ok', 'oka', 'listo', 'chau', 'adios', 'bye', 'obrigado',
        ];

        return in_array($norm, $saludos, true);
    }

    /**
     * Quita el Markdown básico de una respuesta del bot para mostrarla como texto plano.
     */
    private function sinMarkdown(?string $texto): string
    {
        $limpio = preg_replace('/\*{1,3}([^*]+)\*{1,3}/u', '$1', (string) $texto);
        $limpio = preg_replace('/[#>`_]+/u', '', (string) $limpio);

        return trim(preg_replace('/\s+/u', ' ', (string) $limpio));
    }

    /**
     * Saca emoji y símbolos de una etiqueta guardada en tracking_events.
     *
     * Los eventos guardan el textContent del botón, así que arrastran los emoji que la
     * interfaz tenía en ese momento. Limpiarlos al mostrar agrupa el histórico con lo
     * nuevo y evita que el panel muestre emoji sueltos.
     */
    private function sinEmoji(string $texto): string
    {
        $limpio = preg_replace('/[\x{1F000}-\x{1FAFF}\x{2190}-\x{2BFF}\x{FE0F}\x{200D}]/u', '', $texto);

        return trim(preg_replace('/\s+/', ' ', (string) $limpio));
    }

    /**
     * Normaliza una frase para agrupar variantes (minúsculas, sin acentos ni signos).
     */
    private function normalizarFrase(string $texto): string
    {
        $texto = mb_strtolower(trim($texto), 'UTF-8');
        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);
        $texto = preg_replace('/[^a-z0-9 ]+/', '', $texto);
        $texto = preg_replace('/\s+/', ' ', $texto);

        return trim($texto);
    }

    /**
     * Obtener estadísticas diarias
     */
    /**
     * Consultas por día de los últimos N días, incluyendo los días en cero.
     *
     * Se rellenan los huecos porque el sparkline necesita un punto por día: si se
     * saltearan los días sin actividad, la curva mentiría sobre el ritmo real.
     *
     * @return array<int, int>
     */
    private function serieDiaria($comercioId, int $dias = 14): array
    {
        $porDia = ChatConversation::forComercio($comercioId)
            ->where('created_at', '>=', now()->subDays($dias)->startOfDay())
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd');

        $serie = [];
        for ($i = $dias - 1; $i >= 0; $i--) {
            $serie[] = (int) ($porDia[now()->subDays($i)->toDateString()] ?? 0);
        }

        return $serie;
    }

    private function getDailyStats($comercioId, $days = 7)
    {
        return ChatConversation::forComercio($comercioId)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN used_openai = 1 THEN 1 ELSE 0 END) as openai_count'),
                DB::raw('AVG(response_time_ms) as avg_response_time')
            )
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * Exportar conversaciones
     */
    public function exportConversations(Comercio $comercio)
    {
        if (! auth()->user()->isSuperAdmin() && $comercio->user_id !== auth()->id()) {
            abort(403);
        }

        if (! auth()->user()->isSuperAdmin() && ! $comercio->tieneFeature('analytics')) {
            abort(403, 'Las analíticas están disponibles a partir del plan Pro.');
        }

        $conversations = ChatConversation::forComercio($comercio->id)
            ->with('comercio')
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = "conversaciones_{$comercio->nombre}_".now()->format('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($conversations) {
            $file = fopen('php://output', 'w');

            // Headers del CSV
            fputcsv($file, [
                'Fecha',
                'Comercio',
                'Mensaje Usuario',
                'Respuesta Bot',
                'Usó OpenAI',
                'Tiempo Respuesta (ms)',
                'IP',
                'User Agent',
            ]);

            foreach ($conversations as $conversation) {
                fputcsv($file, [
                    $conversation->created_at->format('Y-m-d H:i:s'),
                    $conversation->comercio->nombre,
                    $conversation->user_message,
                    $conversation->bot_response,
                    $conversation->used_openai ? 'Sí' : 'No',
                    $conversation->response_time_ms,
                    $conversation->user_ip,
                    $conversation->user_agent,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
