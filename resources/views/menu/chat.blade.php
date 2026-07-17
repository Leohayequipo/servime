<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $comercio->page_title ?? config('app.name', 'ServiME') }}</title>
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
        /* Burbuja del bot: gris oscuro cálido neutro (estilo mockup), no el color de marca */
        .chat-bubble-bot { background: #37332e; }
        .bg-accent-blue { background: var(--accent-blue) !important; }
        /* Tipografía exacta de la previa */
        .text-text-primary { color: #ffffff !important; }
        .text-text-secondary { color: #cbd5e1 !important; }
        .placeholder-text-secondary::placeholder { color: #cbd5e1 !important; }
        .border-accent-gray { border-color: rgba(255, 255, 255, 0.2) !important; }
        .placeholder-text-secondary::placeholder { color: var(--text-secondary) !important; }
        .focus\:ring-accent-blue:focus { box-shadow: 0 0 0 2px var(--accent-blue) !important; outline: none; }
        
        /* Fullscreen mobile app layout */
        .app { height: 100vh; height: 100dvh; display: flex; flex-direction: column; overflow: hidden; }
        /* El header scrollea con el contenido (no queda fijo, libera altura en mobile) */
        .app-header { padding: 8px 16px 14px; background: transparent; }
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

        /* Indicador "escribiendo" estilo mensajería: burbuja compacta con onda de 3 puntos */
        .typing-bubble {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 14px 16px; width: fit-content;
        }
        .typing-bubble .dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: var(--text-secondary);
            animation: typing-wave 1.3s infinite ease-in-out;
        }
        .typing-bubble .dot:nth-child(2) { animation-delay: .18s; }
        .typing-bubble .dot:nth-child(3) { animation-delay: .36s; }
        @keyframes typing-wave {
            0%, 60%, 100% { transform: translateY(0); opacity: .35; }
            30%           { transform: translateY(-5px); opacity: 1; }
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
            transition: opacity .15s ease, transform .15s ease, background .2s ease;
        }
        .btn-lo-quiero:hover { opacity: .9; }
        .btn-lo-quiero.added { background: #16a34a; border-color: rgba(255,255,255,0.35); }
        #fabWish { transition: transform .2s ease; }
        
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
            position: relative;
            width: 56px; height: 56px; border: none; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: transform .2s, box-shadow .2s;
            box-shadow: 0 10px 24px rgba(0,0,0,0.35);
        }
        .fab-btn:hover { transform: translateY(-3px); box-shadow: 0 14px 28px rgba(0,0,0,.45); }
        /* Feedback inmediato al tocar (mobile): el botón "se hunde" al presionar */
        .fab-btn:active { transform: scale(.9); transition: transform .08s ease; box-shadow: 0 6px 14px rgba(0,0,0,.5); }
        .fab-btn-wish       { background: #2563eb; }
        .fab-btn-categories { background: var(--accent-color); }
        .fab-btn-tags       { background: #ff8c00; }
        .fab-btn span       { font-size: 22px; display: inline-block; }

        /* Cada fab: botón + etiqueta debajo */
        .fab-item { display: flex; flex-direction: column; align-items: center; gap: 3px; }
        .fab-label {
            font-size: 10px; font-weight: 600; color: #fff; line-height: 1; letter-spacing: .2px;
            background: rgba(0,0,0,.6); padding: 2px 6px; border-radius: 6px;
            white-space: nowrap; pointer-events: none;
        }
        /* Badge de cantidad en el fab del pedido */
        .fab-btn .fab-badge {
            position: absolute; top: -5px; right: -5px; min-width: 20px; height: 20px;
            padding: 0 5px; border-radius: 10px; background: #ef4444; color: #fff;
            font-size: 12px; font-weight: 700; line-height: 20px; text-align: center;
            border: 2px solid #000; box-shadow: 0 2px 6px rgba(0,0,0,.4); display: none;
        }
        .fab-btn .fab-badge.show { display: block; }

        /* FAB container */
        .fab-container {
            position: fixed; bottom: 84px; right: 16px; z-index: 50;
            display: flex; gap: 10px; flex-direction: column;
            transition: transform .35s cubic-bezier(.22,1,.36,1), opacity .3s ease;
        }
        /* Dock replegado: los accesos se ocultan hacia la derecha */
        body.fabs-collapsed .fab-container {
            transform: translateX(calc(100% + 26px));
            opacity: 0; pointer-events: none;
        }

        /* Pestaña (handle) en el borde derecho para mostrar/ocultar los accesos */
        .fab-tab {
            position: fixed; right: 0; bottom: 100px; z-index: 55;
            width: 24px; height: 64px;
            display: flex; align-items: center; justify-content: center;
            background: rgba(55,51,46,0.92); color: #fff;
            border: 1px solid rgba(255,255,255,0.14); border-right: none;
            border-radius: 12px 0 0 12px;
            box-shadow: -3px 0 10px rgba(0,0,0,0.35);
            cursor: pointer; font-size: 16px; line-height: 1;
            transition: transform .15s ease, background .2s ease;
        }
        .fab-tab:hover { background: rgba(70,64,58,0.96); }
        .fab-tab:active { transform: scale(.9); }
        .fab-tab .fab-tab-chevron { pointer-events: none; }
        .fab-tab .fab-tab-dot {
            position: absolute; top: -5px; left: -6px; min-width: 16px; height: 16px;
            padding: 0 4px; border-radius: 8px; background: #ef4444; color: #fff;
            font-size: 10px; font-weight: 700; line-height: 16px; text-align: center;
            border: 2px solid #000; box-shadow: 0 2px 6px rgba(0,0,0,.4); display: none;
        }
        /* Animación de "asoma y se esconde" para que se note la pestaña la 1ra vez */
        @keyframes fab-tab-peek {
            0%, 100% { transform: translateX(0); }
            50%      { transform: translateX(-6px); }
        }
        .fab-tab.peek { animation: fab-tab-peek 1s ease-in-out 2; }

        /* Flechita animada mientras el dock está oculto: indica que hay opciones */
        @keyframes fab-chevron-hint {
            0%, 55%, 100% { transform: translateX(0); opacity: .8; }
            25%, 40%      { transform: translateX(-3px); opacity: 1; }
        }
        body.fabs-collapsed .fab-tab .fab-tab-chevron {
            display: inline-block;
            animation: fab-chevron-hint 2.4s ease-in-out infinite;
        }
        @media (prefers-reduced-motion: reduce) {
            body.fabs-collapsed .fab-tab .fab-tab-chevron { animation: none; }
            .fab-tab.peek { animation: none; }
        }

        /* Al ocultar los accesos, el chat gana ancho */
        #chatMessages { transition: padding-right .35s ease; }
        body.fabs-collapsed #chatMessages { padding-right: 1rem; }

        /* Mic button (circular) */
        .mic-button {
            background: var(--accent-color); border: none;
            border-radius: 9999px; width: 46px; height: 46px; flex: 0 0 auto;
            text-align: center; line-height: 46px; padding: 0;
            cursor: pointer; transition: transform .15s ease, background .2s ease, box-shadow .2s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,.35);
        }
        .mic-button:active { transform: scale(.92); }
        .mic-button span { font-size: 20px; }

        /* Input bar (píldora cálida) */
        .chat-input-bar {
            border-radius: 9999px !important;
            background: #1f1d1b !important;
        }
        .chat-input-bar:focus { box-shadow: 0 0 0 2px var(--accent-blue) !important; }
        /* Botón enviar (circular, estilo WhatsApp) */
        .send-btn {
            border-radius: 9999px; width: 46px; height: 46px; flex: 0 0 auto;
            display: flex; align-items: center; justify-content: center;
            border: none; padding: 0; cursor: pointer;
        }
        .send-btn:active { transform: scale(.92); }
        .send-btn svg { width: 20px; height: 20px; fill: currentColor; margin-left: 1px; }

        /* Fondo oscuro detrás de cualquier panel abierto (lo separa del chat) */
        .panel-backdrop {
            display: none; position: fixed; inset: 0; z-index: 45;
            background: rgba(0,0,0,.6); backdrop-filter: blur(2px); -webkit-backdrop-filter: blur(2px);
        }
        .panel-backdrop.show { display: block; }

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
            flex-wrap: wrap;
        }
        .wish-header h4 { font-size: 14px; font-weight: 700; color: #fff; margin: 0; }
        .wish-header-buttons { display: flex; gap: 8px; }
        .wish-btn {
            padding: 6px 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,.12);
            font-size: 12px; cursor: pointer;
        }
        .wish-btn-clear { background: #221111; color: #ffb4b4; margin-right: 6px; }
        .wish-btn-clear.armed { background: #7f1d1d; color: #fff; border-color: rgba(255,255,255,.35); }
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
        <!-- Área de conversación (el header va adentro para que scrollee con el contenido) -->
        <div id="chatScroll" class="chat-scroll">
        <!-- Header (ahora scrollea con el contenido, no queda fijo) -->
        <div class="app-header">
            <div class="max-w-md mx-auto px-4">
                <!-- Logo centrado -->
                <div class="flex justify-center mb-3">
                    @if(!empty($comercio->logo_url))
                        <img src="{{ asset('storage/' . $comercio->logo_url) }}" alt="{{ $comercio->nombre }}" class="w-20 h-20 rounded-full object-cover shadow-2xl neon-glow">
                    @else
                        <div class="w-20 h-20 rounded-full object-cover shadow-2xl neon-glow flex items-center justify-center" style="background: linear-gradient(135deg, {{ $comercio->primary_color ?? '#3b82f6' }}, {{ $comercio->secondary_color ?? '#334155' }});">
                            <span class="text-white text-3xl font-bold">{{ strtoupper(mb_substr($comercio->nombre ?? 'M', 0, 1)) }}</span>
                        </div>
                    @endif
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
                        @if(!empty($comercio->logo_url))
                            <img src="{{ asset('storage/'.$comercio->logo_url) }}" alt="" class="w-6 h-6 rounded-full object-cover mr-2">
                        @else
                            <div class="w-6 h-6 bg-white/20 rounded-full flex items-center justify-center mr-2">
                                <span class="text-white text-xs font-semibold">{{ strtoupper(mb_substr($comercio->chatbot_name ?? 'A', 0, 1)) }}</span>
                            </div>
                        @endif
                        <span class="text-white font-medium text-base" id="preview-chatbot-name">{{ $comercio->chatbot_name ?? 'Asistente IA' }}</span>
                    </div>
                </div>
            </div>
        </div>

            <div id="chatMessages" class="max-w-md mx-auto space-y-4 pl-4 pr-16">
                <div class="chat-bubble-bot rounded-3xl rounded-tl-md px-5 py-4 shadow-lg border border-white/5 animate-fade-in">
                    <div class="flex items-start">
                        <div class="flex-1">
                            <p class="text-text-primary text-base leading-relaxed" id="preview-welcome-message">{{ $comercio->welcome_message ?? '¡Hola! Soy tu asistente virtual. Puedo ayudarte con información sobre nuestro menú. ¿En qué puedo ayudarte?' }}</p>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>

        <!-- Input fijo abajo -->
        <div class="input-fixed">
            <div class="max-w-md mx-auto flex items-center gap-2">
                <button id="micButton"
                        title="Hablar con el asistente"
                        style="display:none"
                        class="mic-button">
                    <span><i class="fas fa-microphone"></i></span>
                </button>
                <input type="text"
                       id="messageInput"
                       placeholder="Escribe tu mensaje..."
                       class="flex-1 min-w-0 border border-accent-gray px-4 py-3 text-text-primary text-base placeholder-text-secondary focus:outline-none chat-input-bar">
                <button id="sendButton" title="Enviar" class="send-btn bg-accent-blue text-white transition-all duration-200 hover:opacity-90 shadow-lg flex-none">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1.101 21.757 23.8 12.028 1.101 2.3l.011 7.912 13.623 1.816-13.623 1.817-.011 7.912z"/></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Fondo oscuro que se muestra cuando hay un panel abierto (lo separa del chat) -->
    <div id="panelBackdrop" class="panel-backdrop"></div>

    <!-- FAB Categorías + Tags + Pre‑pedido -->
    @if($comercio->pedido_habilitado || !empty($categorias) || !empty($tags))
        <button id="fabTab" class="fab-tab" type="button" title="Mostrar/ocultar accesos" aria-label="Mostrar accesos">
            <span id="fabTabDot" class="fab-tab-dot"></span>
            <span class="fab-tab-chevron">‹</span>
        </button>
    @endif
    <div class="fab-container">
        @if($comercio->pedido_habilitado)
            <div class="fab-item">
                <button id="fabWish" title="Resumen para el mozo" class="fab-btn fab-btn-wish">
                    <span>🧾</span>
                    <span class="fab-badge" id="wishBadge"></span>
                </button>
                <span class="fab-label">Pedido</span>
            </div>
        @endif
        @if(!empty($categorias))
            <div class="fab-item">
                <button id="fabCategories" title="Categorías del menú" class="fab-btn fab-btn-categories fab-categories-animated">
                    <span>📋</span>
                </button>
                <span class="fab-label">Categorías</span>
            </div>
        @endif
        @if(!empty($tags))
            <div class="fab-item">
                <button id="fabTags" title="Filtrar por tags" class="fab-btn fab-btn-tags">
                    <span>🏷️</span>
                </button>
                <span class="fab-label">Filtros</span>
            </div>
        @endif
    </div>

    @if($comercio->pedido_habilitado)
        <!-- Panel Resumen para el mozo -->
        <div id="wishPanel" class="wish-panel">
            <div class="wish-panel-inner">
                <div class="wish-header">
                    <h4>Resumen para el mozo</h4>
                    <div class="wish-header-buttons">
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
                            <button onclick="mostrarPromociones('{{ $categoria['nombre'] }}'); hideCategories();"
                                    class="promo-button cat-chip-promo"
                                    onmouseover="this.style.transform='scale(1.08)';"
                                    onmouseout="this.style.transform='scale(1)';">
                                <span class="promo-fire-icon">{{ $categoria['icon'] }}</span> {{ $categoria['nombre'] }}
                            </button>
                        @else
                            <button onclick="askQuestion('{{ $categoria['nombre'] }}', 'categoria', '{{ $categoria['nombre'] }}'); hideCategories();"
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
        // Contexto para el botón "Ver más opciones" (para no volcar el menú completo)
        let lastCategoria = null;   // última categoría tocada
        let lastQuery = null;       // última consulta escrita a mano / atajo
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
                        micButton.querySelector('span').innerHTML = '<i class="fas fa-microphone"></i>';
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
                        micButton.querySelector('span').innerHTML = '<i class="fas fa-microphone"></i>';
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
                        micButton.querySelector('span').innerHTML = '<i class="fas fa-microphone"></i>';
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
                // El input es una píldora siempre (mic y enviar van separados con gap)
                messageInput.style.borderRadius = '9999px';
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

        // Enviar mensaje (con streaming: la respuesta se escribe token a token)
        async function sendMessage(message, tipo, scope, esReconsulta) {
            if (isLoading) return;

            // Recordar contexto para "Ver más opciones" (la reconsulta 'ver_mas' NO pisa el estado)
            if (tipo === 'categoria') {
                lastCategoria = scope || message;
                lastQuery = null;
            } else if (!tipo) {
                lastCategoria = null;
                lastQuery = message;
            }

            isLoading = true;
            const sessionId = getOrCreateSessionId();

            // Indicador de carga en el botón de envío
            const sendButton = document.getElementById('sendButton');
            const originalContent = sendButton.innerHTML;
            sendButton.innerHTML = `<div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>`;
            sendButton.disabled = true;

            addMessage(message, 'user');
            showTypingIndicator();

            let wrapper = null, textEl = null, full = '', started = false, finalized = false;
            const ensureBubble = () => {
                if (!started) {
                    hideTypingIndicator();
                    const b = createBotBubble();
                    wrapper = b.wrapper;
                    textEl = b.textEl;
                    started = true;
                }
            };

            try {
                const resp = await fetch('{{ route("chat.stream", $comercio) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Session-ID': sessionId
                    },
                    body: JSON.stringify(
                        tipo ? { message: message, tipo: tipo, scope: scope || message } : { message: message }
                    )
                });

                if (!resp.ok || !resp.body) throw new Error('Sin stream');

                const reader = resp.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';

                while (true) {
                    const { value, done } = await reader.read();
                    if (done) break;
                    buffer += decoder.decode(value, { stream: true });

                    let sep;
                    while ((sep = buffer.indexOf('\n\n')) !== -1) {
                        const frame = buffer.slice(0, sep);
                        buffer = buffer.slice(sep + 2);
                        const dataLine = frame.split('\n').find(l => l.startsWith('data:'));
                        if (!dataLine) continue;
                        let payload;
                        try { payload = JSON.parse(dataLine.slice(5).trim()); } catch (e) { continue; }

                        if (payload.type === 'delta') {
                            ensureBubble();
                            full += payload.content || '';
                            textEl.textContent = full;
                            scrollToMessageTop(wrapper);
                        } else if (payload.type === 'done') {
                            ensureBubble();
                            finalizeBotBubble(wrapper, textEl, full, payload.platos || [], !esReconsulta);
                            finalized = true;
                        }
                    }
                }

                if (!started) {
                    hideTypingIndicator();
                    addMessage('Lo siento, no pude procesar tu mensaje. Inténtalo de nuevo.', 'bot', [], false);
                } else if (!finalized) {
                    // El stream cortó sin "done": formateamos lo que haya llegado
                    finalizeBotBubble(wrapper, textEl, full, [], !esReconsulta);
                }
            } catch (error) {
                console.error('Error:', error);
                if (started && textEl) {
                    finalizeBotBubble(wrapper, textEl, full || 'Error de conexión. Verificá tu internet e intentá de nuevo.', [], false);
                } else {
                    hideTypingIndicator();
                    addMessage('Error de conexión. Verificá tu internet e intentá de nuevo.', 'bot', [], false);
                }
            } finally {
                isLoading = false;
                sendButton.innerHTML = originalContent;
                sendButton.disabled = false;
            }
        }

        // Crea la burbuja del bot vacía para irla llenando con el stream
        function createBotBubble() {
            const chatMessages = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.innerHTML = `
                <div class="chat-bubble-bot rounded-3xl rounded-tl-md px-5 py-4 shadow-lg border border-white/5 animate-slide-in-left">
                    <div class="flex items-start">
                        <div class="flex-1 min-w-0">
                            <div class="text-text-primary text-base leading-relaxed" data-bot-text style="white-space: pre-wrap;"></div>
                            <div data-bot-extra></div>
                        </div>
                    </div>
                </div>
            `;
            chatMessages.appendChild(messageDiv);
            scrollToMessageTop(messageDiv);
            return { wrapper: messageDiv, textEl: messageDiv.querySelector('[data-bot-text]') };
        }

        // Al terminar el stream: formatea Markdown + tarjetas de platos + botón "Ver más"
        function finalizeBotBubble(wrapper, textEl, full, platos, permitirVerMas) {
            textEl.style.whiteSpace = 'normal';
            textEl.innerHTML = parseMarkdown(full);
            const extra = wrapper.querySelector('[data-bot-extra]');
            if (extra) {
                const verMas = (permitirVerMas && platos && platos.length >= 1 && platos.length <= 6)
                    ? '<button type="button" onclick="verMasOpciones()" class="mt-3 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-accent-blue/40 text-accent-blue text-sm hover:bg-accent-blue/10 transition-colors">Ver más opciones ↓</button>'
                    : '';
                extra.innerHTML = renderPlatoCards(platos) + verMas;
            }
            scrollToMessageTop(wrapper);
            wrapper.querySelectorAll('img').forEach(img => {
                img.addEventListener('load', () => scrollToMessageTop(wrapper));
                img.addEventListener('error', () => scrollToMessageTop(wrapper));
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
            
            // Forzar que cada línea de plato ("- ...") arranque su propio párrafo,
            // así cada plato recibe su botón aunque el bot no deje una línea en blanco entre ellos.
            text = text.replace(/\n(?=\s*-\s)/g, '\n\n');

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
                            currentPlato.parrafos.push(`<div class=\"mt-2 mb-2\"><img src=\"${u}\" alt=\"Plato\" class=\"w-full max-w-xs rounded-2xl shadow-lg object-cover\" style=\"max-height: 200px;\" onerror=\"this.style.display='none'\"></div>`);
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
                        const htmlImg = p.replace(/\[{2,}IMG::([\s\S]*?)\]{2,}/g, (m,u)=>`<div class=\"mt-2 mb-2\"><img src=\"${u}\" alt=\"Plato\" class=\"w-full max-w-xs rounded-2xl shadow-lg object-cover\" style=\"max-height: 200px;\" onerror=\"this.style.display='none'\"></div>`);
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

            // Solo texto: los botones "Lo quiero" e imágenes ahora salen de las tarjetas (data.platos).
            const html = filteredBlocks.map(b => b.parrafos.join('\n')).join('');
            const safeHtml = html.replace(/\[{2,}IMG::([\s\S]*?)\]{2,}/g,'');
            return safeHtml;
        }

        // Agregar mensaje al chat
        // Escape básico para HTML
        function escHtml(s){ return String(s==null?'':s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }

        // Tarjetas de plato (foto + precio + "Lo quiero"), con datos exactos del menú
        function renderPlatoCards(platos){
            if (!platos || !platos.length) return '';
            const cards = platos.map(p => {
                const nombre = escHtml(p.nombre||'');
                const precio = escHtml(p.precio||'');
                const desc = escHtml(p.descripcion||'');
                const img = p.imagen ? `<img src="${escHtml(p.imagen)}" alt="${nombre}" class="w-full h-40 object-cover" onerror="this.style.display='none'">` : '';
                const btn = pedidosHabilitados ? `<button class="btn-lo-quiero" data-nombre="${nombre}" data-price="${precio}" style="margin-top:10px;font-size:15px">Lo quiero</button>` : '';
                return `<div class="rounded-2xl overflow-hidden border border-white/10 bg-black/25 shadow-md">
                    ${img}
                    <div class="p-3.5">
                        <div class="flex items-start justify-between gap-2">
                            <span class="font-semibold text-text-primary">${nombre}</span>
                            ${precio ? `<span class="text-accent-blue font-bold whitespace-nowrap">${precio}</span>` : ''}
                        </div>
                        ${desc ? `<p class="text-text-secondary text-sm mt-1 leading-relaxed">${desc}</p>` : ''}
                        ${btn}
                    </div>
                </div>`;
            }).join('');
            return `<div class="mt-3 space-y-3">${cards}</div>`;
        }

        function addMessage(text, sender, platos = [], permitirVerMas = true) {
            const chatMessages = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');

            // Parsear Markdown para el bot
            const parsedText = sender === 'bot' ? parseMarkdown(text) : text;

            if (sender === 'user') {
                messageDiv.innerHTML = `
                    <div class="flex justify-end animate-slide-in-right">
                        <div class="bg-accent-blue rounded-3xl rounded-tr-md px-4 py-3 max-w-xs text-white text-base shadow-lg transition-shadow duration-200 hover:shadow-xl" style="white-space: pre-wrap;">${parsedText}</div>
                    </div>
                `;
            } else {
                messageDiv.innerHTML = `
                    <div class="chat-bubble-bot rounded-3xl rounded-tl-md px-5 py-4 shadow-lg border border-white/5 animate-slide-in-left">
                        <div class="flex items-start">
                            <div class="flex-1 min-w-0">
                                <div class="text-text-primary text-base leading-relaxed">${parsedText}</div>
                                ${renderPlatoCards(platos)}
                                ${(permitirVerMas && platos && platos.length >= 1 && platos.length <= 6) ? '<button type="button" onclick="verMasOpciones()" class="mt-3 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-accent-blue/40 text-accent-blue text-sm hover:bg-accent-blue/10 transition-colors">Ver más opciones ↓</button>' : ''}
                            </div>
                        </div>
                    </div>
                `;
            }
            
            chatMessages.appendChild(messageDiv);
            if (sender === 'user') {
                scrollToBottom();
            } else {
                // La respuesta del bot: mostrar desde la primera línea
                scrollToMessageTop(messageDiv);
                messageDiv.querySelectorAll('img').forEach(img => {
                    img.addEventListener('load', () => scrollToMessageTop(messageDiv));
                    img.addEventListener('error', () => scrollToMessageTop(messageDiv));
                });
            }
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
        function saveWish(arr){ localStorage.setItem(wishKey(), JSON.stringify(arr)); renderWish(); updateWishBadge(); }
        // Badge del fab de pedido: muestra la cantidad total de ítems cargados
        function updateWishBadge(){
            const badge = document.getElementById('wishBadge');
            if(!badge) return;
            const w = loadWish();
            const total = w.reduce((s,it)=> s + (Number(it.qty)||1), 0);
            if(total > 0){ badge.textContent = total > 99 ? '99+' : String(total); badge.classList.add('show'); }
            else { badge.classList.remove('show'); }
            if (typeof updateFabTabDot === 'function') updateFabTabDot();
        }
        // Quitar un ítem del pedido por nombre (para el toggle del botón "Lo quiero")
        function removeWishByName(nombre){
            if(!pedidosHabilitados) return;
            const clean=(nombre||'').trim(); if(!clean) return;
            const w=loadWish();
            const i=w.findIndex(x=>x.nombre.toLowerCase()===clean.toLowerCase());
            if(i>=0){
                w.splice(i,1);
                saveWish(w);
                fetch('/api/cart/remove',{method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}, body: JSON.stringify({comercio_id: {{ $comercio->id }}, session_id: getOrCreateSessionId(), nombre: clean})});
            }
        }
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
            typingDiv.className = 'animate-slide-in-left';
            typingDiv.innerHTML = `
                <div class="chat-bubble-bot rounded-3xl rounded-tl-md shadow-lg border border-white/5 typing-bubble">
                    <span class="dot"></span><span class="dot"></span><span class="dot"></span>
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

        // Posiciona el inicio de un mensaje cerca del tope (leer desde la 1ra línea)
        function scrollToMessageTop(el) {
            setTimeout(() => {
                const sc = document.getElementById('chatScroll');
                if (!sc || !el) return;
                const delta = el.getBoundingClientRect().top - sc.getBoundingClientRect().top;
                sc.scrollTop += delta - 12; // 12px de aire arriba
            }, 100);
        }

        // ===== Gestión unificada de paneles + botón "atrás" del teléfono =====
        const fabWish = document.getElementById('fabWish');
        const wishPanel = document.getElementById('wishPanel');
        const fabCategories = document.getElementById('fabCategories');
        const categoriesPanel = document.getElementById('categoriesPanel');
        const fabTags = document.getElementById('fabTags');
        const tagsPanel = document.getElementById('tagsPanel');

        // Los paneles son mutuamente excluyentes: sólo uno abierto a la vez
        let openPanelEl = null;

        const panelBackdrop = document.getElementById('panelBackdrop');

        function openPanel(el, onOpen) {
            if (!el) return;
            [wishPanel, categoriesPanel, tagsPanel].forEach(p => { if (p && p !== el) p.style.display = 'none'; });
            const yaHabiaPanel = !!openPanelEl;
            el.style.display = 'block';
            openPanelEl = el;
            if (panelBackdrop) panelBackdrop.classList.add('show'); // atenúa el chat detrás
            if (typeof onOpen === 'function') onOpen();
            // Empujamos un estado al historial: así el botón "atrás" cierra el panel y NO sale del menú
            if (!yaHabiaPanel) history.pushState({ servimePanel: true }, '');
        }

        function closePanel(desdePopstate) {
            if (!openPanelEl) return;
            openPanelEl.style.display = 'none';
            openPanelEl = null;
            if (panelBackdrop) panelBackdrop.classList.remove('show');
            // Si se cerró con un botón (no con "atrás"), consumimos el estado que empujamos
            if (!desdePopstate && history.state && history.state.servimePanel) history.back();
        }

        function togglePanel(el, onOpen) {
            if (openPanelEl === el) closePanel(); else openPanel(el, onOpen);
        }

        // Tocar el fondo oscuro cierra el panel abierto
        if (panelBackdrop) panelBackdrop.addEventListener('click', () => closePanel());

        // Botón "atrás" del teléfono: cierra el panel abierto en lugar de abandonar el chat
        window.addEventListener('popstate', () => { if (openPanelEl) closePanel(true); });

        // Usadas por los onclick del markup y por askQuestion()
        function hideCategories() { if (openPanelEl === categoriesPanel) closePanel(); }
        function hideTags() { if (openPanelEl === tagsPanel) closePanel(); }
        function toggleCategories() { togglePanel(categoriesPanel); }
        function toggleTags() { togglePanel(tagsPanel); }

        if (fabWish && wishPanel) fabWish.addEventListener('click', () => togglePanel(wishPanel, renderWish));
        document.getElementById('wishClose')?.addEventListener('click', () => closePanel());
        if (fabCategories) fabCategories.addEventListener('click', toggleCategories);
        document.getElementById('closeCategories')?.addEventListener('click', () => closePanel());
        if (fabTags) fabTags.addEventListener('click', toggleTags);
        document.getElementById('closeTags')?.addEventListener('click', () => closePanel());

        // ===== Repliegue de los accesos (dock) con pestaña en el borde =====
        const FABS_KEY = 'servime_fabs_collapsed_{{ $comercio->id }}';
        const fabTab = document.getElementById('fabTab');
        const fabTabChevron = fabTab?.querySelector('.fab-tab-chevron');

        // Muestra el puntito con la cantidad del pedido cuando el dock está oculto
        function updateFabTabDot() {
            const dot = document.getElementById('fabTabDot');
            if (!dot) return;
            const collapsed = document.body.classList.contains('fabs-collapsed');
            let total = 0;
            if (pedidosHabilitados) total = loadWish().reduce((s, it) => s + (Number(it.qty) || 1), 0);
            if (collapsed && total > 0) { dot.textContent = total > 99 ? '99+' : String(total); dot.style.display = 'block'; }
            else { dot.style.display = 'none'; }
        }

        function setFabsCollapsed(collapsed, persist = true) {
            document.body.classList.toggle('fabs-collapsed', collapsed);
            if (fabTabChevron) fabTabChevron.textContent = collapsed ? '‹' : '›';
            if (fabTab) fabTab.setAttribute('aria-label', collapsed ? 'Mostrar accesos' : 'Ocultar accesos');
            if (collapsed) closePanel(); // si había un panel abierto, ciérralo al ocultar
            if (persist) localStorage.setItem(FABS_KEY, collapsed ? '1' : '0');
            updateFabTabDot();
        }

        if (fabTab) {
            fabTab.addEventListener('click', () => setFabsCollapsed(!document.body.classList.contains('fabs-collapsed')));

            // Estado inicial: por defecto oculto; se recuerda la preferencia
            const savedFabs = localStorage.getItem(FABS_KEY);
            setFabsCollapsed(savedFabs === null ? true : savedFabs === '1', false);

            // Primera visita: "peek" para que se descubra la pestaña
            if (savedFabs === null) {
                fabTab.classList.add('peek');
                fabTab.addEventListener('animationend', () => fabTab.classList.remove('peek'), { once: true });
            }
        }

        // "Vaciar" pedido: requiere doble toque para evitar borrados accidentales
        const wishClearBtn = document.getElementById('wishClear');
        let wishClearArmed = false, wishClearTimer = null;
        function resetWishClear() {
            wishClearArmed = false;
            if (wishClearTimer) clearTimeout(wishClearTimer);
            if (wishClearBtn) { wishClearBtn.textContent = 'Vaciar'; wishClearBtn.classList.remove('armed'); }
        }
        wishClearBtn?.addEventListener('click', () => {
            if (!wishClearArmed) {
                wishClearArmed = true;
                wishClearBtn.textContent = '¿Vaciar?';
                wishClearBtn.classList.add('armed');
                wishClearTimer = setTimeout(resetWishClear, 3000);
                return;
            }
            resetWishClear();
            clearWish();
        });
        
        function askQuestion(question, tipo, scope) {
            sendMessage(question, tipo, scope);
            hideCategories();
            hideTags();
        }

        // Promos cargadas en el panel de menús: se muestran TODAS tal cual, sin pasar
        // por la IA (así no se resumen, no cambian precios ni se mezclan con el menú).
        const promociones = @json($promociones ?? []);

        function mostrarPromociones(titulo) {
            addMessage(titulo, 'user');
            if (!promociones.length) {
                addMessage('Por ahora no tenemos promos cargadas. ¿Te muestro el menú?', 'bot', [], false);
                return;
            }
            const intro = promociones.length === 1
                ? '🔥 Esta es la promo del día:'
                : '🔥 Estas son las promos del día:';
            addMessage(intro, 'bot', promociones, false);
        }

        // "Ver más": lista TODO lo que coincide, acotado al contexto de la última consulta (no el menú entero)
        function verMasOpciones() {
            // Si venías mirando una categoría, listá TODA esa categoría
            if (lastCategoria) {
                sendMessage('Mostrame TODAS las opciones de la categoría ' + lastCategoria + ', sin limitarte a unas pocas.', 'categoria', lastCategoria, true);
                return;
            }
            // Si fue una consulta escrita, listá solo lo que coincide con ESA consulta
            if (lastQuery) {
                sendMessage('Mostrame todas las opciones que coincidan con lo que te pregunté, sin limitarte a unas pocas.', 'ver_mas', lastQuery, true);
                return;
            }
            sendMessage('Mostrame todas las opciones que coincidan con lo que te pregunté, sin limitarte a unas pocas.', null, null, true);
        }

        // Delegación de eventos para botones "Lo quiero"
        document.getElementById('chatMessages').addEventListener('click', (e) => {
            const t = e.target;
            if (t && t.classList.contains('btn-lo-quiero')) {
                const nombre = t.getAttribute('data-nombre');
                const priceText = t.getAttribute('data-price');

                // Toggle: si ya está agregado, lo quitamos del pedido y volvemos a "Lo quiero"
                if (t.dataset.added) {
                    removeWishByName(nombre);
                    delete t.dataset.added;
                    t.textContent = 'Lo quiero';
                    t.classList.remove('added');
                    t.style.transform = 'scale(.95)';
                    setTimeout(() => { t.style.transform = 'scale(1)'; }, 150);
                    return;
                }

                addWish(nombre, priceText);

                // Feedback visual: pop y QUEDA en verde con "✓ Agregado"
                t.dataset.added = '1';
                t.textContent = '✓ Agregado';
                t.classList.add('added');
                t.style.transform = 'scale(1.12)';
                setTimeout(() => { t.style.transform = 'scale(1)'; }, 150);

                // Rebote del carrito flotante para que se note dónde se sumó
                const fab = document.getElementById('fabWish');
                if (fab) { fab.style.transform = 'scale(1.15)'; setTimeout(() => { fab.style.transform = 'scale(1)'; }, 200); }
            }
        });

        // Render inicial por si había selección previa
        if (pedidosHabilitados) {
            renderWish();
            updateWishBadge();
        }

        // Pregunta pre-cargada desde la pantalla de bienvenida (?q=...)
        (function () {
            try {
                const preQ = new URLSearchParams(window.location.search).get('q');
                if (preQ && preQ.trim()) {
                    // Limpiar el query para que no se reenvíe si el usuario refresca
                    history.replaceState(null, '', window.location.pathname);
                    setTimeout(() => sendMessage(preQ.trim()), 400);
                }
            } catch (e) {}
        })();

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

    @include('menu.partials.tracking', ['trackView' => 'chat'])
</body>
</html>
