<?php

namespace App\Notifications;

use App\Models\Menu;
use Illuminate\Notifications\Notification;

/**
 * "El menú falló al procesarse."
 *
 * Sin esto, un menú que quedó en error sólo se ve entrando a la lista de menús: el
 * comercio cree que ya lo subió y se entera cuando un cliente no encuentra nada.
 */
class MenuConError extends Notification
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
            'tipo' => 'menu_error',
            'titulo' => 'No pudimos procesar tu menú',
            'detalle' => '"'.($this->menu->nombre ?? 'Menú').'" quedó con error. Podés volver a subirlo o escribirnos.',
            'url' => $comercio ? route('menus.index', $comercio) : route('dashboard'),
            'comercio_id' => $comercio?->id,
            'menu_id' => $this->menu->id,
        ];
    }
}
