<?php

namespace App\Console\Commands;

use App\Models\Menu;
use App\Services\RecomendadorService;
use Illuminate\Console\Command;

/**
 * Regenera a mano las recomendaciones de una carta.
 *
 * Hace falta para las cartas que ya estaban procesadas antes de esta función, y cada vez
 * que el comercio cambia los platos: las recomendaciones apuntan a nombres de la carta,
 * así que si se renombra o se borra un plato hay que recalcularlas.
 */
class GenerarRecomendaciones extends Command
{
    protected $signature = 'menu:recomendaciones {menu? : id del menú} {--todos : todas las cartas activas}';

    protected $description = 'Calcula con IA las duplas de la carta (con qué va bien cada plato)';

    public function handle(RecomendadorService $recomendador): int
    {
        $menus = $this->menus();

        if ($menus->isEmpty()) {
            $this->warn('No hay menús para procesar.');

            return self::SUCCESS;
        }

        foreach ($menus as $menu) {
            $this->line("Menú #{$menu->id} · {$menu->nombre}");
            $resultado = $recomendador->generarYGuardar($menu);

            if (isset($resultado['error'])) {
                $this->error('  '.$resultado['error']);

                continue;
            }

            $this->info("  {$resultado['platos']} platos con recomendación");
        }

        return self::SUCCESS;
    }

    private function menus()
    {
        if ($id = $this->argument('menu')) {
            return Menu::where('id', $id)->get();
        }

        if ($this->option('todos')) {
            return Menu::where('estado', Menu::ESTADO_LISTO)->where('activo', true)->get();
        }

        $this->warn('Indicá el id de un menú o usá --todos.');

        return Menu::whereRaw('1 = 0')->get();
    }
}
