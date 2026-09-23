<?php

namespace App\Notifications;

use App\Models\Comercio;
use Illuminate\Notifications\Notification;

/**
 * "El asistente está apagado."
 *
 * `enable_ai_responses` en false con la carta publicada: el cliente que escanea el QR ve
 * el menú, pero no tiene con quién hablar — justo la función que paga. Se apaga con un
 * interruptor en la pantalla del comercio y no hay nada en el panel que lo recuerde.
 */
class AsistenteApagado extends Notification
{
    public function __construct(public Comercio $comercio) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'menu_error',
            'titulo' => "El asistente de {$this->comercio->nombre} está apagado",
            'detalle' => 'Tus clientes ven el menú, pero no pueden preguntarle nada. Se enciende desde los datos del comercio.',
            'url' => route('comercios.edit', $this->comercio),
            'comercio_id' => $this->comercio->id,
        ];
    }
}
