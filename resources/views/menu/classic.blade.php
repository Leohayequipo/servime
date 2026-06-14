<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $comercio->nombre }} - Menú - {{ config('app.name', 'ServiME') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/servime_ia.ico') }}">
    <link rel="shortcut icon" href="{{ asset('images/servime_ia.ico') }}">
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
        <!-- Botón flotante IA -->
        <a href="{{ route('menu.chat', $comercio) }}"
           style="position:fixed;bottom:24px;right:24px;z-index:50;
                  background:rgba(245,158,11,0.15);border:1px solid rgba(245,158,11,0.35);
                  color:#f59e0b;padding:10px 18px;border-radius:12px;
                  font-size:13px;font-weight:600;text-decoration:none;
                  display:inline-flex;align-items:center;gap:8px;
                  backdrop-filter:blur(10px);
                  box-shadow:0 4px 24px rgba(245,158,11,0.15);
                  transition:background 0.2s,border-color 0.2s,box-shadow 0.2s;"
           onmouseover="this.style.background='rgba(245,158,11,0.25)';this.style.borderColor='rgba(245,158,11,0.55)';this.style.boxShadow='0 6px 32px rgba(245,158,11,0.28)'"
           onmouseout="this.style.background='rgba(245,158,11,0.15)';this.style.borderColor='rgba(245,158,11,0.35)';this.style.boxShadow='0 4px 24px rgba(245,158,11,0.15)'">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                <circle cx="9" cy="10" r="1" fill="currentColor"/>
                <circle cx="12" cy="10" r="1" fill="currentColor"/>
                <circle cx="15" cy="10" r="1" fill="currentColor"/>
            </svg>
            Explorar con IA
        </a>
        
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
                       style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;
                              background:rgba(245,158,11,0.15);border:1px solid rgba(245,158,11,0.3);
                              color:#f59e0b;border-radius:12px;font-weight:600;text-decoration:none;
                              backdrop-filter:blur(6px);">
                        Explorar con IA
                    </a>
                </div>
            @endif
        </div>
    </div>
</body>
</html>


