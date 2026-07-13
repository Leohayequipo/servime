<?php

namespace App\Http\Middleware;

use App\Jobs\GeolocateSiteVisit;
use App\Models\SiteVisit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Registra una visita anónima a la web pública (landing) para el contador del footer.
 * No guarda datos personales: el visitante se identifica con un id aleatorio en cookie.
 */
class TrackSiteVisit
{
    /** Cookie con el id anónimo del visitante. */
    private const COOKIE = 'sv_vid';

    /** Ventana en la que un mismo visitante NO vuelve a sumar visita (evita contar cada F5). */
    private const VENTANA_MINUTOS = 30;

    /** Un año, en minutos. */
    private const COOKIE_MINUTOS = 525600;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->deberiaRegistrar($request, $response)) {
            return $response;
        }

        $visitorId = $request->cookie(self::COOKIE);

        if (! is_string($visitorId) || $visitorId === '') {
            $visitorId = Str::random(40);
        }

        // Cache::add() sólo entra si la clave no existía: una visita por visitante cada 30'
        if (Cache::add('site_visit:'.$visitorId, true, now()->addMinutes(self::VENTANA_MINUTOS))) {
            try {
                $ip = $this->ipVisitante($request);

                $visit = SiteVisit::create([
                    'visitor_id' => $visitorId,
                    'path' => Str::limit($request->path(), 190, ''),
                    'referer' => Str::limit((string) $request->headers->get('referer'), 190, '') ?: null,
                    'source' => SiteVisit::detectarSource($request),
                    'device' => SiteVisit::detectarDevice($request->userAgent()),
                    'user_ip' => $ip,
                    'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
                ]);

                // País/ciudad se resuelven en cola: llamar a una API externa acá
                // le metería latencia a la carga de la landing.
                GeolocateSiteVisit::dispatch($visit->id, $ip);

                // El contador ya se renderizó con datos previos a esta visita: invalidamos
                // para que el refresco en vivo del footer incluya al visitante actual.
                Cache::forget('site_visits_stats');
            } catch (\Throwable $e) {
                // El contador nunca debe romper la landing
                report($e);
            }
        }

        return $response->withCookie(cookie(self::COOKIE, $visitorId, self::COOKIE_MINUTOS));
    }

    private function deberiaRegistrar(Request $request, Response $response): bool
    {
        // Sólo cargas reales de página (no AJAX, no prefetch, no redirects)
        if (! $request->isMethod('GET') || $request->ajax() || $request->wantsJson()) {
            return false;
        }

        if ($response->getStatusCode() !== 200) {
            return false;
        }

        // Los usuarios logueados (dueños de comercio / admin) no inflan el contador
        if (auth()->check()) {
            return false;
        }

        return ! $this->esBot((string) $request->userAgent());
    }

    /**
     * IP del visitante detrás del proxy de Hostinger/Cloudflare, SÓLO para estadísticas.
     *
     * A propósito NO usamos trustProxies() global: eso haría que $request->ip() confíe en
     * headers que el cliente puede falsear, y con eso se podrían saltear los rate limits
     * (ej. el throttle del chat con IA, que cuesta plata). Al leer el header únicamente acá,
     * lo peor que puede hacer un header falseado es mentir sobre el país de su propia visita.
     */
    private function ipVisitante(Request $request): string
    {
        foreach (['CF-Connecting-IP', 'X-Forwarded-For'] as $header) {
            $valor = (string) $request->headers->get($header);

            if ($valor === '') {
                continue;
            }

            // X-Forwarded-For puede venir encadenado: "cliente, proxy1, proxy2"
            $candidata = trim(explode(',', $valor)[0]);

            if (filter_var($candidata, FILTER_VALIDATE_IP)) {
                return $candidata;
            }
        }

        return (string) $request->ip();
    }

    private function esBot(string $userAgent): bool
    {
        if ($userAgent === '') {
            return true;
        }

        return (bool) preg_match(
            '/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|whatsapp|telegram|preview|monitor|curl|wget|headless|lighthouse|pingdom|uptime/i',
            $userAgent
        );
    }
}
