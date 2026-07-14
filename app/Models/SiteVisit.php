<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SiteVisit extends Model
{
    protected $fillable = [
        'visitor_id',
        'path',
        'referer',
        'source',
        'device',
        'humano',
        'country_code',
        'country',
        'city',
        'user_ip',
        'user_agent',
    ];

    protected $casts = [
        'humano' => 'boolean',
    ];

    /**
     * Umbral para mostrar el contador en la landing.
     *
     * La prueba social con números chicos RESTA credibilidad en vez de sumarla:
     * "3 visitas" le grita al visitante que no nos usa nadie, justo donde queríamos
     * decir lo contrario. Mejor no mostrar nada hasta tener volumen real.
     * Cuando el proyecto tenga tracción, bajar/ajustar este número y el contador
     * vuelve solo (el tracking nunca dejó de correr).
     */
    public const MIN_VISITAS_CONTADOR = 500;

    /**
     * Sólo visitas confirmadas por un navegador real (ejecutó JS).
     * Los bots quedan guardados pero fuera de toda métrica.
     */
    public function scopeHumanas($query)
    {
        return $query->where('humano', true);
    }

    /** Dominios conocidos → canal. El primero que matchea gana. */
    private const CANALES = [
        'instagram' => ['instagram.com', 'ig.me'],
        'facebook' => ['facebook.com', 'fb.com', 'fb.me'],
        'whatsapp' => ['whatsapp.com', 'wa.me'],
        'google' => ['google.', 'googleadservices.'],
        'tiktok' => ['tiktok.com'],
        'youtube' => ['youtube.com', 'youtu.be'],
        'linkedin' => ['linkedin.com', 'lnkd.in'],
        'x' => ['twitter.com', 'x.com', 't.co'],
        'bing' => ['bing.com'],
        'mercadolibre' => ['mercadolibre.', 'mercadolivre.'],
    ];

    /**
     * Canal por el que llegó la visita.
     * Prioriza ?utm_source= porque los navegadores in-app (Instagram, TikTok)
     * suelen no mandar referer, y si no, deduce por el dominio del referer.
     */
    public static function detectarSource(Request $request): string
    {
        $utm = trim((string) $request->query('utm_source'));
        if ($utm !== '') {
            return substr(preg_replace('/[^a-z0-9_\-]/i', '', strtolower($utm)), 0, 40) ?: 'otro';
        }

        $referer = (string) $request->headers->get('referer');
        if ($referer === '') {
            return 'directo';
        }

        $host = strtolower((string) parse_url($referer, PHP_URL_HOST));
        if ($host === '') {
            return 'directo';
        }

        // Navegación dentro del propio sitio: no es una fuente de tráfico
        if ($host === strtolower((string) parse_url(config('app.url'), PHP_URL_HOST))) {
            return 'directo';
        }

        foreach (self::CANALES as $canal => $dominios) {
            foreach ($dominios as $dominio) {
                if (str_contains($host, $dominio)) {
                    return $canal;
                }
            }
        }

        return 'otro';
    }

    /** Tipo de dispositivo a partir del user agent. */
    public static function detectarDevice(?string $userAgent): string
    {
        $ua = strtolower((string) $userAgent);

        if ($ua === '') {
            return 'desconocido';
        }

        if (preg_match('/ipad|tablet|kindle|silk|playbook/i', $ua)) {
            return 'tablet';
        }

        if (preg_match('/mobile|android|iphone|ipod|blackberry|windows phone|opera mini/i', $ua)) {
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * Resumen de "de dónde entran" para el dashboard de analytics (solo super admin).
     * Devuelve todo ya normalizado a {label, total} para que la vista sea tonta.
     */
    public static function resumen(int $dias, bool $incluirBots = false): array
    {
        $desde = now()->subDays($dias);

        // Base del período. Por defecto sólo personas: los bots no deben mover las métricas.
        $base = fn () => static::where('created_at', '>=', $desde)
            ->when(! $incluirBots, fn ($q) => $q->humanas());

        $ranking = function (string $columna, int $limite = 10) use ($base) {
            return $base()
                ->whereNotNull($columna)
                ->selectRaw("{$columna} as valor, COUNT(*) as total")
                ->groupBy($columna)
                ->orderByDesc('total')
                ->limit($limite)
                ->get()
                ->map(fn ($f) => ['label' => (string) $f->valor, 'total' => (int) $f->total])
                ->all();
        };

        $porCanal = $ranking('source');

        $porPais = $base()
            ->whereNotNull('country_code')
            ->selectRaw('country_code, country, COUNT(*) as total')
            ->groupBy('country_code', 'country')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($f) => [
                'label' => $f->country ?: $f->country_code,
                'code' => $f->country_code,
                'total' => (int) $f->total,
            ])
            ->all();

        return [
            'dias' => $dias,
            'incluir_bots' => $incluirBots,
            'visitas' => $base()->count(),
            'visitantes' => $base()->distinct('visitor_id')->count('visitor_id'),
            // Cuántas se descartaron por no confirmar (bots, crawlers, escáneres)
            'bots' => static::where('created_at', '>=', $desde)->where('humano', false)->count(),
            'canal_top' => $porCanal[0]['label'] ?? '—',
            'por_canal' => $porCanal,
            'por_dispositivo' => $ranking('device', 5),
            'por_pais' => $porPais,
            'por_ciudad' => $ranking('city', 10),
            // Visitas sin geolocalizar (cola pendiente, IP local o el proveedor no la reconoció)
            'sin_geo' => $base()->whereNull('country_code')->count(),
        ];
    }

    /**
     * Métricas del contador del footer. Cacheadas: el footer se renderiza en cada visita
     * y no queremos pegarle a la DB cada vez.
     *
     * @return array{activos:int, visitas:int, consultas_ia:int, mostrar:bool}
     */
    public static function stats(): array
    {
        try {
            return Cache::remember('site_visits_stats', now()->addSeconds(30), function () {
                // Sólo personas: los bots no inflan el contador público
                $visitas = static::humanas()->count();

                return [
                    // Visitantes con actividad en los últimos 5 minutos ("navegando ahora")
                    'activos' => static::humanas()
                        ->where('created_at', '>=', now()->subMinutes(5))
                        ->distinct('visitor_id')
                        ->count('visitor_id'),
                    'visitas' => $visitas,
                    // Consultas realmente respondidas por IA (menús + chat de la landing)
                    'consultas_ia' => ChatConversation::count() + ChatLanding::count(),
                    // El contador aparece solo cuando los números ya juegan a favor
                    'mostrar' => $visitas >= self::MIN_VISITAS_CONTADOR,
                ];
            });
        } catch (\Throwable $e) {
            // La tabla puede no existir todavía (deploy con la migración pendiente o fallada).
            // El contador es decorativo: NUNCA debe tirar abajo la home.
            // No cacheamos el fallo, así se recupera solo apenas la migración corra.
            report($e);

            return ['activos' => 0, 'visitas' => 0, 'consultas_ia' => 0, 'mostrar' => false];
        }
    }
}
