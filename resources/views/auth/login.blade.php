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
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom Tailwind Config -->
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'primary-dark': '#1A2A44',
                        'secondary-dark': '#2A3A55',
                        'accent-blue': '#00C4FF',
                        'accent-green': '#00FF00',
                        'accent-gray': '#333333',
                        'text-primary': '#FFFFFF',
                        'text-secondary': '#CCCCCC',
                        'background': '#000000',
                    },
                    fontFamily: {
                        'sans': ['Roboto', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <!-- Custom Styles -->
    <style>
        :root {
            --primary-dark: #1A2A44;
            --secondary-dark: #2A3A55;
            --accent-blue: #00C4FF;
            --accent-green: #00FF00;
            --accent-gray: #333333;
            --text-primary: #FFFFFF;
            --text-secondary: #CCCCCC;
            --background: #000000;
        }
        
        body {
            font-family: 'Roboto', system-ui, sans-serif;
            background: linear-gradient(135deg, var(--background) 0%, var(--primary-dark) 100%);
            color: var(--text-primary);
            min-height: 100vh;
        }
        
        .glass-effect {
            background: rgba(42, 58, 85, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(0, 196, 255, 0.2);
        }
        
        .neon-glow {
            box-shadow: 0 0 20px rgba(0, 196, 255, 0.5);
        }
        
        .neon-glow-green {
            box-shadow: 0 0 20px rgba(0, 255, 0, 0.5);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--accent-blue), #0099CC);
            color: var(--text-primary);
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 196, 255, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 196, 255, 0.5);
        }
        
        .input-field {
            background: var(--secondary-dark);
            border: 1px solid var(--accent-gray);
            border-radius: 12px;
            padding: 12px 16px;
            color: var(--text-primary);
            transition: all 0.3s ease;
        }
        
        .input-field:focus {
            outline: none;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(0, 196, 255, 0.1);
        }
        
        .input-field::placeholder {
            color: var(--text-secondary);
        }
        
        .floating-particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }
        
        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: var(--accent-blue);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0; }
            50% { transform: translateY(-20px) rotate(180deg); opacity: 1; }
        }
        
        .logo-container {
            position: relative;
            z-index: 10;
        }
        
        .logo-glow {
            filter: drop-shadow(0 0 20px rgba(0, 196, 255, 0.5));
        }
    </style>
</head>
<body class="bg-background text-text-primary min-h-screen flex items-center justify-center relative overflow-hidden">
    
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

    <div class="w-full max-w-md mx-auto px-6 relative z-10">
        
        <!-- Logo Section -->
        <div class="text-center mb-8 logo-container">
            <!-- <img src="{{ asset('images/logo.png') }}" 
                 alt="ServiMe Logo Logo" 
                 class="w-24 h-24 mx-auto mb-6 logo-glow rounded-2xl">-->
            <h1 class="text-4xl font-bold text-text-primary mb-2 logo-glow">{{ config('app.name', 'ServiME') }} POWERED BY OPEN AI</h1>
            <p class="text-text-secondary">Accede a tu panel de administración</p>
        </div>

        <!-- Login Form -->
        <div class="glass-effect rounded-2xl p-8 neon-glow">
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
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <input id="remember_me" 
                               name="remember" 
                               type="checkbox" 
                               class="w-4 h-4 text-accent-blue bg-secondary-dark border-accent-gray rounded focus:ring-accent-blue focus:ring-2">
                        <label for="remember_me" class="ml-2 text-sm text-text-secondary">
                            Recordarme
            </label>
        </div>

            @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" 
                           class="text-sm text-accent-blue hover:text-accent-green transition-colors duration-300">
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
            @if (Route::has('register'))
                <div class="mt-6 text-center">
                    <p class="text-text-secondary">
                        ¿No tienes cuenta? 
                        <a href="{{ route('register') }}" 
                           class="text-accent-blue hover:text-accent-green transition-colors duration-300 font-medium">
                            Regístrate aquí
                        </a>
                    </p>
                </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="text-center mt-8">
            <p class="text-text-secondary text-sm">
                © {{ date('Y') }} ServiMe!! Todos los derechos reservados.
            </p>
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