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
        'country_code',
        'country',
        'city',
        'user_ip',
        'user_agent',
    ];

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
     * Métricas del contador del footer. Cacheadas: el footer se renderiza en cada visita
     * y no queremos pegarle a la DB cada vez.
     *
     * @return array{activos:int, visitas:int, consultas_ia:int}
     */
    public static function stats(): array
    {
        try {
            return Cache::remember('site_visits_stats', now()->addSeconds(30), function () {
                return [
                    // Visitantes con actividad en los últimos 5 minutos ("navegando ahora")
                    'activos' => static::where('created_at', '>=', now()->subMinutes(5))
                        ->distinct('visitor_id')
                        ->count('visitor_id'),
                    'visitas' => static::count(),
                    // Consultas realmente respondidas por IA (menús + chat de la landing)
                    'consultas_ia' => ChatConversation::count() + ChatLanding::count(),
                ];
            });
        } catch (\Throwable $e) {
            // La tabla puede no existir todavía (deploy con la migración pendiente o fallada).
            // El contador es decorativo: NUNCA debe tirar abajo la home.
            // No cacheamos el fallo, así se recupera solo apenas la migración corra.
            report($e);

            return ['activos' => 0, 'visitas' => 0, 'consultas_ia' => 0];
        }
    }
}
