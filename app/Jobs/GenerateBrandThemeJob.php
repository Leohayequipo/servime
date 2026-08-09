<?php

namespace App\Jobs;

use App\Models\Comercio;
use App\Services\BrandThemeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Genera el tema del menú público a partir de la portada del comercio.
 *
 * Va en cola porque procesar la imagen tarda ~1s y esta pantalla se abre en
 * cada escaneo de QR: no se puede pagar dentro del request.
 */
class GenerateBrandThemeJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $tries = 2;

    public function __construct(public Comercio $comercio) {}

    public function handle(BrandThemeService $temas): void
    {
        $comercio = $this->comercio->refresh();

        if (! $comercio->portada_url) {
            return;
        }

        $ruta = Storage::disk('public')->path($comercio->portada_url);

        if (! is_file($ruta)) {
            $comercio->update(['portada_estado' => 'error']);
            Log::warning('Portada inexistente al generar tema', [
                'comercio_id' => $comercio->id,
                'portada_url' => $comercio->portada_url,
            ]);

            return;
        }

        try {
            $comercio->update($temas->generar($comercio, $ruta) + ['portada_estado' => 'listo']);
        } catch (\Throwable $e) {
            $comercio->update(['portada_estado' => 'error']);

            Log::error('Error generando el tema de marca: '.$e->getMessage(), [
                'comercio_id' => $comercio->id,
                'exception' => $e,
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        $this->comercio->update(['portada_estado' => 'error']);
    }
}
