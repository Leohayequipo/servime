<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $comercio->nombre }} - {{ config('app.name', 'ServiME') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/ServiME_IA.ico') }}">
    <link rel="shortcut icon" href="{{ asset('images/ServiME_IA.ico') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700&display=swap');
        
        html, body { 
            height: 100%; 
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        
        body {
            color: #ffffff;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            position: relative;
        }
        
        .video-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -20;
        }
        
        .video-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            z-index: -10;
        }
        
        @keyframes fade-in {
            from { 
                opacity: 0; 
                transform: translateY(10px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }
        
        .animate-fade-in {
            animation: fade-in 0.8s ease-out;
        }
        
        .card-hover {
            transition: all 0.5s ease-out;
        }
        
        .card-hover:hover {
            transform: translateY(-4px);
        }
        
        .card-ia:hover {
            border-color: rgba(99, 102, 241, 0.5) !important;
            box-shadow: 0 0 30px -5px rgba(99, 102, 241, 0.3);
        }
        
        .card-menu:hover {
            border-color: rgba(255, 255, 255, 0.4) !important;
        }
        
        .glass-card {
            background: rgba(17, 24, 39, 0.6);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
    </style>
</head>
<body>
    <!-- Video de Fondo Cinemático -->
    <video class="video-background" autoplay loop muted playsinline>
        <source src="{{ asset('videos/background.mp4') }}" type="video/mp4">
        Tu navegador no soporta videos HTML5.
    </video>
    
    <!-- Overlay Oscuro -->
    <div class="video-overlay"></div>
    
    <!-- Contenido Principal -->
    <div class="relative z-10 w-full max-w-5xl mx-auto px-6 py-16 min-h-screen flex flex-col items-center justify-center animate-fade-in">
        <!-- Header Minimalista -->
        <div class="text-center mb-16">
            @if($comercio->logo_url)
                <img src="{{ asset('storage/' . $comercio->logo_url) }}" 
                     alt="{{ $comercio->nombre }}" 
                     class="mx-auto h-20 w-20 object-contain mb-6 opacity-90">
            @else
                <img src="{{ asset('images/logo_empresa_default.png') }}" 
                     alt="{{ $comercio->nombre }}" 
                     class="mx-auto h-20 w-20 object-contain mb-6 opacity-90">
            @endif
            <h1 class="text-white font-light tracking-[0.2em] text-2xl uppercase mb-2">
                {{ strtoupper($comercio->nombre) }}
            </h1>
            <p class="text-neutral-500 text-xs mt-4">¿Cómo quieres ver el menú?</p>
        </div>
        
        <!-- Tarjetas con Lottie -->
        <div class="flex flex-col md:flex-row items-center justify-center gap-8 md:gap-12">
            <!-- Tarjeta IA -->
            <a href="{{ route('menu.chat', $comercio) }}" 
               class="card-hover card-ia glass-card border border-white/10 rounded-2xl p-10 w-64 text-center group">
                <div class="mb-6">
                    <div class="flex justify-center items-center mb-6 h-20">
                        <lottie-player 
                            src="https://lottie.host/59e7c9c3-7520-4180-b867-629378008a43/7Gk5QxG45K.json"
                            background="transparent"
                            speed="1"
                            loop
                            autoplay
                            style="width: 80px; height: 80px;">
                        </lottie-player>
                    </div>
                    <h2 class="text-white font-medium text-xl mb-3 tracking-wide">Explorar con IA</h2>
                    <p class="text-neutral-400 text-sm leading-relaxed">
                        Chat inteligente que responde tus preguntas sobre el menú.
                    </p>
                </div>
            </a>
            
            <!-- Tarjeta Menú -->
            <a href="{{ route('menu.classic', $comercio) }}" 
               class="card-hover card-menu glass-card border border-white/10 rounded-2xl p-10 w-64 text-center group">
                <div class="mb-6">
                    <div class="flex justify-center items-center mb-6 h-20">
                        <lottie-player 
                            src="https://lottie.host/8b1216a4-4916-4c25-9909-40963267854c/M7N4c9c58K.json"
                            background="transparent"
                            speed="1"
                            hover
                            style="width: 80px; height: 80px;">
                        </lottie-player>
                    </div>
                    <h2 class="text-white font-medium text-xl mb-3 tracking-wide">Ver menú directo</h2>
                    <p class="text-neutral-400 text-sm leading-relaxed">
                        Acceso rápido al menú completo en formato tradicional.
                    </p>
                </div>
            </a>
        </div>
        
        <!-- Footer -->
        <div class="text-center mt-20 text-neutral-600 text-xs">
            <p>Powered by ServiME IA</p>
        </div>
    </div>
</body>
</html>
