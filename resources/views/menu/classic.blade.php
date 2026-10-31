<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Lo pide el buscador de la carta, que postea al servidor. --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
    
    @php
        // Los mismos colores que la pantalla de elección: salen de la foto que subió el
        // comercio (BrandThemeService), no de una paleta elegida por nosotros. Si no hay
        // portada procesada se cae a la escala neutra cálida de siempre, así ningún
        // comercio sin foto se rompe.
        $conPortada = $comercio->tienePortada();
        $sup = $conPortada ? ($comercio->tema_derivado['superficies'] ?? []) : [];
        $velo = $conPortada ? ($comercio->tema_derivado['velo'] ?? []) : [];
        $baseTema = $conPortada ? ($comercio->tema_derivado['base'] ?? null) : null;

        // Las superficies del tema son SEMITRANSPARENTES: están pensadas para ir sobre la
        // portada desenfocada. Sin ese fondo detrás quedarían casi indistinguibles del
        // color base, así que si se usa el tema hay que poner también el fondo.
        $fondoDesenfocado = $conPortada && filled($comercio->portada_fondo_url) && $velo
            ? asset('storage/'.$comercio->portada_fondo_url)
            : null;
    @endphp

    <!-- Estilos personalizados del comercio -->
    <style>
        /* Misma paleta que el chat (menu/chat.blade.php): escala neutra cálida fija
           + UN acento, el color de marca del comercio. Antes esta vista usaba los
           4 colores configurables y quedaba con otra estética que el chat.

           Con portada procesada, el fondo y las superficies se reemplazan por los
           derivados de la foto, para que la carta se vea como la pantalla anterior. */
        :root {
            --bg:        {{ $baseTema ?? '#0F0D0C' }};
            --surface:   {{ $sup['fondo'] ?? '#1A1614' }};
            --surface-2: {{ $sup['hover'] ?? '#241F1B' }};
            --line:      {{ $sup['borde'] ?? 'rgba(255,255,255,.07)' }};
            --line-2:    {{ $sup['borde_arriba'] ?? 'rgba(255,255,255,.12)' }};
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

        /* La carta en texto. Arranca arriba y no centrada: con muchas categorías,
           centrar verticalmente deja la primera fuera de pantalla. */
        .menu-content:has(.lista-categoria) { align-items: flex-start; }

        @if($fondoDesenfocado)
            /* El mismo fondo que la pantalla de elección: la portada desenfocada bajo el
               velo derivado de su propio promedio. Va fijo y detrás de todo. */
            .fondo-portada {
                position: fixed; inset: 0; z-index: 0;
                background: url('{{ $fondoDesenfocado }}') center/cover no-repeat;
            }
            .fondo-portada::after {
                content: ''; position: absolute; inset: 0;
                background:
                    radial-gradient(88% 52% at 50% 26%, {{ $velo['centro'] }} 0%, {{ $velo['halo'] }} 100%),
                    linear-gradient(180deg, {{ $velo['arriba'] }} 0%, {{ $velo['medio'] }} 45%, {{ $velo['abajo'] }} 100%);
            }
            /* Todo el contenido por encima del fondo. */
            .menu-container { position: relative; z-index: 1; }
            .menu-content { background: transparent; }
        @endif

        .cambiar-vista {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 9px 18px; border-radius: 9999px; text-decoration: none;
            background: var(--surface-2); border: 1px solid var(--line-2);
            color: var(--text-dim); font-size: 14px; font-weight: 600;
        }
        .cambiar-vista:hover { color: var(--text); border-color: var(--accent); }

        .lista-categoria { margin-bottom: 30px; }
        .lista-categoria-titulo {
            font-size: 13px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase;
            color: var(--accent); margin: 0 0 10px; padding-bottom: 8px;
            border-bottom: 1px solid var(--line-2);
        }
        .lista-plato {
            display: flex; align-items: flex-start; gap: 14px;
            padding: 14px 0; border-bottom: 1px solid var(--line);
            cursor: pointer; /* toda la fila abre el plato, no sólo la foto */
        }
        .lista-plato:last-child { border-bottom: 0; }
        .lista-plato-texto { flex: 1; min-width: 0; }
        /* Nombre y precio en el mismo renglón, como en las apps de delivery: el ojo
           baja por una sola columna de precios en vez de ir a buscarlos entre el texto. */
        .lista-plato-fila { display: flex; align-items: baseline; gap: 12px; }
        .lista-plato-nombre { margin: 0; flex: 1; font-size: 16px; font-weight: 600; color: var(--text); line-height: 1.3; }
        .lista-plato-estrella { color: var(--accent); font-size: 13px; }
        .lista-plato-desc {
            margin: 4px 0 0; font-size: 13px; color: var(--text-dim); line-height: 1.45;
            /* Dos renglones y corta: una descripción larga empujaba el precio del plato
               siguiente y la lista perdía el ritmo. El texto completo está en el detalle. */
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        .lista-plato-precio {
            margin: 0; font-size: 15px; font-weight: 700;
            color: var(--accent-soft); white-space: nowrap;
        }

        /* ── Buscador de la carta ──
           Se presenta como BUSCADOR y no como un asistente a propósito: cuando un chat no
           entiende, se siente roto; cuando un buscador no encuentra, es normal y el
           cliente prueba otra palabra. Es el mismo motor de búsqueda atrás. */
        /* `min-width: 0` no sobra: sin eso, la fila de atajos —que scrollea sola— le fija
           al contenedor el ancho de TODAS las categorías juntas, y la página entera se iba
           al costado con los precios cortados.
           Y va pegado arriba: con 150 platos, volver a buscar significaba subir hasta el
           principio de la carta. */
        .buscador-wrap {
            width: 100%; min-width: 0; max-width: 768px; margin: 0 auto 18px;
            position: sticky; top: 0; z-index: 20;
            padding: 10px 0 4px;
            background: color-mix(in srgb, var(--bg) 88%, transparent);
            backdrop-filter: blur(12px);
        }
        .buscador {
            display: flex; align-items: center; gap: 10px;
            background: var(--surface); border: 1px solid var(--line-2);
            border-radius: 14px; padding: 12px 14px;
        }
        .buscador svg { flex: none; color: var(--text-dim); }
        .buscador input {
            flex: 1; background: none; border: 0; outline: none;
            color: var(--text); font-size: 16px; /* 16px o iOS hace zoom al tocar */
        }
        .buscador input::placeholder { color: var(--text-dim); }
        .buscador-borrar {
            flex: none; background: none; border: 0; cursor: pointer;
            color: var(--text-dim); font-size: 20px; line-height: 1; padding: 0 2px;
        }
        /* Los atajos van DEBAJO del buscador y no adentro de un menú: son la forma
           principal de moverse cuando no hay asistente, y lo que se esconde no existe. */
        .chips-cat { display: flex; gap: 8px; overflow-x: auto; padding: 12px 2px 2px; scrollbar-width: none; }
        .chips-cat::-webkit-scrollbar { display: none; }
        .chip-cat {
            flex: none; cursor: pointer; white-space: nowrap;
            background: var(--surface); border: 1px solid var(--line);
            color: var(--text-dim); border-radius: 999px;
            padding: 7px 14px; font-size: 13px;
        }
        /* Las promos se marcan con el BORDE y no con el relleno, como en el chat: acá el
           relleno de acento es el atajo elegido, y un relleno fijo parecería "seleccionado".
           Va antes de .activo para que, elegida, se vea como cualquier otra. */
        .chip-cat.promo { border-color: var(--accent); color: var(--text); font-weight: 600; }
        .chip-cat.activo { background: var(--accent); border-color: var(--accent); color: var(--bg); font-weight: 600; }
        .buscador-estado { margin: 12px 2px 0; font-size: 13px; color: var(--text-dim); }

        /* ── "Lo quiero" y "Mi pedido" ── */
        .lista-plato-lado { flex: none; position: relative; display: flex; flex-direction: column; align-items: flex-end; }
        /* Con foto, el botón se apoya en la esquina de la foto, como en las apps de pedidos. */
        .lista-plato-lado.con-foto .btn-quiero { position: absolute; right: -6px; bottom: -6px; }
        .btn-quiero {
            width: 36px; height: 36px; border-radius: 999px; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            background: var(--surface); color: var(--accent-soft);
            border: 1px solid color-mix(in srgb, var(--accent) 50%, transparent);
            font: inherit; font-size: 20px; font-weight: 600; line-height: 1;
            box-shadow: 0 2px 10px rgba(0,0,0,.25);
        }
        /* Ya está en el pedido: el botón muestra cuántos, en el color del local. */
        .btn-quiero.en-pedido { background: var(--accent); border-color: var(--accent); color: #fff; font-size: 15px; }
        .plato-modal-quiero {
            margin-top: 16px; width: 100%; cursor: pointer; border: 0; border-radius: 12px;
            padding: 13px; font: inherit; font-size: 15px; font-weight: 700;
            background: var(--accent); color: #fff;
        }
        .fab-pedido {
            position: fixed; left: 50%; bottom: 18px; transform: translateX(-50%); z-index: 60;
            display: inline-flex; align-items: center; gap: 10px; cursor: pointer;
            background: var(--accent); color: #fff; border: 0; border-radius: 999px;
            padding: 13px 22px; font: inherit; font-size: 15px; font-weight: 700;
            box-shadow: 0 8px 28px rgba(0,0,0,.4);
        }
        .fab-pedido-cant {
            min-width: 24px; height: 24px; padding: 0 7px; border-radius: 999px;
            background: rgba(255,255,255,.22); display: inline-flex; align-items: center; justify-content: center;
            font-size: 13px;
        }
        .fab-pedido.late { animation: late .35s ease; }
        @keyframes late { 50% { transform: translateX(-50%) scale(1.08); } }

        /* El pedido sube desde abajo, como una hoja: se cierra y la carta sigue ahí. */
        .mi-pedido {
            width: 100%; max-width: 560px; margin: auto auto 0; padding: 20px 20px 26px;
            border: 1px solid var(--line-2); border-bottom: 0; border-radius: 20px 20px 0 0;
            /* La superficie del tema puede ser translúcida (sale de la foto del local):
               sobre el fondo sólido, para que la carta de atrás no se lea a través. */
            background-color: var(--bg);
            background-image: linear-gradient(var(--surface), var(--surface));
            color: var(--text); max-height: 86vh;
        }
        /* El diálogo enfoca la X al abrirse: el recuadro sólo con teclado, no al tocar. */
        .mi-pedido-cerrar:focus { outline: none; }
        .mi-pedido-cerrar:focus-visible { outline: 2px solid var(--accent); border-radius: 6px; }
        .mi-pedido::backdrop { background: rgba(0,0,0,.6); }
        .mi-pedido-cabeza { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
        .mi-pedido-cabeza h3 { margin: 0; font-size: 19px; font-weight: 700; }
        .mi-pedido-cerrar { background: none; border: 0; color: var(--text-dim); font-size: 26px; cursor: pointer; line-height: 1; }
        .mi-pedido-fila { display: flex; align-items: center; gap: 10px; padding: 11px 0; border-bottom: 1px solid var(--line); }
        .mi-pedido-fila-nombre { flex: 1; min-width: 0; font-size: 14.5px; }
        .mi-pedido-fila-precio { font-size: 12.5px; color: var(--text-dim); }
        .mi-pedido-cant { display: flex; align-items: center; gap: 6px; }
        .mi-pedido-cant button {
            width: 30px; height: 30px; border-radius: 9px; cursor: pointer;
            background: var(--surface-2); color: var(--text); border: 1px solid var(--line-2); font: inherit; font-size: 16px;
        }
        .mi-pedido-cant span { min-width: 20px; text-align: center; font-variant-numeric: tabular-nums; }
        .mi-pedido-vacio { color: var(--text-dim); font-size: 14px; padding: 12px 0; }
        .mi-pedido-total { display: flex; justify-content: space-between; margin-top: 16px; font-size: 16px; }
        .mi-pedido-total strong { font-variant-numeric: tabular-nums; }
        .mi-pedido-nota { margin: 4px 0 0; font-size: 12px; color: var(--text-dim); }
        .mi-pedido-ayuda { margin: 12px 0 0; font-size: 13px; color: var(--text-dim); text-align: center; }
        /* Igual que .pedir-aviso del chat: es la instrucción, no letra chica. */
        .mi-pedido-aviso {
            margin: 14px 0 0; padding: 14px 16px; border-radius: 12px;
            background: color-mix(in srgb, var(--accent) 14%, var(--surface-2));
            border: 1px solid color-mix(in srgb, var(--accent) 45%, transparent);
            font-size: 17px; font-weight: 600; line-height: 1.35; color: var(--text); text-align: center;
        }
        .mi-pedido-error { margin: 10px 0 0; font-size: 13px; color: #ff9b8a; text-align: center; }
        .btn-pedir {
            margin-top: 18px; width: 100%; cursor: pointer; border: 0; border-radius: 12px;
            padding: 14px; font: inherit; font-size: 16px; font-weight: 700; background: var(--accent); color: #fff;
        }
        .btn-pedir:disabled { opacity: .6; }
        .mi-pedido-vaciar {
            display: block; margin: 16px auto 0; background: none; border: 0; cursor: pointer;
            color: var(--text-dim); font: inherit; font-size: 13px; text-decoration: underline;
        }
        .ya-pedido { margin-bottom: 14px; padding: 12px 14px; border-radius: 12px; background: var(--surface-2); }
        .ya-pedido-titulo { font-size: 12px; letter-spacing: .06em; text-transform: uppercase; color: var(--text-dim); margin-bottom: 6px; }
        .ya-pedido-fila { display: flex; justify-content: space-between; font-size: 13.5px; padding: 3px 0; }

        /* La foto: cuadrada, a la derecha y con el fondo puesto de antemano, así el
           lazy-load no hace saltar la fila cuando termina de cargar. */
        .lista-plato-foto {
            width: 84px; height: 84px; flex: none;
            object-fit: cover; border-radius: 12px;
            background: var(--surface-2);
            border: 1px solid var(--line-2);
        }
        @media (max-width: 380px) {
            .lista-plato-foto { width: 68px; height: 68px; }
        }

        /* La foto es un botón, pero no tiene que parecerlo: sin fondo ni padding, sólo
           el gesto (se agranda un poco al tocarla). */
        .lista-plato-foto-btn {
            padding: 0; border: 0; background: none; cursor: zoom-in; flex: none;
            border-radius: 12px; line-height: 0;
            transition: transform .15s ease;
        }
        .lista-plato-foto-btn:active { transform: scale(.96); }
        .lista-plato-foto-btn:focus-visible { outline: 2px solid var(--accent); outline-offset: 3px; }

        /* El plato en grande */
        .plato-modal {
            border: 0; padding: 0; background: transparent;
            max-width: 460px; width: 92vw;
            color: var(--text);
            overflow: visible;
        }
        .plato-modal::backdrop { background: rgba(0,0,0,.82); backdrop-filter: blur(3px); }
        .plato-modal img {
            width: 100%; aspect-ratio: 1 / 1; object-fit: cover;
            border-radius: 18px 18px 0 0; display: block; background: var(--surface-2);
        }
        /* Sin esto, el `display: block` de arriba le gana al atributo `hidden` y el plato
           sin foto (o con la foto rota) mostraba el ícono de imagen partida. */
        .plato-modal img[hidden] { display: none; }
        .plato-modal img[hidden] + .plato-modal-texto { border-top: 1px solid var(--line-2); border-radius: 18px; }
        .plato-modal-texto {
            background: var(--surface); border: 1px solid var(--line-2); border-top: 0;
            border-radius: 0 0 18px 18px; padding: 18px 20px 22px;
        }
        .plato-modal-texto h3 { margin: 0; font-size: 21px; font-weight: 700; line-height: 1.25; }
        .plato-modal-texto p { margin: 8px 0 0; font-size: 14px; color: var(--text-dim); line-height: 1.5; }
        .plato-modal-texto #platoModalPrecio {
            margin-top: 12px; font-size: 20px; font-weight: 800; color: var(--accent-soft);
        }
        /* Fuera de la tarjeta y sobre el fondo oscuro: no tapa la foto y el pulgar
           llega sin cruzar la pantalla. */
        .plato-modal-cerrar {
            position: absolute; top: -14px; right: -6px; z-index: 2;
            width: 40px; height: 40px; border-radius: 9999px; cursor: pointer;
            background: var(--surface); border: 1px solid var(--line-2);
            color: var(--text); font-size: 22px; line-height: 1;
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

        /* Carta en varias fotos: cada página a todo el ancho y se recorre con scroll. Con
           el tope de alto de arriba, en el celular cada página quedaba chiquita. */
        .menu-image.pagina {
            width: 100%;
            max-height: none;
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
    @if($fondoDesenfocado)
        <div class="fondo-portada"></div>
    @endif

    <div class="menu-container">
        {{-- Botón flotante IA. Sólo si el comercio TIENE el asistente: con la IA apagada
             el chat igual contesta con el buscador, pero llamarlo "Explorar con IA" sería
             mentirle al cliente. --}}
        @if($comercio->tieneAsistente())
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
        @endif

        {{-- Con las dos formas disponibles, el cliente puede pasar de una a la otra. El
             comercio elige con cuál abre; esto es para el que quiere ver la otra. --}}
        {{-- Sólo si hay archivo: sin él, la lista es la única forma y no hay nada que alternar. --}}
        @if($archivoDisponible ?? false)
            <div class="w-full max-w-6xl mx-auto mb-4 text-center">
                @if($vista === \App\Models\Comercio::VISTA_LISTA)
                    <a href="{{ route('menu.classic', $comercio) }}?vista=archivo" class="cambiar-vista">
                        {{ __('Ver el menú original') }}
                    </a>
                @else
                    <a href="{{ route('menu.classic', $comercio) }}?vista=lista" class="cambiar-vista">
                        {{ __('Ver como lista') }}
                    </a>
                @endif
            </div>
        @endif

        <!-- Contenido del menú -->
        <div class="menu-content">
            {{-- `$archivoDisponible` comprueba que el archivo EXISTA, no solo que la base
                 tenga la ruta: si falta, el iframe le mostraba al cliente un 403 o la
                 pantalla de login adentro del menú. Ver PublicMenuController::classic(). --}}
            @if($vista === \App\Models\Comercio::VISTA_LISTA && $platosPorCategoria->isNotEmpty())
                {{-- La carta en texto: se lee en el celular sin hacer zoom, que es lo que
                     no se puede hacer con un PDF diseñado para una hoja A4. --}}
                {{-- Buscar y filtrar. La coincidencia la resuelve el servidor con la misma
                     búsqueda que usa el asistente (sinónimos, plurales, errores de tipeo);
                     acá sólo se esconde lo que no coincide, porque la carta entera ya está
                     dibujada. Sin JavaScript, la lista se ve igual: esto sólo filtra. --}}
                <div class="w-full min-w-0 max-w-3xl mx-auto animate-fade-in">
                <div class="buscador-wrap">
                    <div class="buscador">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <circle cx="11" cy="11" r="7"/><path d="M20 20l-4.3-4.3"/>
                        </svg>
                        <input type="search" id="buscadorCarta" autocomplete="off"
                               placeholder="{{ __('Buscar en la carta') }}"
                               aria-label="{{ __('Buscar en la carta') }}">
                        <button type="button" class="buscador-borrar" id="buscadorBorrar" hidden
                                aria-label="{{ __('Borrar') }}">&times;</button>
                    </div>

                    <div class="chips-cat" id="chipsCategorias">
                        <button type="button" class="chip-cat activo" data-cat="">{{ __('Todo') }}</button>
                        @foreach($platosPorCategoria as $categoria => $platos)
                            @if($categoria === $categoriaPromos)
                                <button type="button" class="chip-cat promo" data-cat="{{ $categoria }}">🔥 {{ $categoria }}</button>
                            @else
                                <button type="button" class="chip-cat" data-cat="{{ $categoria }}">{{ $categoria }}</button>
                            @endif
                        @endforeach
                    </div>

                    <p class="buscador-estado" id="buscadorEstado" hidden></p>
                </div>

                    @foreach($platosPorCategoria as $categoria => $platos)
                        <section class="lista-categoria" data-categoria="{{ $categoria }}">
                            <h2 class="lista-categoria-titulo">@if($categoria === $categoriaPromos)🔥 @endif{{ $categoria }}</h2>

                            {{-- La foto va a la DERECHA y el precio debajo del nombre, como en
                                 las apps de delivery. Con la foto a la izquierda, los pocos
                                 platos que tienen imagen (acá 5 de 151) desalineaban la columna
                                 de texto de todos los demás y la lista quedaba en zigzag. Así,
                                 el que no tiene foto simplemente ocupa todo el ancho. --}}
                            @foreach($platos as $plato)
                                {{-- Toda la fila abre el detalle, no sólo la foto: en un celular el
                                     pulgar cae en cualquier parte del renglón, y la mayoría de los
                                     platos ni siquiera tiene foto que tocar. --}}
                                <article class="lista-plato" onclick="abrirPlato(this)"
                                         data-nombre="{{ $plato['nombre'] }}"
                                         data-desc="{{ $plato['descripcion'] ?? '' }}"
                                         data-precio="{{ $plato['precio'] ?? '' }}"
                                         data-img="{{ $plato['imagen'] ?? '' }}">
                                    <div class="lista-plato-texto">
                                        <div class="lista-plato-fila">
                                            <h3 class="lista-plato-nombre">
                                                {{ $plato['nombre'] }}
                                                @if(!empty($plato['estrella']))
                                                    <span class="lista-plato-estrella" title="{{ __('Recomendado') }}">★</span>
                                                @endif
                                            </h3>
                                            @if(!empty($plato['precio']))
                                                <p class="lista-plato-precio">{{ $plato['precio'] }}</p>
                                            @endif
                                        </div>

                                        @if(!empty($plato['descripcion']))
                                            <p class="lista-plato-desc">{{ $plato['descripcion'] }}</p>
                                        @endif

                                    </div>

                                    <div class="lista-plato-lado {{ !empty($plato['imagen']) ? 'con-foto' : '' }}">
                                        @if(!empty($plato['imagen']))
                                            {{-- onerror: una foto rota se ve peor que ninguna foto.
                                                 Si el archivo no está, la fila queda como las demás. --}}
                                            <img src="{{ $plato['imagen'] }}" alt="{{ $plato['nombre'] }}"
                                                 loading="lazy" decoding="async" class="lista-plato-foto"
                                                 onerror="this.remove(); this.parentElement?.classList.remove('con-foto')">
                                        @endif

                                        {{-- "Lo quiero": suma a la lista privada del cliente. No le llega
                                             a nadie: para eso está "Pedir", dentro de "Mi pedido". --}}
                                        @if($aceptaLista)
                                            <button type="button" class="btn-quiero"
                                                    onclick="event.stopPropagation(); quiero(this.closest('.lista-plato'))"
                                                    aria-label="{{ __('Lo quiero') }}: {{ $plato['nombre'] }}">
                                                <span class="btn-quiero-mas">+</span>
                                                <span class="btn-quiero-cant" hidden></span>
                                            </button>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </section>
                    @endforeach
                </div>
            @elseif($archivoDisponible ?? false)
                <div class="w-full max-w-6xl animate-fade-in">
                    @if($menuActivo->formato === 'pdf')
                        <iframe
                            src="{{ asset('storage/' . $menuActivo->archivo_original) }}#toolbar=0"
                            class="menu-pdf"
                            title="Menú PDF">
                        </iframe>
                    @elseif($menuActivo->tieneVariasFotos())
                        {{-- La carta llegó en varias fotos: todas, en el orden en que el
                             comercio las subió, una debajo de la otra. --}}
                        <div class="space-y-4">
                            @foreach($menuActivo->archivosEnOrden() as $i => $foto)
                                <img
                                    src="{{ asset('storage/' . $foto) }}"
                                    alt="Menú {{ $comercio->nombre }} — página {{ $i + 1 }}"
                                    @if($i > 0) loading="lazy" @endif
                                    class="menu-image pagina mx-auto">
                            @endforeach
                        </div>
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

    {{-- El plato en grande, aislado. <dialog> nativo y no una librería: es una pantalla
         que abre un cliente en el celular, en el wifi del local, y no vale la pena bajar
         un fancybox para esto. Se cierra con la X, tocando el fondo o con Escape. --}}
    <dialog id="platoModal" class="plato-modal">
        <button type="button" class="plato-modal-cerrar" onclick="document.getElementById('platoModal').close()"
                aria-label="{{ __('Cerrar') }}">×</button>
        {{-- onerror: una foto rota se ve peor que ninguna foto, y en el detalle ocupa media
             pantalla. Mismo criterio que en la lista. --}}
        <img id="platoModalImg" src="" alt="" onerror="this.hidden = true">
        <div class="plato-modal-texto">
            <h3 id="platoModalNombre"></h3>
            <p id="platoModalDesc"></p>
            <p id="platoModalPrecio"></p>

            @if($aceptaLista)
                <button type="button" id="platoModalQuiero" class="plato-modal-quiero">{{ __('Lo quiero') }}</button>
            @endif

        </div>
    </dialog>

    @if($aceptaLista)
        {{-- "Mi pedido": aparece recién cuando hay algo. Antes de eso sería un botón que
             no hace nada, tapando la carta. --}}
        <button type="button" id="fabPedido" class="fab-pedido" hidden onclick="abrirMiPedido()">
            {{ __('Mi pedido') }} <span id="fabPedidoCant" class="fab-pedido-cant">0</span>
        </button>

        <dialog id="miPedido" class="mi-pedido">
            <div class="mi-pedido-cabeza">
                <h3>{{ __('Mi pedido') }}</h3>
                <button type="button" class="mi-pedido-cerrar" onclick="document.getElementById('miPedido').close()"
                        aria-label="{{ __('Cerrar') }}">×</button>
            </div>

            @if($puedePedir)
                {{-- Lo que la mesa ya mandó y el mozo ve en su pantalla. --}}
                <div id="yaPedido" class="ya-pedido" hidden></div>
            @endif

            <div id="miPedidoLista" class="mi-pedido-lista"></div>

            <div class="mi-pedido-total">
                <span>{{ __('Total estimado') }}</span>
                <strong id="miPedidoTotal"></strong>
            </div>
            <p id="miPedidoSinPrecio" class="mi-pedido-nota" hidden>{{ __('Algunos platos no tienen precio en la carta.') }}</p>

            {{-- Qué hace el cliente con la lista depende de si el local recibe pedidos en la
                 tablet y de si entró por el QR de una mesa. Se le dice siempre, para que
                 nunca quede con una lista sin saber para qué sirve. --}}
            @if($puedePedir)
                <button type="button" id="btnPedir" class="btn-pedir">{{ __('Pedir') }}</button>
                <p class="mi-pedido-ayuda">{{ __('El mozo lo ve al toque en su pantalla.') }}</p>
                <p id="pedidoError" class="mi-pedido-error" hidden></p>
            @else
                <p class="mi-pedido-aviso">{{ __('Cuando venga el camarer@, mostrale esta pantalla.') }}</p>
            @endif

            <button type="button" id="miPedidoVaciar" class="mi-pedido-vaciar">{{ __('Vaciar mi pedido') }}</button>
        </dialog>
    @endif

    {{-- Textos del pedido, armados acá porque un @json() de varias líneas no compila. --}}
    @php
        $txtPedido = [
            'vacio' => __('Todavía no elegiste nada. Tocá el + de un plato para sumarlo.'),
            'quitar' => __('Quitar'),
            'enviando' => __('Enviando…'),
            'enviado' => __('¡Listo! Ya lo tiene el mozo'),
            'no_se_pudo' => __('No se pudo mandar el pedido. Probá de nuevo.'),
            'ya_pediste' => __('Ya pediste'),
        ];
    @endphp
    <script>
        // ── Buscador de la carta ──
        //
        // Quién decide qué coincide es el SERVIDOR (/api/buscar), que usa la misma búsqueda
        // que el asistente: sinónimos, plurales, errores de tipeo y el arranque de una
        // palabra. Acá sólo se esconde lo que no vino en la respuesta. Escribir esa lógica
        // otra vez en JavaScript sería tener dos búsquedas que se separan con el primer
        // arreglo.
        (function () {
            const input = document.getElementById('buscadorCarta');
            if (!input) return;   // vista archivo: no hay lista que filtrar

            const borrar = document.getElementById('buscadorBorrar');
            const estado = document.getElementById('buscadorEstado');
            const chips = document.querySelectorAll('.chip-cat');
            const secciones = document.querySelectorAll('.lista-categoria');
            const platos = document.querySelectorAll('.lista-plato');

            const mostrarTodo = () => {
                platos.forEach(p => p.style.display = '');
                secciones.forEach(s => s.style.display = '');
            };

            // Filtrar por categoría es instantáneo y no necesita al servidor: la carta
            // entera ya está en la página.
            const filtrarCategoria = (cat) => {
                input.value = '';
                borrar.hidden = true;
                estado.hidden = true;
                mostrarTodo();
                if (!cat) return;
                secciones.forEach(s => s.style.display = s.dataset.categoria === cat ? '' : 'none');
            };

            chips.forEach(chip => chip.addEventListener('click', () => {
                chips.forEach(c => c.classList.toggle('activo', c === chip));
                filtrarCategoria(chip.dataset.cat);
                // El de promos cuenta como apertura de promos y no como una categoría más,
                // igual que en el chat: si no, el panel decía que nadie las abría nunca.
                // "Todo" no se registra: es volver a la carta entera, no una sección, y
                // en el ranking de categorías figuraba como si lo fuera.
                if (chip.classList.contains('promo')) {
                    window.smTrack?.('promo_click', { promo: chip.dataset.cat, vista: 'classic' });
                } else if (chip.dataset.cat) {
                    window.smTrack?.('categoria_tap', { nombre: chip.dataset.cat, vista: 'classic' });
                }
            }));

            const aplicar = (nombres, texto, encontro) => {
                const buscados = new Set(nombres);
                platos.forEach(p => p.style.display = buscados.has(p.dataset.nombre) ? '' : 'none');

                // Una categoría sin ningún plato visible sobra: si no, quedan títulos
                // sueltos separados por aire y parece que la página se rompió.
                secciones.forEach(s => {
                    const visibles = s.querySelectorAll('.lista-plato:not([style*="none"])').length;
                    s.style.display = visibles ? '' : 'none';
                });

                // Encontrado, la lista filtrada habla sola: el aviso es sólo para "no hay".
                estado.textContent = texto;
                estado.hidden = !texto;

                if (!encontro) {
                    // Sin resultados NO se deja la pantalla vacía: se muestra la carta
                    // entera abajo del aviso, que es lo que el cliente vino a ver.
                    mostrarTodo();
                }
            };

            let timer = null;
            let ultima = '';

            input.addEventListener('input', () => {
                const q = input.value.trim();
                borrar.hidden = q === '';
                clearTimeout(timer);

                if (q.length < 2) {
                    estado.hidden = true;
                    chips.forEach(c => c.classList.toggle('activo', c.dataset.cat === ''));
                    mostrarTodo();
                    return;
                }

                // Se espera a que deje de tipear: sin esto se manda un pedido por letra.
                timer = setTimeout(async () => {
                    if (q === ultima) return;
                    ultima = q;

                    try {
                        const r = await fetch('{{ route('menu.buscar', $comercio) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            },
                            body: JSON.stringify({ q, session_id: window.smSessionId?.() || null }),
                        });
                        if (!r.ok) return;
                        const data = await r.json();
                        aplicar(data.platos || [], data.texto || '', data.encontro);
                    } catch (e) {
                        // Que falle la red no puede dejar la carta escondida.
                        mostrarTodo();
                        estado.hidden = true;
                    }
                }, 350);
            });

            borrar.addEventListener('click', () => {
                input.value = '';
                borrar.hidden = true;
                estado.hidden = true;
                ultima = '';
                chips.forEach(c => c.classList.toggle('activo', c.dataset.cat === ''));
                mostrarTodo();
                input.focus();
            });
        })();

        // ── "Lo quiero" y "Mi pedido" ──
        //
        // La lista vive en el teléfono (localStorage) y se copia al servidor con el mismo
        // /api/cart que usa el chat: el botón "Pedir" le manda al mozo lo que está en el
        // servidor, y el panel de analítica cuenta desde ahí. La clave es la MISMA que usa
        // el chat, así el que pasa del chat a la carta no pierde lo que eligió.
        const LISTA_ACTIVA = @json($aceptaLista ?? false);
        const PUEDE_PEDIR = @json($puedePedir ?? false);
        const COMERCIO_ID = {{ $comercio->id }};
        const TXT = @json($txtPedido);

        function sesionCliente() {
            // El partial de tracking expone el id del visitante. En la vista previa del
            // editor ese partial no corre: ahí se lee la misma clave a mano.
            if (window.smSessionId) return window.smSessionId();
            const k = 'servime_chat_session_' + COMERCIO_ID;
            let s = null;
            try { s = localStorage.getItem(k); } catch (e) {}
            if (!s) {
                s = 'session_' + Date.now() + '_' + Math.random().toString(36).slice(2, 11);
                try { localStorage.setItem(k, s); } catch (e) {}
            }
            return s;
        }

        const claveLista = () => 'servime_wish_' + COMERCIO_ID + '_' + sesionCliente();
        const leerLista = () => { try { return JSON.parse(localStorage.getItem(claveLista())) || []; } catch (e) { return []; } };

        function guardarLista(lista) {
            try { localStorage.setItem(claveLista(), JSON.stringify(lista)); } catch (e) {}
            pintarLista();
        }

        function alServidor(ruta, datos) {
            fetch('/api/cart/' + ruta, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify(Object.assign({ comercio_id: COMERCIO_ID, session_id: sesionCliente() }, datos)),
            }).catch(() => {});
        }

        // Precio con formato argentino a centavos: el punto separa miles y la coma decimales.
        // "3.800" son 3800 y "3.50" son 3,50: un valor de miles nunca deja 2 dígitos tras el punto.
        function aCentavos(txt) {
            let s = String(txt || '').replace(/[^0-9.,]/g, '');
            if (!s) return null;
            if (s.includes(',')) s = s.replace(/\./g, '').replace(',', '.');
            else if (!/^\d+\.\d{2}$/.test(s)) s = s.replace(/\./g, '');
            const v = parseFloat(s);
            return isNaN(v) ? null : Math.round(v * 100);
        }

        function quiero(fila) {
            if (!LISTA_ACTIVA || !fila) return;
            const nombre = fila.dataset.nombre;
            const precio = fila.dataset.precio || null;
            const lista = leerLista();
            const i = lista.findIndex(x => x.nombre.toLowerCase() === nombre.toLowerCase());

            if (i >= 0) lista[i].qty += 1;
            else lista.push({ nombre, qty: 1, priceText: precio, priceCents: aCentavos(precio) });

            guardarLista(lista);
            alServidor('incr', { nombre, delta: 1, price_text: precio });

            // Recién ACÁ es "lo quiero": abrir el plato para mirarlo es otra cosa y se
            // registra aparte (dish_view), o el panel mezclaba curiosidad con intención.
            window.smTrack?.('dish_want', { nombre, price: precio, vista: 'classic' });

            const fab = document.getElementById('fabPedido');
            if (fab) { fab.classList.remove('late'); void fab.offsetWidth; fab.classList.add('late'); }
        }

        function cambiarCantidad(i, delta) {
            const lista = leerLista();
            if (!lista[i]) return;
            const nombre = lista[i].nombre;
            lista[i].qty += delta;

            if (lista[i].qty <= 0) {
                lista.splice(i, 1);
                alServidor('remove', { nombre });
            } else {
                alServidor('incr', { nombre, delta });
            }
            guardarLista(lista);
        }

        function pintarLista() {
            if (!LISTA_ACTIVA) return;
            const lista = leerLista();
            const total = lista.reduce((s, it) => s + it.qty, 0);

            // El botón flotante y el "+" de cada plato, con cuántos lleva.
            const fab = document.getElementById('fabPedido');
            if (fab) {
                fab.hidden = total === 0;
                document.getElementById('fabPedidoCant').textContent = total > 99 ? '99+' : total;
            }
            document.querySelectorAll('.lista-plato').forEach(fila => {
                const btn = fila.querySelector('.btn-quiero');
                if (!btn) return;
                const it = lista.find(x => x.nombre.toLowerCase() === fila.dataset.nombre.toLowerCase());
                btn.classList.toggle('en-pedido', !!it);
                btn.querySelector('.btn-quiero-mas').hidden = !!it;
                const cant = btn.querySelector('.btn-quiero-cant');
                cant.hidden = !it;
                cant.textContent = it ? it.qty : '';
            });

            // El panel. Se arma con nodos y no con innerHTML: los nombres vienen de la
            // carta, pero un plato con "<" en el nombre no puede romper la pantalla.
            const caja = document.getElementById('miPedidoLista');
            if (!caja) return;
            caja.replaceChildren();

            if (!lista.length) {
                const p = document.createElement('p');
                p.className = 'mi-pedido-vacio';
                p.textContent = TXT.vacio;
                caja.appendChild(p);
            }

            lista.forEach((it, i) => {
                const fila = document.createElement('div');
                fila.className = 'mi-pedido-fila';

                const texto = document.createElement('div');
                texto.className = 'mi-pedido-fila-nombre';
                texto.textContent = it.nombre;
                if (it.priceText) {
                    const pr = document.createElement('div');
                    pr.className = 'mi-pedido-fila-precio';
                    pr.textContent = it.priceText;
                    texto.appendChild(pr);
                }

                const cant = document.createElement('div');
                cant.className = 'mi-pedido-cant';
                const menos = document.createElement('button');
                menos.type = 'button'; menos.textContent = '−'; menos.setAttribute('aria-label', TXT.quitar);
                menos.addEventListener('click', () => cambiarCantidad(i, -1));
                const n = document.createElement('span');
                n.textContent = it.qty;
                const mas = document.createElement('button');
                mas.type = 'button'; mas.textContent = '+';
                mas.addEventListener('click', () => cambiarCantidad(i, 1));
                cant.append(menos, n, mas);

                fila.append(texto, cant);
                caja.appendChild(fila);
            });

            const centavos = lista.reduce((s, it) => s + (it.priceCents || 0) * it.qty, 0);
            document.getElementById('miPedidoTotal').textContent =
                (centavos / 100).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });
            document.getElementById('miPedidoSinPrecio').hidden = !lista.some(it => it.priceCents == null);

            const pedir = document.getElementById('btnPedir');
            if (pedir) pedir.disabled = lista.length === 0;
        }

        function abrirMiPedido() {
            const lista = leerLista();
            pintarLista();
            document.getElementById('miPedido')?.showModal();
            cargarYaPedido();
            // Abrir el pedido para revisarlo es lo más cerca de una venta que se puede medir.
            // Sólo con algo adentro: vacío, el embudo terminaba más ancho que su escalón anterior.
            if (lista.length) window.smTrack?.('order_summary', { items: lista.length, vista: 'classic' });
        }

        document.getElementById('miPedidoVaciar')?.addEventListener('click', () => {
            guardarLista([]);
            alServidor('clear', {});
        });

        // Tocar el fondo cierra, como el detalle del plato.
        document.getElementById('miPedido')?.addEventListener('click', (e) => {
            if (e.target.id === 'miPedido') e.target.close();
        });

        // ── "Pedir": manda la lista a la tablet del mozo (sólo con QR de mesa) ──
        function pintarYaPedido(items) {
            const caja = document.getElementById('yaPedido');
            if (!caja) return;
            caja.replaceChildren();
            caja.hidden = !items || !items.length;
            if (caja.hidden) return;

            const t = document.createElement('div');
            t.className = 'ya-pedido-titulo';
            t.textContent = TXT.ya_pediste;
            caja.appendChild(t);

            items.forEach(it => {
                const f = document.createElement('div');
                f.className = 'ya-pedido-fila';
                const a = document.createElement('span'); a.textContent = it.nombre;
                const b = document.createElement('span'); b.textContent = '×' + it.qty;
                f.append(a, b);
                caja.appendChild(f);
            });
        }

        async function cargarYaPedido() {
            if (!PUEDE_PEDIR) return;
            try {
                const r = await fetch('/api/comanda/mia', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify({ comercio_id: COMERCIO_ID, session_id: sesionCliente() }),
                });
                const data = await r.json();
                pintarYaPedido(data.pedido || []);
            } catch (e) { /* ya está pedido: no poder mostrarlo no es motivo para alarmar */ }
        }

        document.getElementById('btnPedir')?.addEventListener('click', async (e) => {
            const btn = e.currentTarget;
            const error = document.getElementById('pedidoError');
            if (btn.disabled) return;

            // Bloqueado mientras viaja: un doble toque mandaba dos rondas iguales.
            btn.disabled = true;
            const original = btn.textContent;
            btn.textContent = TXT.enviando;
            error.hidden = true;

            try {
                const r = await fetch('/api/comanda/pedir', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify({ comercio_id: COMERCIO_ID, session_id: sesionCliente() }),
                });
                const data = await r.json();

                if (!data.success) {
                    error.textContent = data.message || TXT.no_se_pudo;
                    error.hidden = false;
                    btn.textContent = original;
                    btn.disabled = false;
                    return;
                }

                // El carrito del servidor ya se vació: se vacía también la copia local, o el
                // cliente vuelve a ver lo mismo y lo manda de nuevo.
                guardarLista([]);
                pintarYaPedido(data.pedido || []);
                btn.textContent = TXT.enviado;
                setTimeout(() => { btn.textContent = original; pintarLista(); }, 2500);
            } catch (err) {
                error.textContent = TXT.no_se_pudo;
                error.hidden = false;
                btn.textContent = original;
                btn.disabled = false;
            }
        });

        pintarLista();

        const platoModal = document.getElementById('platoModal');
        let platoAbierto = null;

        // "Lo quiero" desde el detalle suma el plato que está abierto.
        document.getElementById('platoModalQuiero')?.addEventListener('click', () => {
            quiero(platoAbierto);
            platoModal.close();
        });

        function abrirPlato(btn) {
            platoAbierto = btn;
            const img = document.getElementById('platoModalImg');
            img.src = btn.dataset.img || '';
            img.alt = btn.dataset.nombre;
            img.hidden = !btn.dataset.img;   // la mayoría de los platos no tiene foto

            document.getElementById('platoModalNombre').textContent = btn.dataset.nombre;

            const desc = document.getElementById('platoModalDesc');
            desc.textContent = btn.dataset.desc;
            desc.hidden = !btn.dataset.desc;

            const precio = document.getElementById('platoModalPrecio');
            precio.textContent = btn.dataset.precio;
            precio.hidden = !btn.dataset.precio;

            // Saltar de un plato a otro reusa el mismo diálogo: abrirlo estando abierto
            // tira InvalidStateError, así que se cierra primero.
            if (platoModal.open) {
                platoModal.close();
            }

            platoModal.showModal();
            platoModal.scrollTop = 0;

            // Qué platos miran de cerca. Es `dish_view` y NO `dish_want`: antes se guardaba
            // como "Lo quiero" y el panel mezclaba al que miró con el que lo eligió.
            window.smTrack?.('dish_view', { nombre: btn.dataset.nombre, vista: 'classic' });
        }

        // Tocar el fondo cierra. En un celular es lo que la gente intenta primero,
        // antes que buscar la X.
        platoModal.addEventListener('click', (e) => {
            if (e.target === platoModal) platoModal.close();
        });
    </script>

    @include('menu.partials.tracking', ['trackView' => 'classic'])
</body>
</html>


