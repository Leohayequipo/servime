<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\Comercio;
use App\Models\Menu;
use App\Models\TrackingEvent;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Dashboard principal de analytics
     */
    public function dashboard()
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

        return view('analytics.dashboard', compact('comercios', 'stats'));
    }

    /**
     * Analytics específicos de un comercio
     */
    public function comercio(Comercio $comercio)
    {
        if (! auth()->user()->isSuperAdmin() && $comercio->user_id !== auth()->id()) {
            abort(403);
        }

        if (! auth()->user()->isSuperAdmin() && ! $comercio->tieneFeature('analytics')) {
            abort(403, 'Las analíticas están disponibles a partir del plan Pro.');
        }

        $comercioId = $comercio->id;

        $conversations = ChatConversation::forComercio($comercioId)
            ->recent(30)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'total_conversations' => ChatConversation::forComercio($comercioId)->count(),
            'conversations_today' => ChatConversation::forComercio($comercioId)
                ->whereDate('created_at', today())->count(),
            'openai_usage' => ChatConversation::forComercio($comercioId)->usedOpenAI()->count(),
            'avg_response_time' => ChatConversation::forComercio($comercioId)
                ->whereNotNull('response_time_ms')
                ->avg('response_time_ms'),
        ];

        // Preguntas más frecuentes
        $frequentQuestions = $this->getFrequentQuestions($comercioId);

        // Estadísticas por día
        $dailyStats = $this->getDailyStats($comercioId);

        // Métricas de comportamiento (eventos del menú público) + palabras
        $metrics = $this->getEventMetrics($comercioId);
        $topWords = $this->getTopWords($comercioId);

        return view('analytics.comercio', compact(
            'comercio',
            'conversations',
            'stats',
            'frequentQuestions',
            'dailyStats',
            'metrics',
            'topWords'
        ));
    }

    /**
     * Métricas derivadas de tracking_events (últimos 30 días).
     */
    private function getEventMetrics($comercioId): array
    {
        $events = TrackingEvent::forComercio($comercioId)
            ->recent(30)
            ->get(['event', 'data', 'session_id', 'created_at']);

        // Elección IA vs Clásico
        $choices = $events->where('event', 'menu_choice');
        $menuChoice = [
            'ia' => $choices->filter(fn ($e) => ($e->data['choice'] ?? null) === 'ia')->count(),
            'classic' => $choices->filter(fn ($e) => ($e->data['choice'] ?? null) === 'classic')->count(),
        ];

        // Promos más clickeadas
        $topPromos = $events->where('event', 'promo_click')
            ->groupBy(fn ($e) => $e->data['promo'] ?? '—')
            ->map->count()->sortDesc()->take(6);

        // Platos más deseados ("Lo quiero")
        $topDishes = $events->where('event', 'dish_want')
            ->groupBy(fn ($e) => $e->data['nombre'] ?? '—')
            ->map->count()->sortDesc()->take(8);

        // Embudo por sesiones únicas: visita -> elige -> muestra interés
        $funnel = [
            'visitas' => $events->where('event', 'page_view')->pluck('session_id')->unique()->count(),
            'eligieron' => $choices->pluck('session_id')->unique()->count(),
            'interesados' => $events->where('event', 'dish_want')->pluck('session_id')->unique()->count(),
        ];

        // Heatmap por día de semana (0=Dom..6=Sáb) x hora
        $heatmap = array_fill(0, 7, array_fill(0, 24, 0));
        $heatmapMax = 0;
        foreach ($events->where('event', 'page_view') as $e) {
            $dow = (int) $e->created_at->dayOfWeek;
            $hour = (int) $e->created_at->format('G');
            $heatmap[$dow][$hour]++;
            $heatmapMax = max($heatmapMax, $heatmap[$dow][$hour]);
        }

        return compact('menuChoice', 'topPromos', 'topDishes', 'funnel', 'heatmap', 'heatmapMax');
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
        $mensajes = ChatConversation::forComercio($comercioId)->recent(30)->pluck('user_message');

        $unigramas = [];
        $bigramas = [];

        foreach ($mensajes as $msg) {
            $tokens = $this->tokenizar((string) $msg);

            // Palabras sueltas: contenido (>=4 letras, no stopword)
            foreach ($tokens as $t) {
                if (mb_strlen($t) >= 4 && ! in_array($t, self::STOPWORDS, true)) {
                    $s = $this->singularizar($t);
                    $unigramas[$s] = ($unigramas[$s] ?? 0) + 1;
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
                $frase = $this->singularizar($a).' '.$this->singularizar($b);
                $bigramas[$frase] = ($bigramas[$frase] ?? 0) + 1;
            }
        }

        // Solo bigramas que se repiten (evita ruido de frases únicas)
        $conteo = $unigramas;
        foreach ($bigramas as $frase => $c) {
            if ($c >= 2) {
                $conteo[$frase] = $c;
            }
        }

        arsort($conteo);

        return collect($conteo)->take($limit);
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
        return ChatConversation::forComercio($comercioId)
            ->select('user_message', DB::raw('COUNT(*) as frequency'))
            ->groupBy('user_message')
            ->orderBy('frequency', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener estadísticas diarias
     */
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
