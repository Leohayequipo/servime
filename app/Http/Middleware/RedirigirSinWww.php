<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * `www.servime.net` → `servime.net`, con redirección permanente (301).
 *
 * Las dos direcciones respondían el sitio entero, y cada página se declaraba canónica a sí
 * misma con el dominio por el que había entrado (`url()` usa el de la visita). Para Google
 * eran dos sitios: indexó la home sin www y los menús con www, y la fuerza de uno no le
 * sumaba al otro. El sitemap tenía el mismo problema: arma sus URLs con el dominio del
 * primero que lo pide después de vencer la caché.
 *
 * Los QR ya impresos con www siguen andando: la redirección conserva la ruta y el `?m=`
 * de la mesa. Sólo GET y HEAD: un POST redirigido llega como GET y se pierde lo que
 * mandaba (un mensaje del chat con la página abierta desde antes del cambio, por ejemplo).
 */
class RedirigirSinWww
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        if (str_starts_with($host, 'www.') && in_array($request->method(), ['GET', 'HEAD'], true)) {
            // En producción, https directo: el pedido puede llegar como http desde el CDN
            // de Hostinger, y redirigir a http sumaba un segundo salto hasta https.
            $esquema = app()->environment('production') ? 'https' : $request->getScheme();

            return redirect()->away($esquema.'://'.substr($host, 4).$request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
