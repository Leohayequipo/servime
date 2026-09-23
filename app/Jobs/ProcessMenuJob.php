<?php

namespace App\Jobs;

use App\Models\Menu;
use App\Notifications\MenuConError;
use App\Notifications\MenuListo;
use App\Notifications\PlatosSinPrecio;
use App\Services\MenuProcessorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessMenuJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 700;

    public int $tries = 2;

    public function __construct(public Menu $menu) {}

    public function handle(): void
    {
        (new MenuProcessorService)->processMenu($this->menu);

        $this->menu->refresh()->load('comercio.user');

        // Si el procesamiento no terminó en "listo", no mandamos mail de éxito.
        // El estado de error ya lo dejó marcado el servicio.
        if (! $this->menu->estaListo()) {
            return;
        }

        $user = $this->menu->comercio?->user;

        // La campanita del panel, para el que subió el menú y cerró la pestaña: el mail
        // puede tardar o irse a spam, y el aviso lo espera adentro igual.
        $user?->notify(new MenuListo($this->menu));

        // Y si la IA no pudo leer algunos precios, decirle cuántos: "revisalo antes de
        // activarlo" no señala nada, y así sabe qué mirar.
        $sinPrecio = $this->menu->platosSinPrecio();

        if ($user && $sinPrecio > 0) {
            $user->notify(new PlatosSinPrecio($this->menu, $sinPrecio));
        }

        if ($user?->email) {
            $menuName = $this->menu->nombre ?? 'tu menú';
            $comercioName = $this->menu->comercio->nombre ?? '';
            $menuUrl = route('menus.index', $this->menu->comercio);

            Mail::html(
                view('emails.menu_procesado', compact('user', 'menuName', 'comercioName', 'menuUrl'))->render(),
                function ($msg) use ($user, $menuName) {
                    $msg->to($user->email, $user->name)
                        ->subject("✅ Menú \"{$menuName}\" procesado exitosamente");
                }
            );
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error("ProcessMenuJob: falló para menú #{$this->menu->id}: ".$e->getMessage());
        $this->menu->update(['procesado' => false, 'estado' => \App\Models\Menu::ESTADO_ERROR]);

        $this->menu->load('comercio.user');
        $user = $this->menu->comercio?->user;

        $user?->notify(new MenuConError($this->menu));

        if ($user?->email) {
            $menuName = $this->menu->nombre ?? 'tu menú';

            Mail::html(
                "<p>Hola {$user->name},</p><p>Hubo un problema al procesar el menú <strong>{$menuName}</strong>. Nuestro equipo ya fue notificado. Podés intentar subirlo nuevamente.</p>",
                function ($msg) use ($user, $menuName) {
                    $msg->to($user->email, $user->name)
                        ->subject("⚠️ Error al procesar el menú \"{$menuName}\"");
                }
            );
        }
    }
}
