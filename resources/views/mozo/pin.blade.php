<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Acceso · {{ $comercio->nombre }}</title>
    @vite(['resources/css/app.css'])
    <style>
        /* Teclado grande: esto se usa con una mano, de pie y apurado. */
        .pin-input {
            width: 100%; text-align: center; letter-spacing: .5em;
            font-size: 30px; font-weight: 700; padding: 16px;
        }
    </style>
</head>
<body class="h-full bg-background">
    <div class="min-h-full flex items-center justify-center p-6">
        <div class="w-full max-w-sm">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-text-primary">{{ $comercio->nombre }}</h1>
                <p class="text-text-secondary mt-1">Pantalla de mesas</p>
            </div>

            <form method="POST" action="{{ route('mozo.pin.validar', $comercio->mozo_token) }}"
                  class="metric-card">
                @csrf
                <label for="pin" class="block text-sm font-semibold text-text-primary mb-3 text-center">
                    Ingresá el PIN
                </label>

                <input type="password" name="pin" id="pin" inputmode="numeric" autocomplete="off"
                       class="input-field pin-input" maxlength="12" required autofocus>

                @error('pin')
                    <p class="text-danger text-sm mt-3 text-center">{{ $message }}</p>
                @enderror

                <button type="submit" class="btn-primary w-full mt-5">Entrar</button>
            </form>

            <p class="text-text-secondary text-xs text-center mt-6">
                Si no sabés el PIN, pedíselo al encargado.
            </p>
        </div>
    </div>
</body>
</html>
