<?php

namespace App\Notifications;

use App\Models\Comercio;
use App\Models\Plan;
use Illuminate\Notifications\Notification;

/**
 * "Te cambiamos el plan."
 *
 * El cambio lo hace el super admin a mano, así que hasta ahora el comercio no se
 * enteraba de que ya tenía lo que pidió. El detalle nombra lo que ganó (las features
 * salen del plan, no escritas acá: se editan desde /admin/plans).
 */
class PlanAsignado extends Notification
{
    public function __construct(
        public Comercio $comercio,
        public Plan $plan,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $features = collect($this->plan->features ?? [])
            ->map(fn ($f) => Plan::FEATURES[$f] ?? $f)
            ->implode(', ');

        return [
            'tipo' => 'plan',
            'titulo' => "{$this->comercio->nombre} pasó al plan {$this->plan->nombre}",
            'detalle' => $features !== ''
                ? "Ya podés usar: {$features}."
                : 'Ya está activo en tu cuenta.',
            'url' => route('dashboard'),
            'comercio_id' => $this->comercio->id,
            'plan_id' => $this->plan->id,
        ];
    }
}
