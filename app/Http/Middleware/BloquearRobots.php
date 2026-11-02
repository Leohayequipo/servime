<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Los robots no hablan con el asistente ni suman a las estadísticas.
 *
 * Google ejecuta el JS de las páginas que visita, y los atajos de la portada son links a
 * `/chat?q=...` que mandan la pregunta solos al abrirse. El 1 y 2/11/2026, después de
 * publicar el sitemap con todos los menús, Googlebot dejó 15 mensajes en cuatro comercios:
 * cada uno fue una consulta real a OpenAI, se descontó del cupo del comercio y apareció en
 * su panel como si fuera un cliente. El freno principal es `Disallow: /api/` en robots.txt;
 * esto es para los que no lo respetan.
 *
 * No usa el `esBot()` de TrackSiteVisit a propósito: ese busca "bot" en cualquier lado y
 * agarra celulares reales como el CUBOT. Allá una falsa alarma descuenta una visita; acá
 * deja a un cliente sin respuesta.
 */
class BloquearRobots
{
    // `bot/` es la firma de casi todos (Googlebot/2.1, bingbot/2.0, GPTBot/1.0...) y los
    // robots que se identifican dejan una URL; ningún navegador lleva "http" en su nombre.
    // GoogleOther, el otro robot de Google, no tiene ninguna de las dos (visto en prod).
    private const PATRON = '/bot\/|https?:\/\/|googleother|crawler|spider|headless/i';

    public function handle(Request $request, Closure $next): Response
    {
        if (self::esRobot($request->userAgent())) {
            return response()->json(['success' => false], 403);
        }

        return $next($request);
    }

    public static function esRobot(?string $userAgent): bool
    {
        // Todo navegador manda su nombre; sin nombre es un script.
        if (trim((string) $userAgent) === '') {
            return true;
        }

        return (bool) preg_match(self::PATRON, $userAgent);
    }
}
