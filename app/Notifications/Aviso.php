<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;

/**
 * Manda un aviso sólo si no se mandó ya el mismo.
 *
 * Casi todos los avisos de la campanita se calculan sobre un estado que se repite —el
 * cupo sigue agotado, la carta sigue sin publicar— así que sin esto el comercio recibiría
 * el mismo cartel en cada consulta del chat o en cada corrida del cron, y la campanita se
 * volvería ruido de fondo en un día.
 *
 * La clave no es un campo aparte: son los propios datos de la notificación
 * (`comercio_id`, `menu_id`, `periodo`...), que ya se guardan en `notifications.data`.
 */
class Aviso
{
    /**
     * @param  array<string, mixed>  $criterio  datos que identifican al aviso (ej: ['menu_id' => 4])
     * @param  callable(): Notification  $fabrica
     * @return bool si se mandó
     */
    public static function unaVez(User $user, string $clase, array $criterio, callable $fabrica): bool
    {
        $query = DB::table('notifications')
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->getKey())
            ->where('type', $clase);

        foreach ($criterio as $campo => $valor) {
            $query->where('data->'.$campo, $valor);
        }

        if ($query->exists()) {
            return false;
        }

        $user->notify($fabrica());

        return true;
    }
}
