<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Pantalla para elegir contraseña propia, a la que lleva el aviso del panel (<x-aviso-password />)
 * en las cuentas que todavía tienen la contraseña inicial. Es opcional: no bloquea nada.
 *
 * No pide la contraseña actual, a diferencia del cambio desde el perfil: la de estas
 * cuentas es la inicial, que es pública, así que pedirla no protegería nada.
 */
class CambioPasswordInicialController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->user()->debe_cambiar_password) {
            return redirect()->route('dashboard');
        }

        return view('auth.cambiar-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => [
                'required',
                'confirmed',
                Password::defaults(),
                Rule::notIn([User::passwordInicial()]),
            ],
        ], [
            'password.not_in' => 'Esa es la contraseña inicial, que es la misma para todas las cuentas. Elegí otra.',
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($validated['password']),
            'debe_cambiar_password' => false,
        ])->save();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
