<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            // Qué broker valida el token. El del mail de bienvenida dura 7 días; el de
            // "olvidé mi contraseña", una hora. Lista blanca para que nadie se dé más
            // vencimiento del que le corresponde poniéndolo a mano en la URL.
            'broker' => ['nullable', 'in:bienvenida'],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::broker($request->input('broker') ?: null)->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                    // El mail de bienvenida es la otra forma de dejar la contraseña
                    // inicial: al elegir una propia por acá ya no se le pide cambiarla.
                    'debe_cambiar_password' => hash_equals(User::passwordInicial(), (string) $request->password),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $status == Password::PASSWORD_RESET
                    ? redirect()->route('login')->with('status', '¡Contraseña creada con éxito! Ya podés iniciar sesión.')
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }
}
