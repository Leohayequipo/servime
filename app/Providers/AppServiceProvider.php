<?php

namespace App\Providers;

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
    }
}
