<?php

namespace App\Http\Middleware;

use App\Models\Comercio;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Puerta de la pantalla del mozo: link secreto del local + PIN validado en la sesión.
 *
 * El token identifica al comercio; el PIN es lo que impide que el link, si se filtra en
 * una foto o en un grupo de WhatsApp, le dé a cualquiera la comanda del local en vivo.
 *
 * El token inválido devuelve 404 y no 403 a propósito: un 403 confirmaría que ese link
 * existe pero no tenés permiso, que es justo lo que no hay que contarle a quien está
 * probando tokens.
 */
class MozoAutenticado
{
    /** Clave de sesión donde queda marcado que esta tablet ya pasó el PIN. */
    public static function claveSesion(Comercio $comercio): string
    {
        // Lleva el token adentro: si el dueño rota el link, las tablets que estaban
        // adentro con el token viejo tienen que volver a pedir el PIN.
        return 'mozo:'.$comercio->id.':'.substr((string) $comercio->mozo_token, 0, 8);
    }

    public function handle(Request $request, Closure $next, string $modo = 'estricto'): Response
    {
        $comercio = Comercio::where('mozo_token', $request->route('token'))->first();

        if (! $comercio || ! $comercio->tieneAccesoMozo()) {
            abort(404);
        }

        // El plan manda por encima del link: si el comercio deja de tener la feature, la
        // pantalla se apaga aunque la tablet siga con la sesión abierta.
        if (! $comercio->tieneFeature('comandas')) {
            abort(403, 'Esta función no está disponible en el plan actual del comercio.');
        }

        $request->attributes->set('comercioMozo', $comercio);

        // El modo 'abierto' lo usan la pantalla del PIN y su envío: son las únicas dos
        // rutas a las que se puede llegar sin haberlo puesto todavía.
        if ($modo !== 'abierto' && ! $request->session()->get(self::claveSesion($comercio))) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Sesión vencida'], 401)
                : redirect()->route('mozo.pin', $comercio->mozo_token);
        }

        return $next($request);
    }
}
