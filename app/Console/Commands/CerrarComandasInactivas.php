<?php

namespace App\Console\Commands;

use App\Services\ComandaService;
use Illuminate\Console\Command;

/**
 * Cierra las comandas que quedaron abiertas sin que nadie las cierre.
 *
 * El mozo se va a olvidar de alguna, sobre todo la última del turno. Sin esto, la mesa 7
 * arranca el martes mostrando la milanesa del lunes y el mozo deja de confiar en la
 * pantalla, que es lo único que no se puede permitir.
 */
class CerrarComandasInactivas extends Command
{
    protected $signature = 'comandas:cerrar-inactivas
                            {--horas= : Horas sin pedidos para dar la mesa por terminada}';

    protected $description = 'Cierra las comandas abiertas que llevan horas sin actividad';

    public function handle(ComandaService $comandas): int
    {
        $horas = (int) ($this->option('horas') ?: ComandaService::HORAS_INACTIVIDAD);

        $cerradas = $comandas->cerrarInactivas($horas);

        $this->info($cerradas === 0
            ? "No había comandas con más de {$horas}h sin pedidos."
            : "Se cerraron {$cerradas} comanda(s) con más de {$horas}h sin pedidos.");

        return self::SUCCESS;
    }
}
