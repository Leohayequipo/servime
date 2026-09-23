<?php

namespace App\Notifications;

use App\Models\Comercio;
use App\Models\Menu;
use Illuminate\Notifications\Notification;

/**
 * "Tu carta está lista pero no la ve nadie."
 *
 * El menú terminó de procesarse y quedó sin activar, así que el QR no muestra nada. Es
 * el caso más caro de todos: el comercio cree que ya lo publicó y se entera cuando un
 * cliente en la mesa no encuentra la carta.
 */
class MenuSinPublicar extends Notification
{
    public function __construct(
        public Comercio $comercio,
        public Menu $menu,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'menu_error',
            'titulo' => "La carta de {$this->comercio->nombre} está lista pero no la ve nadie",
            'detalle' => '"'.($this->menu->nombre ?? 'Tu menú').'" se procesó y quedó sin activar. Activalo para que aparezca al escanear el QR.',
            'url' => route('menus.index', $this->comercio),
            'comercio_id' => $this->comercio->id,
            'menu_id' => $this->menu->id,
        ];
    }
}
