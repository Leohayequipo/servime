<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $comercio->nombre }} - Menú - {{ config('app.name', 'ServiME') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/ServiME_IA.ico') }}">
    <link rel="shortcut icon" href="{{ asset('images/ServiME_IA.ico') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
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
            background: #000000;
            color: var(--text-primary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        .menu-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .menu-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            padding: 1rem;
            position: sticky;
            top: 0;
            z-index: 10;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }
        
        .menu-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            background: #000000;
        }
        
        .menu-viewer {
            max-width: 100%;
            max-height: calc(100vh - 200px);
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }
        
        .menu-pdf {
            width: 100%;
            height: calc(100vh - 200px);
            border: none;
            border-radius: 12px;
        }
        
        .menu-image {
            max-width: 100%;
            max-height: calc(100vh - 200px);
            object-fit: contain;
            border-radius: 12px;
        }
        
        @keyframes fade-in {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .animate-fade-in {
            animation: fade-in 0.5s ease-out;
        }
    </style>
</head>
<body>
    <div class="menu-container">
        <!-- Header -->
        <div class="menu-header">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <div class="flex items-center gap-4">
                    @if($comercio->logo_url)
                        <img src="{{ asset('storage/' . $comercio->logo_url) }}" 
                             alt="{{ $comercio->nombre }}" 
                             class="h-10 w-auto object-contain">
                    @else
                        <img src="{{ asset('images/logo_empresa_default.png') }}" 
                             alt="{{ $comercio->nombre }}" 
                             class="h-10 w-auto object-contain">
                    @endif
                    <h1 class="text-xl font-bold text-white">{{ $comercio->nombre }}</h1>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('menu.public', $comercio) }}" 
                       class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-white text-sm font-medium transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Volver
                    </a>
                    <a href="{{ route('menu.chat', $comercio) }}" 
                       class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 rounded-lg text-white text-sm font-medium transition-all shadow-lg">
                        <i class="fas fa-robot mr-2"></i>Probar con IA
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Contenido del menú -->
        <div class="menu-content">
            @if($menuActivo && $menuActivo->archivo_original)
                <div class="w-full max-w-6xl animate-fade-in">
                    @if($menuActivo->formato === 'pdf')
                        <iframe 
                            src="{{ asset('storage/' . $menuActivo->archivo_original) }}#toolbar=0" 
                            class="menu-pdf"
                            title="Menú PDF">
                        </iframe>
                    @else
                        <img 
                            src="{{ asset('storage/' . $menuActivo->archivo_original) }}" 
                            alt="Menú {{ $comercio->nombre }}"
                            class="menu-image mx-auto">
                    @endif
                </div>
            @else
                <div class="text-center max-w-md mx-auto animate-fade-in">
                    <div class="text-6xl mb-6">📄</div>
                    <h2 class="text-2xl font-bold text-white mb-4">Menú no disponible</h2>
                    <p class="text-gray-400 mb-6">
                        No hay un menú disponible en este momento. Por favor, intenta más tarde o explora el menú con IA.
                    </p>
                    <a href="{{ route('menu.chat', $comercio) }}" 
                       class="inline-block px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 rounded-lg text-white font-medium transition-all shadow-lg">
                        <i class="fas fa-robot mr-2"></i>Explorar con IA
                    </a>
                </div>
            @endif
        </div>
    </div>
</body>
</html>

