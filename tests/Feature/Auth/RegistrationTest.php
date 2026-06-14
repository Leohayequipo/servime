<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * El registro público está deshabilitado: el acceso es por suscripción.
     * GET /register redirige al home en lugar de mostrar un formulario.
     */
    public function test_registration_screen_is_closed(): void
    {
        $response = $this->get('/register');

        $response->assertRedirect('/');
    }

    /**
     * POST /register no debe crear usuarios: el alta la hace el administrador.
     */
    public function test_users_can_not_self_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }
}
