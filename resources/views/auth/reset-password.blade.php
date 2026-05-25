<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'ServiME') }} - Nueva Contraseña</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('images/ServiME_IA.ico') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/ServiME_IA.ico') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=roboto:300,400,500,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --primary-dark:   #111318;
            --secondary-dark: #1c1f26;
            --accent-blue:    #f59e0b;
            --accent-green:   #10b981;
            --accent-gray:    #374151;
            --text-primary:   #FFFFFF;
            --text-secondary: #94a3b8;
            --background:     #0b0c10;
        }
        body { font-family: Roboto, Inter, system-ui, sans-serif; color: var(--text-primary); min-height: 100vh; position: relative; }

        .video-background {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            object-fit: cover; z-index: -20; filter: blur(8px); -webkit-filter: blur(8px);
        }
        .video-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,.75); z-index: -10;
        }
        .floating-particles { position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 0; }
        .particle {
            position: absolute; width: 2px; height: 2px;
            background: var(--accent-blue); border-radius: 50%;
            animation: particle-float 6s ease-in-out infinite;
        }
        @keyframes particle-float {
            0%,100% { transform: translateY(0) rotate(0deg); opacity: 0; }
            50%      { transform: translateY(-20px) rotate(180deg); opacity: 1; }
        }
        .logo-container { position: relative; z-index: 10; }
        .logo-glow { filter: drop-shadow(0 0 16px rgba(245,158,11,.5)); }

        .input-field {
            background: rgba(255, 255, 255, 0.05) !important;
            border-color: rgba(255, 255, 255, 0.12) !important;
        }
        .input-field:focus {
            background: rgba(255, 255, 255, 0.08) !important;
            border-color: #f59e0b !important;
        }

        .requirements-box {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 0.5rem;
            padding: 0.875rem 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.8rem;
            color: #94a3b8;
        }
        .requirements-box p { margin-bottom: 0.35rem; }
    </style>
</head>
<body class="text-text-primary min-h-screen flex items-center justify-center relative overflow-x-hidden">

    <!-- Video de Fondo con Blur -->
    <video class="video-background" autoplay loop muted playsinline>
        <source src="{{ asset('videos/background_principal.mp4') }}" type="video/mp4">
    </video>

    <!-- Overlay Oscuro -->
    <div class="video-overlay"></div>

    <!-- Floating Particles -->
    <div class="floating-particles">
        <div class="particle" style="left: 10%; animation-delay: 0s;"></div>
        <div class="particle" style="left: 20%; animation-delay: 1s;"></div>
        <div class="particle" style="left: 30%; animation-delay: 2s;"></div>
        <div class="particle" style="left: 40%; animation-delay: 3s;"></div>
        <div class="particle" style="left: 50%; animation-delay: 4s;"></div>
        <div class="particle" style="left: 60%; animation-delay: 5s;"></div>
        <div class="particle" style="left: 70%; animation-delay: 0.5s;"></div>
        <div class="particle" style="left: 80%; animation-delay: 1.5s;"></div>
        <div class="particle" style="left: 90%; animation-delay: 2.5s;"></div>
    </div>

    <div class="w-full max-w-md mx-auto px-6 relative z-20">

        <!-- Logo -->
        <div class="text-center mb-8 logo-container">
            <img src="{{ asset('images/logo_servime.png') }}" alt="ServiME" class="w-auto mx-auto mb-3 logo-glow" style="height:160px;">
            <p class="text-text-secondary">Creá tu nueva contraseña</p>
        </div>

        <!-- Card -->
        <div class="rounded-2xl p-8" style="background:rgba(12,9,6,0.55);backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);border:1px solid rgba(245,158,11,0.18);">

            <form method="POST" action="{{ route('password.store') }}">
                @csrf

                <!-- Token -->
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <!-- Email -->
                <div class="mb-6">
                    <label for="email" class="block text-sm font-medium text-text-secondary mb-2">Email</label>
                    <input id="email"
                           type="email"
                           name="email"
                           value="{{ old('email', $request->email) }}"
                           required
                           autofocus
                           autocomplete="username"
                           class="input-field w-full"
                           placeholder="tu@email.com">
                    @error('email')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nueva contraseña -->
                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-text-secondary mb-2">Nueva Contraseña</label>
                    <input id="password"
                           type="password"
                           name="password"
                           required
                           autocomplete="new-password"
                           class="input-field w-full"
                           placeholder="••••••••">
                    @error('password')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Confirmar contraseña -->
                <div class="mb-6">
                    <label for="password_confirmation" class="block text-sm font-medium text-text-secondary mb-2">Confirmar Contraseña</label>
                    <input id="password_confirmation"
                           type="password"
                           name="password_confirmation"
                           required
                           autocomplete="new-password"
                           class="input-field w-full"
                           placeholder="••••••••">
                    @error('password_confirmation')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Requisitos -->
                <div class="requirements-box">
                    <p>✔ Mínimo 8 caracteres</p>
                    <p>✔ Al menos una letra mayúscula</p>
                    <p>✔ Al menos una letra minúscula</p>
                    <p>✔ Al menos un número</p>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn-primary w-full text-center py-3 px-6 text-lg font-semibold">
                    RESTABLECER CONTRASEÑA
                </button>
            </form>

            <!-- Volver -->
            <div class="text-center mt-6">
                <a href="{{ route('login') }}" class="text-sm text-text-secondary hover:text-accent-blue transition-colors duration-300">
                    ← Volver al login
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8">
            <p class="text-text-secondary text-sm">© {{ date('Y') }} ServiMe! Todos los derechos reservados.</p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const particles = document.querySelectorAll('.particle');
            particles.forEach((p, i) => {
                p.style.animationDelay = `${i * 0.5}s`;
                p.style.animationDuration = `${6 + Math.random() * 4}s`;
            });
        });
    </script>
</body>
</html>
