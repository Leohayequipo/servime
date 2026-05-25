<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'ServiME') }} - Login</title>
    
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
            --background:     #0a0f1e;
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

        /* Override input background — neutrals instead of navy */
        .input-field {
            background: rgba(255, 255, 255, 0.05) !important;
            border-color: rgba(255, 255, 255, 0.12) !important;
        }
        .input-field:focus {
            background: rgba(255, 255, 255, 0.08) !important;
            border-color: #f59e0b !important;
        }
    </style>
</head>
<body class="text-text-primary min-h-screen flex items-center justify-center relative overflow-hidden">
    
    <!-- Video de Fondo con Blur -->
    <video class="video-background" autoplay loop muted playsinline>
        <source src="{{ asset('videos/background_principal.mp4') }}" type="video/mp4">
        Tu navegador no soporta videos HTML5.
    </video>
    
    <!-- Overlay Oscuro -->
    <div class="video-overlay"></div>
    
    <!-- Floating Particles Background -->
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
        
        <!-- Logo Section -->
        <div class="text-center mb-8 logo-container">
            <img src="{{ asset('images/logo_servime.png') }}" alt="ServiME" class="w-auto mx-auto mb-3 logo-glow" style="height:160px;">
            <p class="text-text-secondary">Accede a tu panel de administración</p>
        </div>

        <!-- Login Form -->
        <div class="rounded-2xl p-8 neon-glow" style="background:rgba(12,9,6,0.55);backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);border:1px solid rgba(245,158,11,0.18);">
    <form method="POST" action="{{ route('login') }}">
        @csrf

                <!-- Email Field -->
                <div class="mb-6">
                    <label for="email" class="block text-sm font-medium text-text-secondary mb-2">
                        Email
                    </label>
                    <input id="email" 
                           type="email" 
                           name="email" 
                           value="{{ old('email') }}" 
                           required 
                           autofocus 
                           autocomplete="username"
                           class="input-field w-full"
                           placeholder="tu@email.com">
                    @error('email')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
        </div>

                <!-- Password Field -->
                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-text-secondary mb-2">
                        Contraseña
                    </label>
                    <input id="password" 
                            type="password"
                            name="password"
                           required 
                           autocomplete="current-password"
                           class="input-field w-full"
                           placeholder="••••••••">
                    @error('password')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
        </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                    <div class="flex items-center">
                        <input id="remember_me"
                               name="remember"
                               type="checkbox"
                               class="w-4 h-4 text-accent-blue bg-white/5 border-white/20 rounded focus:ring-accent-blue focus:ring-2">
                        <label for="remember_me" class="ml-2 text-sm text-text-secondary">
                            Recordarme
                        </label>
                    </div>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" 
                           class="text-sm text-accent-blue hover:text-accent-green transition-colors duration-300 text-center sm:text-right">
                            ¿Olvidaste tu contraseña?
                        </a>
                    @endif
                </div>

                <!-- Login Button -->
                <button type="submit" 
                        class="btn-primary w-full text-center py-3 px-6 text-lg font-semibold">
                    INICIAR SESIÓN
                </button>
            </form>


            <!-- Register Link -->
            {{-- @if (Route::has('register'))
                <div class="mt-6 text-center">
                    <p class="text-text-secondary">
                        ¿No tienes cuenta? 
                        <a href="{{ route('register') }}" 
                           class="text-accent-blue hover:text-accent-green transition-colors duration-300 font-medium">
                            Regístrate aquí
                        </a>
                    </p>
                </div>
            @endif --}}
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 space-y-2">
            <p class="text-text-secondary text-sm">
                © {{ date('Y') }} ServiMe! Todos los derechos reservados.
            </p>
            <a href="{{ url('/?chat=1') }}"
               class="text-xs text-text-secondary/50 hover:text-text-secondary transition-colors duration-300">
                Centro de Ayuda
            </a>
        </div>
    </div>

    <!-- Dark Mode Script -->
    <script src="{{ asset('js/dark-mode.js') }}"></script>
    
    <!-- Scripts -->
    <script>
        // Add floating animation to particles
        document.addEventListener('DOMContentLoaded', function() {
            const particles = document.querySelectorAll('.particle');
            particles.forEach((particle, index) => {
                particle.style.animationDelay = `${index * 0.5}s`;
                particle.style.animationDuration = `${6 + Math.random() * 4}s`;
            });
        });
        
        // Add focus effects to inputs
        document.querySelectorAll('.input-field').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('neon-glow');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('neon-glow');
            });
        });
    </script>
</body>
</html>