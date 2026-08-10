<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // En producción, forzar HTTPS en todas las URLs generadas
        // (assets, rutas, links de email como el reset de contraseña).
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        $this->configurarRateLimiters();
    }

    /**
     * Límites de las rutas públicas del menú.
     *
     * El throttle por defecto de Laravel cuenta por IP, y en un comercio TODOS los clientes
     * salen por el wifi del local con la misma IP pública: un mediodía lleno se comía el
     * cupo entre todos y a la mesa 8 le empezaba a fallar el menú.
     *
     * Por eso cada límite es doble:
     *  - por DISPOSITIVO (el session_id que ya manda el front), que es el cupo justo por persona;
     *  - por IP, mucho más alto, que queda de red de contención.
     *
     * El de IP no sobra: el session_id lo elige el cliente y se puede rotar, así que sin ese
     * segundo límite alcanzaba con mandar un id nuevo en cada request para no tener tope.
     */
    private function configurarRateLimiters(): void
    {
        // Chat con IA: cada mensaje cuesta plata, así que es el más ajustado.
        // El gasto real igual lo topea la cuota mensual del plan del comercio.
        RateLimiter::for('chat-ia', fn (Request $request) => [
            Limit::perMinute(20)->by('sess:'.$this->claveVisitante($request)),
            Limit::perMinute(60)->by('ip:'.$request->ip()),
        ]);

        // Carrito y tracking son escrituras baratas: el límite es contra abuso, no por costo.
        // El de tracking va holgado a propósito: un 429 acá se pierde en silencio y ensucia
        // las métricas del comercio sin que nadie se entere.
        RateLimiter::for('carrito', fn (Request $request) => [
            Limit::perMinute(60)->by('sess:'.$this->claveVisitante($request)),
            Limit::perMinute(300)->by('ip:'.$request->ip()),
        ]);

        RateLimiter::for('tracking', fn (Request $request) => [
            Limit::perMinute(120)->by('sess:'.$this->claveVisitante($request)),
            Limit::perMinute(600)->by('ip:'.$request->ip()),
        ]);
    }

    /**
     * Identifica al dispositivo: header (chat) o body (carrito y tracking).
     *
     * Si no viene ninguno se cae a la IP, y ahí sí todos los del mismo wifi sin id
     * comparten cupo. Es el mal menor: sin esto compartirían un único balde global.
     */
    private function claveVisitante(Request $request): string
    {
        $sessionId = $request->header('X-Session-ID') ?: $request->input('session_id');

        if (! is_string($sessionId) || $sessionId === '') {
            return 'anon-'.$request->ip();
        }

        // Mismo tope que la validación del endpoint, para no armar claves gigantes.
        return substr($sessionId, 0, 100);
    }
}
