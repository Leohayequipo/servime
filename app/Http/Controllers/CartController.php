<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Comercio;
use App\Models\Mesa;
use Illuminate\Http\Request;

class CartController extends Controller
{
    private function getOrCreateCart(int $comercioId, string $sessionId): Cart
    {
        // La mesa sale de la sesión y no del body: estas rutas reciben el comercio por
        // parámetro, así que no pasan por ResolverMesa, pero comparten la misma sesión web.
        $mesaId = Mesa::idDeSesion($comercioId);

        $cart = Cart::firstOrCreate(
            ['comercio_id' => $comercioId, 'session_id' => $sessionId],
            ['total_centavos' => 0, 'mesa_id' => $mesaId]
        );

        // El carrito de un cliente vive para siempre (la clave es su session_id), así que
        // el que vuelve otro día y se sienta en otra mesa reusa el mismo registro. Sin esto
        // su actividad quedaría contada en la mesa de la visita anterior.
        if ($cart->mesa_id !== $mesaId && $mesaId !== null) {
            $cart->update(['mesa_id' => $mesaId]);
        }

        return $cart->load('items');
    }

    /**
     * Convierte un texto de precio a centavos usando formato argentino:
     * el '.' es separador de miles y la ',' es el decimal ("$3.800" => 380000).
     * Un valor de miles nunca termina en exactamente 2 dígitos tras el punto,
     * así se distingue "3.800" (=3800) de "3.50" (=3,50) sin ambigüedad.
     */
    private function parsePriceToCents(?string $txt): ?int
    {
        if ($txt === null) {
            return null;
        }
        $s = preg_replace('/[^0-9.,]/', '', $txt);
        if ($s === '') {
            return null;
        }
        if (preg_match('/^\d{1,3}(,\d{3})+(\.\d+)?$/', $s)) {
            $s = str_replace(',', '', $s);                 // formato inglés: "29,000.00" o "29,000"
        } elseif (str_contains($s, ',')) {
            $s = str_replace(['.', ','], ['', '.'], $s);   // coma decimal, puntos son miles
        } elseif (! preg_match('/^\d+\.\d{2}$/', $s)) {
            $s = str_replace('.', '', $s);                 // puntos = miles (salvo "X.YY" decimal)
        }

        return is_numeric($s) ? (int) round(((float) $s) * 100) : null;
    }

    private function recalcTotal(Cart $cart): void
    {
        $total = 0;
        foreach ($cart->items as $item) {
            if ($item->price_centavos !== null) {
                $total += ($item->price_centavos * $item->qty);
            }
        }
        $cart->total_centavos = $total;
        $cart->save();
    }

    public function show(Request $request)
    {
        $data = $request->validate([
            'comercio_id' => 'required|integer',
            'session_id' => 'required|string',
        ]);
        $comercio = Comercio::find($data['comercio_id']);
        if (! $comercio) {
            return response()->json(['success' => false, 'message' => 'Comercio no encontrado'], 404);
        }
        if (! $comercio->aceptaListaDePedido()) {
            return response()->json(['success' => false, 'message' => 'Los pedidos están deshabilitados para este comercio'], 403);
        }
        $cart = $this->getOrCreateCart($data['comercio_id'], $data['session_id']);

        return response()->json([
            'items' => $cart->items,
            'total_centavos' => $cart->total_centavos,
            'success' => true,
        ]);
    }

    public function incr(Request $request)
    {
        $data = $request->validate([
            'comercio_id' => 'required|integer',
            'session_id' => 'required|string',
            'nombre' => 'required|string',
            'delta' => 'sometimes|integer',
            'price_text' => 'nullable|string',
        ]);
        $delta = $data['delta'] ?? 1;
        $comercio = Comercio::find($data['comercio_id']);
        if (! $comercio) {
            return response()->json(['success' => false, 'message' => 'Comercio no encontrado'], 404);
        }
        if (! $comercio->aceptaListaDePedido()) {
            return response()->json(['success' => false, 'message' => 'Los pedidos están deshabilitados para este comercio'], 403);
        }
        $cart = $this->getOrCreateCart($data['comercio_id'], $data['session_id']);
        $item = $cart->items()->firstOrCreate(['nombre' => $data['nombre']], ['qty' => 0]);
        $item->qty = max(0, $item->qty + $delta);
        if ($item->qty === 0) {
            $item->delete();
        } else {
            // parse price (formato argentino: '.' miles, ',' decimales)
            $priceCents = null;
            if (! empty($data['price_text'])) {
                $priceCents = $this->parsePriceToCents($data['price_text']);
                $item->price_text = $data['price_text'];
            }
            if ($item->price_centavos === null && $priceCents !== null) {
                $item->price_centavos = $priceCents;
            }
            $item->save();
        }
        $cart->load('items');
        $this->recalcTotal($cart);

        return response()->json(['items' => $cart->items, 'total_centavos' => $cart->total_centavos, 'success' => true]);
    }

    public function remove(Request $request)
    {
        $data = $request->validate([
            'comercio_id' => 'required|integer',
            'session_id' => 'required|string',
            'nombre' => 'required|string',
        ]);
        $comercio = Comercio::find($data['comercio_id']);
        if (! $comercio) {
            return response()->json(['success' => false, 'message' => 'Comercio no encontrado'], 404);
        }
        if (! $comercio->aceptaListaDePedido()) {
            return response()->json(['success' => false, 'message' => 'Los pedidos están deshabilitados para este comercio'], 403);
        }
        $cart = $this->getOrCreateCart($data['comercio_id'], $data['session_id']);
        $cart->items()->where('nombre', $data['nombre'])->delete();
        $cart->load('items');
        $this->recalcTotal($cart);

        return response()->json(['items' => $cart->items, 'total_centavos' => $cart->total_centavos, 'success' => true]);
    }

    public function clear(Request $request)
    {
        $data = $request->validate([
            'comercio_id' => 'required|integer',
            'session_id' => 'required|string',
        ]);
        $comercio = Comercio::find($data['comercio_id']);
        if (! $comercio) {
            return response()->json(['success' => false, 'message' => 'Comercio no encontrado'], 404);
        }
        if (! $comercio->aceptaListaDePedido()) {
            return response()->json(['success' => false, 'message' => 'Los pedidos están deshabilitados para este comercio'], 403);
        }
        $cart = $this->getOrCreateCart($data['comercio_id'], $data['session_id']);
        $cart->items()->delete();
        $cart->total_centavos = 0;
        $cart->save();

        return response()->json(['items' => [], 'total_centavos' => 0, 'success' => true]);
    }
}
