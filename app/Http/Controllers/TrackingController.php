<?php

namespace App\Http\Controllers;

use App\Models\Comercio;
use App\Models\Mesa;
use App\Models\TrackingEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TrackingController extends Controller
{
    /**
     * Eventos permitidos (whitelist para no guardar basura).
     */
    private const ALLOWED_EVENTS = [
        'page_view',      // vio una pantalla (data.view: choose|chat|classic)
        'menu_choice',    // eligió IA o clásico (data.choice: ia|classic, data.desde, data.label)
        'promo_click',    // clickeó una promo (data.promo)
        'dish_want',      // clickeó "Lo quiero" (data.nombre, data.price)
        'dish_view',      // abrió el detalle de un plato para mirarlo (data.nombre). NO es
        // "lo quiero": mezclarlos inflaba "Platos más deseados".
        'order_summary',  // abrió el "Resumen para el camarero" (data.items) — el final del embudo
        'mic_start',      // arrancó a dictar por voz
        'categoria_tap',  // tocó una categoría en el chat (data.nombre, el del menú sin traducir)
        'tag_tap',        // filtró por una etiqueta del menú: sin TACC, vegano... (data.nombre)

        // Pedido de reseña en Google. Los DOS hacen falta: con los clicks solos, sin
        // denominador, no se puede saber si el bloque funciona o si simplemente se
        // mostró mucho.
        'resena_view',    // se le mostró el bloque de reseña
        'resena_click',   // tocó el botón y se fue a Google
        // Cerrarlo NO es lo mismo que ignorarlo: el que cierra lo vio y dijo que no (el
        // texto no convence), el que ignora ni lo registró (el momento está mal). Sin
        // este evento las dos cosas caen en la misma bolsa y no se sabe qué corregir.
        'resena_dismiss', // lo cerró con el "Ahora no"

        // Guía de bienvenida (menu/partials/guia). Con los tres se sabe si sirve o molesta:
        // cuántos la ven, cuántos la terminan y en qué paso la cierran los que no.
        'guia_vista',     // se le mostró (data.pantalla: portada|chat, data.repetida)
        'guia_completa',  // llegó al último paso
        'guia_cerrada',   // la cerró antes (data.paso, data.de)
    ];

    /**
     * Registrar un evento del menú público. Endpoint liviano, sin auth.
     */
    public function store(Request $request, Comercio $comercio): Response
    {
        $validated = $request->validate([
            'event' => 'required|string|max:50',
            'session_id' => 'nullable|string|max:100',
            'data' => 'nullable|array',
        ]);

        if (! in_array($validated['event'], self::ALLOWED_EVENTS, true)) {
            // Ignorar eventos desconocidos sin error (el cliente no necesita saberlo)
            return response()->noContent();
        }

        TrackingEvent::create([
            'comercio_id' => $comercio->id,
            // La mesa sale de la sesión (la puso ResolverMesa al escanear el QR), no del
            // body: así el cliente no puede atribuirle su actividad a otra mesa.
            'mesa_id' => Mesa::idDeSesion($comercio->id),
            'session_id' => $validated['session_id'] ?? null,
            'event' => $validated['event'],
            'data' => $validated['data'] ?? null,
            'user_ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        return response()->noContent();
    }
}
