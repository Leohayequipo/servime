<?php

namespace App\Http\Controllers;

use App\Models\Comercio;
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
        'order_summary',  // abrió el "Resumen para el mozo" (data.items) — el final del embudo
        'mic_start',      // arrancó a dictar por voz
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
            'session_id' => $validated['session_id'] ?? null,
            'event' => $validated['event'],
            'data' => $validated['data'] ?? null,
            'user_ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        return response()->noContent();
    }
}
