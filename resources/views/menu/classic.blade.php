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
        }
        .lista-plato:last-child { border-bottom: 0; }
        .lista-plato-texto { flex: 1; min-width: 0; }
        .lista-plato-nombre { margin: 0; font-size: 16px; font-weight: 600; color: var(--text); line-height: 1.3; }
        .lista-plato-estrella { color: var(--accent); font-size: 13px; }
        .lista-plato-desc { margin: 4px 0 0; font-size: 13px; color: var(--text-dim); line-height: 1.45; }
        /* El precio es lo que el cliente busca: va en el acento y con aire propio. */
        .lista-plato-precio {
            margin: 6px 0 0; font-size: 15px; font-weight: 700;
            color: var(--accent-soft); white-space: nowrap;
        }

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
                <div class="w-full max-w-3xl mx-auto animate-fade-in">
                    @foreach($platosPorCategoria as $categoria => $platos)
                        <section class="lista-categoria">
                            <h2 class="lista-categoria-titulo">{{ $categoria }}</h2>

                            {{-- La foto va a la DERECHA y el precio debajo del nombre, como en
                                 las apps de delivery. Con la foto a la izquierda, los pocos
                                 platos que tienen imagen (acá 5 de 151) desalineaban la columna
                                 de texto de todos los demás y la lista quedaba en zigzag. Así,
                                 el que no tiene foto simplemente ocupa todo el ancho. --}}
                            @foreach($platos as $plato)
                                <article class="lista-plato">
                                    <div class="lista-plato-texto">
                                        <h3 class="lista-plato-nombre">
                                            {{ $plato['nombre'] }}
                                            @if(!empty($plato['estrella']))
                                                <span class="lista-plato-estrella" title="{{ __('Recomendado') }}">★</span>
                                            @endif
                                        </h3>
                                        @if(!empty($plato['descripcion']))
                                            <p class="lista-plato-desc">{{ $plato['descripcion'] }}</p>
                                        @endif
                                        @if(!empty($plato['precio']))
                                            <p class="lista-plato-precio">{{ $plato['precio'] }}</p>
                                        @endif
                                    </div>

                                    @if(!empty($plato['imagen']))
                                        {{-- onerror: una foto rota se ve peor que ninguna foto.
                                             Si el archivo no está, la fila queda como las demás. --}}
                                        <button type="button" class="lista-plato-foto-btn"
                                                onclick="abrirPlato(this)"
                                                data-nombre="{{ $plato['nombre'] }}"
                                                data-desc="{{ $plato['descripcion'] ?? '' }}"
                                                data-precio="{{ $plato['precio'] ?? '' }}"
                                                data-img="{{ $plato['imagen'] }}"
                                                aria-label="{{ __('Ver') }} {{ $plato['nombre'] }}">
                                            <img src="{{ $plato['imagen'] }}" alt="{{ $plato['nombre'] }}"
                                                 loading="lazy" decoding="async" class="lista-plato-foto"
                                                 onerror="this.closest('.lista-plato-foto-btn').remove()">
                                        </button>
                                    @endif
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
        <img id="platoModalImg" src="" alt="">
        <div class="plato-modal-texto">
            <h3 id="platoModalNombre"></h3>
            <p id="platoModalDesc"></p>
            <p id="platoModalPrecio"></p>
        </div>
    </dialog>

    <script>
        const platoModal = document.getElementById('platoModal');

        function abrirPlato(btn) {
            document.getElementById('platoModalImg').src = btn.dataset.img;
            document.getElementById('platoModalImg').alt = btn.dataset.nombre;
            document.getElementById('platoModalNombre').textContent = btn.dataset.nombre;

            const desc = document.getElementById('platoModalDesc');
            desc.textContent = btn.dataset.desc;
            desc.hidden = !btn.dataset.desc;

            const precio = document.getElementById('platoModalPrecio');
            precio.textContent = btn.dataset.precio;
            precio.hidden = !btn.dataset.precio;

            platoModal.showModal();

            // Que el comercio pueda ver QUÉ plato miran de cerca: es la señal más
            // parecida a "esto me tentó" que da esta pantalla, donde no hay pedido.
            window.smTrack?.('dish_want', { nombre: btn.dataset.nombre, vista: 'classic' });
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


