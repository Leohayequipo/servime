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
        
        /* Animación de estrellas para icono IA */
        @keyframes sparkle {
            0%, 100% { 
                opacity: 0.5; 
                transform: scale(0.95) rotate(0deg); 
            }
            50% { 
                opacity: 0.9; 
                transform: scale(1.15) rotate(180deg); 
            }
        }
        
        @keyframes sparkle-glow {
            0%, 100% { 
                opacity: 0.6; 
                transform: scale(1) rotate(0deg);
                filter: drop-shadow(0 0 2px rgba(255, 215, 0, 0.6));
            }
            50% { 
                opacity: 0.95; 
                transform: scale(1.2) rotate(180deg);
                filter: drop-shadow(0 0 5px rgba(255, 215, 0, 0.8));
            }
        }
        
        @keyframes float {
            0%, 100% { 
                transform: translateY(0px) scale(1);
            }
            50% { 
                transform: translateY(-4px) scale(1.02);
            }
        }
        
        @keyframes pulse-ring {
            0% {
                transform: scale(0.9);
                opacity: 0.6;
            }
            100% {
                transform: scale(1.5);
                opacity: 0;
            }
        }
        
        .sparkle {
            animation: sparkle-glow 2s ease-in-out infinite;
        }
        
        .sparkle:nth-child(1) { animation-delay: 0s; }
        .sparkle:nth-child(2) { animation-delay: 0.4s; }
        .sparkle:nth-child(3) { animation-delay: 0.8s; }
        
        .sparkle-large {
            animation: sparkle-glow 2.5s ease-in-out infinite;
        }
        
        .float-element {
            animation: float 3s ease-in-out infinite;
        }
        
        .pulse-ring {
            animation: pulse-ring 2s ease-out infinite;
        }
        
        /* Animación suave para el icono de menú */
        @keyframes menu-pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.03); }
        }
        
        @keyframes menu-float {
            0%, 100% { 
                transform: translateY(0px) rotate(0deg);
            }
            50% { 
                transform: translateY(-3px) rotate(1deg);
            }
        }
        
        @keyframes line-glow {
            0%, 100% { 
                opacity: 0.6;
                stroke-width: 1.5;
            }
            50% { 
                opacity: 1;
                stroke-width: 2;
            }
        }
        
        @keyframes food-particle {
            0%, 100% { 
                opacity: 0.4;
                transform: translateY(0px) scale(0.9);
            }
            50% { 
                opacity: 0.8;
                transform: translateY(-5px) scale(1.1);
            }
        }
        
        .menu-icon {
            transition: all 0.3s ease;
            animation: menu-float 3s ease-in-out infinite;
        }
        
        .card-menu:hover .menu-icon {
            animation: menu-pulse 1.5s ease-in-out infinite, menu-float 3s ease-in-out infinite;
        }
        
        .menu-line {
            animation: line-glow 2s ease-in-out infinite;
        }
        
        .menu-line:nth-child(1) { animation-delay: 0s; }
        .menu-line:nth-child(2) { animation-delay: 0.3s; }
        .menu-line:nth-child(3) { animation-delay: 0.6s; }
        .menu-line:nth-child(4) { animation-delay: 0.9s; }
        
        .food-particle {
            animation: food-particle 2.5s ease-in-out infinite;
        }
        
        .food-particle:nth-child(1) { animation-delay: 0s; }
        .food-particle:nth-child(2) { animation-delay: 0.5s; }
        .food-particle:nth-child(3) { animation-delay: 1s; }
    </style>
