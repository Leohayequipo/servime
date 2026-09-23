<?php

namespace App\Notifications;

use App\Models\Comercio;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;

/**
 * "Se acabó el cupo del asistente": a partir de acá el chat responde el mensaje amable
 * y no llama a la IA, así que el comercio pierde la función que vende el producto.
 *
 * Va también por mail porque es el único aviso que no puede esperar a que entre al
 * panel. Por eso encola: se dispara desde el chat de un cliente y no puede colgar
 * esa respuesta esperando al servidor de correo.
 */
class CuotaIaAgotada extends Notification implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public function __construct(
        public Comercio $comercio,
        public int $cupo,
        public string $periodo,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'cuota',
            'titulo' => "{$this->comercio->nombre} se quedó sin cupo del asistente",
            'detalle' => "Usó los {$this->cupo} mensajes de este mes. Hasta que se renueve, el asistente no responde consultas.",
            'url' => route('dashboard'),
            'comercio_id' => $this->comercio->id,
            'periodo' => $this->periodo,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("⚠️ {$this->comercio->nombre} se quedó sin cupo del asistente")
            ->view('emails.cuota_agotada', [
                'user' => $notifiable,
                'comercio' => $this->comercio,
                'cupo' => $this->cupo,
                'soporteWhatsapp' => config('services.support.whatsapp'),
                'soporteEmail' => config('services.support.email'),
            ]);
    }
}
