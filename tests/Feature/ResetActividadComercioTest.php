<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ChatConversation;
use App\Models\Comercio;
use App\Models\IntentClassificationRun;
use App\Models\Menu;
use App\Models\TrackingEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Empezar de nuevo": el super admin borra toda la actividad de un comercio.
 *
 * Lo que se cuida acá es el límite del borrado. Un botón irreversible que se lleva una
 * fila de más arruina un comercio en producción, y uno que se lleva una de menos deja
 * analytics sucio y no sirve para nada.
 */
class ResetActividadComercioTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->superAdmin()->create();
    }

    /** Un comercio con algo de cada cosa que el reset tiene que borrar. */
    private function comercioConActividad(): Comercio
    {
        $comercio = Comercio::factory()->create([
            'ia_mensajes_periodo' => now()->format('Y-m'),
            'ia_mensajes_usados' => 87,
        ]);

        ChatConversation::create([
            'comercio_id' => $comercio->id,
            'session_id' => 'sess-1',
            'user_message' => '¿Tienen sin TACC?',
            'bot_response' => 'Sí, la ensalada.',
        ]);

        TrackingEvent::factory()->create(['comercio_id' => $comercio->id]);

        IntentClassificationRun::create([
            'comercio_id' => $comercio->id,
            'mensajes_procesados' => 10,
        ]);

        $carrito = Cart::create([
            'comercio_id' => $comercio->id,
            'session_id' => 'sess-1',
            'total_centavos' => 10000,
        ]);
        CartItem::create([
            'cart_id' => $carrito->id,
            'nombre' => 'Milanesa',
            'qty' => 1,
            'price_centavos' => 10000,
        ]);

        return $comercio;
    }

    private function resetear(Comercio $comercio, ?User $como = null)
    {
        return $this->actingAs($como ?? $this->admin())
            ->post("/admin/comercios/{$comercio->slug}/reset-actividad");
    }

    public function test_borra_toda_la_actividad_del_comercio(): void
    {
        $comercio = $this->comercioConActividad();

        $this->resetear($comercio)->assertRedirect(route('admin.comercios.index'));

        $this->assertSame(0, ChatConversation::where('comercio_id', $comercio->id)->count());
        $this->assertSame(0, TrackingEvent::where('comercio_id', $comercio->id)->count());
        $this->assertSame(0, IntentClassificationRun::where('comercio_id', $comercio->id)->count());
        $this->assertSame(0, Cart::where('comercio_id', $comercio->id)->count());
    }

    public function test_no_deja_items_de_carrito_huerfanos(): void
    {
        // `cart_items` NO tiene foreign key contra `carts`: borrar el carrito no se lleva
        // sus ítems por cascada, hay que borrarlos a mano. Si alguien "simplifica" eso,
        // este test lo agarra.
        $comercio = $this->comercioConActividad();

        $this->resetear($comercio);

        $this->assertSame(0, CartItem::count());
    }

    public function test_la_cuota_de_ia_vuelve_a_cero(): void
    {
        $comercio = $this->comercioConActividad();

        $this->resetear($comercio);

        $this->assertSame(0, $comercio->refresh()->mensajesIaUsados());
    }

    public function test_no_toca_la_configuracion_ni_el_menu(): void
    {
        // El reset borra actividad, no configuración: si se lleva el menú por delante, el
        // comercio queda sin nada que mostrarle al cliente que ya escaneó el QR.
        $comercio = $this->comercioConActividad();
        $menu = Menu::factory()->create(['comercio_id' => $comercio->id]);
        $comercio->update([
            'pedido_habilitado' => true,
            'chatbot_name' => 'Rita',
            'primary_color' => '#123456',
        ]);

        $this->resetear($comercio);

        $this->assertDatabaseHas('menus', ['id' => $menu->id, 'deleted_at' => null]);

        $comercio->refresh();
        $this->assertTrue($comercio->pedido_habilitado);
        $this->assertSame('Rita', $comercio->chatbot_name);
        $this->assertSame('#123456', $comercio->primary_color);
        $this->assertNotNull(Comercio::find($comercio->id));
    }

    public function test_no_toca_la_actividad_de_otro_comercio(): void
    {
        $comercio = $this->comercioConActividad();
        $ajeno = $this->comercioConActividad();

        $this->resetear($comercio);

        $this->assertSame(1, ChatConversation::where('comercio_id', $ajeno->id)->count());
        $this->assertSame(1, TrackingEvent::where('comercio_id', $ajeno->id)->count());
        $this->assertSame(1, IntentClassificationRun::where('comercio_id', $ajeno->id)->count());
        $this->assertSame(1, Cart::where('comercio_id', $ajeno->id)->count());
        $this->assertSame(1, CartItem::count());
        $this->assertSame(87, $ajeno->refresh()->mensajesIaUsados());
    }

    public function test_el_dueno_del_comercio_no_puede_resetear(): void
    {
        // Es irreversible y no hay papelera: queda del lado de adentro de /admin.
        $comercio = $this->comercioConActividad();
        $dueno = User::find($comercio->user_id);

        $this->resetear($comercio, $dueno)->assertForbidden();

        $this->assertSame(1, ChatConversation::where('comercio_id', $comercio->id)->count());
    }

    public function test_un_invitado_no_puede_resetear(): void
    {
        $comercio = $this->comercioConActividad();

        $this->post("/admin/comercios/{$comercio->slug}/reset-actividad")
            ->assertRedirect(route('login'));

        $this->assertSame(1, ChatConversation::where('comercio_id', $comercio->id)->count());
    }

    public function test_el_boton_aparece_en_la_lista_de_comercios(): void
    {
        $comercio = $this->comercioConActividad();

        $this->actingAs($this->admin())
            ->get('/admin/comercios')
            ->assertOk()
            ->assertSee(route('admin.comercios.reset-actividad', $comercio), false);
    }

    public function test_resetear_dos_veces_no_rompe(): void
    {
        // Sin actividad que borrar tiene que salir igual de limpio, no tirar un 500.
        $comercio = $this->comercioConActividad();

        $this->resetear($comercio);
        $this->resetear($comercio)->assertRedirect(route('admin.comercios.index'));
    }
}
