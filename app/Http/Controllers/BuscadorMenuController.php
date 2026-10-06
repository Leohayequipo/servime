<?php

namespace App\Http\Controllers;

use App\Models\Comercio;
use App\Models\TrackingEvent;
use App\Services\RecomendadorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * El buscador de la carta, sin IA.
 *
 * La coincidencia la resuelve el mismo PHP que le sirve los platos al asistente
 * (`ChatbotService::buscarEnElMenu()`, vía `RecomendadorService`), y no una copia en
 * JavaScript: duplicar esa lógica en el navegador es garantizar que las dos se separen
 * con el primer arreglo —los sinónimos, el tipeo, los plurales viven en un solo lado—.
 *
 * Devuelve NOMBRES, no HTML: la lista de la carta ya está renderizada en la página y el
 * front sólo esconde lo que no coincide. Así no hay dos formas de dibujar un plato.
 *
 * Lo que no encuentra se registra (`busqueda_sin_resultado`): es lo que el cliente quiso
 * preguntar y el menú no supo contestar, o sea la demanda que justifica el asistente.
 */
class BuscadorMenuController extends Controller
{
    public function __construct(private RecomendadorService $recomendador) {}

    public function buscar(Request $request, Comercio $comercio): JsonResponse
    {
        $validado = $request->validate([
            'q' => ['required', 'string', 'max:120'],
            'session_id' => ['nullable', 'string', 'max:100'],
        ]);

        $respuesta = $this->recomendador->responder($comercio, $validado['q']);
        $nombres = array_column($respuesta['platos'], 'nombre');

        // `responder()` nunca deja al cliente con las manos vacías: si no encontró lo que
        // buscaba igual ofrece algo. Por eso el "encontró" no se deduce de que la lista
        // tenga platos: lo dice el recomendador (y no el texto, que sale traducido).
        $encontro = $respuesta['encontro'];

        TrackingEvent::create([
            'comercio_id' => $comercio->id,
            'session_id' => $validado['session_id'] ?? null,
            'event' => $encontro ? 'busqueda' : 'busqueda_sin_resultado',
            'data' => ['q' => mb_substr($validado['q'], 0, 120), 'resultados' => count($nombres)],
            'user_ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
        ]);

        return response()->json([
            'texto' => $respuesta['texto'],
            'encontro' => $encontro,
            'platos' => $nombres,
        ]);
    }
}
