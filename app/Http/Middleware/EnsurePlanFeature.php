<?php

namespace App\Http\Middleware;

use App\Models\Comercio;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe una ruta a comercios cuyo plan incluye la feature dada.
 * El super admin siempre pasa. Uso: ->middleware('plan.feature:excel').
 */
class EnsurePlanFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if ($user && $user->isSuperAdmin()) {
            return $next($request);
        }

        $comercio = $request->route('comercio');

        if ($comercio instanceof Comercio && $comercio->tieneFeature($feature)) {
            return $next($request);
        }

        abort(403, 'Esta función no está disponible en el plan actual del comercio.');
    }
}
