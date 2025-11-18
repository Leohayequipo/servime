<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $comercio->nombre }} - {{ config('app.name', 'ServiME') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/ServiME_IA.ico') }}">
    <link rel="shortcut icon" href="{{ asset('images/ServiME_IA.ico') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Estilos personalizados del comercio -->
    <style>
        :root {
            --primary-color: {{ $comercio->primary_color ?? '#1e293b' }};
            --secondary-color: {{ $comercio->secondary_color ?? '#334155' }};
            --accent-color: {{ $comercio->accent_color ?? '#06b6d4' }};
            --background-color: {{ $comercio->background_color ?? '#0f172a' }};
            --text-primary: #ffffff;
            --text-secondary: #94a3b8;
        }
        
        html, body { 
            height: 100%; 
            margin: 0;
            padding: 0;
        }
        
        body {
            background: linear-gradient(135deg, #000000 0%, var(--background-color) 100%);
            color: var(--text-primary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .animate-fade-in {
            animation: fade-in 0.6s ease-out;
        }
        
        .animate-pulse-slow {
            animation: pulse 3s ease-in-out infinite;
        }
        
        .option-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .option-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }
        
        .option-card:active {
            transform: translateY(-4px);
        }
    </style>
</head>
<body>
    <div class="w-full max-w-2xl px-4 py-8 animate-fade-in">
        <!-- Logo del comercio -->
        <div class="text-center mb-8">
            @if($comercio->logo_url)
                <img src="{{ asset('storage/' . $comercio->logo_url) }}" 
                     alt="{{ $comercio->nombre }}" 
                     class="mx-auto h-24 w-auto object-contain mb-4">
            @else
                <img src="{{ asset('images/logo_empresa_default.png') }}" 
                     alt="{{ $comercio->nombre }}" 
                     class="mx-auto h-24 w-auto object-contain mb-4">
            @endif
            <h1 class="text-3xl font-bold text-white mb-2">{{ $comercio->nombre }}</h1>
            <p class="text-gray-400 text-lg">¿Cómo quieres ver el menú?</p>
        </div>
        
        <!-- Opciones -->
        <div class="grid md:grid-cols-2 gap-6 mt-12">
            <!-- Opción 1: Menú con IA -->
            <a href="{{ route('menu.chat', $comercio) }}" 
               class="option-card bg-gradient-to-br from-blue-600 to-purple-600 rounded-2xl p-8 text-center shadow-2xl border border-blue-500/30">
                <div class="mb-6">
                    <div class="text-6xl mb-4 animate-pulse-slow">🤖</div>
                    <h2 class="text-2xl font-bold text-white mb-3">Explorar con IA</h2>
                    <p class="text-blue-100 text-sm leading-relaxed">
                        Chat inteligente que responde tus preguntas sobre el menú, te recomienda platos y te ayuda a encontrar lo que buscas.
                    </p>
                </div>
                <div class="mt-6 pt-6 border-t border-white/20">
                    <div class="flex items-center justify-center gap-2 text-white/80 text-sm">
                        <span>✨ Recomendaciones</span>
                        <span>•</span>
                        <span>💬 Chat en vivo</span>
                        <span>•</span>
                        <span>🛒 Pre-pedidos</span>
                    </div>
                </div>
            </a>
            
            <!-- Opción 2: Menú clásico -->
            <a href="{{ route('menu.classic', $comercio) }}" 
               class="option-card bg-gradient-to-br from-gray-700 to-gray-900 rounded-2xl p-8 text-center shadow-2xl border border-gray-600/30">
                <div class="mb-6">
                    <div class="text-6xl mb-4">📄</div>
                    <h2 class="text-2xl font-bold text-white mb-3">Ver menú directo</h2>
                    <p class="text-gray-300 text-sm leading-relaxed">
                        Acceso rápido al menú completo. Visualiza el PDF o imagen del menú de forma tradicional.
                    </p>
                </div>
                <div class="mt-6 pt-6 border-t border-white/20">
                    <div class="flex items-center justify-center gap-2 text-white/80 text-sm">
                        <span>⚡ Rápido</span>
                        <span>•</span>
                        <span>👁️ Visual</span>
                        <span>•</span>
                        <span>📱 Fácil</span>
                    </div>
                </div>
            </a>
        </div>
        
        <!-- Footer -->
        <div class="text-center mt-12 text-gray-500 text-sm">
            <p>Powered by ServiME IA</p>
        </div>
    </div>
</body>
</html>

