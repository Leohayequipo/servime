<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carta de {{ $comercio->nombre }} — menú completo</title>
    <meta name="description" content="Menú completo de {{ $comercio->nombre }} con todos los platos y precios actualizados. Miralo desde el celular, sin descargar ninguna app.">
    <link rel="canonical" href="{{ route('menu.classic', $comercio->slug) }}">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Carta de {{ $comercio->nombre }}">
    <meta property="og:url" content="{{ route('menu.classic', $comercio->slug) }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32.png') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('images/servime_ia.ico') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    {{-- Inter estaba declarada en el CSS pero nunca se cargaba (igual que en el chat) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Estilos personalizados del comercio -->
    <style>
        /* Misma paleta que el chat (menu/chat.blade.php): escala neutra cálida fija
           + UN acento, el color de marca del comercio. Antes esta vista usaba los
           4 colores configurables y quedaba con otra estética que el chat. */
        :root {
            --bg:        #0F0D0C;
            --surface:   #1A1614;
            --surface-2: #241F1B;
            --line:      rgba(255,255,255,.07);
            --line-2:    rgba(255,255,255,.12);
            --text:      #F5F2EF;
            --text-dim:  #A39A93;

            --accent: {{ $comercio->colorAcento() }};
            --accent-soft: #E0A458;
            --accent-soft: color-mix(in srgb, var(--accent) 55%, #FFF3E2);

            /* Compatibilidad con nombres usados más abajo en esta vista */
            --primary-color: var(--accent);
            --accent-color: var(--accent);
            --secondary-color: var(--surface-2);
            --background-color: var(--bg);
            --text-primary: var(--text);
            --text-secondary: var(--text-dim);
        }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
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
            background: var(--bg);
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

    @include('menu.partials.tracking', ['trackView' => 'classic'])
</body>
</html>


