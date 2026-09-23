<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * La campanita del panel: abrir un aviso y marcar todos como leídos.
 *
 * Los avisos se buscan SIEMPRE dentro de los del usuario (`$request->user()->notifications()`),
 * así el id de la URL no sirve para leer ni marcar el aviso de otro comercio.
 */
class NotificacionController extends Controller
{
    /**
     * Marca el aviso como leído y lleva a donde se resuelve.
     */
    public function abrir(Request $request, string $notificacion)
    {
        $aviso = $request->user()->notifications()->findOrFail($notificacion);

        $aviso->markAsRead();

        $url = $aviso->data['url'] ?? null;

        return redirect($url && str_starts_with($url, url('/')) ? $url : route('dashboard'));
    }

    public function leerTodas(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
