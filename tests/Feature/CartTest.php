<?php

namespace Tests\Feature;

use App\Models\Comercio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API pública del carrito (por sesión, sin autenticación).
 * Requiere que el comercio tenga los pedidos habilitados.
 */
class CartTest extends TestCase
{
    use RefreshDatabase;

    private function comercioConPedidos(): Comercio
    {
        return Comercio::factory()->create(['pedido_habilitado' => true]);
    }

    public function test_guest_can_add_item_and_total_is_calculated(): void
    {
        $comercio = $this->comercioConPedidos();

        $response = $this->postJson(route('api.cart.incr'), [
            'comercio_id' => $comercio->id,
            'session_id' => 'sess-123',
            'nombre' => 'Milanesa',
            'price_text' => '$100',
        ]);

        $response->assertOk()->assertJson(['success' => true, 'total_centavos' => 10000]);
        $this->assertCount(1, $response->json('items'));
    }

    public function test_guest_can_remove_and_clear_items(): void
    {
        $comercio = $this->comercioConPedidos();
        $payload = ['comercio_id' => $comercio->id, 'session_id' => 'sess-123'];

        $this->postJson(route('api.cart.incr'), $payload + ['nombre' => 'Milanesa', 'price_text' => '$100']);
        $this->postJson(route('api.cart.incr'), $payload + ['nombre' => 'Pizza', 'price_text' => '$200']);

        $remove = $this->postJson(route('api.cart.remove'), $payload + ['nombre' => 'Milanesa']);
        $remove->assertOk();
        $this->assertCount(1, $remove->json('items'));

        $clear = $this->postJson(route('api.cart.clear'), $payload);
        $clear->assertOk()->assertJson(['total_centavos' => 0, 'items' => []]);
    }

    public function test_cart_is_rejected_when_orders_are_disabled(): void
    {
        $comercio = Comercio::factory()->create(['pedido_habilitado' => false]);

        $response = $this->postJson(route('api.cart.incr'), [
            'comercio_id' => $comercio->id,
            'session_id' => 'sess-123',
            'nombre' => 'Milanesa',
        ]);

        $response->assertForbidden()->assertJson(['success' => false]);
    }

    public function test_cart_is_isolated_by_session(): void
    {
        $comercio = $this->comercioConPedidos();

        $this->postJson(route('api.cart.incr'), [
            'comercio_id' => $comercio->id,
            'session_id' => 'sess-A',
            'nombre' => 'Milanesa',
            'price_text' => '$100',
        ]);

        // Otra sesión no ve el ítem de la primera
        $otra = $this->postJson(route('api.cart.show'), [
            'comercio_id' => $comercio->id,
            'session_id' => 'sess-B',
        ]);

        $otra->assertOk();
        $this->assertCount(0, $otra->json('items'));
    }
}
