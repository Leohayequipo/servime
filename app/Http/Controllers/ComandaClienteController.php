<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Comanda;
use App\Models\ComandaItem;
use App\Models\Comercio;
use App\Models\Mesa;
use App\Services\ComandaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lo que el CLIENTE hace con la comanda de su mesa desde el menú público.
 *
 * El carrito sigue siendo su lista privada: acá está el paso que la convierte en pedido
 * visible para el mozo. Son dos cosas distintas a propósito — "Lo quiero" significa
 * "esto me tienta", y si eso cayera directo en la tablet, el mozo vería seis platos por
 * cada uno que la mesa termina pidiendo.
 */
class ComandaClienteController extends Controller
{
    public function __construct(private ComandaService $comandas) {}

    /**
     * Manda el carrito a la comanda de la mesa como una ronda nueva.
     */
    public function pedir(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'comercio_id' => 'required|integer',
            'session_id' => 'required|string|max:100',
        ]);

        [$comercio, $mesa, $error] = $this->contexto($datos['comercio_id']);

        if ($error) {
            return $error;
        }

        $cart = Cart::where('comercio_id', $comercio->id)
            ->where('session_id', $datos['session_id'])
            ->with('items')
            ->first();

        if (! $cart || $cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => __('Todavía no elegiste nada.'),
            ], 422);
        }

        $resultado = $this->comandas->enviarCarrito($cart, $mesa);

        return response()->json([
            'success' => true,
            'ronda' => $resultado['ronda'],
            'mesa' => $mesa->numero,
            'pedido' => $this->pedidoDeLaSesion($resultado['comanda'], $datos['session_id']),
        ]);
    }

    /**
     * Lo que esta mesa ya pidió, para mostrarlo cuando el cliente abre el panel.
     *
     * Devuelve la comanda entera y no sólo lo de este teléfono: la mesa pide junta y a
     * la hora de mirar qué falta, lo que importa es lo que va a traer el mozo.
     */
    public function mia(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'comercio_id' => 'required|integer',
            'session_id' => 'required|string|max:100',
        ]);

        [$comercio, $mesa, $error] = $this->contexto($datos['comercio_id']);

        if ($error) {
            return $error;
        }

        $comanda = Comanda::where('mesa_id', $mesa->id)->abiertas()->with('items')->first();

        return response()->json([
            'success' => true,
            'mesa' => $mesa->numero,
            // Sin comanda abierta no hay nada pedido: puede ser que todavía no pidieron,
            // o que el mozo ya cerró la mesa. Para el cliente es lo mismo: la pantalla
            // vuelve a arrancar limpia en vez de mostrar un pedido fantasma.
            'pedido' => $comanda ? $this->pedidoDeLaSesion($comanda, $datos['session_id']) : [],
        ]);
    }

    /**
     * Comercio y mesa del request, o la respuesta de error correspondiente.
     *
     * @return array{0: ?Comercio, 1: ?Mesa, 2: ?JsonResponse}
     */
    private function contexto(int $comercioId): array
    {
        $comercio = Comercio::find($comercioId);

        if (! $comercio) {
            return [null, null, response()->json(['success' => false, 'message' => 'Comercio no encontrado'], 404)];
        }

        if (! $comercio->pedido_habilitado || ! $comercio->tieneFeature('comandas')) {
            return [null, null, response()->json([
                'success' => false,
                'message' => 'Los pedidos no están habilitados para este comercio',
            ], 403)];
        }

        // La mesa sale de la sesión (la puso ResolverMesa al escanear el QR). Sin mesa no
        // hay a qué comanda mandar el pedido, y el botón ni siquiera se muestra.
        $mesa = Mesa::deSesion($comercio->id);

        if (! $mesa) {
            return [null, null, response()->json([
                'success' => false,
                'message' => __('Escaneá el código QR de tu mesa para poder pedir.'),
            ], 409)];
        }

        return [$comercio, $mesa, null];
    }

    /**
     * La comanda en el formato que muestra el menú.
     *
     * Marca qué pidió este teléfono para que, en una mesa de cuatro, cada uno reconozca
     * lo suyo dentro de la lista común.
     *
     * @return array<int, array<string, mixed>>
     */
    private function pedidoDeLaSesion(Comanda $comanda, string $sessionId): array
    {
        return $comanda->items->map(fn (ComandaItem $item) => [
            'nombre' => $item->nombre,
            'qty' => $item->qty,
            'ronda' => $item->ronda,
            'nota' => $item->nota,
            'mio' => $item->session_id === $sessionId,
            'del_mozo' => $item->loAgregoElMozo(),
        ])->values()->all();
    }
}
