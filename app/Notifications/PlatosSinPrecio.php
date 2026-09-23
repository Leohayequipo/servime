<?php

namespace App\Notifications;

use App\Models\Menu;
use Illuminate\Notifications\Notification;

/**
 * "Quedaron N platos sin precio."
 *
 * Sale junto con el menú procesado, que es el único momento en que el comercio va a
 * abrir la carta a revisarla. Le dice qué mirar: sin esto, "revisalo antes de activarlo"
 * es un consejo que nadie sigue porque no señala nada.
 */
class PlatosSinPrecio extends Notification
{
    public function __construct(
        public Menu $menu,
        public int $sinPrecio,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $comercio = $this->menu->comercio;

        return [
            'tipo' => 'menu_revisar',
            'titulo' => $this->sinPrecio === 1
                ? 'Un plato quedó sin precio'
                : "{$this->sinPrecio} platos quedaron sin precio",
            'detalle' => 'En "'.($this->menu->nombre ?? 'tu menú').'". La IA no los pudo leer de la carta: cargalos antes de activarla.',
            'url' => $comercio
                ? route('menus.edit', [$comercio, $this->menu])
                : route('dashboard'),
            'comercio_id' => $comercio?->id,
            'menu_id' => $this->menu->id,
        ];
    }
}
