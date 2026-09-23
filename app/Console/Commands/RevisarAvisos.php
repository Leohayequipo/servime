<?php

namespace App\Console\Commands;

use App\Models\Comercio;
use App\Models\Menu;
use App\Notifications\AsistenteApagado;
use App\Notifications\Aviso;
use App\Notifications\MenuSinPublicar;
use Illuminate\Console\Command;

/**
 * Avisos que no salen de un evento sino de cómo quedó la cuenta: una carta lista sin
 * publicar, el asistente apagado. Nadie los "dispara", así que hay que ir a mirarlos.
 *
 * Corre una vez por día (ver routes/console.php). Cada aviso se manda **una sola vez**
 * —`Aviso::unaVez()`— porque el estado se repite en cada corrida: sin eso, un menú sin
 * activar generaría un aviso por día hasta que el comercio deje de mirar la campanita.
 */
class RevisarAvisos extends Command
{
    protected $signature = 'avisos:revisar';

    protected $description = 'Revisa el estado de cada comercio y avisa lo que quedó a medias';

    /** Margen antes de avisar por una carta sin publicar: recién subida puede estar por activarse. */
    private const HORAS_DE_GRACIA = 24;

    public function handle(): int
    {
        $mandados = 0;

        $comercios = Comercio::with(['user', 'menus'])
            ->whereHas('user')
            ->get();

        foreach ($comercios as $comercio) {
            $mandados += (int) $this->cartaSinPublicar($comercio);
            $mandados += (int) $this->asistenteApagado($comercio);
        }

        $this->info("Avisos mandados: {$mandados}");

        return self::SUCCESS;
    }

    /**
     * Tiene un menú procesado y NINGUNO publicado: el QR no muestra nada.
     *
     * La condición es "ninguno activo" y no "este menú está inactivo" a propósito: un
     * comercio puede tener cartas viejas apagadas sin que eso sea un problema.
     */
    private function cartaSinPublicar(Comercio $comercio): bool
    {
        if ($comercio->menus->firstWhere('activo', true)) {
            return false;
        }

        $menu = $comercio->menus
            ->filter(fn (Menu $m) => $m->estaListo()
                && $m->updated_at?->lt(now()->subHours(self::HORAS_DE_GRACIA)))
            ->sortByDesc('updated_at')
            ->first();

        if (! $menu) {
            return false;
        }

        return Aviso::unaVez(
            $comercio->user,
            MenuSinPublicar::class,
            ['menu_id' => $menu->id],
            fn () => new MenuSinPublicar($comercio, $menu),
        );
    }

    /**
     * El asistente apagado con la carta publicada. Al revés (apagado y sin carta) no se
     * avisa: ahí todavía está armando el menú y el aviso llegaría antes de tiempo.
     */
    private function asistenteApagado(Comercio $comercio): bool
    {
        if ($comercio->enable_ai_responses || ! $comercio->menus->firstWhere('activo', true)) {
            return false;
        }

        return Aviso::unaVez(
            $comercio->user,
            AsistenteApagado::class,
            ['comercio_id' => $comercio->id],
            fn () => new AsistenteApagado($comercio),
        );
    }
}
