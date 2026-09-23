<?php

namespace App\Jobs;

use App\Models\Menu;
use App\Services\RecomendadorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Calcula las recomendaciones de una carta después de procesarla.
 *
 * Va por la cola y no dentro de ProcessMenuJob para que una falla acá —OpenAI caído, una
 * carta rarísima— no deje el menú entero marcado como fallido: el menú ya está listo y
 * usable, las recomendaciones son un extra que se puede reintentar solo.
 */
class GenerarRecomendacionesJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public int $tries = 2;

    public function __construct(public Menu $menu) {}

    public function handle(RecomendadorService $recomendador): void
    {
        $resultado = $recomendador->generarYGuardar($this->menu);

        if (isset($resultado['error'])) {
            Log::warning("GenerarRecomendacionesJob: menú #{$this->menu->id}: {$resultado['error']}");

            return;
        }

        Log::info("GenerarRecomendacionesJob: menú #{$this->menu->id}, {$resultado['platos']} platos con recomendación");
    }
}
