<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'ServiME') }} - Recuperar Contraseña</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('images/ServiME_IA.ico') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/ServiME_IA.ico') }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Custom Styles -->
    <style>
        :root {
            --bg-primary: #0a0a0a;
            --bg-secondary: #1a1a1a;
            --bg-tertiary: #2a2a2a;
            --text-primary: #ffffff;
            --text-secondary: #b0b0b0;
            --accent-blue: #00d4ff;
            --accent-green: #00ff88;
            --accent-purple: #8b5cf6;
            --border: #333333;
            --shadow: rgba(0, 212, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Figtree', sans-serif;
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
            min-height: 100vh;
            color: var(--text-primary);
            overflow-x: hidden;
        }

        .container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
        }

        /* Animated Background */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: 
                radial-gradient(circle at 20% 80%, rgba(0, 212, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(0, 255, 136, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(139, 92, 246, 0.1) 0%, transparent 50%);
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-20px) rotate(1deg); }
            66% { transform: translateY(10px) rotate(-1deg); }
        }

        .glass-effect {
            background: rgba(26, 26, 26, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .neon-glow {
            box-shadow: 
                0 0 20px rgba(0, 212, 255, 0.3),
                0 0 40px rgba(0, 212, 255, 0.1),
                inset 0 0 20px rgba(0, 212, 255, 0.05);
        }

        .logo-glow {
            text-shadow: 
                0 0 10px rgba(0, 212, 255, 0.5),
                0 0 20px rgba(0, 212, 255, 0.3),
                0 0 30px rgba(0, 212, 255, 0.1);
        }

        .form-container {
            width: 100%;
            max-width: 400px;
            position: relative;
            z-index: 1;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 0.875rem 1rem;
            background: rgba(42, 42, 42, 0.8);
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            color: var(--text-primary);
            font-size: 1rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.1);
            background: rgba(42, 42, 42, 0.9);
        }

        .form-input::placeholder {
            color: var(--text-secondary);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-blue) 0%, var(--accent-green) 100%);
            color: #000;
            border: none;
            border-radius: 0.5rem;
            padding: 0.875rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 212, 255, 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            padding: 0.875rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--accent-blue);
            color: var(--text-primary);
        }

        .error-message {
            color: #ff6b6b;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }

        .success-message {
            color: var(--accent-green);
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }

        .back-link {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: var(--accent-blue);
        }

        .footer {
            text-align: center;
            margin-top: 2rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        /* Responsive */
        @media (max-width: 640px) {
            .container {
                padding: 1rem;
            }
            
            .form-container {
                max-width: 100%;
            }
        }

        /* Utility Classes */
        .text-text-primary {
            color: var(--text-primary);
        }

        .text-text-secondary {
            color: var(--text-secondary);
        }

        .text-4xl {
            font-size: 2.25rem;
        }

        .font-bold {
            font-weight: 700;
        }

        .mb-2 {
            margin-bottom: 0.5rem;
        }

        .mb-8 {
            margin-bottom: 2rem;
        }

        .mb-4 {
            margin-bottom: 1rem;
        }

        .mt-6 {
            margin-top: 1.5rem;
        }

        .text-center {
            text-align: center;
        }

        .w-full {
            width: 100%;
        }

        .py-3 {
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
        }

        .px-6 {
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }

        .text-lg {
            font-size: 1.125rem;
        }

        .font-semibold {
            font-weight: 600;
        }

        .rounded-2xl {
            border-radius: 1rem;
        }

        .p-8 {
            padding: 2rem;
        }
    </style>
</head>
<body>
    <div class="bg-animation"></div>
    
    <div class="container">
        <div class="form-container">
            <div class="glass-effect rounded-2xl p-8 neon-glow">
                <!-- Header -->
                <div class="text-center mb-8">
                    <h1 class="text-4xl font-bold text-text-primary mb-2 logo-glow">{{ config('app.name', 'ServiME') }}</h1>
                    <p class="text-text-secondary">Recuperar tu contraseña</p>
                </div>

                <!-- Session Status -->
                @if (session('status'))
                    <div class="success-message mb-4">
                        {{ session('status') }}
                    </div>
                @endif

                <!-- Form -->
                <form method="POST" action="{{ route('password.email') }}">
                    @csrf

                    <!-- Email Field -->
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input id="email" 
                               type="email" 
                               name="email" 
                               value="{{ old('email') }}" 
                               required 
                               autofocus
                               class="form-input"
                               placeholder="Ingresa tu email">
                        @error('email')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <div class="form-group">
                        <button type="submit" class="btn-primary w-full text-center py-3 px-6 text-lg font-semibold">
                            ENVIAR ENLACE DE RECUPERACIÓN
                        </button>
                    </div>
                </form>

                <!-- Back to Login -->
                <div class="text-center mt-6">
                    <a href="{{ route('login') }}" class="back-link">
                        ← Volver al login
                    </a>
                </div>
            </div>

            <!-- Footer -->
            <div class="footer">
                <p>© {{ date('Y') }} ServiMe! Todos los derechos reservados.</p>
            </div>
        </div>
    </div>
</body>
</html>