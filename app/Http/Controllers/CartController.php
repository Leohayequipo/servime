<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    private function getOrCreateCart(int $comercioId, string $sessionId): Cart
    {
        $cart = Cart::firstOrCreate(
            ['comercio_id' => $comercioId, 'session_id' => $sessionId],
            ['total_centavos' => 0]
        );
        return $cart->load('items');
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
            'session_id' => 'required|string'
        ]);
        $cart = $this->getOrCreateCart($data['comercio_id'], $data['session_id']);
        return response()->json([
            'items' => $cart->items,
            'total_centavos' => $cart->total_centavos,
            'success' => true
        ]);
    }

    public function incr(Request $request)
    {
        $data = $request->validate([
            'comercio_id' => 'required|integer',
            'session_id' => 'required|string',
            'nombre' => 'required|string',
            'delta' => 'sometimes|integer',
            'price_text' => 'nullable|string'
        ]);
        $delta = $data['delta'] ?? 1;
        $cart = $this->getOrCreateCart($data['comercio_id'], $data['session_id']);
        $item = $cart->items()->firstOrCreate(['nombre' => $data['nombre']], ['qty' => 0]);
        $item->qty = max(0, $item->qty + $delta);
        if ($item->qty === 0) { $item->delete(); } else {
            // parse price
            $priceCents = null;
            if (!empty($data['price_text'])) {
                $txt = preg_replace('/[^0-9,\.]/', '', $data['price_text']);
                $txt = str_replace(',', '.', $txt);
                $val = is_numeric($txt) ? (float)$txt : null;
                if ($val !== null) $priceCents = (int) round($val * 100);
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
            'nombre' => 'required|string'
        ]);
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
            'session_id' => 'required|string'
        ]);
        $cart = $this->getOrCreateCart($data['comercio_id'], $data['session_id']);
        $cart->items()->delete();
        $cart->total_centavos = 0;
        $cart->save();
        return response()->json(['items' => [], 'total_centavos' => 0, 'success' => true]);
    }
}


