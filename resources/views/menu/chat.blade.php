<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $comercio->page_title ?? config('app.name', 'ServiME') }}</title>
    {{-- App interactiva: no aporta contenido rastreable, así que la fuerza SEO
         se concentra en la página de elección del menú. --}}
    <meta name="robots" content="noindex, follow">
    <link rel="canonical" href="{{ route('menu.public', $comercio->slug) }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('images/servime_ia.ico') }}">
    <link rel="shortcut icon" href="{{ asset('images/servime_ia.ico') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    {{-- Inter estaba declarada en el CSS pero nunca se cargaba: terminaba renderizando
         en la fuente por defecto del sistema. display=swap para que no bloquee el render. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    
    <!-- Estilos personalizados del comercio -->
    <style>
        /* ── Sistema de color ────────────────────────────────────────────────────
           Escala neutra CÁLIDA fija (no configurable) + UN solo acento, que es el
           color de marca del comercio. Antes había 4 colores configurables (primario,
           secundario, acento, fondo) y salían combinaciones que chocaban: azul de
           Tailwind + verde en el micrófono + naranja pulsante en las promos.
           Ahora el acento se usa SOLO en la acción principal; el resto son neutros. */
        :root {
            --bg:        #0F0D0C;   /* fondo: casi negro con tinte cálido, no negro puro */
            --surface:   #1A1614;   /* burbuja del bot, tarjetas de plato */
            --surface-2: #241F1B;   /* elevado: input, chips, micrófono */
            --line:      rgba(255,255,255,.07);
            --line-2:    rgba(255,255,255,.12);
            --text:      #F5F2EF;
            --text-dim:  #A39A93;

            /* Acento único: el color de marca del comercio (ver Comercio::colorAcento) */
            --accent: {{ $comercio->colorAcento() }};
            /* Versión clara para texto sobre fondo oscuro (precios). El fallback
               estático va primero: si el navegador no soporta color-mix, gana ese. */
            --accent-soft: #E0A458;
            --accent-soft: color-mix(in srgb, var(--accent) 55%, #FFF3E2);

            /* Compatibilidad con nombres usados en el resto de la vista */
            --primary-color: var(--accent);
            --accent-color: var(--accent);
            --text-primary: var(--text);
            --text-secondary: var(--text-dim);
            --accent-blue: var(--accent);
            --accent-gray: rgba(255,255,255,.12);
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
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
            overscroll-behavior-y: contain;
        }
        .bg-background { background: var(--bg) !important; }
        .bg-secondary-dark { background: var(--surface-2) !important; }
        .chat-bubble-bot { background: var(--surface); }
        .bg-accent-blue { background: var(--accent) !important; }
        .text-text-primary { color: var(--text) !important; }
        .text-text-secondary { color: var(--text-dim) !important; }
        .placeholder-text-secondary::placeholder { color: var(--text-dim) !important; }
        .border-accent-gray { border-color: var(--line-2) !important; }
        .focus\:ring-accent-blue:focus { box-shadow: 0 0 0 2px var(--accent) !important; outline: none; }
        /* Precio: el acento aclarado, para que se lea sobre el fondo oscuro
           incluso si el comercio eligió un color de marca oscuro */
        .text-accent-blue { color: var(--accent-soft) !important; }
        
        /* Fullscreen mobile app layout */
        .app { height: 100vh; height: 100dvh; display: flex; flex-direction: column; overflow: hidden; }
        /* El header scrollea con el contenido (no queda fijo, libera altura en mobile) */
        .app-header { padding: 8px 16px 14px; background: transparent; }
        .chat-scroll { flex: 1; overflow-y: auto; -webkit-overflow-scrolling: touch; padding: 12px 16px; padding-bottom: 96px; }
        .input-fixed { position: fixed; left: 0; right: 0; bottom: 0; padding: 8px 12px calc(8px + env(safe-area-inset-bottom)); background: #000000; border-top: 1px solid rgba(255,255,255,0.1); }
        .chips { display: flex; gap: 8px; flex-wrap: wrap; }
        /* Sugerencias de arranque */
        .chip-sugerencia {
            padding: 8px 13px; border-radius: 9999px; font-size: 13px; cursor: pointer;
            background: var(--surface-2); color: var(--text-dim);
            border: 1px solid var(--line-2);
            transition: color .18s ease, border-color .18s ease;
        }
        .chip-sugerencia:hover { color: var(--text); border-color: rgba(255,255,255,.22); }
        .chip-sugerencia:active { transform: scale(.96); }

        /* Selector de idioma */
        .lang-btn {
            display: inline-flex; align-items: center; gap: 3px;
            padding: 5px 9px; border-radius: 9999px; cursor: pointer;
            background: var(--surface-2); border: 1px solid var(--line-2);
            color: var(--text-dim); font-size: 12px; font-weight: 600; letter-spacing: .3px;
            transition: color .18s ease, border-color .18s ease;
        }
        .lang-btn:hover { color: var(--text); border-color: rgba(255,255,255,.22); }

        /* Mesa detectada por el QR. Usa el acento del comercio y no un color propio:
           es un dato del local, no una marca de ServiME dentro del menú del cliente. */
        .mesa-chip {
            display: inline-flex; align-items: center;
            padding: 5px 11px; border-radius: 9999px;
            background: var(--surface-2); border: 1px solid var(--accent);
            color: var(--text); font-size: 12px; font-weight: 700; white-space: nowrap;
        }
        .lang-menu {
            position: absolute; top: calc(100% + 6px); right: 0; z-index: 60;
            min-width: 132px; padding: 5px; border-radius: 12px;
            background: var(--surface); border: 1px solid var(--line-2);
            box-shadow: 0 12px 30px rgba(0,0,0,.5);
            display: none;
        }
        .lang-menu.open { display: block; }
        .lang-item {
            display: block; padding: 8px 10px; border-radius: 8px;
            color: var(--text-dim); font-size: 13px; text-decoration: none;
            transition: background .15s ease, color .15s ease;
        }
        .lang-item:hover { background: var(--surface-2); color: var(--text); }
        .lang-item.is-active { color: var(--accent-soft); font-weight: 600; }
        .chips-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 4px; }
        
        /* El resplandor neón azul se fue: la elevación ahora se sugiere con sombras
           suaves y bordes hairline, no con glows de color. */
        .neon-glow { box-shadow: none; }
        
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
            background: var(--accent);
            border: 1px solid transparent;
            font-weight: 600;
            cursor: pointer;
            transition: opacity .15s ease, transform .15s ease, background .2s ease;
        }
        .btn-lo-quiero:hover { opacity: .9; }
        /* Estado agregado: sin introducir un color nuevo (antes era verde). Se resuelve
           con un neutro y el acento en el texto, que igual lee como confirmación. */
        .btn-lo-quiero.added {
            background: var(--surface-2);
            border-color: var(--accent);
            color: var(--accent-soft);
        }
        #fabWish { transition: transform .2s ease; }
        
        /* Promociones: antes eran un degradado naranja con resplandor pulsante, brillo
           que barría y un emoji de fuego escalando en loop — tres animaciones infinitas
           sobre un cuarto color. Ahora destacan por CONTRASTE: relleno de acento sólido
           contra chips neutros. Es más sobrio y se distingue igual. */
        .promo-fire-icon { display: none; }

        .promo-button {
            position: relative;
            background: var(--accent);
            color: #fff;
            font-weight: 600;
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
        
        /* La animación infinita de este FAB se quitó: el movimiento perpetuo lee
           amateur, no respeta prefers-reduced-motion y consume batería. Además
           impedía que el botón llegara a estar "estable" para automatizaciones. */
        .fab-categories-animated:hover {
            transform: translateY(-3px);
        }

        /* FAB button base */
        .fab-btn {
            position: relative;
            width: 48px; height: 48px; border: none; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: transform .2s, box-shadow .2s;
            box-shadow: 0 10px 24px rgba(0,0,0,0.35);
        }
        .fab-btn:hover { transform: translateY(-3px); box-shadow: 0 14px 28px rgba(0,0,0,.45); }
        /* Feedback inmediato al tocar (mobile): el botón "se hunde" al presionar */
        .fab-btn:active { transform: scale(.9); transition: transform .08s ease; box-shadow: 0 6px 14px rgba(0,0,0,.5); }
        /* Los tres FABs eran azul, verde y naranja. Ahora son neutros: el único que
           lleva el acento es el del pedido, que es la acción con valor de negocio. */
        .fab-btn-wish       { background: var(--accent); color: #fff; }
        .fab-btn-categories { background: var(--surface-2); color: var(--text); border: 1px solid var(--line-2); }
        .fab-btn-tags       { background: var(--surface-2); color: var(--text); border: 1px solid var(--line-2); }
        .fab-btn svg        { width: 20px; height: 20px; fill: currentColor; }

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

        /* El padding derecho del chat se maneja acá (no con una clase de Tailwind) para
           poder reservarlo según el estado del dock sin pelearse con la utilidad.
           Con el dock ABIERTO se reserva el ancho de los botones + su etiqueta, así las
           tarjetas de plato no quedan debajo. Al cerrarlo, el chat gana ese ancho. */
        #chatMessages { transition: padding-right .35s ease; padding-right: 4.5rem; }
        body.fabs-collapsed #chatMessages { padding-right: 1rem; }

        /* Micrófono: va al lado del enviar y en neutro, porque enviar es la acción
           principal y los dos están siempre a la vista. touch-action y user-select evitan
           que el mantener apretado abra el menú de copiar o haga scroll. */
        .mic-button {
            position: relative;
            background: var(--surface-2); color: var(--text); border: 1px solid var(--line-2);
            border-radius: 9999px; width: 46px; height: 46px; flex: 0 0 auto;
            display: flex; align-items: center; justify-content: center; padding: 0;
            cursor: pointer; transition: transform .15s ease, background .2s ease;
            touch-action: none; user-select: none; -webkit-user-select: none; -webkit-touch-callout: none;
        }
        .mic-button[hidden], .send-btn[hidden] { display: none; }
        .mic-button > svg { width: 21px; height: 21px; display: block; fill: currentColor; pointer-events: none; }
        /* Grabando: el botón crece bajo el dedo, como en WhatsApp */
        /* Crece hacia arriba y adentro: escalado desde el centro se salía por el borde */
        .mic-button.grabando-activo { transform: translate(-6px, -10px) scale(1.3); background: #ef4444; color: #fff; border-color: transparent; box-shadow: 0 0 0 6px rgba(239,68,68,.18); }
        .grab-candado {
            position: absolute; left: 50%; bottom: calc(100% + 14px); transform: translateX(-50%);
            display: none; flex-direction: column; align-items: center; gap: 2px;
            padding: 8px 7px 6px; border-radius: 999px; background: var(--surface-2); border: 1px solid var(--line-2);
            color: var(--text-dim); pointer-events: none;
        }
        .grab-candado svg { width: 14px; height: 14px; fill: currentColor; }
        .grab-candado i { font-style: normal; font-size: 12px; line-height: 1; animation: subir 1.2s ease-in-out infinite; }
        .mic-button.grabando-activo .grab-candado { display: flex; transform: translateX(-50%) scale(.74); }
        @keyframes subir { 0%,100% { transform: translateY(2px); opacity: .5; } 50% { transform: translateY(-3px); opacity: 1; } }

        .grabando {
            flex: 1; min-width: 0; height: 46px; display: flex; align-items: center; gap: 10px;
            padding: 0 14px; border-radius: 9999px; background: var(--surface-2); border: 1px solid var(--line-2);
            color: var(--text); font-size: 16px; user-select: none; -webkit-user-select: none;
        }
        .grabando[hidden] { display: none; }
        .grab-punto { width: 10px; height: 10px; border-radius: 50%; background: #ef4444; flex: none; animation: latido 1s ease-in-out infinite; }
        @keyframes latido { 50% { opacity: .25; } }
        .grab-tiempo { font-variant-numeric: tabular-nums; flex: none; }
        .grab-cancelar { margin-left: auto; color: var(--text-dim); font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: opacity .1s; }
        .grab-borrar { flex: none; width: 34px; height: 34px; margin-left: -8px; border-radius: 50%; border: none; background: transparent; color: #f87171; display: flex; align-items: center; justify-content: center; cursor: pointer; }
        .grab-borrar[hidden] { display: none; }
        .grab-borrar svg { width: 21px; height: 21px; fill: currentColor; }

        /* Burbuja de la nota de voz: play, onda y duración, con lo que se entendió abajo */
        .burbuja-voz { min-width: 230px; }
        .voz-fila { display: flex; align-items: center; gap: 10px; }
        .voz-play { flex: none; width: 34px; height: 34px; border-radius: 50%; border: none; background: rgba(255,255,255,.22); color: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; }
        .voz-play svg { width: 16px; height: 16px; fill: currentColor; }
        .voz-onda { flex: 1; height: 26px; display: flex; align-items: center; gap: 2px; }
        .voz-onda span { flex: 1; min-height: 3px; border-radius: 2px; background: rgba(255,255,255,.45); transition: background .1s; }
        .voz-onda span.oida { background: #fff; }
        .voz-dur { flex: none; font-size: 13px; font-variant-numeric: tabular-nums; opacity: .9; }
        .voz-texto { margin-top: 6px; font-size: 15px; line-height: 1.35; opacity: .92; }
        .voz-texto.pendiente { font-style: italic; opacity: .75; }

        /* Ayuda cuando el micrófono está bloqueado */
        .ayuda-mic {
            width: min(92vw, 380px); border: 1px solid var(--line-2); border-radius: 18px; padding: 20px;
            background: var(--surface); color: var(--text);
        }
        .ayuda-mic::backdrop { background: rgba(0,0,0,.6); }
        .ayuda-mic h3 { font-size: 18px; font-weight: 700; margin-bottom: 10px; }
        .ayuda-mic p { font-size: 16px; line-height: 1.45; }
        .ayuda-mic .ayuda-mic-alt { margin-top: 10px; color: var(--text-dim); font-size: 14px; }
        .ayuda-mic-botones { display: flex; gap: 10px; justify-content: flex-end; margin-top: 18px; }
        .ayuda-mic-botones button { border-radius: 12px; padding: 10px 16px; font-size: 15px; font-weight: 600; cursor: pointer; border: none; }
        .ayuda-mic-sec { background: var(--surface-2); color: var(--text); }
        .ayuda-mic-pri { background: var(--accent); color: #fff; }

        /* Input bar: borde neutro en reposo. Antes tenía borde azul permanente y
           parecía estar siempre enfocado (o en estado de error). */
        .chat-input-bar {
            border-radius: 9999px !important;
            background: var(--surface-2) !important;
            border-color: var(--line-2) !important;
        }
        .chat-input-bar:focus {
            border-color: var(--accent) !important;
            box-shadow: none !important;
        }
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
            background: var(--surface); border: 1px solid var(--line-2); border-radius: 16px;
            box-shadow: 0 16px 40px rgba(0,0,0,.55); padding: 14px; max-width: 520px; margin: 0 auto;
        }
        .wish-header {
            display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 8px;
            flex-wrap: wrap;
        }
        .wish-header h4 { font-size: 14px; font-weight: 700; color: var(--text); margin: 0; }
        /* Más grande que el título que lo acompaña: a un metro de distancia se tiene que
           leer el número, no el encabezado. */
        .wish-mesa {
            display: block; margin-top: 2px;
            font-size: 19px; font-weight: 800; color: var(--accent); letter-spacing: .01em;
        }
        .wish-header-buttons { display: flex; gap: 8px; }
        /* Íconos redondos (tachito y cruz): 36px para que se toquen bien con el dedo. */
        .wish-btn {
            min-width: 36px; height: 36px; padding: 0 9px; border-radius: 9999px;
            border: 1px solid var(--line-2); font-size: 12px; cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        }
        .wish-btn svg { width: 17px; height: 17px; flex-shrink: 0; }
        .wish-btn-clear { background: var(--surface-2); color: #C98B86; margin-right: 6px; }
        /* Primer toque: el tachito se pone rojo y suma "¿Vaciar?" para pedir el segundo. */
        .wish-btn-clear.armed { background: #7A2E2E; color: #fff; border-color: rgba(255,255,255,.3); padding: 0 12px; }
        .wish-btn-clear .wish-btn-label:empty { display: none; }
        .wish-btn-close { background: var(--surface-2); color: var(--text); }
        /* Ya pedido: se distingue del carrito por el borde en el acento, para que de un
           vistazo se vea qué está confirmado y qué todavía se está eligiendo. */
        .pedido-hecho {
            margin-bottom: 10px; padding: 10px; border-radius: 10px;
            background: var(--surface-2); border: 1px solid var(--accent);
        }
        .pedido-hecho-titulo {
            font-size: 12px; font-weight: 700; color: var(--accent);
            text-transform: uppercase; letter-spacing: .04em; margin-bottom: 6px;
        }
        .pedido-hecho-item {
            display: flex; justify-content: space-between; gap: 8px;
            font-size: 14px; color: var(--text); padding: 3px 0;
        }
        .pedido-hecho-nota { font-size: 12px; color: var(--text-dim); }

        .btn-pedir {
            width: 100%; margin-top: 12px; padding: 13px;
            border: 0; border-radius: 12px; cursor: pointer;
            background: var(--accent); color: #fff;
            font-size: 16px; font-weight: 800; letter-spacing: .01em;
        }
        .btn-pedir[disabled] { opacity: .55; cursor: default; }
        .pedir-ayuda { margin: 6px 0 0; font-size: 12px; color: var(--text-dim); text-align: center; }
        /* El error queda pegado al botón que se tocó, en vez de sacarlo de la pantalla
           con un alert del navegador. */
        .pedido-error {
            margin: 8px 0 0; padding: 9px 12px; border-radius: 9px;
            background: rgba(201,139,134,.14); border: 1px solid rgba(201,139,134,.4);
            color: #E8C4C0; font-size: 13px; text-align: center;
        }
        /* Aviso, no error: el cliente no hizo nada mal, sólo entró por otra puerta. */
        /* Es la instrucción de qué hacer con la lista: va en grande y con el acento.
           A 13px y en gris claro quedaba como letra chica y el cliente no la leía. */
        .pedir-aviso {
            margin: 14px 0 0; padding: 14px 16px; border-radius: 12px;
            background: color-mix(in srgb, var(--accent) 14%, var(--surface-2));
            border: 1px solid color-mix(in srgb, var(--accent) 45%, transparent);
            font-size: 17px; font-weight: 600; line-height: 1.35; color: var(--text); text-align: center;
        }

        .wish-list {
            display: flex; flex-direction: column; gap: 6px; max-height: 220px; overflow: auto;
        }

        /* Categories / Tags panels */
        .side-panel {
            display: none; position: fixed; right: 16px; bottom: 148px; z-index: 50; max-width: 320px;
        }
        .side-panel-inner {
            background: var(--surface); border: 1px solid var(--line-2); border-radius: 16px;
            box-shadow: 0 16px 40px rgba(0,0,0,.55); padding: 14px;
        }
        .side-panel-inner.amber-border { border-color: var(--line-2); }
        .side-panel-header {
            display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;
        }
        .side-panel-title  { font-size: 13px; font-weight: 600; color: var(--text-primary); margin: 0; }
        .side-panel-close  {
            background: transparent; border: none; color: var(--text-secondary);
            cursor: pointer; font-size: 18px; padding: 0; width: 24px; height: 24px;
            display: flex; align-items: center; justify-content: center;
        }
        /* Grid de 2 columnas iguales: antes los chips se acomodaban por su largo de
           texto y quedaban filas de 1 o 2 con huecos, que se leía como roto.
           Y un degradado abajo indica que hay más para scrollear (antes se cortaba seco). */
        .side-panel-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 8px;
            max-height: 300px; overflow-y: auto;
            -webkit-mask-image: linear-gradient(to bottom, #000 88%, transparent 100%);
                    mask-image: linear-gradient(to bottom, #000 88%, transparent 100%);
        }

        .cat-chip, .cat-chip-promo {
            padding: 11px 12px; border-radius: 12px; font-size: 13px; cursor: pointer;
            text-align: left; line-height: 1.25;
            transition: background .18s ease, border-color .18s ease;
        }
        .cat-chip {
            border: 1px solid var(--line-2); background: var(--surface-2); color: var(--text);
        }
        .cat-chip:hover { background: #2E2823; border-color: rgba(255,255,255,.2); }
        .cat-chip-promo { border: 1px solid transparent; grid-column: 1 / -1; }

        .tag-chip {
            padding: 9px 12px; border-radius: 10px; border: 1px solid var(--line-2);
            background: var(--surface-2); color: var(--text-dim);
            font-size: 12px; font-weight: 500; cursor: pointer; text-align: left;
            transition: color .18s ease, border-color .18s ease;
        }
        .tag-chip:hover { color: var(--text); border-color: rgba(255,255,255,.2); }
    </style>
</head>
<body class="bg-background">
    <div class="app">
        <!-- Área de conversación (el header va adentro para que scrollee con el contenido) -->
        <div id="chatScroll" class="chat-scroll">
        <!-- Header (ahora scrollea con el contenido, no queda fijo) -->
        {{-- Una sola fila: logo + nombre + asistente. Antes eran dos bloques apilados con
             el logo repetido, que se comían ~40% del viewport antes del primer mensaje. --}}
        <div class="app-header">
            <div class="max-w-md mx-auto flex items-center gap-3">
                @if(!empty($comercio->logo_url))
                    <img src="{{ asset('storage/' . $comercio->logo_url) }}" alt="{{ $comercio->nombre }}" class="w-11 h-11 rounded-full object-cover flex-none">
                @else
                    <div class="w-11 h-11 rounded-full flex-none flex items-center justify-center" style="background: {{ $comercio->colorAcento() }};">
                        <span class="text-white text-lg font-bold">{{ strtoupper(mb_substr($comercio->nombre ?? 'M', 0, 1)) }}</span>
                    </div>
                @endif
                <div class="min-w-0 flex-1">
                    <h1 class="text-text-primary font-semibold text-lg leading-tight truncate">{{ $comercio->nombre }}</h1>
                    <p class="text-text-secondary text-sm leading-tight truncate" id="preview-chatbot-name">{{ $comercio->chatbot_name ?? 'Asistente IA' }}</p>
                </div>

                {{-- Mesa reconocida por el QR. No es decorativo: es lo que le permite al
                     cliente darse cuenta de que escaneó el cartel de la mesa de al lado,
                     antes de que su pedido termine en otra mesa. --}}
                @if(!empty($mesaActual))
                    <span class="mesa-chip flex-none" title="{{ __('Mesa detectada por el código QR') }}">
                        {{ __('Mesa') }} {{ $mesaActual->numero }}
                    </span>
                @endif

                {{-- Selector de idioma. Sin banderas a propósito: una bandera es un país,
                     no un idioma (¿español es España o Argentina?). Van los nombres en su
                     propio idioma. Solo aparece si hay más de uno disponible. --}}
                @if(count(\App\Http\Middleware\SetLocale::IDIOMAS) > 1)
                    <div class="relative flex-none">
                        <button type="button" id="langBtn" class="lang-btn" aria-haspopup="true" aria-expanded="false"
                                title="{{ __('Idioma') }}">{{ strtoupper(app()->getLocale()) }}
                            <span aria-hidden="true">⌄</span>
                        </button>
                        <div id="langMenu" class="lang-menu" role="menu">
                            @foreach(\App\Http\Middleware\SetLocale::IDIOMAS as $code => $nombre)
                                <a role="menuitem" href="{{ request()->fullUrlWithQuery(['lang' => $code]) }}"
                                   class="lang-item @if(app()->getLocale() === $code) is-active @endif"
                                   lang="{{ $code }}">{{ $nombre }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

            <div id="chatMessages" class="max-w-md mx-auto space-y-3 pl-4">
                <div class="chat-bubble-bot rounded-2xl rounded-tl-md px-4 py-3 shadow-lg border border-white/5 animate-fade-in">
                    <div class="flex items-start">
                        <div class="flex-1">
                            <p class="text-text-primary text-[16px] leading-normal" id="preview-welcome-message">{{ $comercio->mensajeBienvenida() }}</p>
                        </div>
                    </div>
                </div>

                {{-- Sugerencias de arranque. Cumplen dos funciones: llenan el vacío que
                     quedaba abajo de la bienvenida, y al estar en el idioma del cliente
                     son la señal de que puede escribir en su idioma (mejor que un cartel
                     que diga "hablamos inglés"). Se ocultan al primer mensaje. --}}
                {{-- Los arma el comercio desde el panel; si no configuró nada salen del menú
                     (Comercio::chipsDe). Cada uno lleva la frase que se ve y la pregunta que
                     dispara, que pueden ser distintas: "Meriendas para compartir" puede
                     ejecutar "¿qué tienen para merendar entre dos?". --}}
                @if(!empty($chips))
                    <div id="sugerencias" class="chips pt-1">
                        @foreach($chips as $chip)
                            @php $visible = trim(($chip['emoji'] ?? '').' '.__($chip['label'])); @endphp
                            {{-- tipo 'chip' por lo mismo que los atajos de la portada: las arma la
                                 interfaz, son idénticas entre sí y se comían el top de palabras.
                                 El id identifica la VERSIÓN del chip para poder medirla. --}}
                            <button type="button" class="chip-sugerencia"
                                    data-chip-id="{{ $chip['id'] }}"
                                    onclick="askQuestion(@js($chip['q']), 'chip_chat', null, @js($visible), @js($chip['id']))">{{ $visible }}</button>
                        @endforeach
                    </div>
                @endif
                
            </div>
        </div>

        <!-- Input fijo abajo -->
        <div class="input-fixed">
            <div class="max-w-md mx-auto flex items-center gap-2 barra-chat">
                {{-- Mientras graba, esta franja ocupa el lugar del input (estilo WhatsApp).
                     Con la grabación "trabada" aparecen el tacho y el enviar. --}}
                <div id="grabando" class="grabando" hidden>
                    <button type="button" id="grabBorrar" class="grab-borrar" title="{{ __('Borrar') }}" hidden>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 3h6l1 2h4v2H4V5h4l1-2Zm-3 6h12l-1 12H7L6 9Zm4 2v8h2v-8h-2Zm4 0v8h2v-8h-2Z"/></svg>
                    </button>
                    <span class="grab-punto" aria-hidden="true"></span>
                    <span id="grabTiempo" class="grab-tiempo">0:00</span>
                    <span id="grabCancelar" class="grab-cancelar">‹ {{ __('Deslizá para cancelar') }}</span>
                </div>
                {{-- autocomplete="off": sin esto el navegador ofrece el historial de lo que
                     se escribió antes en este input, y al cliente le aparecía un desplegable
                     con mensajes viejos (incluso de otra persona en un celular compartido).
                     enterkeyhint="send" hace que el teclado mobile muestre "Enviar". --}}
                <input type="text"
                       id="messageInput"
                       placeholder="{{ \App\Support\VistaPrevia::activa($comercio) ? 'Vista previa: el chat no responde acá' : __('Escribe tu mensaje...') }}"
                       autocomplete="off"
                       autocapitalize="sentences"
                       spellcheck="false"
                       enterkeyhint="send"
                       class="flex-1 min-w-0 border border-accent-gray px-4 py-3 text-text-primary text-base placeholder-text-secondary focus:outline-none chat-input-bar">
                {{-- Siempre a la vista junto al enviar (con uno que se reemplazaba por el otro,
                     como en WhatsApp, no se entendía). Mantener apretado graba, soltar envía. --}}
                <button type="button" id="micButton" class="mic-button" hidden
                        title="{{ __('Mantené apretado para grabar') }}" aria-label="{{ __('Mantené apretado para grabar') }}">
                    {{-- El candado sale arriba del botón: deslizar hacia él deja grabando
                         sin tener que mantener el dedo. --}}
                    <span id="grabCandado" class="grab-candado" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M7 10V7a5 5 0 0 1 10 0v3h1a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1v-9a1 1 0 0 1 1-1h1Zm2 0h6V7a3 3 0 0 0-6 0v3Z"/></svg>
                        <i>⌃</i>
                    </span>
                    {{-- El SVG va directo en el botón: envuelto en un <span> inline quedaba
                         desalineado por el baseline y el line-height del span. --}}
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 15a3.5 3.5 0 0 0 3.5-3.5v-6a3.5 3.5 0 1 0-7 0v6A3.5 3.5 0 0 0 12 15Zm6-3.5a6 6 0 0 1-5 5.92V21h-2v-3.58a6 6 0 0 1-5-5.92h2a4 4 0 0 0 8 0h2Z"/></svg>
                </button>
                <button id="sendButton" title="{{ __('Enviar') }}" class="send-btn bg-accent-blue text-white transition-all duration-200 hover:opacity-90 shadow-lg flex-none">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1.101 21.757 23.8 12.028 1.101 2.3l.011 7.912 13.623 1.816-13.623 1.817-.011 7.912z"/></svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Micrófono bloqueado. Una vez que el cliente dijo que no, ningún navegador deja
         volver a preguntar desde la página: hay que habilitarlo a mano, así que se le
         explica cómo según su teléfono. --}}
    <dialog id="ayudaMic" class="ayuda-mic">
        <h3>{{ __('Necesitamos el micrófono') }}</h3>
        <p id="ayudaMicPasos"></p>
        <p class="ayuda-mic-alt">{{ __('O escribí tu pregunta abajo.') }}</p>
        <div class="ayuda-mic-botones">
            <button type="button" id="ayudaMicCerrar" class="ayuda-mic-sec">{{ __('Cerrar') }}</button>
            <button type="button" id="ayudaMicReintentar" class="ayuda-mic-pri">{{ __('Probar de nuevo') }}</button>
        </div>
    </dialog>

    <!-- Fondo oscuro que se muestra cuando hay un panel abierto (lo separa del chat) -->
    <div id="panelBackdrop" class="panel-backdrop"></div>

    <!-- FAB Categorías + Tags + Pre‑pedido -->
    @if($aceptaLista || !empty($categorias) || !empty($tags))
        <button id="fabTab" class="fab-tab" type="button" title="{{ __('Mostrar/ocultar accesos') }}" aria-label="{{ __('Mostrar accesos') }}">
            <span id="fabTabDot" class="fab-tab-dot"></span>
            <span class="fab-tab-chevron">‹</span>
        </button>
    @endif
    <div class="fab-container">
        @if($aceptaLista)
            <div class="fab-item">
                <button id="fabWish" title="{{ __('Resumen para el camarero') }}" class="fab-btn fab-btn-wish">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4h10a2 2 0 0 1 2 2v14.5a.5.5 0 0 1-.77.42L16 19.2l-2.23 1.72a.5.5 0 0 1-.61 0L11 19.2l-2.23 1.72a.5.5 0 0 1-.61 0L6 19.2l-1.23.95A.5.5 0 0 1 4 19.73V6a2 2 0 0 1 2-2Zm1 4v2h8V8H8Zm0 4v2h8v-2H8Z"/></svg>
                    <span class="fab-badge" id="wishBadge"></span>
                </button>
                <span class="fab-label">{{ __('Pedido') }}</span>
            </div>
        @endif
        @if(!empty($categorias))
            <div class="fab-item">
                <button id="fabCategories" title="{{ __('Categorías del menú') }}" class="fab-btn fab-btn-categories fab-categories-animated">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h4v4H4V5Zm6 1h10v2H10V6ZM4 11h4v4H4v-4Zm6 1h10v2H10v-2Zm-6 5h4v4H4v-4Zm6 1h10v2H10v-2Z"/></svg>
                </button>
                <span class="fab-label">{{ __('Categorías') }}</span>
            </div>
        @endif
        @if(!empty($tags))
            <div class="fab-item">
                <button id="fabTags" title="{{ __('Filtrar por tags') }}" class="fab-btn fab-btn-tags">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.6 3H20a1 1 0 0 1 1 1v9.4a1 1 0 0 1-.29.7l-7.6 7.6a1 1 0 0 1-1.42 0l-8.4-8.4a1 1 0 0 1 0-1.41l7.6-7.6A1 1 0 0 1 10.6 3Zm5.9 3a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z"/></svg>
                </button>
                <span class="fab-label">{{ __('Filtros') }}</span>
            </div>
        @endif
    </div>

    @if($aceptaLista)
        <!-- Panel Resumen para el camarero -->
        <div id="wishPanel" class="wish-panel">
            <div class="wish-panel-inner">
                <div class="wish-header">
                    {{-- Con mesa reconocida, el número va en el título y no en un rincón:
                         esta pantalla se la muestra el cliente al mozo, y el mozo la mira
                         un segundo. Es el dato que le ahorra preguntar "¿qué mesa son?". --}}
                    <h4>
                        {{ __('Resumen para el camarero') }}
                        @if(!empty($mesaActual))
                            <span class="wish-mesa">{{ __('Mesa') }} {{ $mesaActual->numero }}</span>
                        @endif
                    </h4>
                    <div class="wish-header-buttons">
                        <button id="wishClear" class="wish-btn wish-btn-clear" title="{{ __('Vaciar') }}" aria-label="{{ __('Vaciar') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"/><path d="M19 6l-1 14a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                            <span class="wish-btn-label"></span>
                        </button>
                        <button id="wishClose" class="wish-btn wish-btn-close" title="{{ __('Cerrar') }}" aria-label="{{ __('Cerrar') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
                        </button>
                    </div>
                </div>
                {{-- Lo que la mesa YA pidió, que es lo que el mozo tiene que traer. Va
                     arriba de la lista que el cliente está armando porque esta pantalla
                     se le muestra al mozo, y lo primero que él mira es lo confirmado. --}}
                @if($puedePedir)
                    <div id="pedidoHecho" class="pedido-hecho" hidden></div>
                @endif

                <div id="wishList" class="wish-list"></div>

                {{-- El paso que convierte la lista privada en pedido. Sin él, el mozo vería
                     en su tablet cada plato que alguien tocó por curiosidad y descartó. --}}
                @if($puedePedir)
                    <button id="btnPedir" class="btn-pedir" type="button">
                        {{ __('Pedir') }}
                    </button>
                    <p class="pedir-ayuda">{{ __('El mozo lo ve al toque en su pantalla.') }}</p>
                @else
                    {{-- Entró sin mesa (QR público, redes, un link): la lista es para
                         mostrársela al camarero. No se lo manda a buscar "el QR de su mesa":
                         hay locales que usan sólo el QR público y ese QR no existe. --}}
                    <p class="pedir-aviso">
                        {{ __('Cuando venga el camarer@, mostrale esta pantalla.') }}
                    </p>
                @endif
            </div>
        </div>
    @endif

    <!-- Panel de Categorías -->
    @if(!empty($categorias))
        <div id="categoriesPanel" class="side-panel">
            <div class="side-panel-inner">
                <div class="side-panel-header">
                    <h4 class="side-panel-title">{{ __('Categorías') }}</h4>
                    <button id="closeCategories" class="side-panel-close">×</button>
                </div>
                <div class="side-panel-grid">
                    @foreach($categorias as $categoria)
                        @if(isset($categoria['es_promocion']) && $categoria['es_promocion'])
                            {{-- Se MUESTRA el nombre traducido pero se ENVÍA el original:
                                 el modo categoría del prompt lo busca tal cual está en el menú. --}}
                            <button onclick="mostrarPromociones(@js($categoria['nombre'])); hideCategories();"
                                    class="promo-button cat-chip-promo">
                                {{ $categoria['nombre_visible'] ?? $categoria['nombre'] }}
                            </button>
                        @else
                            {{-- Sin emoji: el mapa de iconos de Comercio::getIconForCategory()
                                 no cubre la mayoría de las categorías reales (adicionales,
                                 empanadas, hamburguesas, papas, gaseosas...) y todas caían
                                 al 🍽️ genérico, repetido 6 veces. Tipografía sola es más limpio. --}}
                            {{-- data-categoria lleva el nombre ORIGINAL del menú, no el
                                 traducido: si no, la misma categoría vista en otro idioma
                                 aparecería como dos en el panel de analytics. --}}
                            <button onclick="askQuestion(@js($categoria['nombre']), 'categoria', @js($categoria['nombre']), @js($categoria['nombre_visible'] ?? $categoria['nombre'])); hideCategories();"
                                    class="cat-chip" data-categoria="{{ $categoria['nombre'] }}">
                                {{ $categoria['nombre_visible'] ?? $categoria['nombre'] }}
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
                    <h4 class="side-panel-title">{{ __('Filtrar por Tags') }}</h4>
                    <button id="closeTags" class="side-panel-close">×</button>
                </div>
                <div class="side-panel-grid">
                    @foreach($tags as $tag)
                        {{-- El hover iba en atributos inline (y en touch no existe): ahora es CSS --}}
                        {{-- tipo 'tag': lo manda el filtro, no lo escribió el cliente. Sin eso,
                             "muéstrame" y "opciones" entraban al top de palabras del panel. --}}
                        <button onclick="askQuestion('muéstrame opciones {{ str_replace('_', ' ', strtolower($tag)) }}', 'tag', @js($tag)); hideTags();"
                                class="tag-chip" data-tag="{{ $tag }}">
                            {{ str_replace('_', ' ', $tag) }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <script>
        @php
            // Textos de interfaz que necesita el JS (el resto van con __() en el Blade)
            $t = [
                'lo_quiero' => __('Lo quiero'),
                'agregado' => __('Agregado'),
                'ver_mas' => __('Ver más opciones'),
                'mantene_para_grabar' => __('Mantené apretado el micrófono para grabar y soltalo para enviar.'),
                'listo_para_grabar' => __('¡Listo! Ahora mantené apretado el micrófono para grabar.'),
                'transcribiendo' => __('Transcribiendo…'),
                'no_te_entendi' => __('No te entendí. Probá de nuevo o escribilo.'),
                'asistente_no_disponible' => __('El asistente no está disponible ahora.'),
                'sin_microfono' => __('No pudimos usar el micrófono de este teléfono.'),
                'reproducir' => __('Reproducir'),
                'mic_pasos_android' => __('Tocá el ícono que está a la izquierda de la dirección de la página, entrá en Permisos y activá el Micrófono. Después tocá «Probar de nuevo».'),
                'mic_pasos_ios' => __('Tocá «aA» en la barra de la dirección, entrá en Configuración del sitio web y en Micrófono elegí Permitir. Después tocá «Probar de nuevo».'),
                'mic_pasos_app' => __('Este navegador no deja usar el micrófono. Tocá los tres puntos de arriba y elegí «Abrir en el navegador».'),
                'escribi' => __('Escribe tu mensaje...'),
                'total' => __('Total'),
                'vacio' => __('Tu pedido está vacío'),
                'sin_precio' => __('algunos platos no tienen precio confirmado'),
                'agregado_ok' => __('✓ Agregado'),
                'sin_items' => __('No hay ítems agregados.'),
                'total_estimado' => __('Total estimado:'),
                'vaciar' => __('Vaciar'),
                'vaciar_confirmar' => __('¿Vaciar?'),
                'quitar' => __('Quitar'),
                'ya_pediste' => __('Ya pediste'),
                'lo_agrego_el_mozo' => __('lo agregó el mozo'),
                'enviando' => __('Enviando…'),
                'pedido_enviado' => __('¡Pedido enviado!'),
                'no_se_pudo_pedir' => __('No se pudo enviar el pedido. Probá de nuevo o avisale al mozo.'),
            ];
        @endphp
        const T = @json($t);

        let isLoading = false;
        // Contexto para el botón "Ver más opciones" (para no volcar el menú completo)
        let lastCategoria = null;   // última categoría tocada
        let lastQuery = null;       // última consulta escrita a mano / atajo
        const pedidosHabilitados = @json($aceptaLista);
        
        // ===== Notas de voz (estilo WhatsApp) =====
        // Mantener apretado el micrófono graba; soltar envía; deslizar a la izquierda
        // cancela; deslizar hacia arriba traba la grabación para no tener que sostener.
        //
        // Antes era el dictado del navegador (Web Speech API), que muchos teléfonos no
        // traen (Firefox, Samsung, Xiaomi, el navegador de Instagram): el botón ni aparecía.
        // Grabar audio lo soporta casi todo, y el texto lo saca el servidor
        // (TranscripcionService). Tampoco se esconde más el botón cuando se negó el permiso:
        // quedaba oculto para siempre, aunque después lo habilitaran.
        const vozDisponible = !!(window.isSecureContext && navigator.mediaDevices?.getUserMedia && window.MediaRecorder);
        const SEGUNDOS_MAXIMOS = @json(\App\Services\TranscripcionService::SEGUNDOS_MAXIMOS);
        const DESLIZAR_CANCELA = 90;   // px a la izquierda
        const DESLIZAR_TRABA = 70;     // px hacia arriba

        let permisoMic = false;        // ya lo concedió en esta página
        let grab = null;               // grabación en curso

        // Los dos botones siempre a la vista. Se probó el cambio de uno por otro según
        // haya texto (como WhatsApp) y confundía: parecía que no se podía escribir.
        function actualizarBotonesBarra() {
            if (grab) return;
            document.getElementById('micButton').hidden = !vozDisponible;
            document.getElementById('sendButton').hidden = false;
        }

        async function estadoPermisoMic() {
            if (permisoMic) return 'granted';
            try {
                return (await navigator.permissions.query({ name: 'microphone' })).state;
            } catch (e) {
                return 'desconocido'; // Safari viejo: no informa, se pregunta al tocar
            }
        }

        // Pide el permiso sin grabar. Es lo que hace WhatsApp la primera vez: el cartel del
        // navegador interrumpe el "mantener apretado", así que primero se pide y después
        // se avisa que ya puede grabar.
        async function pedirPermisoMic() {
            try {
                const s = await navigator.mediaDevices.getUserMedia({ audio: true });
                s.getTracks().forEach(t => t.stop());
                permisoMic = true;
                document.getElementById('ayudaMic').open && document.getElementById('ayudaMic').close();
                mostrarAviso(T.listo_para_grabar, 3200);
                return true;
            } catch (e) {
                manejarErrorMic(e);
                return false;
            }
        }

        function manejarErrorMic(e) {
            if (e && (e.name === 'NotAllowedError' || e.name === 'SecurityError' || e.name === 'PermissionDeniedError')) {
                mostrarAyudaMic();
            } else if (e && (e.name === 'NotFoundError' || e.name === 'DevicesNotFoundError')) {
                mostrarAviso(T.sin_microfono, 3500);
            } else {
                console.warn('Micrófono:', e);
                mostrarAviso(T.sin_microfono, 3500);
            }
        }

        function mostrarAyudaMic() {
            const ua = navigator.userAgent;
            let pasos = T.mic_pasos_android;
            if (/Instagram|FBAN|FBAV|FB_IAB|Line\/|; wv\)/i.test(ua)) pasos = T.mic_pasos_app;
            else if (/iPhone|iPad|iPod/i.test(ua)) pasos = T.mic_pasos_ios;
            document.getElementById('ayudaMicPasos').textContent = pasos;
            const d = document.getElementById('ayudaMic');
            if (!d.open) d.showModal();
        }
        document.getElementById('ayudaMicCerrar')?.addEventListener('click', () => document.getElementById('ayudaMic').close());
        document.getElementById('ayudaMicReintentar')?.addEventListener('click', pedirPermisoMic);

        function formatoTiempo(seg) {
            seg = Math.max(0, Math.round(seg));
            return Math.floor(seg / 60) + ':' + String(seg % 60).padStart(2, '0');
        }

        function tipoDeGrabacion() {
            // Chrome/Android graban webm-opus; Safari, mp4. Se deja elegir al navegador si
            // no soporta ninguno de los dos explícitamente.
            for (const t of ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg;codecs=opus']) {
                if (MediaRecorder.isTypeSupported?.(t)) return t;
            }
            return '';
        }

        async function empezarGrabacion(xInicial, yInicial) {
            const mic = document.getElementById('micButton');
            grab = { inicio: 0, x: xInicial, y: yInicial, trabada: false, cancelada: false, terminada: false, niveles: [], chunks: [] };
            const actual = grab;
            mic.classList.add('grabando-activo');
            mostrarFranjaGrabando(true);

            try {
                actual.stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            } catch (e) {
                terminarGrabacion(false);
                manejarErrorMic(e);
                return;
            }
            permisoMic = true;
            // Si soltó mientras se abría el micrófono, fue un toque: no hay nada que grabar.
            if (actual.terminada) { actual.stream.getTracks().forEach(t => t.stop()); return; }

            const tipo = tipoDeGrabacion();
            actual.recorder = new MediaRecorder(actual.stream, tipo ? { mimeType: tipo } : undefined);
            actual.recorder.ondataavailable = ev => { if (ev.data && ev.data.size) actual.chunks.push(ev.data); };
            actual.recorder.start();
            actual.inicio = Date.now();
            window.smTrack?.('mic_start');

            // La onda de la burbuja sale del volumen real mientras grababa, como en WhatsApp.
            try {
                const Ctx = window.AudioContext || window.webkitAudioContext;
                actual.ctx = new Ctx();
                const analizador = actual.ctx.createAnalyser();
                analizador.fftSize = 512;
                actual.ctx.createMediaStreamSource(actual.stream).connect(analizador);
                const buf = new Uint8Array(analizador.fftSize);
                actual.medidor = setInterval(() => {
                    analizador.getByteTimeDomainData(buf);
                    let suma = 0;
                    for (const v of buf) { const d = (v - 128) / 128; suma += d * d; }
                    actual.niveles.push(Math.sqrt(suma / buf.length));
                }, 100);
            } catch (e) { /* sin onda real: se dibuja una genérica */ }

            actual.reloj = setInterval(() => {
                const seg = (Date.now() - actual.inicio) / 1000;
                document.getElementById('grabTiempo').textContent = formatoTiempo(seg);
                if (seg >= SEGUNDOS_MAXIMOS) terminarGrabacion(true);
            }, 200);
        }

        function mostrarFranjaGrabando(si) {
            document.getElementById('grabando').hidden = !si;
            document.getElementById('messageInput').hidden = si;
            document.getElementById('sendButton').hidden = true;
            document.getElementById('grabTiempo').textContent = '0:00';
            document.getElementById('grabCancelar').hidden = false;
            document.getElementById('grabCancelar').style.opacity = 1;
            document.getElementById('grabBorrar').hidden = true;
        }

        // Trabada: ya no hace falta sostener el dedo. Aparecen el tacho y el enviar.
        function trabarGrabacion() {
            if (!grab || grab.trabada) return;
            grab.trabada = true;
            document.getElementById('micButton').classList.remove('grabando-activo');
            document.getElementById('micButton').hidden = true;
            document.getElementById('grabCancelar').hidden = true;
            document.getElementById('grabBorrar').hidden = false;
            document.getElementById('sendButton').hidden = false;
        }

        function terminarGrabacion(enviar) {
            const actual = grab;
            if (!actual || actual.terminada) return;
            actual.terminada = true;
            grab = null;
            clearInterval(actual.reloj);
            clearInterval(actual.medidor);
            document.getElementById('micButton').classList.remove('grabando-activo');
            mostrarFranjaGrabando(false);
            actualizarBotonesBarra();

            const duracion = actual.inicio ? (Date.now() - actual.inicio) / 1000 : 0;
            const cerrar = () => {
                actual.stream?.getTracks().forEach(t => t.stop());
                actual.ctx?.close?.();
            };

            if (!actual.recorder) { cerrar(); if (enviar) mostrarAviso(T.mantene_para_grabar, 2600); return; }

            actual.recorder.onstop = () => {
                cerrar();
                if (!enviar) return;
                // Un toque corto no es un audio: WhatsApp explica cómo se usa.
                if (duracion < 0.8) { mostrarAviso(T.mantene_para_grabar, 2600); return; }
                const blob = new Blob(actual.chunks, { type: actual.recorder.mimeType || 'audio/webm' });
                enviarNotaDeVoz(blob, duracion, actual.niveles);
            };
            actual.recorder.stop();
        }

        // Dibuja N barras a partir de los niveles medidos (o una onda genérica sin ellos).
        function barrasDeOnda(niveles, n = 28) {
            const out = [];
            for (let i = 0; i < n; i++) {
                let v;
                if (niveles && niveles.length) {
                    const a = Math.floor(i * niveles.length / n), b = Math.max(a + 1, Math.floor((i + 1) * niveles.length / n));
                    v = Math.max(...niveles.slice(a, b));
                } else {
                    v = 0.15 + 0.25 * Math.abs(Math.sin(i * 1.7));
                }
                out.push(Math.round(Math.min(100, Math.max(12, v * 260))));
            }
            return out;
        }

        function burbujaDeVoz(url, duracion, niveles) {
            const div = document.createElement('div');
            const barras = barrasDeOnda(niveles).map(h => `<span style="height:${h}%"></span>`).join('');
            div.innerHTML = `
                <div class="flex justify-end">
                    <div class="burbuja-voz bg-accent-blue rounded-2xl rounded-tr-md px-3 py-2.5 max-w-[85%] text-white shadow-lg">
                        <div class="voz-fila">
                            <button type="button" class="voz-play" aria-label="${escHtml(T.reproducir)}">
                                <svg viewBox="0 0 24 24" data-icono="play"><path d="M8 5v14l11-7z"/></svg>
                            </button>
                            <div class="voz-onda">${barras}</div>
                            <span class="voz-dur">${formatoTiempo(duracion)}</span>
                        </div>
                        <div class="voz-texto pendiente">${escHtml(T.transcribiendo)}</div>
                    </div>
                </div>`;

            const audio = new Audio(url);
            const btn = div.querySelector('.voz-play');
            const spans = [...div.querySelectorAll('.voz-onda span')];
            const icono = (play) => btn.innerHTML = play
                ? '<svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>'
                : '<svg viewBox="0 0 24 24"><path d="M6 5h4v14H6zM14 5h4v14h-4z"/></svg>';
            btn.addEventListener('click', () => { audio.paused ? audio.play() : audio.pause(); });
            audio.addEventListener('play', () => icono(false));
            audio.addEventListener('pause', () => icono(true));
            audio.addEventListener('ended', () => { icono(true); spans.forEach(s => s.classList.remove('oida')); });
            audio.addEventListener('timeupdate', () => {
                // duration de un webm grabado suele venir Infinity: se usa la medida al grabar
                const total = isFinite(audio.duration) ? audio.duration : duracion;
                const hasta = Math.floor((audio.currentTime / total) * spans.length);
                spans.forEach((s, i) => s.classList.toggle('oida', i < hasta));
            });
            return div;
        }

        async function enviarNotaDeVoz(blob, duracion, niveles) {
            const burbuja = burbujaDeVoz(URL.createObjectURL(blob), duracion, niveles);
            document.getElementById('sugerencias')?.remove();
            document.getElementById('chatMessages').appendChild(burbuja);
            scrollToBottom();
            const textoEl = burbuja.querySelector('.voz-texto');

            isLoading = true;
            let texto = null, error = null;
            try {
                const ext = (blob.type.includes('mp4') ? 'mp4' : blob.type.includes('ogg') ? 'ogg' : 'webm');
                const datos = new FormData();
                datos.append('audio', blob, 'nota.' + ext);
                const resp = await fetch('{{ route("chat.transcribir", $comercio) }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Session-ID': getOrCreateSessionId(),
                    },
                    body: datos,
                });
                const json = await resp.json().catch(() => ({}));
                if (resp.ok && json.texto) texto = json.texto;
                else error = json.error === 'no_disponible' ? T.asistente_no_disponible : T.no_te_entendi;
            } catch (e) {
                error = T.no_te_entendi;
            }
            isLoading = false;

            textoEl.classList.remove('pendiente');
            if (!texto) {
                textoEl.textContent = error;
                return;
            }
            textoEl.textContent = texto;
            sendMessage(texto, null, null, false, null, null, { sinBurbuja: true, voz: true });
        }

        (function initMicrofono() {
            const mic = document.getElementById('micButton');
            const input = document.getElementById('messageInput');
            if (!mic || !input) return;

            input.addEventListener('input', actualizarBotonesBarra);
            actualizarBotonesBarra();
            if (!vozDisponible) return;

            mic.addEventListener('contextmenu', e => e.preventDefault());

            let presionado = false;
            mic.addEventListener('pointerdown', async (e) => {
                e.preventDefault();
                presionado = true;
                if (@json(\App\Support\VistaPrevia::activa($comercio))) { avisoVistaPrevia(); return; }
                if (isLoading || grab) return;

                // Sin permiso todavía: se pide, y la próxima vez que lo mantenga, graba.
                const estado = await estadoPermisoMic();
                if (estado !== 'granted') {
                    if (estado === 'denied') mostrarAyudaMic();
                    else pedirPermisoMic();
                    return;
                }

                // Consultar el permiso es asíncrono: si ya soltó, fue un toque y no se graba
                // (si no, quedaba grabando sin nadie apretando).
                if (!presionado) { mostrarAviso(T.mantene_para_grabar, 2600); return; }
                try { mic.setPointerCapture(e.pointerId); } catch (err) {}
                empezarGrabacion(e.clientX, e.clientY);
            });

            mic.addEventListener('pointermove', (e) => {
                if (!grab || grab.trabada) return;
                const dx = e.clientX - grab.x, dy = e.clientY - grab.y;
                // El "deslizá para cancelar" se va apagando a medida que se acerca al corte
                document.getElementById('grabCancelar').style.opacity = String(Math.max(.2, 1 + dx / DESLIZAR_CANCELA));
                if (dx < -DESLIZAR_CANCELA) terminarGrabacion(false);
                else if (dy < -DESLIZAR_TRABA) trabarGrabacion();
            });

            const soltar = (enviar) => () => { presionado = false; if (grab && !grab.trabada) terminarGrabacion(enviar); };
            mic.addEventListener('pointerup', soltar(true));
            mic.addEventListener('pointercancel', soltar(false));

            document.getElementById('grabBorrar').addEventListener('click', () => terminarGrabacion(false));
            // Si cambia de app en medio de una grabación, se descarta: no se manda un audio
            // que el cliente no terminó.
            document.addEventListener('visibilitychange', () => { if (document.hidden && grab) terminarGrabacion(false); });
        })();

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
        // Cartelito para la vista previa del editor. Estilos a mano y no con clases: esta
        // pantalla lleva el tema del comercio, y el aviso tiene que leerse sobre cualquiera.
        function avisoVistaPrevia() {
            mostrarAviso('Vista previa: el asistente responde en tu menú real, no acá.', 2600);
        }

        // Cartelito flotante arriba de la barra (vista previa, pistas del micrófono).
        function mostrarAviso(texto, ms) {
            document.getElementById('avisoFlotante')?.remove();
            const aviso = document.createElement('div');
            aviso.id = 'avisoFlotante';
            aviso.textContent = texto;
            aviso.style.cssText = 'position:fixed;left:50%;bottom:90px;transform:translateX(-50%);'
                + 'z-index:9999;max-width:88%;padding:10px 16px;border-radius:16px;'
                + 'background:rgba(24,18,12,.92);color:#F2EDE3;font-size:15px;line-height:1.35;text-align:center;'
                + 'box-shadow:0 6px 20px rgba(0,0,0,.25);';
            document.body.appendChild(aviso);
            setTimeout(() => aviso.remove(), ms || 2600);
        }

        // opciones.sinBurbuja: el mensaje ya está en pantalla (la nota de voz dibuja la suya).
        // opciones.voz: se guarda para saber cuánto se usa el micrófono.
        async function sendMessage(message, tipo, scope, esReconsulta, etiqueta, chipId, opciones = {}) {
            if (isLoading) return;

            // Adentro del editor (vista previa) el chat se ve pero no manda nada: cada
            // mensaje es una llamada a la IA que se descuenta de la cuota del plan. Todas las
            // entradas al chat —escribir, un atajo, una categoría— pasan por acá.
            if (@json(\App\Support\VistaPrevia::activa($comercio))) {
                avisoVistaPrevia();
                return;
            }

            // Las sugerencias de arranque son solo para la pantalla vacía
            document.getElementById('sugerencias')?.remove();

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

            // La burbuja muestra `etiqueta` cuando el mensaje lo armó la interfaz: el texto
            // que se le manda al bot es largo y está en español, y al cliente le aparecía así.
            if (opciones.sinBurbuja) {
                window.smResena?.contarMensaje(); // lo que addMessage() hace por el resto
            } else {
                addMessage(etiqueta || message, 'user');
            }
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
                    // chip_id va aparte de `scope`: el scope acota la respuesta del modelo, y
                    // meterle un identificador ahí le ensuciaría el prompt.
                    // `etiqueta` se guarda para que el historial muestre lo mismo que vio el
                    // cliente ("Ver más opciones") y no el texto interno que va al modelo.
                    body: JSON.stringify(Object.assign(
                        tipo
                            ? { message: message, tipo: tipo, scope: scope || message, chip_id: chipId || null }
                            : { message: message },
                        etiqueta ? { etiqueta: etiqueta } : {},
                        opciones.voz ? { voz: true } : {}
                    ))
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
                <div class="chat-bubble-bot rounded-2xl rounded-tl-md px-4 py-3 shadow-lg border border-white/5 animate-slide-in-left">
                    <div class="flex items-start">
                        <div class="flex-1 min-w-0">
                            <div class="text-text-primary text-[16px] leading-normal" data-bot-text style="white-space: pre-wrap;"></div>
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
                    ? `<button type="button" onclick="verMasOpciones()" class="mt-3 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-accent-blue/40 text-accent-blue text-sm hover:bg-accent-blue/10 transition-colors">${T.ver_mas} ↓</button>`
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
                const btn = pedidosHabilitados ? `<button class="btn-lo-quiero" data-nombre="${nombre}" data-price="${precio}" style="margin-top:10px;font-size:15px">${T.lo_quiero}</button>` : '';
                return `<div class="rounded-2xl overflow-hidden border border-white/10 bg-black/25 shadow-md">
                    ${img}
                    <div class="p-3.5">
                        <div class="flex items-start justify-between gap-2">
                            {{-- flex-1 + min-w-0 para que el nombre largo baje de línea en vez de
                                 quedar cortado por el precio ("Cheesecake frutos" sin "rojos") --}}
                            <span class="font-semibold text-text-primary flex-1 min-w-0 break-words">${nombre}</span>
                            ${precio ? `<span class="text-accent-blue font-bold whitespace-nowrap flex-none">${precio}</span>` : ''}
                        </div>
                        ${desc ? `<p class="text-text-secondary text-[15px] mt-1 leading-snug">${desc}</p>` : ''}
                        ${btn}
                    </div>
                </div>`;
            }).join('');
            return `<div class="mt-3 space-y-3">${cards}</div>`;
        }

        function addMessage(text, sender, platos = [], permitirVerMas = true) {
            const chatMessages = document.getElementById('chatMessages');
            const messageDiv = armarBurbuja(text, sender, platos, permitirVerMas);

            chatMessages.appendChild(messageDiv);
            if (sender === 'user') {
                // Cuenta para el pedido de reseña: mide que haya usado el asistente de
                // verdad, no que haya entrado y rebotado.
                window.smResena?.contarMensaje();
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

        // El nodo de una burbuja, sin agregarlo. Lo usan addMessage() y el historial.
        function armarBurbuja(text, sender, platos = [], permitirVerMas = true) {
            const messageDiv = document.createElement('div');

            // Parsear Markdown para el bot
            const parsedText = sender === 'bot' ? parseMarkdown(text) : text;

            if (sender === 'user') {
                messageDiv.innerHTML = `
                    <div class="flex justify-end animate-slide-in-right">
                        <div class="bg-accent-blue rounded-2xl rounded-tr-md px-3.5 py-2.5 max-w-[80%] text-white text-[16px] leading-normal shadow-lg" style="white-space: pre-wrap;">${parsedText}</div>
                    </div>
                `;
            } else {
                messageDiv.innerHTML = `
                    <div class="chat-bubble-bot rounded-2xl rounded-tl-md px-4 py-3 shadow-lg border border-white/5 animate-slide-in-left">
                        <div class="flex items-start">
                            <div class="flex-1 min-w-0">
                                <div class="text-text-primary text-[16px] leading-normal">${parsedText}</div>
                                ${renderPlatoCards(platos)}
                                ${(permitirVerMas && platos && platos.length >= 1 && platos.length <= 6) ? `<button type="button" onclick="verMasOpciones()" class="mt-3 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-accent-blue/40 text-accent-blue text-sm hover:bg-accent-blue/10 transition-colors">${T.ver_mas} ↓</button>` : ''}
                            </div>
                        </div>
                    </div>
                `;
            }

            return messageDiv;
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
        // Precio a centavos con formato argentino: '.' = separador de miles, ',' = decimales.
        // Un valor de miles nunca termina en exactamente 2 dígitos tras el punto (agrupan de a 3),
        // así distinguimos "3.800" (=3800) de "3.50" (=3,50) sin ambigüedad.
        function parsePriceToCents(txt){
            if(!txt) return null;
            let s = String(txt).replace(/[^0-9.,]/g,'');
            if(!s) return null;
            if(s.includes(',')){
                s = s.replace(/\./g,'').replace(',', '.');   // coma decimal, puntos son miles
            } else if(!/^\d+\.\d{2}$/.test(s)){
                s = s.replace(/\./g,'');                      // puntos = miles (salvo "X.YY" decimal)
            }
            const v = parseFloat(s);
            return isNaN(v) ? null : Math.round(v*100);
        }
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
                return `<div style="display:flex; align-items:center; justify-content:space-between; gap:8px; border:1px solid rgba(255,255,255,0.08); padding:8px; border-radius:10px;"><div style=\"color:var(--text); font-size:14px;\">${it.nombre}${it.priceText?` — ${it.priceText}`:''}</div><div style=\"display:flex; align-items:center; gap:6px;\"><button class=\"wdec\" data-idx=\"${idx}\" style=\"width:28px; height:28px; border-radius:6px; background:var(--surface-2); color:var(--text); border:1px solid var(--line-2);\">-</button><span style=\"min-width:22px; text-align:center; color:var(--text-dim);\">${it.qty}</span><button class=\"winc\" data-idx=\"${idx}\" style=\"width:28px; height:28px; border-radius:6px; background:var(--surface-2); color:var(--text); border:1px solid var(--line-2);\">+</button><button class=\"wdel\" data-idx=\"${idx}\" style=\"margin-left:6px; padding:4px 8px; border-radius:6px; background:var(--surface-2); color:var(--text-dim); border:1px solid var(--line-2);\">${T.quitar}</button></div></div>`;
              }).join('')
              : `<div style="color:var(--text-dim); font-size:13px;">${T.sin_items}</div>`;
            const totalCents=w.reduce((s,it)=>s+((it.priceCents||0)*it.qty),0);
            const fmtTotal=(totalCents/100).toLocaleString("es-AR",{style:"currency",currency:"ARS",minimumFractionDigits:2,maximumFractionDigits:2});
            let totalHtml = `<div style=\"margin-top:14px; color:#fff; font-size:1.12em;\"><strong>${T.total_estimado}</strong> ${fmtTotal}</div>`;
            if(anyNoPrecio) totalHtml += `<div style=\"margin-top:2px; color:#bdbdbd; font-size:12px;\">algunos platos no tienen precio confirmado</div>`;
            list.innerHTML = htmlItems + totalHtml;
            // binds
            list.querySelectorAll('.winc').forEach(b=>b.addEventListener('click',()=>incWish(parseInt(b.getAttribute('data-idx'),10))));
            list.querySelectorAll('.wdec').forEach(b=>b.addEventListener('click',()=>decWish(parseInt(b.getAttribute('data-idx'),10))));
            list.querySelectorAll('.wdel').forEach(b=>b.addEventListener('click',()=>delWish(parseInt(b.getAttribute('data-idx'),10))));
        }

        // ===== Pedido confirmado (comanda de la mesa) =====
        //
        // El carrito de arriba es la lista privada del cliente; esto es lo que ya mandó y
        // el mozo ve en su pantalla. Son dos cosas distintas a propósito: si cada "Lo
        // quiero" fuera un pedido, el mozo vería seis platos por cada uno que la mesa
        // termina pidiendo de verdad.
        const puedePedir = @json($puedePedir ?? false);

        function pedirHeaders() {
            return {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            };
        }

        function renderPedidoHecho(items) {
            const caja = document.getElementById('pedidoHecho');
            if (!caja) return;

            if (!items || !items.length) {
                caja.hidden = true;
                caja.innerHTML = '';
                return;
            }

            const filas = items.map(it => {
                const nota = it.nota ? `<div class="pedido-hecho-nota">${it.nota}</div>` : '';
                const quien = it.del_mozo ? ` <span class="pedido-hecho-nota">(${T.lo_agrego_el_mozo})</span>` : '';
                return `<div class="pedido-hecho-item"><div>${it.nombre}${quien}${nota}</div><div>×${it.qty}</div></div>`;
            }).join('');

            caja.innerHTML = `<div class="pedido-hecho-titulo">${T.ya_pediste}</div>${filas}`;
            caja.hidden = false;
        }

        async function cargarPedidoHecho() {
            if (!puedePedir) return;
            try {
                const r = await fetch('/api/comanda/mia', {
                    method: 'POST',
                    headers: pedirHeaders(),
                    body: JSON.stringify({ comercio_id: {{ $comercio->id }}, session_id: getOrCreateSessionId() }),
                });
                const data = await r.json();
                // Sin comanda abierta (o con la mesa ya cerrada por el mozo) la caja
                // desaparece, en vez de dejar colgado un pedido que ya no existe.
                renderPedidoHecho(data.pedido || []);
            } catch (e) {
                // El pedido ya está hecho del lado del servidor: no poder mostrarlo no es
                // motivo para alarmar al cliente en medio de la comida.
            }
        }

        async function enviarPedido() {
            const btn = document.getElementById('btnPedir');
            if (!btn || btn.disabled) return;

            // Bloquear mientras el request está en vuelo: sin esto, un doble toque manda
            // dos rondas iguales y el mozo ve el pedido duplicado.
            btn.disabled = true;
            const textoOriginal = btn.textContent;
            btn.textContent = T.enviando;

            try {
                const r = await fetch('/api/comanda/pedir', {
                    method: 'POST',
                    headers: pedirHeaders(),
                    body: JSON.stringify({ comercio_id: {{ $comercio->id }}, session_id: getOrCreateSessionId() }),
                });
                const data = await r.json();

                if (!data.success) {
                    btn.textContent = textoOriginal;
                    btn.disabled = false;
                    mostrarErrorPedido(data.message || T.no_se_pudo_pedir);
                    return;
                }

                // El carrito se vació en el servidor: se limpia también el espejo local,
                // o el cliente vuelve a ver lo mismo y lo manda de nuevo.
                saveWish([]);
                renderPedidoHecho(data.pedido || []);
                btn.textContent = T.pedido_enviado;

                setTimeout(() => { btn.textContent = textoOriginal; btn.disabled = false; }, 2500);
            } catch (e) {
                btn.textContent = textoOriginal;
                btn.disabled = false;
                mostrarErrorPedido(T.no_se_pudo_pedir);
            }
        }

        /**
         * El error va dentro del panel, no en un alert del navegador.
         *
         * El alert nativo no matchea la estética (regla del proyecto) y además saca al
         * cliente de la pantalla justo cuando está tratando de pedir. Acá el mensaje
         * aparece pegado al botón que tocó.
         */
        function mostrarErrorPedido(mensaje) {
            let caja = document.getElementById('pedidoError');

            if (!caja) {
                caja = document.createElement('p');
                caja.id = 'pedidoError';
                caja.className = 'pedido-error';
                document.getElementById('btnPedir')?.insertAdjacentElement('afterend', caja);
            }

            caja.textContent = mensaje;
            caja.hidden = false;
            clearTimeout(caja.dataset.t);
            caja.dataset.t = setTimeout(() => { caja.hidden = true; }, 6000);
        }

        // Mostrar indicador de escritura
        function showTypingIndicator() {
            const chatMessages = document.getElementById('chatMessages');
            const typingDiv = document.createElement('div');
            typingDiv.id = 'typingIndicator';
            typingDiv.className = 'animate-slide-in-left';
            typingDiv.innerHTML = `
                <div class="chat-bubble-bot rounded-2xl rounded-tl-md shadow-lg border border-white/5 typing-bubble">
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

        // Abrir el resumen es el final real del recorrido: el cliente armó el pedido y se lo
        // va a mostrar al camarero. Se manda con la cantidad de ítems, y solo al ABRIR el panel
        // (togglePanel también cierra) para no contar dos veces la misma consulta.
        if (fabWish && wishPanel) fabWish.addEventListener('click', () => {
            const abriendo = openPanelEl !== wishPanel;
            togglePanel(wishPanel, renderWish);
            if (abriendo) {
                const items = loadWish().reduce((s, it) => s + (Number(it.qty) || 1), 0);
                window.smTrack?.('order_summary', { items: items });
                // Se consulta al abrir y no en un polling: lo que cambia mientras el panel
                // está cerrado no le sirve a nadie, y son clientes en un local con wifi
                // compartido.
                cargarPedidoHecho();
            }
        });
        document.getElementById('btnPedir')?.addEventListener('click', enviarPedido);
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
            if (wishClearBtn) { wishClearBtn.querySelector('.wish-btn-label').textContent = ''; wishClearBtn.classList.remove('armed'); }
        }
        wishClearBtn?.addEventListener('click', () => {
            if (!wishClearArmed) {
                wishClearArmed = true;
                wishClearBtn.querySelector('.wish-btn-label').textContent = T.vaciar_confirmar;
                wishClearBtn.classList.add('armed');
                wishClearTimer = setTimeout(resetWishClear, 3000);
                return;
            }
            resetWishClear();
            clearWish();
        });
        
        // Selector de idioma
        (function () {
            const btn = document.getElementById('langBtn');
            const menu = document.getElementById('langMenu');
            if (!btn || !menu) return;

            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const abierto = menu.classList.toggle('open');
                btn.setAttribute('aria-expanded', abierto ? 'true' : 'false');
            });
            document.addEventListener('click', () => {
                menu.classList.remove('open');
                btn.setAttribute('aria-expanded', 'false');
            });
        })();

        function askQuestion(question, tipo, scope, etiqueta, chipId) {
            sendMessage(question, tipo, scope, false, etiqueta, chipId);
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
                sendMessage('Mostrame TODAS las opciones de la categoría ' + lastCategoria + ', sin limitarte a unas pocas.', 'categoria', lastCategoria, true, T.ver_mas);
                return;
            }
            // Si fue una consulta escrita, listá solo lo que coincide con ESA consulta
            if (lastQuery) {
                sendMessage('Mostrame todas las opciones que coincidan con lo que te pregunté, sin limitarte a unas pocas.', 'ver_mas', lastQuery, true, T.ver_mas);
                return;
            }
            sendMessage('Mostrame todas las opciones que coincidan con lo que te pregunté, sin limitarte a unas pocas.', null, null, true, T.ver_mas);
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
                    t.textContent = T.lo_quiero;
                    t.classList.remove('added');
                    t.style.transform = 'scale(.95)';
                    setTimeout(() => { t.style.transform = 'scale(1)'; }, 150);
                    return;
                }

                addWish(nombre, priceText);

                // Feedback visual: pop y QUEDA en verde con "✓ Agregado"
                t.dataset.added = '1';
                t.textContent = T.agregado_ok;
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

        // La charla de esta misma visita (ChatConversation::HORAS_VISITA). Sin esto, ir a
        // la carta y volver dejaba el chat vacío, mientras el asistente se acordaba de
        // todo lo hablado. Las tarjetas vienen rearmadas contra el menú de ahora.
        const historialListo = (async function () {
            // En la vista previa del editor el chat no manda nada: tampoco trae nada.
            if (@json(\App\Support\VistaPrevia::activa($comercio))) return;
            try {
                const resp = await fetch('{{ route("chat.historial", $comercio) }}', {
                    headers: { 'Accept': 'application/json', 'X-Session-ID': getOrCreateSessionId() }
                });
                if (!resp.ok) return;
                const { mensajes } = await resp.json();
                if (!mensajes || !mensajes.length) return;

                // Las sugerencias son de la pantalla vacía, y esta ya no lo está.
                document.getElementById('sugerencias')?.remove();

                const frag = document.createDocumentFragment();
                mensajes.forEach(m => {
                    // El texto del cliente se escapa: addMessage() lo inserta tal cual.
                    // Sin "Ver más": ese botón depende de la última consulta en memoria.
                    // El audio no se guarda: la nota de voz vuelve como su texto, con el 🎤.
                    frag.appendChild(armarBurbuja((m.voz ? '🎤 ' : '') + escHtml(m.cliente), 'user'));
                    frag.appendChild(armarBurbuja(m.respuesta || '', 'bot', m.platos || [], false));
                });
                // Lo que ya está en el pedido vuelve como "✓ Agregado", igual que lo dejó el
                // cliente: con "Lo quiero" un segundo toque sumaba otra unidad sin querer.
                const enPedido = new Set(loadWish().map(x => (x.nombre || '').toLowerCase()));
                frag.querySelectorAll('.btn-lo-quiero').forEach(b => {
                    if (enPedido.has((b.dataset.nombre || '').trim().toLowerCase())) {
                        b.dataset.added = '1';
                        b.textContent = T.agregado_ok;
                        b.classList.add('added');
                    }
                });
                // Sin animación de entrada: son veinte burbujas deslizándose a la vez.
                frag.querySelectorAll('[class*="animate-"]').forEach(el => {
                    el.className = el.className.replace(/\banimate-\S+/g, '');
                });

                // Pegado a la bienvenida, no al final: si el cliente llegó a escribir
                // antes de que esto volviera, su mensaje nuevo queda abajo, donde va.
                document.getElementById('chatMessages').firstElementChild.after(frag);
                scrollToBottom();
            } catch (e) {}
        })();

        // Pregunta pre-cargada desde la pantalla de bienvenida (?q=...)
        (function () {
            try {
                const params = new URLSearchParams(window.location.search);
                const preQ = params.get('q');
                const preChip = params.get('chip');
                if (preQ && preQ.trim()) {
                    // Limpiar el query para que no se reenvíe si el usuario refresca
                    history.replaceState(null, '', window.location.pathname);
                    // tipo 'chip_portada': la pregunta la armó el atajo de la portada, no la
                    // tipeó nadie. Sin esto se guardaba como escrita y "¿Qué me recomendás?"
                    // era la palabra más buscada del comercio. No cambia cómo responde el
                    // modelo. Va separado de 'chip_chat' porque son dos momentos distintos y
                    // hay que poder compararlos.
                    // Después del historial, para que la pregunta nueva quede al final.
                    historialListo.then(() => setTimeout(() => sendMessage(preQ.trim(), 'chip_portada', preQ.trim(), false, null, preChip), 400));
                }
            } catch (e) {}
        })();

        // Event listeners
        document.getElementById('sendButton').addEventListener('click', function() {
            // Con la grabación trabada, este botón manda el audio.
            if (grab && grab.trabada) { terminarGrabacion(true); return; }
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
            if (message) {
                sendMessage(message);
                messageInput.value = '';
                actualizarBotonesBarra();
            }
        });

        document.getElementById('messageInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const message = this.value.trim();
                if (message) {
                    sendMessage(message);
                    this.value = '';
                    actualizarBotonesBarra();
                }
            }
        });

        // Auto-focus SOLO en desktop. En mobile abría el teclado apenas se escanea el QR
        // y tapaba media pantalla antes de que el cliente leyera la bienvenida.
        // (hover:hover + pointer:fine) = puntero de mouse, no dedo.
        // Tampoco adentro de un marco (el celular de la vista previa del editor): el foco
        // salta del documento de afuera al de adentro, y al comerciante se le iba el cursor
        // del campo que estaba editando cada vez que el celular cargaba el chat.
        if (window.self === window.top && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
            document.getElementById('messageInput').focus();
        }
    </script>

    @include('menu.partials.tracking', ['trackView' => 'chat'])

    {{-- Va DESPUÉS del tracking: el bloque usa window.smTrack para registrar que se mostró. --}}
    {{-- En vista previa se incluye aunque esté apagado o sin link: el comercio está
         escribiendo esos textos y tiene que poder verlos. --}}
    @if($comercio->pideResena() || \App\Support\VistaPrevia::activa($comercio))
        @include('menu.partials.resena')
    @endif
</body>
</html>
