<?php

namespace App\Http\Controllers;

use App\Models\Comercio;
use App\Models\TrackingEvent;
use App\Services\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * El buscador de la carta, sin IA.
 *
 * La coincidencia la resuelve el mismo PHP que le sirve los platos al asistente
 * (`ChatbotService::buscarEnElMenu()`), y no una copia en JavaScript: duplicar esa lógica
 * en el navegador es garantizar que las dos se separen con el primer arreglo —los
 * sinónimos, el tipeo, los plurales viven en un solo lado—.
 *
 * Devuelve NOMBRES, no HTML: la lista de la carta ya está renderizada en la página y el
 * front sólo esconde lo que no coincide. Así no hay dos formas de dibujar un plato.
 *
 * Lo que no encuentra se registra (`busqueda_sin_resultado`): es lo que el cliente quiso
 * preguntar y la carta no supo contestar, o sea la demanda que justifica el asistente.
 */
class BuscadorMenuController extends Controller
{
    /**
     * Tope de resultados. Alto a propósito: esto filtra la carta, y el que busca
     * "hamburguesa" quiere ver las ocho, no las tres primeras.
     */
    private const MAX_RESULTADOS = 40;

    public function __construct(private ChatbotService $chatbot) {}

    public function buscar(Request $request, Comercio $comercio): JsonResponse
    {
        $validado = $request->validate([
            'q' => ['required', 'string', 'max:120'],
            'session_id' => ['nullable', 'string', 'max:100'],
        ]);

        $nombres = array_column(
            $this->chatbot->buscarEnElMenu($comercio, $validado['q'], self::MAX_RESULTADOS),
            'nombre'
        );
        $encontro = $nombres !== [];

        TrackingEvent::create([
            'comercio_id' => $comercio->id,
            'session_id' => $validado['session_id'] ?? null,
            'event' => $encontro ? 'busqueda' : 'busqueda_sin_resultado',
            'data' => ['q' => mb_substr($validado['q'], 0, 120), 'resultados' => count($nombres)],
            'user_ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
        ]);

        return response()->json([
            // Encontrado, la lista filtrada habla sola. Sin resultados se avisa, y el front
            // deja la carta entera a la vista en vez de una pantalla vacía.
            'texto' => $encontro ? '' : __('No encontré eso en la carta. Podés mirar el menú completo acá abajo.'),
            'encontro' => $encontro,
            'platos' => $nombres,
        ]);
    }
}
