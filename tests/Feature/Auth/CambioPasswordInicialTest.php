<?php

namespace Tests\Feature\Auth;

use App\Models\Comercio;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * La contraseña inicial es la misma para todas las cuentas: una cuenta que la sigue teniendo
 * ve un aviso en el panel hasta elegir otra. Es un recordatorio, no un bloqueo.
 */
class CambioPasswordInicialTest extends TestCase
{
    use RefreshDatabase;

    private function cuentaConPasswordInicial(): User
    {
        $user = User::factory()->create(['password' => Hash::make(User::passwordInicial())]);
        $user->forceFill(['debe_cambiar_password' => true])->save();

        return $user;
    }

    public function test_la_cuenta_creada_por_el_admin_nace_marcada(): void
    {
        Mail::fake();
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Parrilla Don José',
            'email' => 'donjose@example.com',
            'username' => '',
            'whatsapp' => '',
            'role' => 'user',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(User::where('email', 'donjose@example.com')->first()->debe_cambiar_password);
    }

    public function test_una_cuenta_marcada_sigue_usando_el_panel_y_ve_el_aviso(): void
    {
        $user = $this->cuentaConPasswordInicial();

        // No se la obliga: entra a todo, pero cada pantalla le recuerda que cambie la contraseña.
        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('Estás usando la contraseña inicial')
            ->assertSee(route('password.cambiar'), false);
        $this->actingAs($user)->get('/profile')->assertOk()->assertSee('Estás usando la contraseña inicial');
    }

    public function test_una_cuenta_con_contrasena_propia_no_ve_el_aviso(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Estás usando la contraseña inicial');
    }

    public function test_la_pantalla_es_opcional_y_se_puede_seguir_al_panel(): void
    {
        $user = $this->cuentaConPasswordInicial();

        $this->actingAs($user)->get(route('password.cambiar'))
            ->assertOk()
            ->assertSee('Elegí tu contraseña')
            ->assertSee('Ahora no, seguir al panel');
    }

    public function test_el_menu_publico_no_se_ve_afectado(): void
    {
        Storage::fake('public');
        $user = $this->cuentaConPasswordInicial();
        $comercio = Comercio::factory()->for($user)->create();
        Menu::factory()->for($comercio)->activo()->create(['procesado' => true]);

        $this->actingAs($user)->get("/menu/{$comercio->slug}/classic")
            ->assertOk()
            ->assertDontSee('Estás usando la contraseña inicial');
    }

    public function test_al_elegir_una_propia_se_libera_el_panel(): void
    {
        $user = $this->cuentaConPasswordInicial();

        $this->actingAs($user)->put(route('password.cambiar.guardar'), [
            'password' => 'MiParrilla2026',
            'password_confirmation' => 'MiParrilla2026',
        ])->assertSessionHasNoErrors()->assertRedirect('/dashboard');

        $user->refresh();
        $this->assertFalse($user->debe_cambiar_password);
        $this->assertTrue(Hash::check('MiParrilla2026', $user->password));
        $this->actingAs($user)->get('/dashboard')->assertDontSee('Estás usando la contraseña inicial');
    }

    public function test_no_acepta_volver_a_poner_la_inicial(): void
    {
        $user = $this->cuentaConPasswordInicial();

        $this->actingAs($user)->put(route('password.cambiar.guardar'), [
            'password' => User::passwordInicial(),
            'password_confirmation' => User::passwordInicial(),
        ])->assertSessionHasErrors('password');

        $this->assertTrue($user->refresh()->debe_cambiar_password);
    }

    public function test_entrar_con_la_inicial_marca_la_cuenta_aunque_no_lo_estuviera(): void
    {
        $user = User::factory()->create(['password' => Hash::make(User::passwordInicial())]);
        $this->assertFalse($user->refresh()->debe_cambiar_password);

        $this->post('/login', [
            'email' => $user->email,
            'password' => User::passwordInicial(),
        ])->assertRedirect('/dashboard');

        $this->assertTrue($user->refresh()->debe_cambiar_password);
        $this->get('/dashboard')->assertOk()->assertSee('Estás usando la contraseña inicial');
    }

    public function test_entrar_con_una_propia_no_marca_la_cuenta(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertFalse($user->refresh()->debe_cambiar_password);
        $this->get('/dashboard')->assertOk();
    }

    public function test_elegirla_desde_el_mail_de_bienvenida_apaga_la_marca(): void
    {
        $user = $this->cuentaConPasswordInicial();
        $token = Password::broker('bienvenida')->createToken($user);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'MiParrilla2026',
            'password_confirmation' => 'MiParrilla2026',
            'broker' => 'bienvenida',
        ])->assertSessionHasNoErrors();

        $this->assertFalse($user->refresh()->debe_cambiar_password);
    }

    public function test_una_cuenta_sin_marca_que_entra_a_la_pantalla_va_al_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('password.cambiar'))->assertRedirect(route('dashboard'));
    }
}
