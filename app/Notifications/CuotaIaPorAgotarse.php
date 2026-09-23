<?php

namespace App\Notifications;

use App\Models\Comercio;
use Illuminate\Notifications\Notification;

/**
 * "Te queda poco cupo de mensajes del asistente este mes."
 *
 * Avisa al cruzar el 80%, no cuando ya se agotó: cuando se agota, el asistente deja de
 * responder en pleno servicio y el comercio se entera por sus clientes. Con el aviso
 * temprano tiene tiempo de pedir más cupo.
 */
class CuotaIaPorAgotarse extends Notification
{
    public function __construct(
        public Comercio $comercio,
        public int $usados,
        public int $cupo,
        public string $periodo,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $restantes = max(0, $this->cupo - $this->usados);

        return [
            'tipo' => 'cuota',
            'titulo' => "A {$this->comercio->nombre} le queda poco cupo del asistente",
            'detalle' => "Usó {$this->usados} de {$this->cupo} mensajes de este mes. Le quedan {$restantes}.",
            // Al dashboard y no a analytics: ahí ya está el aviso de cuota, y el panel de
            // analytics depende del plan (un Básico caería en la pantalla de "falta el plan").
            'url' => route('dashboard'),
            'comercio_id' => $this->comercio->id,
            'periodo' => $this->periodo,
        ];
    }
}
