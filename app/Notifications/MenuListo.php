<?php

namespace App\Notifications;

use App\Models\Menu;
use Illuminate\Notifications\Notification;

/**
 * "Tu menú terminó de procesarse."
 *
 * El procesamiento va por la cola y tarda: el que sube el PDF y cierra la pestaña hoy
 * sólo se entera por mail. Este es el mismo aviso, esperándolo en el panel.
 */
class MenuListo extends Notification
{
    public function __construct(public Menu $menu) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $comercio = $this->menu->comercio;

        return [
            'tipo' => 'menu_listo',
            'titulo' => 'Tu menú quedó listo',
            'detalle' => '"'.($this->menu->nombre ?? 'Menú').'"'
                .($comercio ? ' de '.$comercio->nombre : '')
                .' terminó de procesarse. Revisalo antes de activarlo.',
            'url' => $comercio ? route('menus.index', $comercio) : route('dashboard'),
            'comercio_id' => $comercio?->id,
            'menu_id' => $this->menu->id,
        ];
    }
}