</head>
<body>
    <!-- Video de Fondo Cinemático -->
    <video class="video-background" autoplay loop muted playsinline>
        <source src="{{ asset('videos/background_principal.mp4') }}" type="video/mp4">
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
               class="card-hover card-ia glass-card border border-white/10 rounded-2xl p-10 w-72 md:w-80 text-center group">
                <div class="mb-6">
                    <div class="flex justify-center items-center mb-8 h-32 relative">
                        <!-- Anillo de pulso alrededor del icono (más sutil) -->
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="pulse-ring w-28 h-28 rounded-full border border-indigo-400/30"></div>
                        </div>
                        
                        <!-- Icono AI con estrellas -->
                        <svg width="120" height="120" viewBox="0 0 80 80" class="relative z-10 float-element" style="transform: scale(1.5);">
                            <!-- Estrella superior izquierda -->
                            <g class="sparkle" transform="translate(-3, -3)">
                                <path d="M0 0 L3 -5 L6 0 L3 3 L0 0" fill="#FFD700" opacity="0.85"/>
                                <path d="M3 -5 L3 3" stroke="#FFD700" stroke-width="0.8" opacity="0.85"/>
                            </g>
                            
                            <!-- Estrella superior derecha -->
                            <g class="sparkle sparkle-large" transform="translate(52, -6)">
                                <path d="M0 0 L4 -6 L8 0 L4 4 L0 0" fill="#FFD700" opacity="0.9"/>
                                <path d="M4 -6 L4 4" stroke="#FFD700" stroke-width="1" opacity="0.9"/>
                            </g>
                            
                            <!-- Estrella derecha -->
                            <g class="sparkle" transform="translate(68, 22)">
                                <path d="M0 0 L2.5 -4 L5 0 L2.5 2.5 L0 0" fill="#FFD700" opacity="0.8"/>
                                <path d="M2.5 -4 L2.5 2.5" stroke="#FFD700" stroke-width="0.8" opacity="0.8"/>
                            </g>
                            
                            <!-- Partículas adicionales (más pequeñas y sutiles) -->
                            <circle class="sparkle" cx="10" cy="15" r="1.5" fill="#60A5FA" opacity="0.5"/>
                            <circle class="sparkle" cx="70" cy="10" r="1.2" fill="#A78BFA" opacity="0.5"/>
                            <circle class="sparkle" cx="5" cy="50" r="1.3" fill="#34D399" opacity="0.5"/>
                            
                            <!-- Cuadrado redondeado -->
                            <rect x="20" y="20" width="40" height="40" rx="8" fill="none" stroke="#ffffff" stroke-width="2.5" opacity="0.95"/>
                            <!-- Texto AI -->
                            <text x="40" y="45" font-family="Arial, sans-serif" font-size="20" font-weight="bold" fill="#ffffff" text-anchor="middle" opacity="1">AI</text>
                        </svg>
                    </div>
                    <h2 class="text-white font-medium text-2xl mb-4 tracking-wide">Explorar con IA</h2>
                    <p class="text-neutral-400 text-base leading-relaxed">
                        Chat inteligente que responde tus preguntas sobre el menú.
                    </p>
                </div>
            </a>
            
            <!-- Tarjeta Menú -->
            <a href="{{ route('menu.classic', $comercio) }}" 
               class="card-hover card-menu glass-card border border-white/10 rounded-2xl p-10 w-72 md:w-80 text-center group">
                <div class="mb-6">
                    <div class="flex justify-center items-center mb-8 h-32 relative">
                        <!-- Partículas de comida flotantes -->
                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <div class="food-particle absolute" style="left: 10%; top: 15%;">
                                <svg width="12" height="12" viewBox="0 0 12 12">
                                    <circle cx="6" cy="6" r="2" fill="#FBBF24" opacity="0.7"/>
                                </svg>
                            </div>
                            <div class="food-particle absolute" style="right: 15%; top: 20%;">
                                <svg width="10" height="10" viewBox="0 0 10 10">
                                    <circle cx="5" cy="5" r="1.5" fill="#EF4444" opacity="0.6"/>
                                </svg>
                            </div>
                            <div class="food-particle absolute" style="left: 20%; bottom: 25%;">
                                <svg width="8" height="8" viewBox="0 0 8 8">
                                    <circle cx="4" cy="4" r="1.2" fill="#10B981" opacity="0.6"/>
                                </svg>
                            </div>
                        </div>
                        
                        <!-- Icono de Menú Clásico -->
                        <svg width="120" height="120" viewBox="0 0 80 80" class="menu-icon" style="transform: scale(1.5);">
                            <!-- Menú abierto estilo clásico -->
                            <g transform="translate(15, 20)">
                                <!-- Página del menú -->
                                <rect x="0" y="0" width="50" height="40" rx="4" fill="none" stroke="#ffffff" stroke-width="2" opacity="0.9"/>
                                <!-- Líneas de texto del menú (animadas) -->
                                <line x1="8" y1="10" x2="42" y2="10" class="menu-line" stroke="#ffffff" stroke-width="1.5" opacity="0.7"/>
                                <line x1="8" y1="18" x2="38" y2="18" class="menu-line" stroke="#ffffff" stroke-width="1.5" opacity="0.7"/>
                                <line x1="8" y1="26" x2="35" y2="26" class="menu-line" stroke="#ffffff" stroke-width="1.5" opacity="0.7"/>
                                <line x1="8" y1="34" x2="40" y2="34" class="menu-line" stroke="#ffffff" stroke-width="1.5" opacity="0.7"/>
                            </g>
                            <!-- Decoración: tenedor y cuchillo cruzados -->
                            <g transform="translate(25, 15)" opacity="0.6">
                                <!-- Tenedor -->
                                <line x1="0" y1="0" x2="0" y2="12" stroke="#ffffff" stroke-width="1.5"/>
                                <line x1="-2" y1="3" x2="2" y2="3" stroke="#ffffff" stroke-width="1"/>
                                <line x1="-2" y1="6" x2="2" y2="6" stroke="#ffffff" stroke-width="1"/>
                                <!-- Cuchillo -->
                                <line x1="20" y1="0" x2="20" y2="12" stroke="#ffffff" stroke-width="1.5"/>
                                <line x1="18" y1="2" x2="22" y2="8" stroke="#ffffff" stroke-width="1"/>
                            </g>
                            <!-- Estrella decorativa pequeña -->
                            <g transform="translate(60, 15)" opacity="0.5" class="food-particle">
                                <path d="M0 0 L1.5 -2 L3 0 L1.5 1.5 L0 0" fill="#FFD700"/>
                            </g>
                        </svg>
                    </div>
                    <h2 class="text-white font-medium text-2xl mb-4 tracking-wide">Ver menú directo</h2>
                    <p class="text-neutral-400 text-base leading-relaxed">
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
