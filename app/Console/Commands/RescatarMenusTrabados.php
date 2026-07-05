<?php

namespace App\Console\Commands;

use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RescatarMenusTrabados extends Command
{
    protected $signature = 'menus:rescatar-trabados {--minutos=20 : Minutos en "procesando" a partir de los cuales se considera trabado}';

    protected $description = 'Detecta menús que quedaron "procesando" demasiado tiempo (job que nunca corrió o murió), los marca como error y avisa al comercio.';

    public function handle(): int
    {
        $minutos = (int) $this->option('minutos');
        $limite = now()->subMinutes($minutos);

        $trabados = Menu::where('estado', Menu::ESTADO_PROCESANDO)
            ->where('updated_at', '<', $limite)
            ->with('comercio.user')
            ->get();

        if ($trabados->isEmpty()) {
            $this->info('No hay menús trabados.');

            return self::SUCCESS;
        }

        foreach ($trabados as $menu) {
            $menu->update(['procesado' => false, 'estado' => Menu::ESTADO_ERROR]);

            Log::warning("RescatarMenusTrabados: menú #{$menu->id} llevaba +{$minutos} min en 'procesando'. Marcado como error.", [
                'menu_id' => $menu->id,
                'comercio_id' => $menu->comercio_id,
            ]);

            $user = $menu->comercio?->user;
            if ($user?->email) {
                $menuName = $menu->nombre ?? 'tu menú';
                try {
                    Mail::html(
                        "<p>Hola {$user->name},</p><p>El menú <strong>{$menuName}</strong> no se pudo procesar (tardó más de lo esperado). Podés volver a subirlo desde tu panel.</p>",
                        function ($msg) use ($user, $menuName) {
                            $msg->to($user->email, $user->name)
                                ->subject("⚠️ El menú \"{$menuName}\" no se pudo procesar");
                        }
                    );
                } catch (\Throwable $e) {
                    Log::error("RescatarMenusTrabados: no se pudo avisar por mail del menú #{$menu->id}: {$e->getMessage()}");
                }
            }
        }

        $this->info("Se marcaron como error {$trabados->count()} menú(s) trabado(s).");

        return self::SUCCESS;
    }
}
