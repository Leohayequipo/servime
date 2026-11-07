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

    /**
     * La IA copia el precio "tal como aparece" en el PDF, así que llega en cualquier formato.
     * Una carta con "$29,000.00" se leyó como 29 pesos y el total del pedido daba $69.
     *
     * @dataProvider formatosDePrecio
     */
    public function test_el_precio_se_lee_igual_en_cualquier_formato(string $texto, int $centavos): void
    {
        $comercio = $this->comercioConPedidos();

        $this->postJson(route('api.cart.incr'), [
            'comercio_id' => $comercio->id,
            'session_id' => 'sess-formato',
            'nombre' => 'Bife',
            'price_text' => $texto,
        ])->assertOk()->assertJson(['total_centavos' => $centavos]);
    }

    public static function formatosDePrecio(): array
    {
        return [
            'inglés con decimales' => ['$29,000.00', 2900000],
            'inglés sin decimales' => ['$ 45,000', 4500000],
            'argentino' => ['$29.000', 2900000],
            'argentino con decimales' => ['$29.000,50', 2900050],
            'sin separador' => ['$29000', 2900000],
            'punto al final' => ['$3800.', 380000],
            'decimal con punto' => ['$3.50', 350],
            'decimal con coma' => ['$3,50', 350],
        ];
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

    /**
     * Campos que el formulario de "Configuración del comercio" manda siempre. Ninguno
     * tiene que ver con los pedidos: son los mínimos que exige la validación.
     */
    private function camposDelPerfil(Comercio $comercio): array
    {
        return [
            'nombre' => $comercio->nombre,
            'primary_color' => '#3B82F6',
            'secondary_color' => '#1E40AF',
            'accent_color' => '#F59E0B',
            'background_color' => '#FFFFFF',
            'chatbot_name' => 'Asistente IA',
        ];
    }

    public function test_guardar_el_perfil_del_comercio_no_apaga_los_pedidos(): void
    {
        // El switch de pedidos NO está en el formulario del comercio (vive en la página del
        // menú), así que escribir la columna a ciegas desde este endpoint le apagaba el
        // carrito al comercio cada vez que guardaba cualquier otra cosa: colores, portada,
        // horarios. Y nadie lo relacionaba con eso.
        $comercio = $this->comercioConPedidos();

        $this->actingAs($comercio->user)
            ->put("/comercios/{$comercio->slug}", $this->camposDelPerfil($comercio));

        $this->assertTrue($comercio->refresh()->pedido_habilitado);
    }

    public function test_borrar_el_logo_no_apaga_los_pedidos(): void
    {
        // Mismo endpoint, pero con un request PARCIAL armado a mano por el front.
        $comercio = $this->comercioConPedidos();

        $this->actingAs($comercio->user)
            ->put("/comercios/{$comercio->slug}", $this->camposDelPerfil($comercio) + ['delete_logo' => '1']);

        $this->assertTrue($comercio->refresh()->pedido_habilitado);
    }

    public function test_el_switch_de_pedidos_guarda_las_dos_posiciones(): void
    {
        // La contracara: el endpoint que SÍ maneja el switch tiene que poder apagarlo, para
        // que la guarda de arriba no lo deje clavado en prendido.
        $comercio = $this->comercioConPedidos();
        $url = "/comercios/{$comercio->slug}/menus/update-chatbot-config";

        $this->actingAs($comercio->user)
            ->postJson($url, ['pedido_habilitado' => '0'])
            ->assertOk();
        $this->assertFalse($comercio->refresh()->pedido_habilitado);

        $this->actingAs($comercio->user)
            ->postJson($url, ['pedido_habilitado' => '1'])
            ->assertOk();
        $this->assertTrue($comercio->refresh()->pedido_habilitado);
    }
}
