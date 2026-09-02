<?php

namespace App\Http\Middleware;

use App\Models\Comercio;
use App\Models\Mesa;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lee el `?m=<token>` del QR de la mesa y lo deja guardado en la sesión.
 *
 * El token entra UNA vez, cuando el cliente escanea, y de ahí en más vive en la
 * sesión: así la mesa sobrevive a la navegación (portada → chat → menú clásico) sin
 * arrastrar el query string por todos lados, y sobre todo sin que el front tenga que
 * mandarla en cada request. Como `/api/track`, `/api/cart/*` y `/api/chat/*` son rutas
 * web con la misma sesión, el backend la lee solo: el cliente no puede decir que está
 * en otra mesa.
 *
 * Un token que no existe, de otro comercio o de una mesa desactivada se ignora en
 * silencio y el menú abre normal. Un QR viejo dando vueltas por el salón nunca puede
 * dejar a alguien sin poder ver la carta.
 */
class ResolverMesa
{
    public function handle(Request $request, Closure $next): Response
    {
        $comercio = $request->route('comercio');
        $token = $request->query('m');

        if ($comercio instanceof Comercio && is_string($token) && $token !== '') {
            $mesa = Mesa::where('comercio_id', $comercio->id)
                ->where('token', $token)
                ->activas()
                ->first();

            if ($mesa) {
                Mesa::recordarEnSesion($mesa);
            }
        }

        // Las vistas del menú necesitan el número para mostrarlo. Solo en GET: en los
        // POST de tracking, carrito y chat alcanza con el id de la sesión y cargar el
        // modelo sería una consulta por evento, al pedo.
        if ($comercio instanceof Comercio && $request->isMethod('GET')) {
            view()->share('mesaActual', Mesa::deSesion($comercio->id));
        }

        return $next($request);
    }
}
