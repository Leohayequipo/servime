<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $comercio->page_title ?? config('app.name', 'ServiME') }}</title>
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
            --accent-blue: {{ $comercio->primary_color ?? '#3b82f6' }};
            --accent-green: #10b981;
            --accent-purple: #8b5cf6;
            --accent-pink: #ec4899;
            --secondary-dark: {{ $comercio->secondary_color ?? '#334155' }};
            --accent-gray: #6b7280;
        }
        
        /* Animaciones personalizadas */
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slide-in-left {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes slide-in-right {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .animate-fade-in {
            animation: fade-in 0.5s ease-out;
        }

        .animate-slide-in-left {
            animation: slide-in-left 0.4s ease-out;
        }

        .animate-slide-in-right {
            animation: slide-in-right 0.4s ease-out;
        }
        
        html, body { height: 100%; }
        body {
            background: #000000;
            color: var(--text-primary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            overscroll-behavior-y: contain;
        }
        /* Mapeo de clases usadas en la vista previa del administrador */
        .bg-background { background: #000000 !important; }
        .bg-secondary-dark { background: var(--secondary-color) !important; }
        .bg-accent-blue { background: var(--accent-blue) !important; }
        /* Tipografía exacta de la previa */
        .text-text-primary { color: #ffffff !important; }
        .text-text-secondary { color: #cbd5e1 !important; }
        .placeholder-text-secondary::placeholder { color: #cbd5e1 !important; }
        .border-accent-gray { border-color: rgba(255, 255, 255, 0.2) !important; }
        .placeholder-text-secondary::placeholder { color: var(--text-secondary) !important; }
        .focus\:ring-accent-blue:focus { box-shadow: 0 0 0 2px var(--accent-blue) !important; outline: none; }
        
        /* Fullscreen mobile app layout */
        .app { min-height: 100dvh; display: flex; flex-direction: column; }
        .app-header { position: sticky; top: 0; z-index: 10; padding: 12px 16px; background: #000000; }
        .chat-scroll { flex: 1; overflow-y: auto; -webkit-overflow-scrolling: touch; padding: 12px 16px; padding-bottom: 96px; }
        .input-fixed { position: fixed; left: 0; right: 0; bottom: 0; padding: 8px 12px calc(8px + env(safe-area-inset-bottom)); background: #000000; border-top: 1px solid rgba(255,255,255,0.1); }
        .chips { display: flex; gap: 8px; flex-wrap: wrap; }
        .chips-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 4px; }
        
        .neon-glow {
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
        }
        
        .typing-indicator {
            animation: bounce 1.4s infinite ease-in-out both;
        }
        
        @keyframes bounce {
            0%, 80%, 100% {
                transform: scale(0);
            }
            40% {
                transform: scale(1);
            }
        }

        /* Botón "Lo quiero" dentro de mensajes */
        .btn-lo-quiero {
            display: inline-block;
            margin-left: 8px;
            padding: 2px 8px;
            font-size: 12px;
            border-radius: 9999px;
            color: #ffffff;
            background: #3b82f6;
            border: 1px solid rgba(255,255,255,0.15);
            cursor: pointer;
            transition: opacity .15s ease;
        }
        .btn-lo-quiero:hover { opacity: .9; }
        
        /* Animaciones para promociones */
        @keyframes fire-pulse {
            0%, 100% { 
                transform: scale(1);
                filter: brightness(1);
            }
            50% { 
                transform: scale(1.15);
                filter: brightness(1.3);
            }
        }
        
        @keyframes promo-glow {
            0%, 100% {
                box-shadow: 0 2px 8px rgba(255, 140, 0, 0.3),
                           0 0 10px rgba(255, 140, 0, 0.2);
            }
            50% {
                box-shadow: 0 2px 12px rgba(255, 140, 0, 0.6),
                           0 0 20px rgba(255, 140, 0, 0.5),
                           0 0 30px rgba(255, 100, 0, 0.3);
            }
        }
        
        @keyframes promo-shimmer {
            0% {
                background-position: -100% 0;
            }
            100% {
                background-position: 100% 0;
            }
        }
        
        .promo-fire-icon {
            display: inline-block;
            animation: fire-pulse 1.5s ease-in-out infinite;
        }
        
        .promo-button {
            position: relative;
            overflow: hidden;
            animation: promo-glow 2s ease-in-out infinite;
            background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%);
        }
        
        .promo-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.3),
                transparent
            );
            animation: promo-shimmer 3s ease-in-out infinite;
        }
        
        /* Animaciones para botón FAB de categorías */
        @keyframes fab-float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-8px);
            }
        }
        
        @keyframes fab-pulse {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 10px 24px rgba(0,0,0,0.35);
            }
            50% {
                transform: scale(1.05);
                box-shadow: 0 12px 28px rgba(0,0,0,0.45);
            }
        }
        
        @keyframes fab-combined {
            0%, 100% {
                transform: translateY(0) scale(1);
                box-shadow: 0 10px 24px rgba(0,0,0,0.35);
            }
            25% {
                transform: translateY(-6px) scale(1.03);
                box-shadow: 0 12px 26px rgba(0,0,0,0.4);
            }
            50% {
                transform: translateY(-8px) scale(1.05);
                box-shadow: 0 14px 28px rgba(0,0,0,0.45);
            }
            75% {
                transform: translateY(-6px) scale(1.03);
                box-shadow: 0 12px 26px rgba(0,0,0,0.4);
            }
        }
        
        .fab-categories-animated {
            animation: fab-combined 3s ease-in-out infinite;
        }
        
        .fab-categories-animated:hover {
            animation-play-state: paused;
            transform: translateY(-4px) scale(1.08) !important;
            box-shadow: 0 16px 32px rgba(0,0,0,0.5) !important;
        }

        /* FAB button base */
        .fab-btn {
            width: 56px; height: 56px; border: none; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: transform .2s, box-shadow .2s;
            box-shadow: 0 10px 24px rgba(0,0,0,0.35);
        }
        .fab-btn:hover { transform: translateY(-3px); box-shadow: 0 14px 28px rgba(0,0,0,.45); }
        .fab-btn-wish       { background: #2563eb; }
        .fab-btn-categories { background: var(--accent-color); }
        .fab-btn-tags       { background: #ff8c00; }
        .fab-btn span       { font-size: 22px; display: inline-block; }

        /* FAB container */
        .fab-container {
            position: fixed; bottom: 84px; right: 16px; z-index: 50;
            display: flex; gap: 10px; flex-direction: column;
        }

        /* Mic button */
        .mic-button {
            background: var(--accent-color); border: none;
            border-radius: 8px 0 0 8px; padding: 12px;
            cursor: pointer; transition: all .2s;
        }
        .mic-button span { font-size: 20px; }

        /* Input bar */
        .chat-input-bar { border-radius: 8px; }

        /* Wish panel */
        .wish-panel {
            display: none; position: fixed; left: 16px; right: 16px; bottom: 148px; z-index: 60;
        }
        .wish-panel-inner {
            background: #0b0b0b; border: 1px solid rgba(255,255,255,.12); border-radius: 12px;
            box-shadow: 0 10px 24px rgba(0,0,0,.45); padding: 12px; max-width: 520px; margin: 0 auto;
        }
        .wish-header {
            display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 8px;
        }
        .wish-header h4 { font-size: 14px; font-weight: 700; color: #fff; margin: 0; }
        .wish-header-buttons { display: flex; gap: 8px; }
        .wish-btn {
            padding: 6px 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,.12);
            font-size: 12px; cursor: pointer;
        }
        .wish-btn-copy  { background: #141414; color: #fff; }
        .wish-btn-clear { background: #221111; color: #ffb4b4; }
        .wish-btn-close { background: #141414; color: #fff; }
        .wish-list {
            display: flex; flex-direction: column; gap: 6px; max-height: 220px; overflow: auto;
        }

        /* Categories / Tags panels */
        .side-panel {
            display: none; position: fixed; right: 16px; bottom: 148px; z-index: 50; max-width: 320px;
        }
        .side-panel-inner {
            background: #0b0b0b; border: 1px solid rgba(255,255,255,.12); border-radius: 12px;
            box-shadow: 0 10px 24px rgba(0,0,0,.45); padding: 12px;
        }
        .side-panel-inner.amber-border { border-color: rgba(255,140,0,.3); }
        .side-panel-header {
            display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;
        }
        .side-panel-title  { font-size: 13px; font-weight: 600; color: var(--text-primary); margin: 0; }
        .side-panel-close  {
            background: transparent; border: none; color: var(--text-secondary);
            cursor: pointer; font-size: 18px; padding: 0; width: 24px; height: 24px;
            display: flex; align-items: center; justify-content: center;
        }
        .side-panel-grid   { display: flex; flex-wrap: wrap; gap: 6px; max-height: 260px; overflow-y: auto; }

        .cat-chip-promo {
            padding: 10px 12px; border-radius: 10px; border: 1px solid rgba(255,140,0,.5);
            color: #fff; font-size: 13px; font-weight: 600; cursor: pointer;
            transition: all .2s; position: relative; z-index: 1;
        }
        .cat-chip {
            padding: 10px 12px; border-radius: 10px; border: 1px solid rgba(255,255,255,.12);
            background: #141414; color: #fff; font-size: 13px; cursor: pointer; transition: background .2s;
        }
        .tag-chip {
            padding: 8px 12px; border-radius: 8px; border: 1px solid rgba(255,140,0,.3);
            background: rgba(255,140,0,.15); color: #ff8c00; font-size: 12px; font-weight: 500;
            cursor: pointer; transition: all .2s;
        }
    </style>
</head>
<body class="bg-background">
    <div class="app">
        <!-- Header fijo -->
        <div class="app-header">
            <div class="max-w-md mx-auto px-4">
                <!-- Logo centrado -->
                <div class="flex justify-center mb-3">
                    <img src="{{ $comercio->logo_url ? asset('storage/' . $comercio->logo_url) : 'http://127.0.0.1:8000/storage/logos/7KSRtWP0gxvJn8aclINW6dS3QYHAVRf0uAe1XVc4.jpg' }}" alt="{{ $comercio->nombre }}" class="w-20 h-20 rounded-full object-cover shadow-2xl neon-glow">
                </div>
                <!-- Nombre del comercio centrado -->
                <div class="text-center">
                    <h4 class="text-text-primary font-semibold text-xl leading-tight mb-1">{{ $comercio->nombre }}</h4>
                    <p class="text-text-secondary text-base">Menú Digital</p>
                </div>
            </div>
            <div class="max-w-md mx-auto mt-3">
                <div class="bg-gradient-to-r from-accent-blue to-blue-600 rounded-xl p-3 neon-glow" style="background: linear-gradient(to right, {{ $comercio->primary_color ?? '#3b82f6' }}, {{ $comercio->secondary_color ?? '#334155' }});">
                    <div class="flex items-center">
                        <div class="w-6 h-6 bg-white/20 rounded-full flex items-center justify-center mr-2">
                            <span class="text-white text-xs">🤖</span>
                        </div>
                        <span class="text-white font-medium text-base" id="preview-chatbot-name">{{ $comercio->chatbot_name ?? 'Asistente IA' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Área de conversación -->
        <div id="chatScroll" class="chat-scroll">
            <div id="chatMessages" class="max-w-md mx-auto space-y-4 px-4">
                <div class="bg-gradient-to-r from-secondary-dark to-secondary-dark/80 rounded-xl p-4 shadow-lg animate-fade-in">
                    <div class="flex items-start space-x-3">
                        <div class="w-8 h-8 bg-gradient-to-br from-accent-blue to-blue-500 rounded-full flex items-center justify-center flex-shrink-0 shadow-md">
                            <span class="text-white text-sm">🤖</span>
                        </div>
                        <div class="flex-1">
                            <p class="text-text-primary text-base leading-relaxed" id="preview-welcome-message">{{ $comercio->welcome_message ?? '¡Hola! Soy tu asistente virtual. Puedo ayudarte con información sobre nuestro menú. ¿En qué puedo ayudarte?' }}</p>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>

        <!-- Input fijo abajo -->
        <div class="input-fixed">
            <div class="max-w-md mx-auto flex">
                <button id="micButton"
                        title="Hablar con el asistente"
                        style="display:none"
                        class="mic-button">
                    <span>🎤</span>
                </button>
                <input type="text"
                       id="messageInput"
                       placeholder="Escribe tu mensaje..."
                       class="flex-1 bg-secondary-dark border border-accent-gray px-3 py-3 text-text-primary text-base placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-accent-blue chat-input-bar">
                <button id="sendButton" class="bg-accent-blue hover:bg-blue-600 px-5 py-3 rounded-r-lg text-white text-base font-medium transition-colors duration-200">
                    Enviar
                </button>
            </div>
        </div>
    </div>

    <!-- FAB Categorías + Tags + Pre‑pedido -->
    <div class="fab-container">
        @if($comercio->pedido_habilitado)
            <button id="fabWish" title="Resumen para el mozo" class="fab-btn fab-btn-wish">
                <span>🧾</span>
            </button>
        @endif
        @if(!empty($categorias))
            <button id="fabCategories" title="Categorías del menú" class="fab-btn fab-btn-categories fab-categories-animated">
                <span>📋</span>
            </button>
        @endif
        @if(!empty($tags))
            <button id="fabTags" title="Filtrar por tags" class="fab-btn fab-btn-tags">
                <span>🏷️</span>
            </button>
        @endif
    </div>

    @if($comercio->pedido_habilitado)
        <!-- Panel Resumen para el mozo -->
        <div id="wishPanel" class="wish-panel">
            <div class="wish-panel-inner">
                <div class="wish-header">
                    <h4>Resumen para el mozo</h4>
                    <div class="wish-header-buttons">
                        <button id="wishCopy"  class="wish-btn wish-btn-copy">Copiar</button>
                        <button id="wishClear" class="wish-btn wish-btn-clear">Vaciar</button>
                        <button id="wishClose" class="wish-btn wish-btn-close">Cerrar</button>
                    </div>
                </div>
                <div id="wishList" class="wish-list"></div>
            </div>
        </div>
    @endif

    <!-- Panel de Categorías -->
    @if(!empty($categorias))
        <div id="categoriesPanel" class="side-panel">
            <div class="side-panel-inner">
                <div class="side-panel-header">
                    <h4 class="side-panel-title">Categorías</h4>
                    <button id="closeCategories" class="side-panel-close">×</button>
                </div>
                <div class="side-panel-grid">
                    @foreach($categorias as $categoria)
                        @if(isset($categoria['es_promocion']) && $categoria['es_promocion'])
                            <button onclick="askQuestion('{{ $categoria['nombre'] }}'); hideCategories();"
                                    class="promo-button cat-chip-promo"
                                    onmouseover="this.style.transform='scale(1.08)';"
                                    onmouseout="this.style.transform='scale(1)';">
                                <span class="promo-fire-icon">{{ $categoria['icon'] }}</span> {{ $categoria['nombre'] }}
                            </button>
                        @else
                            <button onclick="askQuestion('{{ $categoria['nombre'] }}'); hideCategories();"
                                    class="cat-chip"
                                    onmouseover="this.style.background='#1f1f1f';"
                                    onmouseout="this.style.background='#141414';">
                                {{ $categoria['icon'] }} {{ $categoria['nombre'] }}
                            </button>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Panel de Tags -->
    @if(!empty($tags))
        <div id="tagsPanel" class="side-panel">
            <div class="side-panel-inner amber-border">
                <div class="side-panel-header">
                    <h4 class="side-panel-title">Filtrar por Tags</h4>
                    <button id="closeTags" class="side-panel-close">×</button>
                </div>
                <div class="side-panel-grid">
                    @foreach($tags as $tag)
                        <button onclick="askQuestion('muéstrame opciones {{ str_replace('_', ' ', strtolower($tag)) }}'); hideTags();"
                                class="tag-chip"
                                onmouseover="this.style.background='rgba(255,140,0,0.25)'; this.style.transform='scale(1.05)';"
                                onmouseout="this.style.background='rgba(255,140,0,0.15)'; this.style.transform='scale(1)';">
                            {{ str_replace('_', ' ', $tag) }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <script>
        let isLoading = false;
        const pedidosHabilitados = @json($comercio->pedido_habilitado);
        
        // ===== Reconocimiento de Voz =====
        let recognition = null;
        let isListening = false;
        const micPermissionKey = 'servime_mic_permission_{{ $comercio->id }}';
        const micRejectedKey = 'servime_mic_rejected_{{ $comercio->id }}';
        
        // Detectar soporte de Speech Recognition
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        const isSpeechSupported = SpeechRecognition !== undefined;
        
        // Verificar si el usuario ya rechazó el permiso
        const micRejected = localStorage.getItem(micRejectedKey) === 'true';
        
        // Inicializar reconocimiento de voz cuando el DOM esté listo
        function initSpeechRecognition() {
            const micButton = document.getElementById('micButton');
            const messageInput = document.getElementById('messageInput');
            
            if (!micButton || !messageInput) return;
            
            // Inicializar reconocimiento de voz si está soportado y no fue rechazado
            if (isSpeechSupported && !micRejected) {
                try {
                    recognition = new SpeechRecognition();
                    recognition.lang = 'es-ES';
                    recognition.continuous = false;
                    recognition.interimResults = false;
                    
                    recognition.onstart = function() {
                        isListening = true;
                        micButton.style.background = '#ef4444';
                        micButton.querySelector('span').textContent = '🔴';
                        messageInput.placeholder = 'Escuchando...';
                    };
                    
                    recognition.onresult = function(event) {
                        const transcript = event.results[0][0].transcript;
                        messageInput.value = transcript;
                        messageInput.placeholder = 'Escribe tu mensaje...';
                        // Opcional: enviar automáticamente o permitir editar
                        // sendMessage(transcript);
                    };
                    
                    recognition.onerror = function(event) {
                        isListening = false;
                        micButton.style.background = 'var(--accent-color)';
                        micButton.querySelector('span').textContent = '🎤';
                        messageInput.placeholder = 'Escribe tu mensaje...';
                        
                        if (event.error === 'not-allowed' || event.error === 'denied') {
                            // Usuario rechazó el permiso
                            localStorage.setItem(micRejectedKey, 'true');
                            micButton.style.display = 'none';
                            alert('Permiso de micrófono denegado. Puedes seguir usando el chat escribiendo.');
                        } else if (event.error === 'network') {
                            // Error de red: solo mostrar una vez, no spamear la consola
                            if (!recognition._networkErrorShown) {
                                recognition._networkErrorShown = true;
                                // Silenciosamente reiniciar después de un tiempo
                                setTimeout(() => {
                                    recognition._networkErrorShown = false;
                                }, 5000);
                            }
                        } else if (event.error !== 'no-speech' && event.error !== 'aborted') {
                            // Solo mostrar errores significativos, no los temporales
                            console.warn('Error en reconocimiento de voz:', event.error);
                        }
                    };
                    
                    recognition.onend = function() {
                        isListening = false;
                        micButton.style.background = 'var(--accent-color)';
                        micButton.querySelector('span').textContent = '🎤';
                        messageInput.placeholder = 'Escribe tu mensaje...';
                    };
                    
                    // Solicitar permiso la primera vez que se presiona el botón
                    micButton.addEventListener('click', function() {
                        if (!isListening) {
                            // Intentar iniciar reconocimiento (solicitará permiso si es la primera vez)
                            try {
                                recognition.start();
                            } catch (error) {
                                if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
                                    localStorage.setItem(micRejectedKey, 'true');
                                    micButton.style.display = 'none';
                                    alert('Permiso de micrófono denegado. Puedes seguir usando el chat escribiendo.');
                                }
                            }
                        } else {
                            // Detener reconocimiento
                            recognition.stop();
                        }
                    });
                    
                    // Verificar si ya se dio permiso antes (mostrar botón si es así)
                    // Safari no soporta bien navigator.permissions.query, así que usamos fallback
                    const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
                    
                    if (isSafari) {
                        // Safari: usar solo localStorage como referencia
                        const savedPermission = localStorage.getItem(micPermissionKey);
                        if (savedPermission === 'granted') {
                            micButton.style.display = 'block';
                        } else {
                            // En Safari, mostrar botón y solicitar permiso al hacer clic
                            // (Safari solicita permiso automáticamente al llamar start())
                            micButton.style.display = 'block';
                        }
                    } else {
                        // Chrome, Edge, etc: usar permissions.query
                        navigator.permissions.query({ name: 'microphone' }).then(function(result) {
                            if (result.state === 'granted') {
                                micButton.style.display = 'block';
                                localStorage.setItem(micPermissionKey, 'granted');
                            } else if (result.state === 'prompt') {
                                // Mostrar botón pero solicitar permiso al hacer clic
                                micButton.style.display = 'block';
                            } else {
                                // Permiso denegado
                                localStorage.setItem(micRejectedKey, 'true');
                                micButton.style.display = 'none';
                            }
                        }).catch(function() {
                            // Fallback: mostrar botón y solicitar permiso al hacer clic
                            // (algunos navegadores no soportan permissions.query)
                            const savedPermission = localStorage.getItem(micPermissionKey);
                            if (savedPermission === 'granted') {
                                micButton.style.display = 'block';
                            } else {
                                // Mostrar botón para intentar solicitar permiso
                                micButton.style.display = 'block';
                            }
                        });
                    }
                    
                } catch (error) {
                    console.error('Error inicializando reconocimiento de voz:', error);
                }
            }
            
            // Función para ajustar estilo del input cuando el micrófono está visible
            function updateInputStyle() {
                if (micButton.style.display === 'block' || micButton.style.display === '') {
                    messageInput.style.borderRadius = '0';
                } else {
                    messageInput.style.borderRadius = '8px';
                }
            }
            
            // Ajustar estilo inicial
            updateInputStyle();
            
            // Observar cambios en el display del botón
            const observer = new MutationObserver(updateInputStyle);
            observer.observe(micButton, { attributes: true, attributeFilter: ['style'] });
        }
        
        // Inicializar cuando el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initSpeechRecognition);
        } else {
            initSpeechRecognition();
        }

        // Obtener o crear session_id persistente
        function getOrCreateSessionId() {
            const storageKey = 'servime_chat_session_{{ $comercio->id }}';
            let sessionId = localStorage.getItem(storageKey);
            
            if (!sessionId) {
                // Generar nuevo session_id único
                sessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                localStorage.setItem(storageKey, sessionId);
            }
            
            return sessionId;
        }

        // Enviar mensaje
        function sendMessage(message) {
            if (isLoading) return;
            
            isLoading = true;
            
            // Obtener session_id persistente
            const sessionId = getOrCreateSessionId();
            
            // Mostrar indicador de carga en el botón de envío
            const sendButton = document.getElementById('sendButton');
            const originalContent = sendButton.innerHTML;
            sendButton.innerHTML = `
                <div class="flex items-center justify-center space-x-1">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                    <span class="text-xs">Enviando...</span>
                </div>
            `;
            sendButton.disabled = true;
            
            // Agregar mensaje del usuario
            addMessage(message, 'user');
            
            // Mostrar indicador de escritura
            showTypingIndicator();
            
            // Enviar al servidor con session_id
            fetch('{{ route("chat.api", $comercio) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Session-ID': sessionId
                },
                body: JSON.stringify({ message: message })
            })
            .then(response => response.json())
            .then(data => {
                hideTypingIndicator();
                if (data.response) {
                    addMessage(data.response, 'bot');
                } else {
                    addMessage('Lo siento, no pude procesar tu mensaje. Inténtalo de nuevo.', 'bot');
                }
            })
            .catch(error => {
                hideTypingIndicator();
                addMessage('Error de conexión. Verifica tu internet e inténtalo de nuevo.', 'bot');
                console.error('Error:', error);
            })
            .finally(() => {
                isLoading = false;
                // Restaurar botón de envío
                sendButton.innerHTML = originalContent;
                sendButton.disabled = false;
            });
        }

        // Convertir Markdown básico a HTML con soporte para imágenes, tags y saltos de línea
        function parseMarkdown(text) {
            // Convertir **texto** a <strong>texto</strong> (antes de procesar saltos)
            text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
            // Convertir *texto* a <em>texto</em> (antes de procesar saltos)
            text = text.replace(/\*(.+?)\*/g, '<em>$1</em>');
            
            // Convertir [TAGS:tag1,tag2] a badges visuales
            text = text.replace(/\[TAGS:([^\]]+)\]/g, function(match, tagsStr) {
                const tags = tagsStr.split(',').map(t => t.trim()).filter(t => t);
                if (tags.length === 0) return '';
                const tagsHtml = tags.map(tag => {
                    // Convertir guiones bajos a espacios y capitalizar
                    const displayTag = tag.replace(/_/g, ' ').toUpperCase();
                    return `<span style="display:inline-block;padding:2px 8px;margin:2px;border-radius:6px;font-size:11px;font-weight:500;background:rgba(255,140,0,0.2);color:#ff8c00;border:1px solid rgba(255,140,0,0.3);">${displayTag}</span>`;
                }).join('');
                return `<div style="margin-top:4px;margin-bottom:4px;">${tagsHtml}</div>`;
            });
            
            // Dividir en párrafos (doble salto de línea)
            const paragraphs = text.split(/\n\n+/);
            
            // Procesar cada párrafo
            const processedBlocks = [];
            let currentPlato = null;

            paragraphs.forEach(paragraph => {
                if (paragraph.trim() === '') return;

                // Marcar imágenes [IMAGEN:url] pero NO renderizarlas todavía
                // Las adjuntaremos sólo si pertenecen al plato actual (evita imágenes coladas)
                let p = paragraph.replace(/\[IMAGEN:([^\]]+)\]/g, function(match, url) {
                    const clean = (url || '').trim();
                    // Validar URL: debe ser http/https o ruta absoluta/relativa válida
                    const isValid = clean && 
                        clean.toLowerCase() !== 'null' && 
                        clean.toLowerCase() !== 'undefined' && 
                        clean !== '-' && 
                        (clean.startsWith('http://') || 
                         clean.startsWith('https://') || 
                         clean.startsWith('/') ||
                         clean.startsWith('./') ||
                         clean.match(/^[a-zA-Z0-9][a-zA-Z0-9._/-]*\.(jpg|jpeg|png|gif|webp)/i));
                    if (!isValid) {
                        console.warn('URL de imagen inválida descartada:', clean);
                        return '';
                    }
                    return `[[IMG::${clean}]]`;
                });
                // Saltos simples a <br>
                p = p.replace(/\n/g, '<br>');

                // ¿Es inicio de plato? Buscar múltiples patrones flexibles
                // Patrón 1: - <strong>nombre</strong> — precio (formato estándar)
                let platoMatch = p.match(/^\s*-\s*<strong>([^<]+)<\/strong>\s*[—–-]\s*(?:<strong>)?([^<\n]+)(?:<\/strong>)?/);
                
                // Patrón 2: - <strong>nombre</strong> - precio (guión corto)
                if (!platoMatch) {
                    platoMatch = p.match(/^\s*-\s*<strong>([^<]+)<\/strong>\s*-\s*(?:<strong>)?([^<\n]+)(?:<\/strong>)?/);
                }
                
                // Patrón 3: - <strong>nombre</strong> : precio (dos puntos)
                if (!platoMatch) {
                    platoMatch = p.match(/^\s*-\s*<strong>([^<]+)<\/strong>\s*:\s*(?:<strong>)?([^<\n]+)(?:<\/strong>)?/);
                }
                
                // Patrón 4: - nombre — precio (sin negrita en nombre)
                if (!platoMatch) {
                    platoMatch = p.match(/^\s*-\s*([^:\n<—–]+?)\s*[—–-]\s*([^<\n]+)/);
                }
                
                // Patrón 5: - nombre - precio (sin negrita, guión corto)
                if (!platoMatch) {
                    platoMatch = p.match(/^\s*-\s*([^:\n<-]+?)\s*-\s*([^<\n]+)/);
                }
                
                // Patrón 6: nombre — precio (sin guión inicial, pero con formato de precio)
                if (!platoMatch) {
                    platoMatch = p.match(/^\s*<strong>([^<]+)<\/strong>\s*[—–-]\s*(?:<strong>)?([0-9,\.\s€$]+)(?:<\/strong>)?/);
                }
                
                if (platoMatch) {
                    // Siguiente bloque de plato
                    if (currentPlato) processedBlocks.push(currentPlato);
                    const nombre = platoMatch[1].trim();
                    const precio = (platoMatch[2]||'').trim();
                    
                    // Validar que tenemos nombre y precio válidos
                    if (nombre && precio && (precio.match(/[0-9]/) || precio.includes('€') || precio.includes('$'))) {
                        currentPlato = {
                            nombre: nombre,
                            precio: precio,
                            parrafos: [ `<p class=\"mb-1 last:mb-0\">${p.replace(/\[{2,}IMG::([\s\S]*?)\]{2,}/g,'').trim()}</p>` ],
                            tieneBoton: true,
                            _imgTaken: false,
                            _linesSinceStart: 0
                        };
                        // Si el propio encabezado trae imagen, insertar SOLO la imagen (sin repetir texto)
                        const mHead = p.match(/\[{2,}IMG::([\s\S]*?)\]{2,}/);
                        if (mHead && mHead[1]) {
                            const u = mHead[1].trim();
                            currentPlato.parrafos.push(`<div class=\"mt-2 mb-2\"><img src=\"${u}\" alt=\"Plato\" class=\"w-full max-w-xs rounded-lg shadow-lg object-cover\" style=\"max-height: 200px;\" onerror=\"this.style.display='none'\"></div>`);
                            currentPlato._imgTaken = true;
                        }
                        return;
                    }
                }

                // ¿Es cabecera de categoría? Si hay un plato abierto, ciérralo antes
                const textOnly = p.replace(/<[^>]+>/g,'').trim();
                const isCategoria = (/^[🍽️🥘🍕🍝🥗🍖🍤🍷🧀🍰☕🍺]/.test(textOnly)) ||
                                    (/^[A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑ\s]+$/.test(textOnly) && !textOnly.includes('€') && !textOnly.startsWith('-'));
                if (isCategoria) {
                    p = p.replace(/\[\[IMG::[^\]]+\]\]/g,'');
                    if (currentPlato) { processedBlocks.push(currentPlato); currentPlato = null; }
                    // Añadir un margen superior suave para separar categorías
                    processedBlocks.push({ nombre: null, parrafos: [`<p class=\"mb-2 last:mb-0\" style=\"margin-top:14px;\">${p}</p>`], tieneBoton: false });
                    return;
                }
                // Si hay marcador de imagen, asociarla sólo si es inmediatamente cercana al plato
                if (p.includes('[[IMG::') && currentPlato) {
                    // Asociar imagen solo si aparece inmediatamente tras el encabezado
                    if (!currentPlato._imgTaken && currentPlato._linesSinceStart <= 3) {
                        const htmlImg = p.replace(/\[{2,}IMG::([\s\S]*?)\]{2,}/g, (m,u)=>`<div class=\"mt-2 mb-2\"><img src=\"${u}\" alt=\"Plato\" class=\"w-full max-w-xs rounded-lg shadow-lg object-cover\" style=\"max-height: 200px;\" onerror=\"this.style.display='none'\"></div>`);
                        currentPlato.parrafos.push(htmlImg);
                        currentPlato._imgTaken = true;
                        return;
                    }
                    // si no corresponde, eliminar el marcador (no renderizar imagen)
                    p = p.replace(/\[{2,}IMG::([\s\S]*?)\]{2,}/g,'');
                }
                // Si es descripción, y hay un plato en curso
                if (currentPlato) {
                    p = p.replace(/\[\[IMG::([\s\S]*?)\]\]/g,'');
                    currentPlato.parrafos.push(`<p class=\"mb-2 last:mb-0\">${p}</p>`);
                    currentPlato._linesSinceStart += 1;
                    return;
                }
                // Si no, es texto libre (categoría u otro)
                // Limpiar marcadores de imagen en texto suelto para que no se muestren
                p = p.replace(/\[\[IMG::([\s\S]*?)\]\]/g,'');
                processedBlocks.push({ nombre: null, parrafos: [`<p class=\"mb-2 last:mb-0\">${p}</p>`], tieneBoton: false });
            });
            if (currentPlato) processedBlocks.push(currentPlato);

            // Filtrar duplicados de platos por nombre (quedarse con el primero)
            const filteredBlocks = [];
            const seenNames = new Set();
            processedBlocks.forEach(b => {
                if (b.tieneBoton && b.nombre) {
                    const key = b.nombre.toLowerCase();
                    if (seenNames.has(key)) return; // omitir duplicado
                    seenNames.add(key);
                }
                filteredBlocks.push(b);
            });

            const html = filteredBlocks.map(b => {
                const join = b.parrafos.join('\n');
                // Caso normal: ya detectamos plato
                if (pedidosHabilitados && b.tieneBoton && b.nombre) {
                    return `${join}\n<button class=\"btn-lo-quiero\" data-nombre=\"${b.nombre.replace(/\"/g,'\\\"')}\" data-price=\"${(b.precio||'').replace(/\"/g,'\\\"')}\" style=\"display:inline-block;margin-top:8px;font-size:15px\">Lo quiero</button>`;
                }
                // Fallback: detectar línea de plato en el bloque con múltiples patrones
                let m = null;
                
                // Patrón 1: - <strong>nombre</strong> — precio
                m = join.match(/^\-\s*<strong>([^<]+)<\/strong>\s*[—–-]\s*(?:<strong>)?([^<\n]+)(?:<\/strong>)?/m);
                
                // Patrón 2: - <strong>nombre</strong> - precio
                if (!m) {
                    m = join.match(/^\-\s*<strong>([^<]+)<\/strong>\s*-\s*(?:<strong>)?([^<\n]+)(?:<\/strong>)?/m);
                }
                
                // Patrón 3: - nombre — precio (sin negrita)
                if (!m) {
                    m = join.match(/^\-\s*([^:\n<—–]+?)\s*[—–-]\s*([0-9,\.\s€$]+)/m);
                }
                
                // Patrón 4: - nombre - precio (sin negrita, guión corto)
                if (!m) {
                    m = join.match(/^\-\s*([^:\n<-]+?)\s*-\s*([0-9,\.\s€$]+)/m);
                }
                
                // Patrón 5: <strong>nombre</strong> — precio (sin guión inicial)
                if (!m) {
                    m = join.match(/<strong>([^<]+)<\/strong>\s*[—–-]\s*(?:<strong>)?([0-9,\.\s€$]+)(?:<\/strong>)?/m);
                }
                
                if (m) {
                    const nombre = (m[1]||'').trim();
                    const precio = (m[2]||'').trim();
                    // Validar que tenemos nombre y precio válidos
                    if (nombre && precio && (precio.match(/[0-9]/) || precio.includes('€') || precio.includes('$'))) {
                        if (pedidosHabilitados) {
                            return `${join}\n<button class=\"btn-lo-quiero\" data-nombre=\"${nombre.replace(/\"/g,'\\\"')}\" data-price=\"${precio.replace(/\"/g,'\\\"')}\" style=\"display:inline-block;margin-top:8px;font-size:15px\">Lo quiero</button>`;
                        }
                    }
                }
                return join;
            }).join('');
            const safeHtml = html.replace(/\[{2,}IMG::([\s\S]*?)\]{2,}/g,'');
            return safeHtml;
        }

        // Agregar mensaje al chat
        function addMessage(text, sender) {
            const chatMessages = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            
            // Parsear Markdown para el bot
            const parsedText = sender === 'bot' ? parseMarkdown(text) : text;
            
            if (sender === 'user') {
                messageDiv.innerHTML = `
                    <div class="flex justify-end animate-slide-in-right">
                        <div class="bg-gradient-to-r from-accent-blue to-blue-500 rounded-xl p-4 max-w-xs text-white text-base shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105" style="white-space: pre-wrap;">${parsedText}</div>
                    </div>
                `;
            } else {
                messageDiv.innerHTML = `
                    <div class="bg-gradient-to-r from-secondary-dark to-secondary-dark/80 rounded-xl p-4 shadow-lg animate-slide-in-left">
                        <div class="flex items-start space-x-3">
                            <div class="w-8 h-8 bg-gradient-to-br from-accent-blue to-blue-500 rounded-full flex items-center justify-center flex-shrink-0 shadow-md">
                                <span class="text-white text-sm">🤖</span>
                            </div>
                            <div class="flex-1">
                                <div class="text-text-primary text-base leading-relaxed">${parsedText}</div>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            chatMessages.appendChild(messageDiv);
            scrollToBottom();
        }

        // ===== Wishlist (pre‑pedido simple con "Lo quiero") =====
        const WISH_KEY_BASE = 'servime_wish_{{ $comercio->id }}_';
        const wishKey = () => WISH_KEY_BASE + getOrCreateSessionId();
        function loadWish(){ try{ const r=localStorage.getItem(wishKey()); return r?JSON.parse(r):[];}catch{return [];} }
        async function syncCart(arr){
            try {
                const sessionId = getOrCreateSessionId();
                // enviar diferencias simples: mandamos estado actual item por item
                // aquí solo refrescamos desde servidor con show para mantener simple
                await fetch('/api/cart/show', {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}, body: JSON.stringify({comercio_id: {{ $comercio->id }}, session_id: sessionId})});
            } catch(e) {}
        }
        function saveWish(arr){ localStorage.setItem(wishKey(), JSON.stringify(arr)); renderWish(); }
        function parsePriceToCents(txt){ if(!txt) return null; const m=String(txt).replace(/[^0-9,\.]/g,'').replace(',', '.'); const v=parseFloat(m); return isNaN(v)?null:Math.round(v*100); }
        function addWish(nombre, priceText){
            if (!pedidosHabilitados) return;
            const clean=(nombre||'').trim(); if(!clean) return;
            const w=loadWish();
            const i=w.findIndex(x=>x.nombre.toLowerCase()===clean.toLowerCase());
            const priceTxt = (priceText||'').trim() || null;
            const priceC = parsePriceToCents(priceTxt);
            if(i>=0) {
                w[i].qty+=1;
                // Si antes no teníamos precio y ahora sí, actualizarlo
                if((w[i].priceCents==null || isNaN(w[i].priceCents)) && priceC!=null){
                    w[i].priceCents = priceC;
                    w[i].priceText = priceTxt;
                }
            } else {
                w.push({nombre:clean, qty:1, priceText: priceTxt, priceCents: priceC});
            }
            saveWish(w);
            // sync server increment
            fetch('/api/cart/incr', {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}, body: JSON.stringify({comercio_id: {{ $comercio->id }}, session_id: getOrCreateSessionId(), nombre: clean, delta: 1, price_text: priceTxt})});
        }
        function decWish(idx){ if(!pedidosHabilitados) return; const w=loadWish(); if(w[idx]){ w[idx].qty=Math.max(0,w[idx].qty-1); const name=w[idx].nombre; if(w[idx].qty===0){ w.splice(idx,1); fetch('/api/cart/remove',{method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}, body: JSON.stringify({comercio_id: {{ $comercio->id }}, session_id: getOrCreateSessionId(), nombre: name})}); } else { fetch('/api/cart/incr',{method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}, body: JSON.stringify({comercio_id: {{ $comercio->id }}, session_id: getOrCreateSessionId(), nombre: name, delta: -1})}); } saveWish(w);} }
        function incWish(idx){ if(!pedidosHabilitados) return; const w=loadWish(); if(w[idx]){ w[idx].qty+=1; saveWish(w); fetch('/api/cart/incr',{method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}, body: JSON.stringify({comercio_id: {{ $comercio->id }}, session_id: getOrCreateSessionId(), nombre: w[idx].nombre, delta: 1})}); } }
        function delWish(idx){ if(!pedidosHabilitados) return; const w=loadWish(); const name=w[idx]?.nombre; w.splice(idx,1); saveWish(w); if(name){ fetch('/api/cart/remove',{method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}, body: JSON.stringify({comercio_id: {{ $comercio->id }}, session_id: getOrCreateSessionId(), nombre: name})}); } }
        function clearWish(){ if(!pedidosHabilitados) return; saveWish([]); fetch('/api/cart/clear',{method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}, body: JSON.stringify({comercio_id: {{ $comercio->id }}, session_id: getOrCreateSessionId()})}); }
        function copyWish(){ if(!pedidosHabilitados) return; const w=loadWish(); const t=w.length?w.map(it=>`- ${it.nombre} x${it.qty}${it.priceText?` — ${it.priceText}`:''}`).join('\n'):'Sin ítems'; navigator.clipboard.writeText(`Resumen para el mozo\n${t}`); }
        function renderWish(){
            if(!pedidosHabilitados) return;
            const list=document.getElementById('wishList'); if(!list) return;
            const w=loadWish();
            let anyNoPrecio = false;
            const htmlItems = w.length
              ? w.map((it,idx)=>{
                if(it.priceCents==null) anyNoPrecio=true;
                return `<div style="display:flex; align-items:center; justify-content:space-between; gap:8px; border:1px solid rgba(255,255,255,0.08); padding:8px; border-radius:10px;"><div style=\"color:#fff; font-size:14px;\">${it.nombre}${it.priceText?` — ${it.priceText}`:''}</div><div style=\"display:flex; align-items:center; gap:6px;\"><button class=\"wdec\" data-idx=\"${idx}\" style=\"width:28px; height:28px; border-radius:6px; background:#141414; color:#fff; border:1px solid rgba(255,255,255,0.12);\">-</button><span style=\"min-width:22px; text-align:center; color:#cbd5e1;\">${it.qty}</span><button class=\"winc\" data-idx=\"${idx}\" style=\"width:28px; height:28px; border-radius:6px; background:#141414; color:#fff; border:1px solid rgba(255,255,255,0.12);\">+</button><button class=\"wdel\" data-idx=\"${idx}\" style=\"margin-left:6px; padding:4px 8px; border-radius:6px; background:#221111; color:#ffb4b4; border:1px solid rgba(255,255,255,0.12);\">Quitar</button></div></div>`;
              }).join('')
              : '<div style="color:#94a3b8; font-size:13px;">No hay ítems agregados.</div>';
            const totalCents=w.reduce((s,it)=>s+((it.priceCents||0)*it.qty),0);
            const fmtTotal=(totalCents/100).toLocaleString("es-AR",{style:"currency",currency:"ARS",minimumFractionDigits:2,maximumFractionDigits:2});
            let totalHtml = `<div style=\"margin-top:14px; color:#fff; font-size:1.12em;\"><strong>Total estimado:</strong> ${fmtTotal}</div>`;
            if(anyNoPrecio) totalHtml += `<div style=\"margin-top:2px; color:#bdbdbd; font-size:12px;\">algunos platos no tienen precio confirmado</div>`;
            list.innerHTML = htmlItems + totalHtml;
            // binds
            list.querySelectorAll('.winc').forEach(b=>b.addEventListener('click',()=>incWish(parseInt(b.getAttribute('data-idx'),10))));
            list.querySelectorAll('.wdec').forEach(b=>b.addEventListener('click',()=>decWish(parseInt(b.getAttribute('data-idx'),10))));
            list.querySelectorAll('.wdel').forEach(b=>b.addEventListener('click',()=>delWish(parseInt(b.getAttribute('data-idx'),10))));
        }

        // Mostrar indicador de escritura
        function showTypingIndicator() {
            const chatMessages = document.getElementById('chatMessages');
            const typingDiv = document.createElement('div');
            typingDiv.id = 'typingIndicator';
            typingDiv.innerHTML = `
                <div class="bg-gradient-to-r from-secondary-dark to-secondary-dark/80 rounded-xl p-4 shadow-lg animate-slide-in-left">
                    <div class="flex items-start space-x-3">
                        <div class="w-8 h-8 bg-gradient-to-br from-accent-blue to-blue-500 rounded-full flex items-center justify-center flex-shrink-0 shadow-md">
                            <span class="text-white text-sm">🤖</span>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center space-x-3">
                                <div class="flex space-x-1">
                                    <div class="w-2 h-2 bg-accent-blue rounded-full animate-bounce"></div>
                                    <div class="w-2 h-2 bg-accent-blue rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                                    <div class="w-2 h-2 bg-accent-blue rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                                </div>
                                <span class="text-text-primary text-sm font-medium">Escribiendo...</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            chatMessages.appendChild(typingDiv);
            scrollToBottom();
        }

        // Ocultar indicador de escritura
        function hideTypingIndicator() {
            const typingIndicator = document.getElementById('typingIndicator');
            if (typingIndicator) {
                typingIndicator.remove();
                scrollToBottom();
            }
        }

        // Scroll al final
        function scrollToBottom() {
            setTimeout(() => {
                const chatScroll = document.getElementById('chatScroll');
                if (chatScroll) chatScroll.scrollTop = chatScroll.scrollHeight;
            }, 100);
        }

        // Wishlist panel handlers
        const fabWish = document.getElementById('fabWish');
        const wishPanel = document.getElementById('wishPanel');
        if (fabWish && wishPanel) fabWish.addEventListener('click', () => {
            const shown = wishPanel.style.display === 'block';
            wishPanel.style.display = shown ? 'none' : 'block';
            if (!shown) renderWish();
        });
        document.getElementById('wishClose')?.addEventListener('click', () => { if (wishPanel) wishPanel.style.display = 'none'; });
        document.getElementById('wishClear')?.addEventListener('click', clearWish);
        document.getElementById('wishCopy')?.addEventListener('click', copyWish);

        // Categories panel handlers
        const fabCategories = document.getElementById('fabCategories');
        const categoriesPanel = document.getElementById('categoriesPanel');
        function showCategories() { 
            if (categoriesPanel) {
                categoriesPanel.style.display = 'block';
                if (tagsPanel) tagsPanel.style.display = 'none';
            }
        }
        function hideCategories() { if (categoriesPanel) categoriesPanel.style.display = 'none'; }
        function toggleCategories() { 
            if (categoriesPanel) {
                const isVisible = categoriesPanel.style.display === 'block';
                categoriesPanel.style.display = isVisible ? 'none' : 'block';
                if (!isVisible && tagsPanel) tagsPanel.style.display = 'none';
            }
        }
        if (fabCategories) fabCategories.addEventListener('click', toggleCategories);
        document.getElementById('closeCategories')?.addEventListener('click', hideCategories);
        
        // Tags panel handlers
        const fabTags = document.getElementById('fabTags');
        const tagsPanel = document.getElementById('tagsPanel');
        function showTags() { 
            if (tagsPanel) {
                tagsPanel.style.display = 'block';
                if (categoriesPanel) categoriesPanel.style.display = 'none';
            }
        }
        function hideTags() { if (tagsPanel) tagsPanel.style.display = 'none'; }
        function toggleTags() { 
            if (tagsPanel) {
                const isVisible = tagsPanel.style.display === 'block';
                tagsPanel.style.display = isVisible ? 'none' : 'block';
                if (!isVisible && categoriesPanel) categoriesPanel.style.display = 'none';
            }
        }
        if (fabTags) fabTags.addEventListener('click', toggleTags);
        document.getElementById('closeTags')?.addEventListener('click', hideTags);
        
        function askQuestion(question) {
            sendMessage(question);
            hideCategories();
            hideTags();
        }

        // Delegación de eventos para botones "Lo quiero"
        document.getElementById('chatMessages').addEventListener('click', (e) => {
            const t = e.target;
            if (t && t.classList.contains('btn-lo-quiero')) {
                const nombre = t.getAttribute('data-nombre');
                const priceText = t.getAttribute('data-price');
                addWish(nombre, priceText);
            }
        });

        // Render inicial por si había selección previa
        if (pedidosHabilitados) {
            renderWish();
        }

        // Event listeners
        document.getElementById('sendButton').addEventListener('click', function() {
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
            if (message) {
                sendMessage(message);
                messageInput.value = '';
            }
        });

        document.getElementById('messageInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const message = this.value.trim();
                if (message) {
                    sendMessage(message);
                    this.value = '';
                }
            }
        });

        // Auto-focus
        document.getElementById('messageInput').focus();
    </script>
</body>
</html>