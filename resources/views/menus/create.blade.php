<x-app-layout>
    <style>
        /* ── Bloques dentro de una sección del editor ──────────────────────────
           Sin cromo de tarjeta: adentro de una sección abierta, una tarjeta blanca con
           borde, sombra, círculo de color y título grande se leía como OTRA sección, al
           mismo nivel que la que se había abierto. Acá son partes de un panel: las separa
           una línea fina, y el título es un rótulo chico. */
        /* La separación la da el `space-y` del cuerpo de la sección; acá va sólo la línea,
           y sólo entre bloques hermanos: algunos vienen envueltos en un div propio. */
        .editor-bloque + .editor-bloque {
            padding-top: 1.75rem;
            border-top: 1px solid rgb(var(--c-line) / .6);
        }

        .editor-bloque-titulo {
            font-size: .75rem;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: rgb(var(--c-ink-soft));
            margin-bottom: 1rem;
        }

        /* Botón que manda el archivo a la IA al crear un menú - Verde forzado */
        #btn-actualizar-menu,
        .btn-actualizar-menu,
        button[type="submit"][id="btn-actualizar-menu"] {
            background-color: rgb(var(--c-ok)) !important;
            border: 2px solid rgb(var(--c-ok) / .3) !important;
        }
        
        #btn-actualizar-menu:hover:not(:disabled),
        .btn-actualizar-menu:hover:not(:disabled) {
            background-color: rgb(var(--c-ok) / .85) !important;
        }

        /* Apagado: no hay archivo nuevo que procesar. Gris plano, sin sombra ni
           movimiento, para que se lea como "todavía no" y no como un botón roto. */
        #btn-actualizar-menu:disabled,
        .btn-actualizar-menu:disabled {
            background-color: rgb(var(--c-line)) !important;
            border-color: rgb(var(--c-line)) !important;
            color: rgb(var(--c-ink-soft)) !important;
            box-shadow: none !important;
            transform: none !important;
            cursor: not-allowed;
        }

        /* Forzar estilos para inputs */
        input:not([type="checkbox"]):not([type="radio"]):not([type="file"]):not([type="range"]),
        select,
        textarea {
            background-color: rgb(var(--c-elev-2)) !important;
            color: rgb(var(--c-ink)) !important;
            border-color: rgb(var(--c-line)) !important;
        }

        .faq-container {
            background-color: rgb(var(--c-elev-2)) !important;
            border: 1px solid rgb(var(--c-line)) !important;
        }
        
        /* Espaciado personalizado para alineación perfecta */
        .space-y-custom > :not([hidden]) ~ :not([hidden]) {
            --tw-space-y-reverse: 0 !important;
            margin-top: calc(1.0rem * calc(1 - var(--tw-space-y-reverse))) !important;
            margin-bottom: calc(1.0rem * var(--tw-space-y-reverse)) !important;
        }
        
        /* Modal de iconos */
        .icon-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(40, 66, 54, .55);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .icon-modal-content {
            background: rgb(var(--c-elev-2));
            border-radius: 1rem;
            padding: 2rem;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            border: 1px solid rgb(var(--c-line));
        }
        
        .icon-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .icon-option {
            background: rgb(var(--c-elev-1));
            border: 1px solid rgb(var(--c-line));
            border-radius: 0.5rem;
            padding: 0.75rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 1.5rem;
        }
        
        .icon-option:hover {
            background: rgb(var(--c-accent) / .12);
            transform: scale(1.1);
        }
        
        select option {
            background-color: rgb(var(--c-elev-2)) !important;
            color: rgb(var(--c-ink)) !important;
        }

        select:focus {
            outline: none !important;
            border-color: rgb(var(--c-accent) / .5) !important;
            box-shadow: 0 0 0 2px rgb(var(--c-accent) / .15) !important;
        }
        
        /* Modal de archivos */
        .file-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(40, 66, 54, .6);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .file-modal-content {
            background: rgb(var(--c-elev-2));
            border-radius: 1rem;
            padding: 2rem;
            max-width: 90vw;
            max-height: 90vh;
            overflow: hidden;
            border: 1px solid rgb(var(--c-line));
            position: relative;
        }
        
        .file-modal-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .file-modal-body {
            max-height: 70vh;
            overflow: auto;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .file-modal-body img {
            max-width: 100%;
            max-height: 100%;
            border-radius: 0.5rem;
        }
        
        .file-modal-body iframe {
            width: 100%;
            height: 70vh;
            border: none;
            border-radius: 0.5rem;
        }
        
        /* Estilos específicos para los swatches de temas */
        .theme-swatch {
            border: none !important;
            cursor: pointer !important;
            /* No forzar background-color aquí, dejar que los estilos inline funcionen */
        }
        
        .theme-swatch:hover {
            transform: scale(1.1) !important;
            opacity: 0.9 !important;
        }
        
        .theme-swatch:focus {
            outline: none !important;
        }
    </style>
    <div class="min-h-screen">
        <div class="{{ $isEdit ? 'max-w-[1400px]' : 'max-w-3xl' }} mx-auto px-4 sm:px-6 lg:px-8 py-8">

            {{-- Barra del editor. Fija arriba mientras se baja por las secciones, porque lo
                 que responde es la pregunta que el comercio se hace todo el tiempo: ¿esto ya lo
                 ven mis clientes? Antes el editor nunca decía si el menú estaba publicado, y
                 activarlo sólo se podía desde la lista. --}}
            <div class="{{ $isEdit ? 'md:sticky md:top-[73px] z-40 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-4 bg-background/95 backdrop-blur border-b border-accent-gray mb-6' : 'mb-10' }} flex flex-wrap items-center justify-between gap-4">
                <div class="min-w-0">
                    @if($isEdit && $menu)
                        <div class="flex items-center gap-3 flex-wrap">
                            <h1 class="text-2xl font-bold text-text-primary truncate">{{ $menu->nombre ?: 'Menú sin nombre' }}</h1>
                            @if($menu->activo)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-accent-green/15 text-accent-green">
                                    <span class="w-1.5 h-1.5 rounded-full bg-accent-green"></span>
                                    Publicado
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border border-warning/50 text-text-primary">
                                    <span class="w-1.5 h-1.5 rounded-full bg-warning"></span>
                                    Borrador · tus clientes todavía no lo ven
                                </span>
                            @endif
                        </div>
                        <p class="text-text-secondary text-sm mt-0.5">{{ $comercio->nombre }}</p>
                    @else
                        <h1 class="text-3xl font-bold text-text-primary mb-2">Crear Menú</h1>
                        <p class="text-text-secondary mt-0.5">{{ $comercio->nombre }}</p>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    @if($isEdit && $menu)
                        <a href="{{ route('menu.public', $comercio) }}" target="_blank" rel="noopener" class="btn-secondary">
                            Ver menú ↗
                        </a>
                        @unless($menu->activo)
                            <form method="POST" action="{{ route('menus.activar', [$comercio, $menu]) }}"
                                  onsubmit="event.preventDefault(); const f=this; showConfirmWithLoader('¿Publicar este menú? Pasa a ser el que ven tus clientes al escanear el QR.', r=>{ if(r) f.submit(); }, 'Publicando...');">
                                @csrf
                                <input type="hidden" name="volver" value="editor">
                                <button type="submit" class="btn-primary">Publicar</button>
                            </form>
                        @endunless
                    @endif
                    @if(auth()->user()->isSuperAdmin() || $comercio->tieneFeature('excel'))
                    <!-- Dropdown Gestión Excel -->
                    <div class="relative" id="excelDropdown">
                        <button id="excelDropdownButton" 
                                class="bg-accent-green hover:brightness-95 text-white font-medium py-3 px-6 rounded-xl transition-all duration-300 shadow-lg inline-flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M3 14h18M10 3v18"/>
                            </svg>
                            Gestión Excel
                            <svg id="excelDropdownChevron" class="w-4 h-4 ml-2 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        
                        @php
                            $puedeExportar = $isEdit && isset($menu) && $menu && $menu->procesado;
                            $comercioTieneMenus = $comercio->menus()->withTrashed()->exists();
                            $puedeImportar = $isEdit || $comercioTieneMenus;
                        @endphp

                        <!-- Dropdown Menu -->
                        <div id="excelDropdownMenu"
                             class="absolute right-0 mt-2 w-64 rounded-xl shadow-lg bg-secondary-dark border border-accent-gray z-50 hidden">
                            <div class="py-2">

                                {{-- 1. EXPORTAR --}}
                                @if($puedeExportar)
                                    <a href="{{ route('menus.export', [$comercio, $menu]) }}"
                                       class="flex items-start gap-3 px-4 py-3 hover:bg-accent-gray/50 transition-colors duration-200">
                                        <svg class="w-5 h-5 mt-0.5 text-accent-green flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        <div>
                                            <div class="text-sm text-text-primary font-medium">Exportar a Excel</div>
                                            <div class="text-xs text-text-secondary mt-0.5">Descargá el menú para editarlo</div>
                                        </div>
                                    </a>
                                @else
                                    <div class="flex items-start gap-3 px-4 py-3 opacity-40 cursor-not-allowed">
                                        <svg class="w-5 h-5 mt-0.5 text-text-secondary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        <div>
                                            <div class="text-sm text-text-secondary font-medium">Exportar a Excel</div>
                                            <div class="text-xs text-text-secondary/60 mt-0.5">Disponible cuando el menú tenga datos</div>
                                        </div>
                                    </div>
                                @endif

                                {{-- 2. IMPORTAR --}}
                                @if($puedeImportar)
                                    <div class="mx-4 my-1 border-t border-accent-gray/50"></div>

                                    <form id="importFormDropdown" method="POST" action="{{ route('menus.preview.import', $comercio) }}" enctype="multipart/form-data">
                                        @csrf
                                        @if($isEdit && isset($menu) && $menu)
                                            <input type="hidden" name="menu_id" value="{{ $menu->id }}">
                                        @endif
                                        <label for="importFileDropdown" class="cursor-pointer flex items-start gap-3 px-4 py-3 hover:bg-accent-gray/50 transition-colors duration-200">
                                            <svg class="w-5 h-5 mt-0.5 text-accent-blue flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                            </svg>
                                            <div>
                                                <div class="text-sm text-text-primary font-medium">Importar Excel editado</div>
                                                <div class="text-xs text-text-secondary mt-0.5">Subí el archivo que exportaste</div>
                                            </div>
                                            <input type="file" id="importFileDropdown" name="archivo"
                                                   accept=".xlsx,.xls" required class="hidden">
                                        </label>
                                    </form>
                                @endif

                            </div>
                        </div>
                    </div>
                    @endif

                    <a href="{{ route('menus.index', $comercio) }}"
                       class="bg-secondary-dark hover:bg-accent-gray text-text-primary font-medium py-3 px-6 rounded-xl transition-all duration-300 border border-accent-gray">
                        ← Volver
                    </a>
                </div>
            </div>

            {{-- Mensaje de éxito oculto - se muestra en modal mejorado --}}
            @if (session('success'))
                <div class="hidden" id="success-message" data-message="{{ session('success') }}"></div>
            @endif

            {{-- Mensaje de error oculto - se muestra en modal mejorado --}}
            @if (session('error'))
                <div class="hidden" id="error-message" data-message="{{ session('error') }}"></div>
            @endif


            @php
                // Lo que dice cada sección cerrada. Sale de los mismos datos que ya muestra la
                // pantalla; no hay consultas nuevas salvo lo que el menú ya trae decodificado.
                $datosMenuEditor = $menu ? json_decode(preg_replace('/^```json\s*|```\s*$/', '', trim((string) $menu->contexto_gpt)), true) : null;
                $categoriasEditor = is_array($datosMenuEditor['categorias'] ?? null) ? $datosMenuEditor['categorias'] : [];
                $esCategoriaPromo = fn ($c) => in_array(mb_strtolower(trim($c['nombre'] ?? '')), ['promociones del día', 'promociones del dia'], true);
                $platosEditor = collect($categoriasEditor)->reject($esCategoriaPromo)->flatMap(fn ($c) => $c['platos'] ?? []);
                $cantidadCategorias = collect($categoriasEditor)->reject($esCategoriaPromo)->count();
                $cantidadPromos = collect($categoriasEditor)->filter($esCategoriaPromo)->flatMap(fn ($c) => $c['platos'] ?? [])->count();
                $sinDescripcion = $platosEditor->filter(fn ($p) => blank($p['descripcion'] ?? null))->count();
                $sinFoto = $platosEditor->filter(fn ($p) => blank($p['imagen'] ?? null))->count();

                // Lo que tiene y lo que le falta, de un vistazo y con la sección cerrada:
                // "21 categorías · 149 platos · 87 sin descripción · 149 sin foto".
                // Lo que ya está completo no se nombra: sólo suma ruido.
                $resumenPlatos = $platosEditor->isEmpty()
                    ? 'Todavía no hay platos'
                    : collect([
                        $cantidadCategorias.' '.($cantidadCategorias === 1 ? 'categoría' : 'categorías'),
                        $platosEditor->count().' '.($platosEditor->count() === 1 ? 'plato' : 'platos'),
                        $sinDescripcion ? $sinDescripcion.' sin descripción' : null,
                        $sinFoto ? $sinFoto.' sin foto' : null,
                    ])->filter()->implode(' · ');

                $resumenAspecto = ($comercio->tienePortada() ? 'Con portada' : 'Sin portada')
                    .' · Carta como '.($comercio->menu_clasico_vista === \App\Models\Comercio::VISTA_LISTA ? 'lista' : 'archivo');

                $cfgEditor = $comercio->chatbot_config ?? [];
                $tonosEditor = ['cercano_divertido' => 'Cercano y divertido', 'calido_atento' => 'Cálido y atento', 'formal_elegante' => 'Formal y elegante', 'canchero' => 'Canchero'];
                $resumenAsistente = ($comercio->chatbot_name ?: 'Asistente IA').' · '
                    .($tonosEditor[$cfgEditor['tono'] ?? \App\Models\Comercio::PERSONALIDAD_POR_DEFECTO['tono']] ?? '');

                $resumenVender = collect([
                    $cantidadPromos ? $cantidadPromos.' promo'.($cantidadPromos === 1 ? '' : 's') : null,
                    $comercio->pedido_habilitado ? 'Pedidos en línea' : null,
                    $comercio->pideResena() ? 'Reseñas' : null,
                ])->filter()->implode(' · ') ?: 'Promociones, atajos, pedidos y reseñas';

                // URL del celular para cada sección: la pantalla pública real, en modo vista
                // previa y con ESTE menú (ver App\Support\VistaPrevia).
                $previaEditor = fn (string $ruta, array $extra = []) => $menu
                    ? route($ruta, $comercio).'?'.http_build_query(array_merge(['preview' => 1, 'menu' => $menu->id], $extra))
                    : null;
            @endphp

            {{-- Al crear, sólo nombre y archivo: todo lo demás aparece en el editor cuando la IA
                 termina de leer el menú. Las otras secciones se renderizan igual pero ocultas,
                 porque el JavaScript de esta pantalla usa sus elementos sin chequear que existan. --}}
            @unless($isEdit)
                <p class="text-text-secondary text-sm mb-6">
                    Subí tu carta y la IA saca los platos. Cuando termine vas a poder revisarlos,
                    configurar el asistente, los colores y la portada, todo desde la misma pantalla.
                </p>
            @endunless

            <div class="{{ $isEdit ? 'lg:grid lg:grid-cols-[minmax(0,1fr)_340px] lg:gap-8 lg:items-start' : '' }}">
            <div id="editor-secciones" class="min-w-0 space-y-4">

            <div class="{{ $isEdit ? 'space-y-4' : 'hidden' }}">

                <x-editor.seccion id="platos" titulo="Tus platos" icono="📋" :resumen="$resumenPlatos" :lista="$platosEditor->isNotEmpty()" :preview="$previaEditor('menu.classic', ['vista' => 'lista'])" preview-titulo="Así ven tu carta" :abierta="true">
            <!-- 📋 Contenido Extraído del Menú - Sección de ancho completo -->
            @if($menu && $menu->procesado && ($menu->texto_extraido || $menu->contexto_gpt))
                <div>
                    <div class="editor-bloque">
                        
                        @php
                            // Obtener el contenido JSON desde contexto_gpt o texto_extraido
                            $rawContent = $menu->contexto_gpt ?? $menu->texto_extraido;
                            
                            // Limpiar el contenido: remover ```json y ``` si están presentes
                            $jsonContent = $rawContent;
                            if (strpos($jsonContent, '```json') !== false) {
                                $jsonContent = preg_replace('/```json\s*/', '', $jsonContent);
                                $jsonContent = preg_replace('/```\s*$/', '', $jsonContent);
                            }
                            
                            // Decodificar el JSON
                            $contenido = json_decode(trim($jsonContent), true);
                        @endphp
                        
                        @if($contenido && is_array($contenido))
                            <div class="space-y-6">
                                
                                <!-- Categorías y platos -->
                                @php
                                    // Obtener categorías del formato unificado
                                    $categorias = $contenido['categorias'] ?? null;
                                @endphp
                                
                                @if($categorias && is_array($categorias))
                                    {{-- Las categorías, plegadas y en UNA columna. Antes iban en una grilla de tres
                                         columnas con cada plato como tarjeta alta: con 21 categorías y 150 platos la vista
                                         saltaba de columna en columna y no se veía el conjunto. Ahora se ven todas de un
                                         vistazo, con cuánto tienen y qué les falta, y se abre una a la vez.

                                         Cada plato es una fila: nombre y precio, que es lo que más se corrige. Foto,
                                         descripción y etiquetas van en el detalle que se abre con la flecha.

                                         Los ids, `.plato-item`, `data-plato-index` y `#platos-container-N` NO se tocan:
                                         de eso cuelgan la edición en el lugar, las fotos, las etiquetas y el reordenamiento
                                         con arrastre. Los bloques con lógica están copiados tal cual. --}}
                                    <div class="space-y-3" id="editor-categorias">
                                        @foreach($categorias as $categoriaIndex => $categoria)
                                            @php
                                                // Filtrar "Promociones del Día" ya que tiene su propia sección dedicada
                                                $nombreCategoria = strtolower(trim($categoria['nombre'] ?? ''));
                                                if ($nombreCategoria === 'promociones del día') {
                                                    continue;
                                                }
                                            @endphp
                                        @php
                                            $platosCat = is_array($categoria['platos'] ?? null) ? $categoria['platos'] : [];
                                            $sinDescCat = collect($platosCat)->filter(fn ($p) => blank($p['descripcion'] ?? null))->count();
                                        @endphp
                                        <div class="editor-categoria border border-accent-gray rounded-xl overflow-hidden bg-secondary-dark" data-categoria-editor="{{ $categoriaIndex }}">
                                            <button type="button" class="editor-categoria-cabecera w-full flex items-center gap-3 px-4 sm:px-5 py-3.5 text-left hover:bg-primary-dark/60 transition-colors" aria-expanded="false">
                                                <svg class="editor-categoria-flecha w-4 h-4 text-text-secondary flex-shrink-0 -rotate-90 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                <span class="flex-1 min-w-0 font-semibold text-text-primary truncate">{{ $categoria['nombre'] ?? 'Sin nombre' }}</span>
                                                <span class="text-sm text-text-secondary flex-shrink-0">{{ count($platosCat) }} {{ count($platosCat) === 1 ? 'plato' : 'platos' }}</span>
                                                @if($sinDescCat)
                                                    <span class="hidden sm:inline text-xs px-2 py-0.5 rounded-full border border-accent-gray text-text-secondary flex-shrink-0">{{ $sinDescCat }} sin descripción</span>
                                                @endif
                                            </button>

                                            <div class="editor-categoria-cuerpo hidden border-t border-accent-gray">
                                                <div class="divide-y divide-accent-gray/60" id="platos-container-{{ $categoriaIndex }}" data-categoria-index="{{ $categoriaIndex }}">
                                                    @foreach($platosCat as $platoIndex => $plato)
                                                            @php
                                                                // Transformar nombre: eliminar guiones y normalizar capitalización
                                                                $nombrePlato = $plato['nombre'] ?? 'Sin nombre';
                                                                // Eliminar guiones y underscores
                                                                $nombrePlato = str_replace(['-', '_'], ' ', $nombrePlato);
                                                                // Normalizar: primera letra de cada palabra en mayúscula, resto en minúscula
                                                                $nombrePlato = mb_convert_case($nombrePlato, MB_CASE_TITLE, 'UTF-8');
                                                                // Limpiar espacios múltiples
                                                                $nombrePlato = preg_replace('/\s+/', ' ', trim($nombrePlato));
                                                            @endphp
                                                        <div class="group plato-item bg-secondary-dark hover:bg-primary-dark/40 transition-colors cursor-move" data-plato-index="{{ $platoIndex }}">
                                                            <div class="flex items-center gap-2 sm:gap-3 px-3 sm:px-4 py-2">
                                                                    <div class="flex items-center justify-center w-5 h-5 text-accent-blue opacity-50 group-hover:opacity-100 transition-opacity cursor-grab active:cursor-grabbing flex-shrink-0">
                                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                                            <path d="M9 5h2v2H9V5zm0 6h2v2H9v-2zm0 6h2v2H9v-2zm4-12h2v2h-2V5zm0 6h2v2h-2v-2zm0 6h2v2h-2v-2z"></path>
                                                                        </svg>
                                                                    </div>
                                                                    <h5 class="text-text-primary font-medium text-base leading-snug cursor-text hover:text-accent-blue transition-colors capitalize flex-1 min-w-0 truncate"
                                                                        ondblclick="enableEditNombre({{ $categoriaIndex }}, {{ $platoIndex }})"
                                                                        onclick="handleEditClick(event, {{ $categoriaIndex }}, {{ $platoIndex }}, 'nombre')"
                                                                        id="nombre-display-{{ $categoriaIndex }}-{{ $platoIndex }}">
                                                                        {{ $nombrePlato }}
                                                                    </h5>
                                                                @if(blank($plato['descripcion'] ?? null))
                                                                    <button type="button" class="plato-detalle-toggle hidden md:inline-flex flex-shrink-0 text-[11px] px-2 py-0.5 rounded-full border border-accent-gray text-text-secondary hover:border-accent-blue/50 hover:text-accent-deep transition-colors" title="Agregar descripción">sin descripción</button>
                                                                @endif
                                                                    <!-- Precio (alineado a la derecha) -->
                                                                    <div class="flex items-center gap-2 flex-shrink-0">
                                                                        <div class="flex items-center bg-secondary-dark border border-accent-blue/50 rounded-lg overflow-hidden shadow-md hover:border-accent-blue transition-colors">
                                                                            <span class="text-accent-blue font-bold text-sm px-2.5 py-1.5 border-r border-accent-blue/40 bg-accent-blue/10">$</span>
                                                                            {{-- El precio se muestra CON su formato. Antes iba un
                                                                                 preg_replace('/[^0-9]/') que convertía "$5.700,00" en
                                                                                 570000, y al perder el foco guardaba eso: pasar por el
                                                                                 campo multiplicaba el precio por cien. --}}
                                                                            <input type="text"
                                                                                   value="{{ \App\Support\Precio::paraEditar($plato['precio'] ?? '') }}"
                                                                                   placeholder="0"
                                                                                   data-original-value="{{ \App\Support\Precio::paraEditar($plato['precio'] ?? '') }}"
                                                                                   oninput="this.value = this.value.replace(/[^0-9.,]/g, '')"
                                                                                   onblur="updatePlatoPrecio({{ $categoriaIndex }}, {{ $platoIndex }}, this.value)"
                                                                                   class="text-accent-blue font-bold text-base bg-transparent px-2.5 py-1.5 focus:outline-none focus:ring-2 focus:ring-accent-blue/50 transition-all w-20 text-right placeholder:text-accent-gray/50"
                                                                                   id="precio-input-{{ $categoriaIndex }}-{{ $platoIndex }}">
                                                                        </div>
                                                                        <span class="text-accent-blue text-xs min-w-[16px]" id="precio-status-{{ $categoriaIndex }}-{{ $platoIndex }}"></span>
                                                                    </div>
                                                                    <!-- Plato estrella: el asistente lo recomienda primero. Vive acá y no
                                                                         en una lista aparte para que la marca viaje con el plato. -->
                                                                    <button type="button"
                                                                            onclick="event.stopPropagation(); togglePlatoEstrella(this, {{ $categoriaIndex }}, {{ $platoIndex }})"
                                                                            data-estrella="{{ ! empty($plato['estrella']) ? '1' : '0' }}"
                                                                            id="estrella-{{ $categoriaIndex }}-{{ $platoIndex }}"
                                                                            class="plato-estrella-btn flex-shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-lg transition-colors {{ ! empty($plato['estrella']) ? 'text-warning bg-warning/20' : 'text-text-secondary hover:text-warning hover:bg-warning/10' }}"
                                                                            title="{{ ! empty($plato['estrella']) ? 'Es plato estrella: el asistente lo recomienda primero' : 'Marcar como plato estrella' }}">
                                                                        <svg class="w-5 h-5" fill="{{ ! empty($plato['estrella']) ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                                                        </svg>
                                                                    </button>
                                                                <!-- Abrir el detalle: foto, descripción y etiquetas -->
                                                                <button type="button" class="plato-detalle-toggle flex-shrink-0 inline-flex items-center justify-center w-8 h-8 text-text-secondary hover:text-accent-deep hover:bg-accent-blue/10 rounded-lg transition-colors" aria-expanded="false" title="Foto, descripción y etiquetas">
                                                                    <svg class="plato-detalle-flecha w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                                </button>
                                                                    <!-- Botón de edición visible en móvil -->
                                                                    <button type="button"
                                                                            onclick="event.stopPropagation(); enableEditNombre({{ $categoriaIndex }}, {{ $platoIndex }})"
                                                                            class="md:hidden flex-shrink-0 inline-flex items-center justify-center w-8 h-8 bg-accent-blue/20 hover:bg-accent-blue/40 text-accent-blue rounded-lg transition-colors"
                                                                            title="Editar nombre">
                                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                                        </svg>
                                                                    </button>
                                                                    <!-- Eliminar producto -->
                                                                    <button type="button"
                                                                            onclick="event.stopPropagation(); eliminarPlato({{ $categoriaIndex }}, {{ $platoIndex }}, @js($nombrePlato))"
                                                                            class="flex-shrink-0 inline-flex items-center justify-center w-8 h-8 text-text-secondary hover:text-danger hover:bg-danger/10 rounded-lg transition-colors"
                                                                            title="Eliminar producto">
                                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                                        </svg>
                                                                    </button>
                                                            </div>

                                                            <div class="plato-detalle hidden flex flex-col sm:flex-row gap-4 px-4 pb-4 sm:pl-12 cursor-default">
                                                                    <!-- Imagen -->
                                                                    <div class="flex-shrink-0" id="plato-imagen-container-{{ $categoriaIndex }}-{{ $platoIndex }}">
                                                                        @if(isset($plato['imagen']) && !empty($plato['imagen']))
                                                                            <div class="relative group/image">
                                                                                <div class="w-20 h-20 rounded-lg overflow-hidden border-2 border-accent-blue/30 shadow-md bg-secondary-dark">
                                                                                    <img src="{{ asset($plato['imagen']) }}" 
                                                                                         alt="{{ $nombrePlato }}"
                                                                                         class="w-full h-full object-cover"
                                                                                         id="plato-imagen-{{ $categoriaIndex }}-{{ $platoIndex }}">
                                                                                </div>
                                                                                <!-- Overlay con botones al hover -->
                                                                                <div class="absolute inset-0 bg-black/70 opacity-0 group-hover/image:opacity-100 transition-opacity duration-200 rounded-lg flex items-center justify-center gap-2">
                                                                                    <label class="cursor-pointer">
                                                                                        <input type="file" 
                                                                                               accept="image/jpeg,image/png,image/jpg,image/gif"
                                                                                               class="hidden"
                                                                                               onchange="uploadPlatoImagen(event, {{ $categoriaIndex }}, {{ $platoIndex }})"
                                                                                               id="imagen-input-{{ $categoriaIndex }}-{{ $platoIndex }}">
                                                                                        <span class="inline-flex items-center px-2.5 py-1 bg-accent-blue hover:opacity-80 text-black text-xs font-semibold rounded transition-opacity cursor-pointer shadow-xl">
                                                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                                                            </svg>
                                                                                            Cambiar
                                                                                        </span>
                                                                                    </label>
                                                                                    <button type="button"
                                                                                            onclick="removePlatoImagen({{ $categoriaIndex }}, {{ $platoIndex }}, '{{ $plato['imagen'] }}')"
                                                                                            class="inline-flex items-center justify-center w-7 h-7 bg-danger hover:brightness-95 text-white rounded transition-colors cursor-pointer shadow-xl"
                                                                                            title="Eliminar imagen">
                                                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                                                        </svg>
                                                                                    </button>
                                                                                </div>
                                                                            </div>
                                                                        @else
                                                                            <label class="cursor-pointer">
                                                                                <input type="file" 
                                                                                       accept="image/jpeg,image/png,image/jpg,image/gif"
                                                                                       class="hidden"
                                                                                       onchange="uploadPlatoImagen(event, {{ $categoriaIndex }}, {{ $platoIndex }})"
                                                                                       id="imagen-input-{{ $categoriaIndex }}-{{ $platoIndex }}">
                                                                                <div class="w-20 h-20 rounded-lg border-2 border-dashed border-accent-blue/40 bg-secondary-dark/80 flex flex-col items-center justify-center text-text-secondary hover:border-accent-blue hover:bg-secondary-dark transition-all duration-200">
                                                                                    <svg class="w-5 h-5 mb-0.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                                                    </svg>
                                                                                    <span class="text-[9px] font-semibold text-accent-blue">Imagen</span>
                                                                                </div>
                                                                            </label>
                                                                        @endif
                                                                    </div>
                                                                <div class="flex-1 min-w-0 space-y-3">
                                                                    <!-- Descripción -->
                                                                    <div id="descripcion-container-{{ $categoriaIndex }}-{{ $platoIndex }}" class="w-full relative">
                                                                        @if(isset($plato['descripcion']) && !empty($plato['descripcion']))
                                                                            <p class="text-text-secondary text-sm leading-relaxed cursor-text hover:text-text-primary transition-colors w-full whitespace-normal pr-8"
                                                                               ondblclick="enableEditDescripcion({{ $categoriaIndex }}, {{ $platoIndex }})"
                                                                               onclick="handleEditClick(event, {{ $categoriaIndex }}, {{ $platoIndex }}, 'descripcion')"
                                                                               id="descripcion-display-{{ $categoriaIndex }}-{{ $platoIndex }}">
                                                                                {{ $plato['descripcion'] }}
                                                                            </p>
                                                                        @else
                                                                            <p class="text-accent-gray text-sm leading-relaxed cursor-text hover:text-accent-blue transition-colors italic w-full whitespace-normal pr-8"
                                                                               ondblclick="enableEditDescripcion({{ $categoriaIndex }}, {{ $platoIndex }})"
                                                                               onclick="handleEditClick(event, {{ $categoriaIndex }}, {{ $platoIndex }}, 'descripcion')"
                                                                               id="descripcion-display-{{ $categoriaIndex }}-{{ $platoIndex }}">
                                                                                Click para agregar descripción...
                                                                            </p>
                                                                        @endif
                                                                        <!-- Botón de edición visible en móvil -->
                                                                        <button type="button"
                                                                                onclick="event.stopPropagation(); enableEditDescripcion({{ $categoriaIndex }}, {{ $platoIndex }})"
                                                                                class="md:hidden absolute top-0 right-0 inline-flex items-center justify-center w-7 h-7 bg-accent-blue/20 hover:bg-accent-blue/40 text-accent-blue rounded-lg transition-colors"
                                                                                title="Editar descripción">
                                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                                            </svg>
                                                                        </button>
                                                                    </div>
                                                                    <!-- Tags -->
                                                                    <div id="tags-container-{{ $categoriaIndex }}-{{ $platoIndex }}" class="w-full">
                                                                        <div class="flex flex-wrap gap-1.5 mb-1.5" id="tags-list-{{ $categoriaIndex }}-{{ $platoIndex }}">
                                                                            @if(isset($plato['tags']) && is_array($plato['tags']) && !empty($plato['tags']))
                                                                                @foreach($plato['tags'] as $tagIndex => $tag)
                                                                                    @if(!empty($tag))
                                                                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-accent-blue/20 text-accent-blue border border-accent-blue/40 hover:bg-accent-blue/30 transition-colors tag-item" 
                                                                                              data-tag-index="{{ $tagIndex }}">
                                                                                            {{ $tag }}
                                                                                            <button type="button" 
                                                                                                    onclick="removeTag({{ $categoriaIndex }}, {{ $platoIndex }}, '{{ $tag }}')"
                                                                                                    class="hover:text-accent-blue transition-colors p-0.5 rounded"
                                                                                                    title="Eliminar tag">
                                                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                                                                </svg>
                                                                                            </button>
                                                                                        </span>
                                                                                    @endif
                                                                                @endforeach
                                                                            @endif
                                                                        </div>
                                                                        <div class="w-full max-w-xs">
                                                                            <input type="text" 
                                                                                   placeholder="Escribe un tag y presiona Enter o coma"
                                                                                   title="Escribe el nombre del tag y presiona Enter o la tecla coma (,) para agregarlo"
                                                                                   class="text-xs bg-secondary-dark border border-accent-gray/70 rounded-md px-2.5 py-1.5 text-text-secondary placeholder:text-accent-gray focus:outline-none focus:ring-2 focus:ring-accent-blue/50 focus:border-accent-blue/70 hover:border-accent-gray transition-all w-full"
                                                                                   id="tag-input-{{ $categoriaIndex }}-{{ $platoIndex }}"
                                                                                   onkeydown="handleTagInput(event, {{ $categoriaIndex }}, {{ $platoIndex }})"
                                                                                   autocomplete="off">
                                                                            <p class="text-[10px] text-text-secondary mt-1 ml-0.5">💡 Presiona Enter o coma (,) para agregar</p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>

                                                <div class="px-4 py-3 border-t border-accent-gray/60">
                                                    <button type="button"
                                                        onclick="agregarPlato({{ $categoriaIndex }}, @js($categoria['nombre'] ?? 'esta categoría'))"
                                                            class="inline-flex items-center gap-2 px-3 py-1.5 text-accent-deep hover:bg-accent-blue/10 rounded-lg text-sm font-semibold transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                        Agregar plato
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>{{-- /#editor-categorias --}}
                                @else
                                    <div class="text-center py-8">
                                        <p class="text-text-secondary">No se encontraron categorías de platos en el menú procesado.</p>
                                        <p class="text-text-secondary text-sm mt-2">Claves disponibles: {{ implode(', ', array_keys($contenido)) }}</p>
                                    </div>
                                @endif
                                
                                <!-- Información adicional -->
                                @if(isset($contenido['informacion_adicional']) && !empty($contenido['informacion_adicional']))
                                    <div class="bg-secondary-dark border border-accent-gray rounded-lg p-4">
                                        <h4 class="text-lg font-semibold text-text-primary mb-2">ℹ️ Información Adicional</h4>
                                        <p class="text-text-secondary">{{ $contenido['informacion_adicional'] }}</p>
                                    </div>
                                @endif
                            </div>
                        @else
                            <!-- Fallback: mostrar JSON si no se puede parsear -->
                            <div class="bg-secondary-dark rounded-lg p-6">
                                <pre class="whitespace-pre-wrap text-sm text-text-secondary">{{ $rawContent }}</pre>
                            </div>
                        @endif
                    </div>
                </div>
            @elseif($menu && $menu->procesado)
                <div>
                    <div class="editor-bloque">
                        <h3 class="editor-bloque-titulo">Contenido Extraído</h3>
                        <div class="text-center py-12">
                            <div class="w-16 h-16 bg-accent-gray rounded-full flex items-center justify-center mx-auto mb-6">
                                <span class="text-text-secondary text-2xl">📄</span>
                            </div>
                            <h4 class="text-xl font-medium text-text-primary mb-3">No hay contenido procesado</h4>
                            <p class="text-text-secondary">El menú fue procesado pero no se extrajo contenido estructurado.</p>
                        </div>
                    </div>
                </div>
            @elseif($menu)
                {{-- Acá no se llega estando en proceso: `MenuController@edit` corta antes y
                     muestra `menus.procesando`. Quedan dos casos, el archivo que la IA no
                     pudo leer y el menú viejo que nunca se procesó, y los dos se resuelven
                     con el mismo botón: re-encolar el archivo que YA está guardado. Antes
                     esta pantalla decía "vuelve más tarde" y mandaba a subirlo de nuevo. --}}
                <div>
                    <div class="editor-bloque">
                        <h3 class="editor-bloque-titulo">Contenido Extraído</h3>

                        <div class="py-10">
                            <div class="max-w-md mx-auto text-center">
                                @if($menu->tuvoError())
                                    <div class="w-14 h-14 bg-danger/10 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <span class="text-2xl">⚠️</span>
                                    </div>
                                    <h4 class="text-xl font-medium text-text-primary mb-2">No pudimos leer el menú</h4>
                                    <p class="text-text-secondary mb-6">
                                        El archivo quedó guardado, así que podés volver a intentarlo sin
                                        subirlo de nuevo. Si vuelve a fallar, probá con una foto más
                                        nítida o un PDF donde el texto se lea derecho.
                                    </p>
                                @else
                                    <div class="w-14 h-14 bg-accent-blue/10 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <span class="text-2xl">📋</span>
                                    </div>
                                    <h4 class="text-xl font-medium text-text-primary mb-2">Todavía no leímos este menú</h4>
                                    <p class="text-text-secondary mb-6">
                                        El archivo está subido, pero la IA nunca sacó los platos.
                                        Podés pedírselo ahora.
                                    </p>
                                @endif

                                @if($menu->archivo_original)
                                    <button type="button" id="menuProcesoReintentar" class="btn-primary">
                                        {{ $menu->tuvoError() ? 'Volver a intentar' : 'Procesar ahora' }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    (function () {
                        const boton = document.getElementById('menuProcesoReintentar');
                        if (!boton) return;

                        const URL_REINTENTAR = @json(route('menus.reintentar', [$comercio, $menu]));
                        const TOKEN = @json(csrf_token());

                        boton.addEventListener('click', async function () {
                            boton.disabled = true;
                            try {
                                const r = await fetch(URL_REINTENTAR, {
                                    method: 'POST',
                                    headers: { 'X-CSRF-TOKEN': TOKEN, 'Accept': 'application/json' },
                                });
                                if (r.ok) {
                                    // El menú queda "procesando": al recargar, el controlador
                                    // manda solo a la pantalla de progreso.
                                    window.location.reload();
                                    return;
                                }
                                showError('No se pudo reintentar. Probá recargando la página.');
                            } catch (e) {
                                showError('No se pudo reintentar. Revisá la conexión.');
                            }
                            boton.disabled = false;
                        });
                    })();
                </script>
            @else
                <div>
                    <div class="editor-bloque">
                        <h3 class="editor-bloque-titulo">Contenido Extraído</h3>
                        <div class="text-center py-12">
                            <div class="w-16 h-16 bg-accent-gray rounded-full flex items-center justify-center mx-auto mb-6">
                                <span class="text-text-secondary text-2xl">📝</span>
                            </div>
                            <h4 class="text-xl font-medium text-text-primary mb-3">No hay menú cargado</h4>
                            <p class="text-text-secondary">Sube un archivo de menú para ver el contenido extraído aquí.</p>
                        </div>
                    </div>
                </div>
            @endif
                </x-editor.seccion>

                <x-editor.seccion id="aspecto" titulo="Cómo se ve" icono="🎨" :resumen="$resumenAspecto" :lista="$comercio->tienePortada()" :preview="$previaEditor('menu.public')" preview-titulo="Lo que ven al escanear el QR" :local="true">
                <div class="space-y-6">
                    
                    <!-- 🎨 Color de la marca -->
                    <div class="editor-bloque">
                        <h3 class="editor-bloque-titulo">Color de tu marca</h3>
                        
                        {{-- Selector de acento. Antes eran 8 temas que seteaban 4 colores cada
                             uno (y en los oscuros `primary` era un casi-negro, no un acento).
                             Ahora es UN color: el resto de la interfaz son neutros cálidos
                             fijos, así cualquier elección queda bien. Ver Comercio::ACENTOS. --}}
                        <div class="mb-6">
                            <p class="text-text-secondary text-sm mb-3">Botones, precios y detalles del menú salen de este color.</p>
                            <div class="flex items-center gap-3 flex-wrap">
                                @foreach(\App\Models\Comercio::ACENTOS as $key => $acento)
                                    <button type="button"
                                            class="theme-swatch w-10 h-10 rounded-full transition-all duration-200 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-secondary-dark"
                                            style="background-color: {{ $acento['color'] }} !important; border: none !important;"
                                            data-theme="{{ $key }}"
                                            data-color="{{ $acento['color'] }}"
                                            title="{{ $acento['nombre'] }}">
                                    </button>
                                @endforeach
                                    </div>
                        </div>
                    </div>
                </div>

            {{-- 🏪 Portada: la foto de la que salen el fondo, el hero y los colores del
                 menú público. Vive acá, con el resto de lo que ve el cliente, y no en
                 "datos del comercio": el dato se guarda en `comercios`, igual que los
                 colores, los atajos y las reseñas, pero lo que se configura es la
                 pantalla que el cliente ve al escanear el QR.

                 Guarda por AJAX contra `comercios.portada.guardar`, no con el
                 formulario de esta pantalla: el encuadre y el tagline se guardan cada
                 uno por su lado.

                 Feature de plan. El super admin siempre la ve, y un comercio que ya
                 tiene portada la sigue viendo aunque haya bajado de plan, para poder
                 borrarla. --}}
            @if(auth()->user()->isSuperAdmin() || $comercio->puedePersonalizarPortada() || $comercio->portada_url)
            @php $puedeEditarPortada = auth()->user()->isSuperAdmin() || $comercio->puedePersonalizarPortada(); @endphp

            <div class="editor-bloque" id="bloque-portada"
                 data-preview="{{ $previaEditor('menu.public') }}" data-preview-titulo="La portada del QR"
                 data-url-guardar="{{ route('comercios.portada.guardar', $comercio) }}"
                 data-url-estado="{{ route('comercios.portada.estado', $comercio) }}"
                 data-url-reintentar="{{ route('comercios.portada.reintentar', $comercio) }}"
                 data-estado="{{ $comercio->portada_estado }}">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="editor-bloque-titulo !mb-0">Portada</h3>
                    @if($comercio->portada_url)
                        <button type="button" onclick="eliminarPortada()"
                                class="bg-danger hover:brightness-95 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200">
                            🗑️ Eliminar
                        </button>
                    @endif
                </div>

                <p class="text-xs text-text-secondary mb-4">
                    Es lo primero que ve el cliente al escanear el QR. De esta foto salen
                    el fondo y los colores del menú, así que conviene una del local o de
                    un plato — no el menú escaneado.
                </p>

                {{-- Estado del procesado. Se consulta solo: sin esto decía
                     "recargá en unos segundos" para siempre, y si la cola estaba
                     caída el comerciante no tenía forma de enterarse. --}}
                <div class="mb-4 hidden" id="portadaProgreso">
                    <div class="rounded-lg border border-warning/30 bg-warning/10 px-3 py-2">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs text-warning">Generando el tema…</p>
                            <span class="text-xs text-warning/70" id="portadaSegundos"></span>
                        </div>
                        <div class="h-1 w-full overflow-hidden rounded-full bg-warning/40">
                            <div class="progreso-barra h-full w-1/3 rounded-full bg-warning"></div>
                        </div>
                        <p class="mt-2 hidden text-xs text-warning" id="portadaDemora">
                            Está tardando más de lo normal.
                            <button type="button" id="portadaReintentar"
                                    class="underline hover:text-accent-blue">Reintentar</button>
                        </p>
                    </div>
                </div>

                <div class="mb-4 hidden rounded-lg border border-danger/30 bg-danger/10 px-3 py-2"
                     id="portadaError">
                    <p class="text-xs text-danger">
                        No pudimos procesar esta imagen. Probá con otra foto.
                        <button type="button" id="portadaReintentar2"
                                class="underline hover:text-accent-blue">Reintentar</button>
                    </p>
                </div>

                <style>
                    /* Indeterminada a propósito: el procesado tarda distinto según
                       la imagen, así que un porcentaje sería inventado. */
                    @keyframes progreso-idas {
                        0%   { transform: translateX(-100%); }
                        100% { transform: translateX(300%); }
                    }
                    .progreso-barra { animation: progreso-idas 1.4s ease-in-out infinite; }
                    @media (prefers-reduced-motion: reduce) {
                        .progreso-barra { animation: none; width: 100%; opacity: .6; }
                    }
                </style>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    {{-- Izquierda: cómo se ve y qué parte se muestra --}}
                    <div>
                        @if($comercio->tienePortada())
                            {{-- Encuadre. La imagen se guarda entera y acá se elige qué parte
                                 queda a la vista: son dos números que van a `object-position`,
                                 así que mover esto no regenera nada. --}}
                            <div id="portadaActual">
                                <p class="text-xs text-text-secondary mb-1">Así se ve en el celular</p>
                                <div class="w-full max-w-xs overflow-hidden rounded-lg border border-accent-gray"
                                     style="aspect-ratio: 376 / 238;">
                                    <img id="focoPreview"
                                         src="{{ asset('storage/' . $comercio->portada_hero_url) }}"
                                         alt="Vista previa del encuadre"
                                         onerror="document.getElementById('portadaActual').remove()"
                                         class="w-full h-full object-cover"
                                         style="object-position: {{ $comercio->focoPortada() }};">
                                </div>

                                <p class="text-xs text-text-secondary mt-3 mb-1">
                                    Tocá lo que no puede faltar (el cartel, un plato…)
                                </p>
                                <div id="focoPicker" class="relative w-full max-w-xs cursor-crosshair select-none rounded-lg overflow-hidden border border-accent-gray">
                                    <img src="{{ asset('storage/' . $comercio->portada_hero_url) }}"
                                         alt="Elegir el punto focal" draggable="false"
                                         class="w-full block opacity-80">
                                    <span id="focoMarca"
                                          class="pointer-events-none absolute h-5 w-5 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white shadow-[0_0_0_2px_rgba(0,0,0,.5)]"
                                          style="left: {{ $comercio->portada_focal_x ?? 50 }}%; top: {{ $comercio->portada_focal_y ?? 50 }}%;"></span>
                                </div>

                                <div class="mt-1 flex max-w-xs items-center justify-between">
                                    <span id="focoValor" class="text-xs text-text-secondary">
                                        {{ $comercio->portada_focal_x ?? 50 }}% · {{ $comercio->portada_focal_y ?? 50 }}%
                                    </span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-text-secondary" id="focoEstado"></span>
                                        <button type="button" id="focoCentrar"
                                                class="text-xs text-text-secondary hover:text-accent-blue underline transition-colors duration-200">
                                            Centrar
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Aviso persistente: la subió igual, pero conviene que sepa
                                 por qué se ve borrosa cada vez que entra acá. --}}
                            @if($comercio->tema_derivado['baja_calidad'] ?? false)
                                <div class="mt-3 rounded-lg border border-warning/40 bg-warning/10 px-3 py-2">
                                    <p class="text-xs text-warning">
                                        <strong>Calidad baja.</strong>
                                        La imagen es de {{ $comercio->tema_derivado['ancho'] }}×{{ $comercio->tema_derivado['alto'] }}
                                        y se recomienda 900×600 o más. Se ve borrosa en el menú.
                                        Podés reemplazarla cuando quieras.
                                    </p>
                                </div>
                            @endif

                            <div class="mt-3 flex items-center gap-2">
                                <span class="h-5 w-5 rounded border border-white/20"
                                      style="background: {{ $comercio->tema_derivado['base'] }}"
                                      title="Tono base: {{ $comercio->tema_derivado['base'] }}"></span>
                                <span class="h-5 w-5 rounded border border-white/20"
                                      style="background: {{ $comercio->tema_derivado['acento_sugerido'] }}"
                                      title="Acento sugerido: {{ $comercio->tema_derivado['acento_sugerido'] }}"></span>
                                <p class="text-xs text-text-secondary">Tono y acento detectados</p>
                            </div>

                            @if($comercio->tieneAcentoSugeridoDistinto())
                                {{-- Se propone, no se pisa: el color elegido en el selector de
                                     temas manda, y el extraído suele traer la madera o la comida
                                     en vez de la marca. --}}
                                <button type="button"
                                        onclick="usarAcentoSugerido('{{ $comercio->tema_derivado['acento_sugerido'] }}')"
                                        class="w-full max-w-xs mt-3 px-2 py-1.5 rounded-lg border border-accent-gray text-xs text-text-primary hover:bg-white/5 transition-all duration-200">
                                    Usar {{ $comercio->tema_derivado['acento_sugerido'] }} como acento
                                </button>
                            @endif
                        @else
                            <div class="flex h-full min-h-[8rem] items-center justify-center rounded-lg border border-dashed border-accent-gray bg-secondary-dark p-4">
                                <p class="text-xs text-text-secondary text-center">
                                    Todavía no hay portada.<br>
                                    El menú abre con el diseño estándar.
                                </p>
                            </div>
                        @endif
                    </div>

                    {{-- Derecha: subir la foto y la frase --}}
                    <div>
                        @if($puedeEditarPortada)
                            <div class="mb-3 hidden" id="portadaPreviewWrap">
                                <img id="portadaPreview" alt="Portada nueva"
                                     class="w-full max-w-xs h-24 object-cover rounded-lg border-2 border-accent-blue">
                                <p class="text-xs text-accent-blue mt-1" id="portadaMedidas"></p>
                            </div>

                            {{-- Informativo: nunca impide subirla. Rechazar una foto chica
                                 dejaba al comercio sin portada. --}}
                            <div class="mb-3 hidden rounded-lg border border-warning/40 bg-warning/10 px-3 py-2"
                                 id="portadaAviso">
                                <p class="text-xs text-warning">
                                    <strong>Calidad baja.</strong>
                                    <span id="portadaAvisoDetalle"></span>
                                    Se va a ver borrosa en el menú. Podés subirla igual y cambiarla cuando quieras.
                                </p>
                            </div>

                            <label for="portada" class="block text-sm font-medium text-text-secondary mb-2">
                                {{ $comercio->portada_url ? 'Cambiar la foto' : 'Subir una foto' }}
                            </label>
                            <input type="file"
                                   id="portada"
                                   accept="image/*"
                                   class="w-full px-3 py-2 bg-secondary-dark border border-accent-gray rounded-lg text-text-primary text-xs file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-accent-blue file:text-white hover:file:brightness-95 focus:outline-none focus:ring-2 focus:ring-accent-blue transition-all duration-200">
                            <p class="text-xs text-text-secondary mt-1">
                                JPG o PNG, hasta 8 MB. Recomendado: 900×600 o más.
                            </p>

                            <div class="mt-2 flex items-center gap-2">
                                <button type="button" id="portadaSubir" disabled
                                        class="text-xs px-3 py-1.5 rounded-lg bg-accent-blue/20 border border-accent-blue text-accent-blue hover:bg-accent-blue/30 disabled:opacity-40 disabled:cursor-not-allowed">
                                    Subir portada
                                </button>
                                <span class="text-xs text-text-secondary" id="portadaSubirEstado"></span>
                            </div>
                        @else
                            <p class="text-xs text-text-secondary mb-4">
                                Cambiar la portada requiere un plan que incluya personalización.
                            </p>
                        @endif

                        {{-- Frase corta bajo el nombre, en la portada. Va aparte de la
                             descripción del comercio porque recortar un párrafo largo deja
                             la frase cortada al medio. --}}
                        <div class="mt-6 pt-6 border-t border-accent-gray">
                            <label for="tagline" class="block text-sm font-medium text-text-secondary mb-2">
                                Frase del menú
                            </label>
                            <input type="text"
                                   id="tagline"
                                   maxlength="80"
                                   value="{{ $comercio->tagline }}"
                                   placeholder="Ej: Experiencia, sabor e inteligencia en tu mesa."
                                   class="w-full px-3 py-2 bg-secondary-dark border border-accent-gray rounded-lg text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent transition-all duration-200">
                            <div class="mt-2 flex items-center gap-2">
                                <button type="button" onclick="guardarTagline(this)"
                                        class="text-xs px-3 py-1.5 rounded-lg bg-accent-blue/20 border border-accent-blue text-accent-blue hover:bg-accent-blue/30">
                                    Guardar frase
                                </button>
                                <span class="text-xs text-text-secondary" id="tagline-estado"></span>
                            </div>
                            <p class="text-xs text-text-secondary/70 mt-2">
                                Aparece bajo el nombre del comercio. Máx. 80 caracteres.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Con qué abre el "menú clásico", la opción de ver la carta sin el asistente. --}}
            @php $tieneArchivo = $comercio->tieneArchivoDeMenu(); @endphp
            <div class="editor-bloque" id="bloque-vista-clasica"
                 data-preview="{{ $previaEditor('menu.classic', ['vista' => 'lista']) }}" data-preview-titulo="La carta del cliente">
                <h3 class="editor-bloque-titulo">Menú clásico</h3>

                <p class="text-xs text-text-secondary mb-4">
                    Es lo que ve el cliente cuando elige mirar la carta en vez de usar el
                    asistente. El cliente igual puede pasar de una a la otra con un botón.
                </p>

                <div class="space-y-2">
                    {{-- Sin archivo en el disco, elegir "mostrar el archivo" no hace nada:
                         la vista pública cae igual a la lista. Ofrecerlo sin avisar deja al
                         comerciante tocando una opción que no cambia lo que ve el cliente. --}}
                    <label class="flex items-start gap-3 p-3 rounded-lg bg-secondary-dark border border-accent-gray
                                  {{ $tieneArchivo ? 'cursor-pointer' : 'opacity-60 cursor-not-allowed' }}">
                        <input type="radio" name="vista_clasica" value="archivo"
                               @checked($comercio->menu_clasico_vista !== 'lista')
                               @disabled(! $tieneArchivo)
                               onchange="guardarVistaClasica(this.value)"
                               class="mt-1 border-accent-gray bg-primary-dark text-accent-blue">
                        <span class="min-w-0">
                            <span class="block text-sm text-text-primary">Tu archivo (PDF o imagen)</span>
                            <span class="block text-xs text-text-secondary mt-1">
                                El menú tal como lo diseñaste. Conserva tu estética, pero en el
                                celular suele haber que hacer zoom.
                            </span>
                            @unless($tieneArchivo)
                                <span class="block text-xs text-warning mt-2">
                                    No hay ningún archivo cargado, así que tus clientes ven la carta en
                                    lista. Subí el PDF o la imagen de tu menú para poder elegir esta opción.
                                </span>
                            @endunless
                        </span>
                    </label>

                    <label class="flex items-start gap-3 p-3 rounded-lg bg-secondary-dark border border-accent-gray cursor-pointer">
                        <input type="radio" name="vista_clasica" value="lista"
                               @checked($comercio->menu_clasico_vista === 'lista')
                               onchange="guardarVistaClasica(this.value)"
                               class="mt-1 border-accent-gray bg-primary-dark text-accent-blue">
                        <span class="min-w-0">
                            <span class="block text-sm text-text-primary">La carta en lista</span>
                            <span class="block text-xs text-text-secondary mt-1">
                                Los platos por categoría, con precio, descripción y foto. Se lee
                                en el celular sin hacer zoom y se actualiza cuando editás un plato.
                            </span>
                        </span>
                    </label>
                </div>

                <p id="vista-clasica-estado" class="text-xs text-text-secondary mt-3"></p>
            </div>
                </x-editor.seccion>

                <x-editor.seccion id="asistente" titulo="Tu asistente" icono="🤖" :resumen="$resumenAsistente" :preview="$previaEditor('menu.chat')" preview-titulo="Así los recibe tu asistente" :local="true">
                <div class="space-y-6">
                    <div class="editor-bloque">
                        <h3 class="editor-bloque-titulo">Chatbot</h3>
                        
                        <div class="space-y-custom">
                            <!-- Nombre del Chatbot -->
                            <div>
                                <label for="chatbot_name" class="block text-sm font-medium text-text-secondary mb-3">
                                    Nombre del Chatbot
                                </label>
                                <input type="text" 
                                       id="chatbot_name" 
                                       value="{{ $comercio->chatbot_name ?? 'Asistente IA' }}"
                                       class="w-full px-4 py-3 bg-secondary-dark border border-accent-gray rounded-xl text-text-primary placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent transition-all duration-200">
                            </div>
                            
                            <!-- Mensaje de Bienvenida -->
                            <div>
                                <label for="welcome_message" class="block text-sm font-medium text-text-secondary mb-3">
                                    Mensaje de Bienvenida
                                </label>
                                <textarea id="welcome_message" 
                                          rows="4"
                                          maxlength="500"
                                          class="w-full px-4 py-3 bg-secondary-dark border border-accent-gray rounded-xl text-text-primary placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent transition-all duration-200 resize-none">{{ $comercio->welcome_message ?? '¡Hola! Soy tu asistente virtual. Puedo ayudarte con información sobre nuestro menú. ¿En qué puedo ayudarte?' }}</textarea>
                                <p class="text-xs text-text-secondary mt-2">Máximo 500 caracteres</p>
                            </div>

                            <!-- 🤖 Personalidad del asistente -->
                            @php
                                $cfg = $comercio->chatbot_config ?? [];
                                $chatbotService = new \App\Services\ChatbotService;
                                $platosMenu = $chatbotService->platosDelMenu($comercio);
                                $estrellasMenu = $chatbotService->platosEstrella($comercio);
                                $focosElegidos = $cfg['focos'] ?? [];
                            @endphp
                            <div>
                                <label class="block text-sm font-medium text-text-secondary mb-1">Personalidad del asistente</label>
                                <p class="text-xs text-text-secondary mb-4">Cómo habla y qué querés que venda. Se guarda solo.</p>

                                <p class="text-xs text-text-secondary uppercase tracking-wide mb-2">Tono</p>
                                <div class="flex flex-wrap gap-2 mb-4">
                                    @foreach(['cercano_divertido'=>'Cercano y divertido','calido_atento'=>'Cálido y atento','formal_elegante'=>'Formal y elegante','canchero'=>'Canchero'] as $val=>$label)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="cfg_tono" value="{{ $val }}" class="peer sr-only" @checked(($cfg['tono'] ?? \App\Models\Comercio::PERSONALIDAD_POR_DEFECTO['tono'])===$val)>
                                            <span class="inline-block px-3 py-1.5 rounded-full border border-accent-gray text-sm text-text-secondary peer-checked:bg-accent-blue/20 peer-checked:border-accent-blue peer-checked:text-accent-blue transition-colors">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <p class="text-xs text-text-secondary uppercase tracking-wide mb-2">Cómo trata</p>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach(['voseo'=>'Vos','tuteo'=>'Tú','neutro'=>'Neutro'] as $val=>$label)
                                                <label class="cursor-pointer">
                                                    <input type="radio" name="cfg_trato" value="{{ $val }}" class="peer sr-only" @checked(($cfg['trato'] ?? \App\Models\Comercio::PERSONALIDAD_POR_DEFECTO['trato'])===$val)>
                                                    <span class="inline-block px-3 py-1.5 rounded-full border border-accent-gray text-sm text-text-secondary peer-checked:bg-accent-blue/20 peer-checked:border-accent-blue peer-checked:text-accent-blue transition-colors">{{ $label }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-xs text-text-secondary uppercase tracking-wide mb-2">Emojis</p>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach(['moderado'=>'Moderados','minimo'=>'Pocos','ninguno'=>'Ninguno'] as $val=>$label)
                                                <label class="cursor-pointer">
                                                    <input type="radio" name="cfg_emojis" value="{{ $val }}" class="peer sr-only" @checked(($cfg['emojis'] ?? \App\Models\Comercio::PERSONALIDAD_POR_DEFECTO['emojis'])===$val)>
                                                    <span class="inline-block px-3 py-1.5 rounded-full border border-accent-gray text-sm text-text-secondary peer-checked:bg-accent-blue/20 peer-checked:border-accent-blue peer-checked:text-accent-blue transition-colors">{{ $label }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <p class="text-xs text-text-secondary uppercase tracking-wide mb-1">Qué querés que destaque</p>
                                <p class="text-xs text-text-secondary mb-3">
                                    Lo que el asistente va a empujar mientras charla, como pedirle al camarero que ofrezca
                                    postre antes de traer la cuenta. No filtra ni esconde nada del menú: el cliente
                                    sigue viendo todo. Marcá solo lo que tu carta realmente tenga.
                                </p>
                                <div class="flex flex-wrap gap-2 mb-4">
                                    @foreach(\App\Services\ChatbotService::FOCOS as $val => $foco)
                                        <label class="cursor-pointer">
                                            <input type="checkbox" name="cfg_focos" value="{{ $val }}" class="peer sr-only" @checked(in_array($val, $focosElegidos))>
                                            <span class="inline-block px-3 py-1.5 rounded-full border border-accent-gray text-sm text-text-secondary peer-checked:bg-accent-green/20 peer-checked:border-accent-green peer-checked:text-accent-green transition-colors">{{ $foco['label'] }}</span>
                                        </label>
                                    @endforeach
                                </div>

                                {{-- Los platos estrella se marcan con la ⭐ de cada plato, en "Contenido
                                     Extraído". Acá solo se muestran: antes había un cajón con los 151
                                     platos del menú y era imposible ver cuáles estaban marcados. --}}
                                <p class="text-xs text-text-secondary uppercase tracking-wide mb-2">
                                    Platos estrella (los recomienda primero)
                                    <span class="normal-case text-text-secondary/70">— <span id="estrellas-contador">{{ count($estrellasMenu) }}</span> de {{ \App\Http\Controllers\MenuController::MAX_ESTRELLAS }}</span>
                                </p>
                                <div id="estrellas-resumen" class="flex flex-wrap gap-2 mb-2">
                                    @foreach($estrellasMenu as $nombre)
                                        <span class="inline-block px-3 py-1.5 rounded-full border border-warning bg-warning/20 text-sm text-warning">⭐ {{ $nombre }}</span>
                                    @endforeach
                                </div>
                                <p id="estrellas-vacio" class="text-sm text-text-secondary mb-4 {{ count($estrellasMenu) > 0 ? 'hidden' : '' }}">
                                    @if(count($platosMenu) > 0)
                                        Ninguno marcado. Tocá la ⭐ de un plato en «Contenido Extraído», más abajo.
                                    @else
                                        Activá un menú procesado para elegir tus platos estrella.
                                    @endif
                                </p>

                                <p class="text-xs text-text-secondary uppercase tracking-wide mb-2">Detalles de tu local (opcional)</p>
                                <textarea id="cfg_detalles" rows="3" maxlength="600"
                                          class="w-full px-4 py-3 bg-secondary-dark border border-accent-gray rounded-xl text-text-primary placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent transition-all duration-200 resize-none"
                                          placeholder="Ej: Cervecería artesanal; recomendá siempre una cerveza para acompañar. La estrella es la Barrica Burger.">{{ $cfg['detalles'] ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>

                </div>
                </x-editor.seccion>

                <x-editor.seccion id="vender" titulo="Vender más" icono="🚀" :resumen="$resumenVender" :preview="$previaEditor('menu.chat')" preview-titulo="Promos y atajos en el chat" :local="true">
            <!-- Sección de Promociones del Día - ARRIBA DE CONTENIDO EXTRAÍDO, ANCHO COMPLETO -->
            @if($isEdit && $menu && (auth()->user()->isSuperAdmin() || $comercio->tieneFeature('promociones')))
                @php
                    $promocionesCategoria = null;
                    $promocionesIndex = -1;
                    
                    // Solo intentar leer promociones si hay contexto_gpt
                    if ($menu->contexto_gpt) {
                        $jsonContent = $menu->contexto_gpt;
                        $clean = trim($jsonContent);
                        if (strpos($clean, '```json') !== false) {
                            $clean = preg_replace('/```json\s*/', '', $clean);
                            $clean = preg_replace('/```\s*$/', '', $clean);
                        }
                        $contenido = json_decode(trim($clean), true);
                        
                        // Buscar categoría de promociones
                        if ($contenido && isset($contenido['categorias']) && is_array($contenido['categorias'])) {
                            foreach ($contenido['categorias'] as $idx => $cat) {
                                if (isset($cat['nombre']) && strtolower(trim($cat['nombre'])) === 'promociones del día') {
                                    $promocionesCategoria = $cat;
                                    $promocionesIndex = $idx;
                                    break;
                                }
                            }
                        }
                    }
                    
                    // Si no existe, crear estructura vacía
                    if (!$promocionesCategoria) {
                        $promocionesCategoria = [
                            'nombre' => 'Promociones del Día',
                            'platos' => []
                        ];
                    }
                @endphp
                
                <div>
                    <div class="editor-bloque">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="editor-bloque-titulo !mb-0">Promociones del Día</h3>
                            <div class="flex items-center gap-3">
                                <button type="button" 
                                        onclick="addPromocion()"
                                        class="bg-accent-blue hover:bg-accent-blue text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center gap-2">
                                    <span>➕</span>
                                    <span>Agregar Promoción</span>
                                </button>
                            </div>
                        </div>
                        <div class="bg-accent-blue/10 border border-accent-blue/30 rounded-xl p-6">
                            
                            <div id="promociones-container" class="space-y-4">
                                <!-- Las promociones se agregarán aquí -->
                                @if(isset($promocionesCategoria['platos']) && is_array($promocionesCategoria['platos']))
                                    @foreach($promocionesCategoria['platos'] as $promoIndex => $promo)
                                        @php
                                            $promoId = $promo['id'] ?? ($promoIndex + 1000000); // ID único o fallback
                                        @endphp
                                        <div class="promocion-item bg-secondary-dark rounded-lg p-4 border border-accent-gray" data-promo-index="{{ $promoIndex }}" data-promo-id="{{ $promoId }}">
                                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">
                                                <!-- Nombre -->
                                                <div class="md:col-span-3">
                                                    <label class="block text-xs text-text-secondary mb-1.5">Nombre</label>
                                                    <input type="text" 
                                                           data-promo-field="nombre"
                                                           data-promo-index="{{ $promoIndex }}"
                                                           data-promo-id="{{ $promoId }}"
                                                           value="{{ old("promociones.{$promoIndex}.nombre", $promo['nombre'] ?? '') }}"
                                                           placeholder="Nombre de la promoción"
                                                           class="w-full px-3 py-2 bg-background border border-accent-gray rounded-lg text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-blue">
                                                </div>
                                                
                                                <!-- Precio -->
                                                <div class="md:col-span-2">
                                                    <label class="block text-xs text-text-secondary mb-1.5">Precio</label>
                                                    <div class="flex items-center bg-background border border-accent-gray rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-accent-blue">
                                                        <span class="text-text-primary font-medium text-sm px-3 py-2 border-r border-accent-gray">$</span>
                                                        <input type="text" 
                                                               data-promo-field="precio"
                                                               data-promo-index="{{ $promoIndex }}"
                                                               data-promo-id="{{ $promoId }}"
                                                               value="{{ preg_replace('/[^0-9]/', '', old("promociones.{$promoIndex}.precio", $promo['precio'] ?? '')) }}"
                                                               placeholder="0"
                                                               oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                                               class="w-full px-3 py-2 bg-transparent text-text-primary focus:outline-none flex-1 text-right">
                                                    </div>
                                                </div>
                                                
                                                <!-- Descripción -->
                                                <div class="md:col-span-4">
                                                    <label class="block text-xs text-text-secondary mb-1.5">Descripción</label>
                                                    <textarea data-promo-field="descripcion"
                                                              data-promo-index="{{ $promoIndex }}"
                                                              data-promo-id="{{ $promoId }}"
                                                              rows="2"
                                                              placeholder="Descripción de la promoción"
                                                              class="w-full px-3 py-2 bg-background border border-accent-gray rounded-lg text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-blue resize-none">{{ old("promociones.{$promoIndex}.descripcion", $promo['descripcion'] ?? '') }}</textarea>
                                                </div>
                                                
                                                <!-- Imagen -->
                                                <div class="md:col-span-2">
                                                    <label class="block text-xs text-text-secondary mb-1.5">Imagen</label>
                                                    <div id="promo-imagen-container-{{ $promoId }}">
                                                        @if(isset($promo['imagen']) && !empty($promo['imagen']))
                                                            <div class="relative inline-block">
                                                                <img src="{{ asset($promo['imagen']) }}" 
                                                                     alt="{{ $promo['nombre'] ?? 'Promoción' }}"
                                                                     class="w-20 h-20 object-cover rounded-lg border border-accent-gray"
                                                                     id="promo-imagen-{{ $promoId }}">
                                                                <button type="button"
                                                                        onclick="removePromocionImagen({{ $promoId }}, '{{ $promo['imagen'] }}')"
                                                                        class="absolute -top-2 -right-2 bg-danger hover:brightness-95 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">
                                                                    ×
                                                                </button>
                                                            </div>
                                                        @else
                                                            <label class="cursor-pointer">
                                                                <input type="file" 
                                                                       accept="image/jpeg,image/png,image/jpg,image/gif"
                                                                       class="hidden"
                                                                       onchange="uploadPromocionImagen(event, {{ $promoId }})"
                                                                       id="promo-imagen-input-{{ $promoId }}">
                                                                <span class="inline-flex items-center px-3 py-2 bg-accent-blue hover:bg-accent-blue text-white text-sm font-medium rounded-lg transition-colors cursor-pointer">
                                                                    📷 Subir
                                                                </span>
                                                            </label>
                                                        @endif
                                                    </div>
                                                </div>
                                                
                                                <!-- Botón Eliminar -->
                                                <div class="md:col-span-1 flex items-end">
                                                    <button type="button" 
                                                            onclick="removePromocion({{ $promoId }})"
                                                            class="w-full bg-danger hover:brightness-95 text-white px-3 py-2 rounded-lg text-sm transition-colors">
                                                        🗑️
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            
                            <p class="text-xs text-text-secondary mt-4">
                                💡 Las promociones aparecerán como una categoría especial en el menú del chat.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Atajos y Reseñas van a lo ANCHO, fuera de la grilla de dos columnas.
                 Estaban en la columna izquierda y ahí sobraba la mitad de la pantalla:
                 la derecha tiene una sola tarjeta. Además, en media columna los inputs
                 de los atajos quedaban tan angostos que la frase salía cortada. --}}

            {{-- ⚡ Atajos: los botones que el cliente toca en vez de escribir.
                 Llevan la frase que se VE y la pregunta que EJECUTA, que pueden
                 ser distintas ("Meriendas para compartir" puede disparar "¿qué
                 tienen para merendar entre dos?"). Si no se configura ninguno,
                 siguen saliendo automáticos del menú. --}}
            @if($comercio->tieneFeature('analytics'))
                @php
                    // En el orden en que los ve el cliente: primero la portada
                    // (antes de entrar), después los del chat.
                    $superficies = [
                        'portada' => ['titulo' => 'Atajos de la portada', 'ayuda' => 'Lo primero que ve el cliente, antes de entrar al chat.'],
                        'chat' => ['titulo' => 'Atajos del chat', 'ayuda' => 'Aparecen debajo del saludo, antes del primer mensaje.'],
                    ];
                    // Emojis de gastronomía para el selector: escribirlos a mano
                    // es incómodo y en escritorio hay que acordarse de Win + .
                    $emojisSugeridos = ['✨','🔥','⭐','🌾','🌱','🥗','🍕','🍔','🌭','🥪','🌮','🍟','🍝','🍜','🥘','🍳','🥐','🥖','🧀','🥩','🍗','🐟','🦐','🥑','🍰','🧁','🍦','🍪','☕','🧉','🍺','🍷','🥤','🧃','🍹','👨‍🍳','🎉','❤️'];
                @endphp
            <div class="editor-bloque">
                <h3 class="editor-bloque-titulo">Atajos</h3>

                <p class="text-xs text-text-secondary mb-4">
                    Los botones que el cliente toca en vez de escribir. Podés poner una frase corta
                    y que dispare otra pregunta. Si no cargás ninguno, se arman solos con tu menú.
                </p>

                {{-- Cada pantalla en su propia tarjeta. Antes los dos grupos iban
                     uno abajo del otro sin nada que los separara y se leían como una
                     sola lista larga: se editaban los de la portada creyendo que
                     eran los del chat. Son dos pantallas distintas del cliente y se
                     miden por separado, así que también se ven por separado. --}}
                    {{-- Los dos grupos lado a lado: a lo ancho entran, y así se compara
                         de un vistazo lo que ve el cliente antes de entrar contra lo que
                         ve una vez adentro. --}}
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                    @foreach($superficies as $superficie => $info)
                    @php $lista = $comercio->chipsDe($superficie); @endphp
                    <div class="p-4 bg-secondary-dark/60 border border-accent-gray rounded-xl"
                         data-superficie="{{ $superficie }}" data-maximo="{{ \App\Support\Chip::visibles($superficie) }}"
                         data-preview="{{ $superficie === 'portada' ? $previaEditor('menu.public') : $previaEditor('menu.chat') }}"
                         data-preview-titulo="{{ $superficie === 'portada' ? 'Atajos de la portada' : 'Atajos del chat' }}">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <p class="text-sm font-semibold text-text-primary">{{ $info['titulo'] }}</p>
                            {{-- Lo actualiza el JS al agregar o quitar: si fuera solo
                                 el valor del servidor, mentiría apenas tocás una fila. --}}
                            <span class="text-xs text-text-secondary/70 flex-none chips-contador">
                                {{ count($lista) }}/{{ \App\Support\Chip::visibles($superficie) }}
                            </span>
                        </div>
                        <p class="text-xs text-text-secondary/70 mb-3">
                            {{ $info['ayuda'] }}
                            @if(empty($comercio->chips[$superficie] ?? null))
                                <span class="text-accent-blue">Ahora son automáticos.</span>
                            @endif
                        </p>

                        <div class="space-y-2 chips-lista">
                            @foreach($lista as $chip)
                                {{-- Los cuatro controles miden lo mismo (h-10) y van centrados:
                                     el emoji era 48×40, los inputs 38 por el padding y la × 32×36,
                                     así que cada fila terminaba en tres alturas escalonadas y dos
                                     botones de proporciones distintas. --}}
                                <div class="chip-fila flex gap-2 items-center">
                                    {{-- El emoji se elige de una grilla: tipearlo requiere
                                         acordarse de Win + . y en el celular es peor. --}}
                                    <button type="button" onclick="abrirEmojis(this)"
                                            class="chip-emoji-btn w-10 h-10 flex-none bg-secondary-dark border border-accent-gray rounded-lg text-lg leading-none hover:border-accent-blue"
                                            title="Elegir ícono">{{ $chip['emoji'] ?: '🍽️' }}</button>
                                    <input type="hidden" value="{{ $chip['emoji'] }}" class="chip-emoji">
                                    <input type="text" value="{{ $chip['label'] }}" maxlength="40" placeholder="Lo que se ve"
                                           class="chip-label flex-1 min-w-0 h-10 px-3 bg-secondary-dark border border-accent-gray rounded-lg text-sm text-text-primary">
                                    <input type="text" value="{{ $chip['q'] }}" maxlength="200" placeholder="La pregunta que ejecuta"
                                           class="chip-q flex-1 min-w-0 h-10 px-3 bg-secondary-dark border border-accent-gray rounded-lg text-sm text-text-primary">
                                    <button type="button" onclick="quitarChip(this)"
                                            class="flex-none w-10 h-10 rounded-lg text-text-secondary hover:text-danger hover:bg-danger/10" title="Quitar">×</button>
                                </div>
                            @endforeach
                        </div>

                        {{-- mt-3 y no mt-2: arriba de la lista hay 12px (el mb-3 de la ayuda),
                             así que con 8px abajo el bloque quedaba descentrado. --}}
                        <div class="flex items-center gap-2 mt-3">
                            <button type="button" onclick="agregarChip(this)"
                                    class="text-xs px-3 py-1.5 rounded-lg border border-accent-gray text-text-secondary hover:border-accent-blue hover:text-accent-blue">+ Agregar</button>
                            <button type="button" onclick="guardarChips(this)"
                                    class="text-xs px-3 py-1.5 rounded-lg bg-accent-blue/20 border border-accent-blue text-accent-blue hover:bg-accent-blue/30">Guardar atajos</button>
                            <span class="text-xs text-text-secondary chips-estado"></span>
                        </div>
                    </div>
                    @endforeach
                    </div>

                {{-- Grilla de emojis, una sola para toda la pantalla: se abre
                     al lado del botón que la pidió. --}}
                <div id="emoji-picker" class="hidden fixed z-50 bg-secondary-dark border border-accent-gray rounded-xl p-2 shadow-2xl"
                     style="max-width: 260px">
                    <div class="grid grid-cols-8 gap-1">
                        @foreach($emojisSugeridos as $emoji)
                            <button type="button" onclick="elegirEmoji(@js($emoji))"
                                    class="w-7 h-7 rounded hover:bg-accent-blue/20 text-lg leading-none">{{ $emoji }}</button>
                        @endforeach
                    </div>
                    <button type="button" onclick="elegirEmoji('')"
                            class="w-full mt-1 text-xs text-text-secondary hover:text-danger py-1">Sin ícono</button>
                </div>
            </div>

            {{-- Reseñas de Google. El interruptor vive acá, junto a los atajos,
                 porque es donde el comercio configura lo que ve el cliente; pero
                 el dato se guarda en `comercios`, no en el menú: el link es de la
                 ficha del negocio y no se tiene que perder al subir otra carta.

                 El campo del link también está acá. Estaba en "datos del comercio", y
                 eso dejaba el interruptor apagado con un cartel que mandaba a otra
                 pantalla a buscar lo único que lo habilita. --}}
            @php $tieneLink = filled($comercio->google_review_url); @endphp

            <div class="editor-bloque" id="bloque-resenas"
                 data-preview="{{ $previaEditor('menu.chat') }}" data-preview-titulo="El pedido de reseña">
                <div class="flex items-center gap-2 mb-1">
                    <h3 class="editor-bloque-titulo !mb-0">Reseñas en Google</h3>
                    {{-- En el celular de al lado el cartel se ve SIEMPRE y al instante, y eso
                         no es lo que le pasa al cliente. Sin decirlo, el comercio piensa que
                         a todos les aparece apenas entran. --}}
                    <x-ayuda titulo="Cómo se ve esto de verdad"
                             texto="En la vista previa de al lado el cartel aparece siempre y al toque, para que puedas ver los textos mientras los escribís. Al cliente sólo le aparece si usó el asistente y ya lleva un rato en la mesa, cuando vuelve a mirar el celular. Y a quien lo cierra con «Ahora no» no se le vuelve a mostrar por 15 días; en la vista previa reaparece cada vez."
                             admin="Umbrales en config/servime.php (servime.resenas: mensajes, minutos, dias_gracia), iguales para todos los comercios. La vista previa llama directo a mostrar() y no guarda estado en localStorage, así no le apaga el pedido real al dueño en ese teléfono." />
                </div>

                <p class="text-xs text-text-secondary mb-4">
                    Al cliente que usó el asistente y ya lleva un rato en la mesa se le
                    ofrece dejar su reseña. Aparece cuando vuelve a mirar el celular.
                </p>

                <div class="mb-3">
                    <label for="google-review-url" class="block text-sm font-medium text-text-secondary mb-2">
                        Link de reseñas de Google
                    </label>
                    <div class="flex flex-wrap items-center gap-2">
                        <input type="url"
                               id="google-review-url"
                               maxlength="255"
                               value="{{ $comercio->google_review_url }}"
                               placeholder="https://g.page/r/..."
                               class="flex-1 min-w-[16rem] h-10 px-3 bg-secondary-dark border border-accent-gray rounded-lg text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent transition-all duration-200">
                        {{-- h-10 como el input que tiene al lado: con py-1.5 quedaba 10px
                             más bajo y la fila se veía desparejo. --}}
                        <button type="button" onclick="guardarLinkResenas(this)"
                                class="flex-none h-10 text-xs px-4 rounded-lg bg-accent-blue/20 border border-accent-blue text-accent-blue hover:bg-accent-blue/30">
                            Guardar link
                        </button>
                        <span class="text-xs text-text-secondary" id="link-resenas-estado"></span>
                    </div>
                    <p class="text-xs text-text-secondary/70 mt-1">
                        Se saca del perfil del negocio en Google, en «Pedir reseñas».
                    </p>
                </div>

                <div id="resenas-caja"
                     class="flex items-start gap-3 p-3 rounded-lg bg-secondary-dark border border-accent-gray
                            {{ $tieneLink ? '' : 'opacity-60' }}">
                    <button type="button"
                            id="resenas-switch"
                            role="switch"
                            aria-checked="{{ $comercio->resenas_activo ? 'true' : 'false' }}"
                            aria-label="Pedir reseña en Google"
                            onclick="alternarResenas(this)"
                            @disabled(! $tieneLink)
                            class="relative flex-none w-11 h-6 rounded-full transition-colors duration-200 disabled:cursor-not-allowed
                                   {{ $comercio->resenas_activo ? 'bg-accent-green' : 'bg-accent-gray' }}">
                        <span class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform duration-200
                                     {{ $comercio->resenas_activo ? 'translate-x-5' : '' }}"></span>
                    </button>
                    <div class="min-w-0">
                        <p class="text-sm text-text-primary leading-tight">Pedir reseña en Google</p>
                        <p class="text-xs text-text-secondary mt-1" id="resenas-ayuda">
                            @if(! $tieneLink)
                                Cargá el link acá arriba para poder activarlo.
                            @elseif($comercio->resenas_activo)
                                Activo — se le muestra a quien cumpla las condiciones.
                            @else
                                Listo para activar.
                            @endif
                        </p>
                    </div>
                </div>

                {{-- Los textos van vacíos cuando el comercio no los tocó, con el
                     de fábrica como placeholder: así se ve qué va a salir sin
                     tener que copiarlo, y borrar el campo vuelve al de fábrica. --}}
                <div class="mt-3 space-y-2">
                    <input type="text" id="resena-titulo" maxlength="80"
                           value="{{ $comercio->resena_titulo }}"
                           placeholder="{{ \App\Models\Comercio::RESENA_TITULO }}"
                           class="w-full px-3 py-2 bg-secondary-dark border border-accent-gray rounded-lg text-sm text-text-primary">
                    <textarea id="resena-texto" maxlength="200" rows="2"
                              placeholder="{{ \App\Models\Comercio::RESENA_TEXTO }}"
                              class="w-full px-3 py-2 bg-secondary-dark border border-accent-gray rounded-lg text-sm text-text-primary resize-none">{{ $comercio->resena_texto }}</textarea>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="guardarTextosResena(this)"
                                class="text-xs px-3 py-1.5 rounded-lg bg-accent-blue/20 border border-accent-blue text-accent-blue hover:bg-accent-blue/30">Guardar textos</button>
                        <span class="text-xs text-text-secondary" id="resena-estado"></span>
                    </div>
                    <p class="text-xs text-text-secondary/70">
                        Vacío = se usa el texto por defecto. Ojo: lo que escribas se
                        muestra tal cual, sin traducir al idioma del cliente.
                    </p>
                </div>

                {{-- Los números NO van acá: esta pantalla es para configurar.
                     Cuántos lo vieron y cuántos lo tocaron se miran en Analytics,
                     junto al resto de las métricas del menú. --}}
            </div>

            @endif

            {{-- Pedidos en Línea queda FUERA del formulario a propósito: el interruptor se
                 guarda solo por AJAX y no viaja en el submit. Adentro, y justo encima del
                 botón, parecía que había que apretar "Procesar" para confirmarlo. --}}
            @if($isEdit)
                                <div id="pedido-card" class="relative overflow-hidden rounded-2xl mb-8 border pedido-card {{ old('pedido_habilitado', $comercio->pedido_habilitado) ? 'bg-accent-green/10 border-accent-green/40 shadow-lg shadow-accent-green/10' : 'bg-secondary-dark border-accent-gray shadow-lg' }} transition-all duration-300 hover:shadow-xl">
                                    <div class="relative p-6">
                                        <!-- Texto arriba -->
                                        <div class="mb-5">
                                            <div class="flex items-center gap-3 mb-2">
                                                <h3 class="text-text-primary font-bold text-xl tracking-tight">Pedidos en Línea</h3>
                                                @if(old('pedido_habilitado', $comercio->pedido_habilitado))
                                                    <span class="pedido-active-badge inline-flex items-center gap-1.5 px-2.5 py-1 bg-accent-green/15 backdrop-blur-sm border border-accent-green/40 rounded-lg">
                                                        <span class="pedido-status-dot w-1.5 h-1.5 bg-accent-green rounded-full animate-pulse"></span>
                                                        <span class="text-xs font-semibold text-accent-green">ACTIVO</span>
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="text-text-secondary text-sm leading-relaxed">Los clientes podrán agregar platos al pedido directamente desde el menú digital</p>
                                        </div>
                                        
                                        <!-- Icono y botón abajo -->
                                        <div class="flex items-center justify-between gap-4 pt-4 border-t border-accent-gray">
                                            <div class="relative flex-shrink-0">
                                                <div class="pedido-icon-container w-14 h-14 {{ old('pedido_habilitado', $comercio->pedido_habilitado) ? 'bg-accent-green hover:brightness-95 shadow-lg shadow-green-500/50' : 'bg-accent-gray hover:brightness-95' }} rounded-xl flex items-center justify-center transform transition-all duration-300 hover:scale-110 hover:rotate-3">
                                                    <span class="text-2xl filter drop-shadow-lg">🛒</span>
                                                    @if(old('pedido_habilitado', $comercio->pedido_habilitado))
                                                        <div class="pedido-status-dot absolute -top-1 -right-1 w-4 h-4 bg-accent-green rounded-full border-3 border-secondary-dark animate-pulse"></div>
                                                    @endif
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-center gap-4 flex-1 justify-end">
                                                <label class="relative inline-flex items-center cursor-pointer group">
                                                    <input type="checkbox"
                                                           id="pedido_habilitado"
                                                           value="1"
                                                           {{ old('pedido_habilitado', $comercio->pedido_habilitado) ? 'checked' : '' }}
                                                           class="sr-only peer pedido-toggle"
                                                           onchange="updatePedidoStatus(this.checked);">
                                                    <div class="w-16 h-9 bg-accent-gray peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-accent-blue/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-1 after:left-[5px] after:bg-white after:border-accent-gray after:border-2 after:rounded-full after:h-7 after:w-7 after:transition-all after:shadow-lg peer-checked:bg-accent-green peer-checked:shadow-lg peer-checked:shadow-green-500/50 transition-all duration-300"></div>
                                                </label>
                                                <div id="pedido-status" class="flex items-center justify-center min-w-[100px] h-9 px-4 rounded-xl font-semibold text-sm transition-all duration-300 {{ old('pedido_habilitado', $comercio->pedido_habilitado) ? 'bg-accent-green/15 text-accent-green border-2 border-accent-green/40 backdrop-blur-sm' : 'bg-accent-gray/30 text-text-secondary border-2 border-accent-gray/30' }}">
                                                    {{ old('pedido_habilitado', $comercio->pedido_habilitado) ? '✓ Activo' : '○ Inactivo' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                {{-- Sugerencias: "va bien con" debajo de cada plato y "parecidos" al abrirlo.
                     Encendidas por defecto; el plan decide si existen y el comercio, si las
                     muestra. Se guarda solo, igual que Pedidos en Línea. --}}
                @if($comercio->tieneFeature('sugerencias'))
                    <div class="rounded-2xl mb-8 border border-accent-gray bg-secondary-dark shadow-lg p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-text-primary font-bold text-xl tracking-tight">Sugerencias a tus clientes</h3>
                                <p class="text-text-secondary text-sm leading-relaxed mt-1">
                                    Debajo de cada plato, la carta muestra con qué va bien (por ejemplo, papas con la
                                    hamburguesa) y, al abrirlo, otros platos parecidos. Las arma la IA cuando procesás la carta.
                                </p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer flex-none mt-1">
                                <input type="checkbox"
                                       id="sugerencias_activas"
                                       class="sr-only peer"
                                       {{ $comercio->sugerencias_activas !== false ? 'checked' : '' }}
                                       onchange="guardarSugerenciasActivas(this)">
                                <div class="w-16 h-9 bg-accent-gray peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-accent-blue/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-1 after:left-[5px] after:bg-white after:border-accent-gray after:border-2 after:rounded-full after:h-7 after:w-7 after:transition-all after:shadow-lg peer-checked:bg-accent-green peer-checked:shadow-lg peer-checked:shadow-green-500/50 transition-all duration-300"></div>
                            </label>
                        </div>
                    </div>
                @endif
            @endif
                </x-editor.seccion>

            </div>

                <x-editor.seccion id="archivo" titulo="Archivo del menú" icono="📄" resumen="Nombre del menú y archivo original" :preview="$previaEditor('menu.classic', ['vista' => 'archivo'])" preview-titulo="Tu archivo original" :sin-cabecera="! $isEdit" :abierta="! $isEdit">
            {{-- El nombre del menú y su archivo.

                 Al CREAR es el formulario que manda el archivo a la IA (MenuController@store).

                 Al EDITAR ya no se puede reemplazar el archivo: se sube una carta nueva. Reprocesar
                 encima de la misma pisaba en silencio todo lo trabajado a mano —precios,
                 descripciones, etiquetas, estrellas— y borraba las promociones, que viven en el
                 mismo JSON. Con una carta nueva la vieja queda intacta, y los clientes no ven nada
                 a medio procesar: la nueva es un borrador hasta que se publica.

                 Por eso en edición esto no es un <form>: todo lo de acá se guarda solo, y con un
                 form un Enter en el nombre lo enviaba. --}}
            @if($isEdit)
                <div class="editor-bloque space-y-6">
            @else
                <form method="POST" action="{{ route('menus.store', $comercio) }}" enctype="multipart/form-data" class="metric-card space-y-6" id="menu-form">
                    @csrf
            @endif
                            <!-- Nombre del Menú -->
                    <div class="mb-6">
                                <label for="nombre" class="block text-sm font-medium text-text-secondary mb-3">
                                    Nombre del Menú <span class="text-danger">*</span>
                                </label>
                                {{-- En edición se guarda solo al salir del campo: ponerle otro
                                     nombre a un menú no tiene por qué disparar el procesamiento
                                     con IA, que tarda y cuesta. Al crear todavía no existe la
                                     fila, así que ahí viaja con el formulario. --}}
                                <input type="text"
                                       id="nombre"
                                       name="nombre"
                                       value="{{ old('nombre', $menu->nombre ?? '') }}"
                                       required
                                       @if($isEdit && $menu)
                                           data-guardar-en="{{ route('menus.update.nombre', [$comercio, $menu]) }}"
                                       @endif
                                       placeholder="Ej: Carta principal, Menú de la noche…"
                                       class="w-full px-4 py-3 bg-secondary-dark border border-accent-gray rounded-xl text-text-primary placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent transition-all duration-200">
                                <p id="nombre-estado" class="text-xs text-text-secondary mt-2"></p>
                                <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                            </div>

                            <!-- Menú Actual (solo en edición) -->
                            @if($isEdit && $menu && $menu->archivo_original)
                                <div class="bg-secondary-dark border border-accent-gray rounded-xl p-6 mb-6 shadow-lg hover:shadow-xl transition-shadow duration-300">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div class="flex items-center space-x-4 flex-1 min-w-0">
                                            @if($menu->formato === 'pdf')
                                                <div class="w-14 h-14 bg-danger/20 border border-danger/40 rounded-xl flex items-center justify-center flex-shrink-0 shadow-md">
                                                    <span class="text-danger text-2xl">📄</span>
                                                </div>
                                            @else
                                                <div class="w-14 h-14 bg-accent-blue/10 border border-accent-blue/30 rounded-xl flex items-center justify-center flex-shrink-0 shadow-md">
                                                    <span class="text-accent-blue text-2xl">🍽️</span>
                                                </div>
                                            @endif
                                            <div class="min-w-0 flex-1">
                                                <h4 class="text-text-primary font-semibold text-lg mb-1">Menú Actual</h4>
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-accent-green/90 text-xs bg-accent-gray/20 px-2 py-1 rounded-md font-medium">
                                                        {{ $menu->updated_at->format('d/m/Y') }}
                                                    </span>
                                                    <span class="text-accent-green/90 text-xs bg-accent-gray/20 px-2 py-1 rounded-md font-medium">
                                                        {{ $menu->updated_at->format('H:i') }}
                                                    </span>
                                            </div>
                                        </div>
                                        </div>
                                        <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 flex-shrink-0">
                                            <button type="button"
                                                    onclick="openFileModal('{{ asset('storage/' . $menu->archivo_original) }}', '{{ $menu->formato }}')"
                                                    class="px-4 py-2.5 bg-accent-blue/10 hover:bg-accent-blue/20 text-accent-blue border border-accent-blue/20 rounded-xl text-sm font-medium transition-all duration-200 whitespace-nowrap">
                                                👁️ Ver
                                            </button>
                                            <a href="{{ asset('storage/' . $menu->archivo_original) }}" 
                                               download
                                               class="px-4 py-2.5 bg-accent-gray/30 hover:bg-accent-gray/50 text-text-primary border border-accent-gray rounded-xl text-sm font-medium transition-colors whitespace-nowrap flex items-center justify-center gap-2">
                                                <span>📥</span>
                                                <span>Descargar</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endif

                    @if($isEdit)
                        <div class="bg-secondary-dark border border-accent-gray rounded-xl p-6">
                            <h4 class="text-text-primary font-semibold text-lg mb-1">¿Cambió tu carta?</h4>
                            <p class="text-sm text-text-secondary mb-4">
                                Subila como una carta nueva. Esta queda como está, con sus fotos,
                                promociones y platos estrella, y la nueva no la ven tus clientes
                                hasta que la publiques.
                            </p>
                            <a href="{{ route('menus.create', $comercio) }}" class="btn-secondary inline-flex">
                                Subir una carta nueva
                            </a>
                        </div>
                    @else
                        <div class="bg-secondary-dark border border-accent-gray rounded-xl p-6">
                            <label for="archivo" class="block text-sm font-medium text-text-secondary mb-3">
                                Archivo del Menú
                            </label>
                                <input type="file"
                                       id="archivo"
                                       name="archivo"
                                       accept=".pdf,.jpeg,.jpg,.png,.gif"
                                       required
                                       class="w-full px-4 py-3 bg-background border border-accent-gray rounded-xl text-text-primary file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-accent-blue file:text-black hover:file:opacity-80 focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent transition-all duration-200" />
                                <x-input-error :messages="$errors->get('archivo')" class="mt-2" />
                            <p class="mt-2 text-sm text-text-secondary">
                                PDF o foto (JPG, PNG, GIF), hasta 10 MB. La IA lee los platos, las
                                categorías y los precios: tarda entre medio minuto y un par de minutos.
                            </p>

                            {{-- El botón arranca apagado y se prende cuando están el nombre y el
                                 archivo: así nadie lo aprieta para que le diga qué falta. --}}
                            <div class="mt-6 flex flex-col sm:flex-row sm:items-center sm:justify-end gap-3">
                                <p id="procesar-ayuda" class="text-xs text-text-secondary sm:text-right sm:flex-1"></p>
                                {{-- Sin style inline: el color y el hover salen del CSS de arriba,
                                     que es el único lugar donde se puede apagar. --}}
                                <button type="submit"
                                        id="btn-actualizar-menu"
                                        class="btn-actualizar-menu text-white font-bold text-lg py-4 px-10 rounded-xl transition-all duration-300 shadow-2xl hover:shadow-accent-green/40 hover:scale-105 active:scale-95 transform flex items-center justify-center gap-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span>Crear y procesar menú</span>
                                </button>
                            </div>
                        </div>
                    @endif

            @if($isEdit)
                </div>
            @else
                </form>
            @endif
                </x-editor.seccion>

            </div>{{-- /#editor-secciones --}}

            @if($isEdit)
                {{-- El celular de la vista previa: la pantalla pública REAL del comercio, la que
                     corresponde a la sección abierta, cargada en modo vista previa (no suma
                     estadísticas ni gasta IA). Se recarga sola después de cada guardado. --}}
                <aside class="hidden lg:block lg:sticky lg:top-44" aria-label="Vista previa del menú">
                    {{-- El celular carga la página a 390×844 —un teléfono real— y se achica
                         con `scale` para entrar en la columna. Antes el iframe medía 296px de
                         ancho, y a ese ancho el menú corta los textos y apila los botones de
                         otra manera: la vista previa mostraba algo que ningún cliente veía.
                         296/390 = 0,759, y 844 × 0,759 = 641px de alto ya escalado. --}}
                    <div class="mx-auto w-[320px] h-[665px] rounded-[2.75rem] bg-text-primary p-3 shadow-xl">
                      <div class="relative w-full h-full overflow-hidden rounded-[2.1rem] bg-secondary-dark">
                        <iframe id="previewFrame"
                                style="width: 390px; height: 844px; transform: scale(0.759); transform-origin: top left;"
                                src="{{ $previaEditor('menu.classic', ['vista' => 'lista']) }}"
                                data-src="{{ $previaEditor('menu.classic', ['vista' => 'lista']) }}"
                                title="Vista previa de lo que ve tu cliente"
                                class="absolute top-0 left-0 border-0 bg-secondary-dark"></iframe>
                      </div>
                    </div>
                    <p class="text-center text-sm font-medium text-text-primary mt-4" id="previewTitulo">Así ven tu carta</p>
                    <p class="text-center text-xs text-text-secondary mt-1">Se actualiza sola cuando guardás un cambio.</p>
                </aside>
            @endif
            </div>

        </div>
    </div>

    @if($isEdit)
    <script>
        // ── Editor en secciones + celular de vista previa ─────────────────────────────
        (function () {
            const secciones = Array.from(document.querySelectorAll('.editor-seccion'));
            const frame = document.getElementById('previewFrame');
            const tituloPrevia = document.getElementById('previewTitulo');
            const CLAVE = 'servime_editor_seccion_{{ $menu?->id }}';

            const cuerpoDe = (sec) => sec.querySelector('.editor-seccion-cuerpo');
            const estaAbierta = (sec) => !cuerpoDe(sec).classList.contains('hidden');

            function mostrarEnCelular(origen) {
                if (!frame || !origen || !origen.dataset.preview) return;
                // Sólo si cambia la pantalla: recargar la misma al abrir otra sección que
                // también usa el chat hacía parpadear el celular sin motivo.
                if (frame.dataset.src !== origen.dataset.preview) {
                    frame.dataset.src = origen.dataset.preview;
                    frame.src = origen.dataset.preview;
                }
                if (tituloPrevia) tituloPrevia.textContent = origen.dataset.previewTitulo || 'Así lo ve tu cliente';
            }

            // Un bloque puede mostrar una pantalla distinta a la de su sección: los atajos de
            // la portada se ven al escanear el QR, no en el chat, y editándolos con el chat en
            // el celular no se veía lo que se estaba cambiando. Gana el bloque más cercano a
            // lo que se toca; si no declara ninguna, queda la pantalla de la sección.
            const secciones_ = document.getElementById('editor-secciones');
            ['focusin', 'click'].forEach((evento) => {
                secciones_?.addEventListener(evento, (e) => {
                    if (e.target.closest('.editor-seccion-cabecera')) return;   // eso lo maneja la sección
                    const bloque = e.target.closest('[data-preview]:not(.editor-seccion)');
                    if (bloque) mostrarEnCelular(bloque);
                });
            });

            function marcar(sec, abrir) {
                cuerpoDe(sec).classList.toggle('hidden', !abrir);
                sec.querySelector('.editor-seccion-cabecera').setAttribute('aria-expanded', abrir ? 'true' : 'false');
                sec.querySelector('.editor-seccion-flecha')?.classList.toggle('rotate-180', abrir);
            }

            function abrir(sec, { recordar = true, desplazar = true } = {}) {
                // Una abierta a la vez: la pantalla queda corta y el celular sabe qué mostrar.
                secciones.forEach((s) => marcar(s, s === sec));
                mostrarEnCelular(sec);
                if (recordar) { try { localStorage.setItem(CLAVE, sec.dataset.seccion); } catch (e) {} }
                if (desplazar) sec.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            secciones.forEach((sec) => {
                sec.querySelector('.editor-seccion-cabecera').addEventListener('click', () => {
                    if (estaAbierta(sec)) {
                        marcar(sec, false);
                        try { localStorage.removeItem(CLAVE); } catch (e) {}
                    } else {
                        abrir(sec);
                    }
                });
            });

            // Al volver a la pantalla (por ejemplo después de guardar el formulario, que recarga)
            // queda abierta la sección en la que estaba: si no, volvía siempre a los platos.
            let guardada = null;
            try { guardada = localStorage.getItem(CLAVE); } catch (e) {}
            const porHash = location.hash.startsWith('#seccion-') ? document.querySelector(location.hash) : null;
            const inicial = porHash || secciones.find((s) => s.dataset.seccion === guardada);
            if (inicial) {
                abrir(inicial, { recordar: false, desplazar: !!porHash });
            } else {
                const yaAbierta = secciones.find(estaAbierta);
                if (yaAbierta) mostrarEnCelular(yaAbierta);
            }

            // ── Categorías de platos: una abierta a la vez ──────────────────────────────────
            const CLAVE_CATEGORIA = 'servime_editor_categoria_{{ $menu?->id }}';

            function abrirCategoria(cat, abrir) {
                cat.querySelector('.editor-categoria-cuerpo').classList.toggle('hidden', !abrir);
                cat.querySelector('.editor-categoria-cabecera').setAttribute('aria-expanded', abrir ? 'true' : 'false');
                cat.querySelector('.editor-categoria-flecha').classList.toggle('-rotate-90', !abrir);
            }

            document.querySelectorAll('.editor-categoria').forEach((cat) => {
                cat.querySelector('.editor-categoria-cabecera').addEventListener('click', () => {
                    const abrir = cat.querySelector('.editor-categoria-cuerpo').classList.contains('hidden');
                    document.querySelectorAll('.editor-categoria').forEach((c) => abrirCategoria(c, c === cat && abrir));
                    try {
                        abrir ? localStorage.setItem(CLAVE_CATEGORIA, cat.dataset.categoriaEditor)
                              : localStorage.removeItem(CLAVE_CATEGORIA);
                    } catch (e) {}
                });
            });

            // Agregar, borrar o reordenar un plato recarga la pantalla: sin esto volvía todo
            // plegado y había que buscar de nuevo la categoría en la que se estaba trabajando.
            try {
                const guardadaCat = localStorage.getItem(CLAVE_CATEGORIA);
                const cat = guardadaCat !== null && document.querySelector(`.editor-categoria[data-categoria-editor="${guardadaCat}"]`);
                if (cat) abrirCategoria(cat, true);
            } catch (e) {}

            // ── Detalle de cada plato (foto, descripción, etiquetas) ─────────────────────────
            // Delegado: "sin descripción" y la flecha abren lo mismo.
            document.addEventListener('click', (e) => {
                const toggle = e.target.closest('.plato-detalle-toggle');
                if (!toggle) return;
                e.stopPropagation();
                const item = toggle.closest('.plato-item');
                const detalle = item.querySelector('.plato-detalle');
                const abrir = detalle.classList.contains('hidden');
                detalle.classList.toggle('hidden', !abrir);
                item.querySelectorAll('.plato-detalle-toggle[aria-expanded]').forEach((b) => b.setAttribute('aria-expanded', abrir ? 'true' : 'false'));
                item.querySelector('.plato-detalle-flecha')?.classList.toggle('rotate-180', abrir);
                // Desde "sin descripción" se va directo a escribirla.
                if (abrir && !toggle.hasAttribute('aria-expanded')) {
                    const [, cat, plato] = item.querySelector('[id^="descripcion-display-"]').id.match(/(\d+)-(\d+)$/);
                    if (typeof enableEditDescripcion === 'function') enableEditDescripcion(Number(cat), Number(plato));
                }
            });

            // Recargar el celular después de cada guardado. Todos los guardados de esta pantalla
            // van por fetch, así que se envuelve una sola vez en vez de tocar cada función.
            if (frame) {
                const fetchOriginal = window.fetch;
                let espera = null;
                window.fetch = function (input, init) {
                    const metodo = String((init && init.method) || (input && input.method) || 'GET').toUpperCase();
                    return fetchOriginal.apply(this, arguments).then((respuesta) => {
                        if (metodo !== 'GET' && respuesta.ok) {
                            clearTimeout(espera);
                            espera = setTimeout(() => {
                                try { frame.contentWindow.location.reload(); } catch (e) { frame.src = frame.dataset.src; }
                            }, 700);
                        }
                        return respuesta;
                    });
                };
            }
        })();
    </script>
    @endif

    
    <!-- SortableJS for drag & drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

    <!-- JavaScript para vista previa en tiempo real -->
    <script>
        
        // Función global para actualizar el estado visual del toggle de pedidos
        function updatePedidoStatus(isChecked) {
            const pedidoStatus = document.getElementById('pedido-status');
            const card = document.querySelector('.pedido-card') || document.querySelector('[class*="bg-gradient-to-br"][class*="from-green"]');
            
            if (pedidoStatus) {
                if (isChecked) {
                    pedidoStatus.textContent = '✓ Activo';
                    // Tokens, no `green-500`: la paleta genérica de Tailwind no está en la
                    // identidad y sobre el crema del panel ni siquiera se lee.
                    pedidoStatus.className = 'flex items-center justify-center min-w-[100px] h-9 px-4 rounded-xl font-semibold text-sm transition-all duration-300 bg-accent-green/15 text-accent-green border-2 border-accent-green/40 backdrop-blur-sm';
                } else {
                    pedidoStatus.textContent = '○ Inactivo';
                    pedidoStatus.className = 'flex items-center justify-center min-w-[100px] h-9 px-4 rounded-xl font-semibold text-sm transition-all duration-300 bg-accent-gray/30 text-text-secondary border-2 border-accent-gray/30';
                }
            }
            
            // Actualizar el card con animación
            if (card) {
                if (isChecked) {
                    card.classList.remove('bg-secondary-dark', 'border-accent-gray');
                    card.classList.add('bg-accent-green/10', 'border-accent-green/40', 'shadow-accent-green/10');
                } else {
                    card.classList.remove('bg-accent-green/10', 'border-accent-green/40', 'shadow-accent-green/10');
                    card.classList.add('bg-secondary-dark', 'border-accent-gray');
                }
            }

            // Y GUARDARLO. Hasta acá esta función sólo pintaba: el interruptor se movía,
            // se veía verde, y al recargar volvía atrás. El checkbox tampoco tenía `name`,
            // así que tampoco viajaba con el formulario — el pedido en línea no se podía
            // encender desde esta pantalla.
            guardarPedidoHabilitado(isChecked);
        }

        // Se guarda al tocar, como Pedidos en Línea. Si el servidor no lo guarda, el
        // interruptor vuelve a donde estaba: dejarlo movido sería mentirle al comercio.
        async function guardarSugerenciasActivas(input) {
            const activo = input.checked;

            try {
                const r = await fetch(`{{ route('menus.update.chatbot.config', ['comercio' => $comercio->slug]) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ sugerencias_activas: activo })
                });
                const data = await r.json();

                if (!data.success) {
                    throw new Error(data.error || 'no se pudo guardar');
                }
            } catch (e) {
                input.checked = !activo;
                showError('No se pudieron cambiar las sugerencias. Probá de nuevo.');
            }
        }

        async function guardarPedidoHabilitado(activo) {
            const estado = document.getElementById('pedido-status');
            const textoPrevio = estado ? estado.textContent : '';

            try {
                const r = await fetch(`{{ route('menus.update.chatbot.config', ['comercio' => $comercio->slug]) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ pedido_habilitado: activo })
                });
                const data = await r.json();

                if (!data.success) {
                    throw new Error(data.error || 'no se pudo guardar');
                }
            } catch (e) {
                // Se revierte lo que se pintó: dejar el interruptor en verde cuando el
                // servidor no lo guardó es peor que no dejarlo mover.
                const toggle = document.getElementById('pedido_habilitado');
                if (toggle) toggle.checked = !activo;
                if (estado) estado.textContent = textoPrevio;
                showError('No se pudo cambiar el pedido en línea. Probá de nuevo.');
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar estado si el toggle existe
            const pedidoToggle = document.querySelector('.pedido-toggle');
            if (pedidoToggle) {
                updatePedidoStatus(pedidoToggle.checked);
                pedidoToggle.addEventListener('change', function() {
                    updatePedidoStatus(this.checked);
                    savePedidoStatus(this.checked); // guarda en el server (acá sí está en scope)
                });
            }
            
            // Interceptar el input de archivo para procesar PDFs
            // El formulario sólo existe al CREAR un menú: en edición el archivo no se
            // reemplaza (se sube una carta nueva) y todo lo demás se guarda solo.
            const fileInput = document.getElementById('archivo');
            if (fileInput) {
                // Elegir (o sacar) el archivo es lo que prende y apaga el botón.
                fileInput.addEventListener('change', () => window.actualizarBotonProcesar());
            }

            const menuForm = document.getElementById('menu-form');
            if (menuForm) {
                let enviando = false;
                menuForm.addEventListener('submit', function (event) {
                    event.preventDefault();
                    // Un doble click mandaba el archivo dos veces: dos menús y dos llamadas a la IA.
                    if (enviando) return;
                    enviando = true;
                    LoadingManager.showGlobalLoader('Subiendo menú...');
                    menuForm.submit();
                });
            }
            
            // Aplicar acento elegido. Antes seteaba 4 colores por tema; ahora solo el
            // color de marca: el resto de la interfaz son neutros cálidos fijos.
            const ACENTOS = @json(collect(\App\Models\Comercio::ACENTOS)->map->color);

            function applyTheme(theme) {
                const color = ACENTOS[theme];
                if (!color) return;


                updateActiveSwatch(theme);
                saveThemeColors({ primary: color });
            }

            // El acento que salió de la portada. Es una SUGERENCIA: se aplica sólo si
            // el comercio toca el botón, porque la extracción por frecuencia suele
            // devolver la madera o la comida y no la marca.
            window.usarAcentoSugerido = function (hex) {

                // Puede coincidir con uno de los predefinidos o no; si no, quedan todos
                // sin marcar en vez de dejar marcado el anterior, que ya no es el vigente.
                const predefinido = Object.keys(ACENTOS).find(
                    k => ACENTOS[k].toLowerCase() === hex.toLowerCase()
                );

                updateActiveSwatch(predefinido || null);
                saveThemeColors({ primary: hex });
            };

            // Función para guardar los colores automáticamente
            async function saveThemeColors(colors) {
                try {
                    const url = '{{ route("menus.update.theme.colors", $comercio) }}';
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    
                    // Solo el color de marca: los otros tres ya no se usan en el menú
                    const formData = new FormData();
                    formData.append('primary_color', colors.primary);
                    formData.append('_token', token);
                    
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });
                    
                    let data;
                    try {
                        data = await response.json();
                    } catch (e) {
                        console.error('Error al parsear respuesta:', e);
                        throw new Error('Error al procesar la respuesta del servidor');
                    }
                    
                    if (response.ok && data.success) {
                        showTemporaryMessage('Tema guardado', 'success');
                    } else {
                        const errorMsg = data.error || data.message || (data.errors ? JSON.stringify(data.errors) : 'Error desconocido');
                        console.error('Error al guardar colores:', errorMsg);
                        showTemporaryMessage('Error al guardar el tema', 'error');
                    }
                } catch (error) {
                    console.error('Error al guardar colores:', error);
                    showTemporaryMessage('Error al guardar el tema', 'error');
                }
            }
            
            // Función para mostrar mensaje temporal
            function showTemporaryMessage(message, type) {
                // Crear elemento de mensaje si no existe
                let messageEl = document.getElementById('theme-save-message');
                if (!messageEl) {
                    messageEl = document.createElement('div');
                    messageEl.id = 'theme-save-message';
                    messageEl.className = 'fixed top-4 right-4 z-50 px-4 py-2 rounded-lg shadow-lg transition-opacity duration-300';
                    document.body.appendChild(messageEl);
                }
                
                // Estilos según el tipo
                if (type === 'success') {
                    messageEl.className = 'fixed top-4 right-4 z-50 px-4 py-2 rounded-lg shadow-lg bg-accent-green text-white';
                } else {
                    messageEl.className = 'fixed top-4 right-4 z-50 px-4 py-2 rounded-lg shadow-lg bg-danger text-white';
                }
                
                messageEl.textContent = message;
                messageEl.style.opacity = '1';
                
                // Ocultar después de 2 segundos
                setTimeout(() => {
                    messageEl.style.opacity = '0';
                    setTimeout(() => {
                        messageEl.style.display = 'none';
                    }, 300);
                }, 2000);
            }
            
            // Función genérica para mostrar notificaciones (usada en uploadPlatoImagen)
            // Hacerla global para que esté disponible en todos los bloques script
            window.showNotification = function(message, type = 'success') {
                // Crear elemento de notificación único
                const notificationId = 'notification-' + Date.now();
                const notification = document.createElement('div');
                notification.id = notificationId;
                
                // Estilos según el tipo
                if (type === 'success') {
                    notification.className = 'fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg bg-accent-green text-white flex items-center gap-2 animate-fade-in';
                } else {
                    notification.className = 'fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg bg-danger text-white flex items-center gap-2 animate-fade-in';
                }
                
                notification.innerHTML = `<span>${message}</span>`;
                
                document.body.appendChild(notification);
                
                // Mostrar con animación
                setTimeout(() => {
                    notification.style.opacity = '1';
                    notification.style.transform = 'translateX(0)';
                }, 10);
                
                // Ocultar después de 3 segundos
                setTimeout(() => {
                    notification.style.opacity = '0';
                    notification.style.transform = 'translateX(100%)';
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }, 3000);
            };
            
            
            // Función para actualizar el estado activo de los swatches
            function updateActiveSwatch(activeTheme) {
                document.querySelectorAll('.theme-swatch').forEach(swatch => {
                    const theme = swatch.dataset.theme;
                    if (theme === activeTheme) {
                        // Agregar anillo para el tema activo con color blanco/azul claro
                        swatch.classList.add('ring-2', 'ring-offset-2');
                        swatch.classList.add('ring-white', 'ring-opacity-80');
                        swatch.style.boxShadow = '0 0 0 2px rgba(255, 255, 255, 0.8), 0 0 0 4px rgba(245, 158, 11, 0.4)';
                        swatch.style.transform = 'scale(1.1)';
                    } else {
                        // Remover anillo de los temas no activos
                        swatch.classList.remove('ring-2', 'ring-offset-2', 'ring-white', 'ring-opacity-80');
                        swatch.style.boxShadow = '';
                        swatch.style.transform = '';
                    }
                });
            }
            
            // Event listeners para los swatches
            document.querySelectorAll('.theme-swatch').forEach(swatch => {
                swatch.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const theme = this.dataset.theme;
                    applyTheme(theme);
                    updateActiveSwatch(theme);
                });
            });
            
            // Marca el swatch del acento guardado. La clave la resuelve el modelo
            // (Comercio::acentoActual) contra Comercio::ACENTOS, así no hay un mapa
            // de colores duplicado acá que se desincronice.
            function initializeActiveTheme() {
                updateActiveSwatch(@json($comercio->acentoActual() ?? 'ambar'));
            }
            
            // Inicializar después de que se cargue el DOM
            setTimeout(initializeActiveTheme, 100);
            
            // Función para guardar configuración del chatbot automáticamente
            async function saveChatbotConfig() {
                try {
                    const url = '{{ route("menus.update.chatbot.config", $comercio) }}';
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    
                    const formData = new FormData();
                    formData.append('chatbot_name', document.getElementById('chatbot_name').value);
                    formData.append('welcome_message', document.getElementById('welcome_message').value);
                    formData.append('_token', token);

                    // Personalidad del asistente (chips + estrellas + detalles)
                    const tono = document.querySelector('input[name="cfg_tono"]:checked');
                    if (tono) formData.append('chatbot_config[tono]', tono.value);
                    const trato = document.querySelector('input[name="cfg_trato"]:checked');
                    if (trato) formData.append('chatbot_config[trato]', trato.value);
                    const emojis = document.querySelector('input[name="cfg_emojis"]:checked');
                    if (emojis) formData.append('chatbot_config[emojis]', emojis.value);
                    document.querySelectorAll('input[name="cfg_focos"]:checked').forEach(el => formData.append('chatbot_config[focos][]', el.value));
                    const det = document.getElementById('cfg_detalles');
                    if (det && det.value.trim()) formData.append('chatbot_config[detalles]', det.value.trim());
                    
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });
                    
                    let data;
                    try {
                        data = await response.json();
                    } catch (e) {
                        throw new Error('Error al procesar la respuesta del servidor');
                    }
                    
                    if (response.ok && data.success) {
                        showTemporaryMessage('Configuración del chatbot guardada', 'success');
                    } else {
                        const errorMsg = data.error || data.message || (data.errors ? JSON.stringify(data.errors) : 'Error desconocido');
                        console.error('Error al guardar configuración del chatbot:', errorMsg);
                        showTemporaryMessage('Error al guardar configuración', 'error');
                    }
                } catch (error) {
                    console.error('Error al guardar configuración del chatbot:', error);
                    showTemporaryMessage('Error al guardar configuración', 'error');
                }
            }
            
            // Función para guardar estado de pedidos automáticamente
            async function savePedidoStatus(isEnabled) {
                try {
                    const url = '{{ route("menus.update.chatbot.config", $comercio) }}';
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    
                    const formData = new FormData();
                    formData.append('pedido_habilitado', isEnabled ? '1' : '0');
                    formData.append('_token', token);
                    
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });
                    
                    let data;
                    try {
                        data = await response.json();
                    } catch (e) {
                        throw new Error('Error al procesar la respuesta del servidor');
                    }
                    
                    if (response.ok && data.success) {
                        showTemporaryMessage(isEnabled ? 'Pedidos en línea activados' : 'Pedidos en línea desactivados', 'success');
                    } else {
                        const errorMsg = data.error || data.message || (data.errors ? JSON.stringify(data.errors) : 'Error desconocido');
                        console.error('Error al guardar estado de pedidos:', errorMsg);
                        showTemporaryMessage('Error al guardar estado de pedidos', 'error');
                    }
                } catch (error) {
                    console.error('Error al guardar estado de pedidos:', error);
                    showTemporaryMessage('Error al guardar estado de pedidos', 'error');
                }
            }
            
            // El nombre y la bienvenida del asistente se guardan solos al dejar de escribir.
            let chatbotNameTimeout;
            document.getElementById('chatbot_name').addEventListener('input', function() {
                // Guardar automáticamente después de 1 segundo sin escribir
                clearTimeout(chatbotNameTimeout);
                chatbotNameTimeout = setTimeout(() => {
                    saveChatbotConfig();
                }, 1000);
            });
            
            let welcomeMessageTimeout;
            document.getElementById('welcome_message').addEventListener('input', function() {
                // Guardar automáticamente después de 1 segundo sin escribir
                clearTimeout(welcomeMessageTimeout);
                welcomeMessageTimeout = setTimeout(() => {
                    saveChatbotConfig();
                }, 1000);
            });
            
            // Auto-guardar la personalidad del asistente al cambiar
            document.querySelectorAll('input[name="cfg_tono"], input[name="cfg_trato"], input[name="cfg_emojis"], input[name="cfg_focos"]').forEach(el => {
                el.addEventListener('change', saveChatbotConfig);
            });
            const cfgDetalles = document.getElementById('cfg_detalles');
            if (cfgDetalles) {
                let cfgDetallesTimeout;
                cfgDetalles.addEventListener('input', function() {
                    clearTimeout(cfgDetallesTimeout);
                    cfgDetallesTimeout = setTimeout(saveChatbotConfig, 1000);
                });
            }

            // ── Atajos (chips) ────────────────────────────────────────────────────────
            // El tope sale de cada bloque porque no es el mismo en las dos pantallas: la
            // portada entra en una grilla de tres y el chat banca cuatro.
            // El contador del encabezado de cada tarjeta, en vivo.
            window.actualizarContadorChips = function(bloque) {
                const contador = bloque.querySelector('.chips-contador');
                if (!contador) return;
                const usados = bloque.querySelectorAll('.chip-fila').length;
                contador.textContent = `${usados}/${bloque.dataset.maximo}`;
            };

            window.quitarChip = function(btn) {
                const bloque = btn.closest('[data-superficie]');
                btn.closest('.chip-fila').remove();
                actualizarContadorChips(bloque);
            };

            window.agregarChip = function(btn) {
                const bloque = btn.closest('[data-superficie]');
                const lista = bloque.querySelector('.chips-lista');
                const maximo = parseInt(bloque.dataset.maximo, 10) || 4;

                if (lista.querySelectorAll('.chip-fila').length >= maximo) {
                    // showInfo y no showError: el comercio no hizo nada mal, llegó a un
                    // límite. Un cartel rojo que dice «Error» por usar bien la pantalla
                    // asusta al que recién empieza a configurar su menú.
                    showInfo(
                        `Acá entran ${maximo} atajos y ya los tenés puestos. Si querés sumar otro, ` +
                        `borrá alguno con la × y probá cuál funciona mejor.`,
                        'Ya llegaste al máximo'
                    );

                    return;
                }

                // ⚠ Estas clases son las mismas que las de la fila que imprime Blade más
                // arriba: si cambian allá, cambian acá. Con markup distinto, la fila recién
                // agregada queda con otra altura que las que ya estaban.
                const fila = document.createElement('div');
                fila.className = 'chip-fila flex gap-2 items-center';
                fila.innerHTML = `
                    <button type="button" onclick="abrirEmojis(this)" class="chip-emoji-btn w-10 h-10 flex-none bg-secondary-dark border border-accent-gray rounded-lg text-lg leading-none hover:border-accent-blue" title="Elegir ícono">🍽️</button>
                    <input type="hidden" class="chip-emoji">
                    <input type="text" maxlength="40" placeholder="Lo que se ve" class="chip-label flex-1 min-w-0 h-10 px-3 bg-secondary-dark border border-accent-gray rounded-lg text-sm text-text-primary">
                    <input type="text" maxlength="200" placeholder="La pregunta que ejecuta" class="chip-q flex-1 min-w-0 h-10 px-3 bg-secondary-dark border border-accent-gray rounded-lg text-sm text-text-primary">
                    <button type="button" onclick="quitarChip(this)" class="flex-none w-10 h-10 rounded-lg text-text-secondary hover:text-danger hover:bg-danger/10" title="Quitar">×</button>
                `;
                lista.appendChild(fila);
                actualizarContadorChips(bloque);
                fila.querySelector('.chip-label').focus();
            };

            // Selector de emoji: una sola grilla para toda la pantalla, se abre junto al
            // botón que la pidió y recuerda a cuál tiene que devolverle la elección.
            let emojiDestino = null;

            window.abrirEmojis = function(btn) {
                const picker = document.getElementById('emoji-picker');
                if (!picker) return;
                emojiDestino = btn;

                // El picker se cuelga del <body> la primera vez. Nace dentro de una tarjeta,
                // y un ancestro con `transform` hace que `position: fixed` se posicione contra
                // ESE elemento y no contra la ventana: la grilla terminaba fuera de la pantalla
                // y parecía que no abría. La .metric-card ya no se transforma en :hover, pero
                // esto se queda: cualquier ancestro con transform lo vuelve a romper.
                if (picker.parentElement !== document.body) {
                    document.body.appendChild(picker);
                }

                const r = btn.getBoundingClientRect();
                picker.classList.remove('hidden');
                // Si no entra abajo, se abre para arriba.
                const alto = picker.offsetHeight;
                picker.style.left = `${Math.max(8, Math.min(r.left, window.innerWidth - 270))}px`;
                picker.style.top = (r.bottom + alto + 8 > window.innerHeight)
                    ? `${Math.max(8, r.top - alto - 6)}px`
                    : `${r.bottom + 6}px`;
            };

            window.elegirEmoji = function(emoji) {
                if (emojiDestino) {
                    emojiDestino.textContent = emoji || '🍽️';
                    emojiDestino.closest('.chip-fila').querySelector('.chip-emoji').value = emoji;
                }
                document.getElementById('emoji-picker')?.classList.add('hidden');
                emojiDestino = null;
            };

            // Cerrar al tocar fuera
            document.addEventListener('click', function(e) {
                const picker = document.getElementById('emoji-picker');
                if (!picker || picker.classList.contains('hidden')) return;
                if (!picker.contains(e.target) && !e.target.closest('.chip-emoji-btn')) {
                    picker.classList.add('hidden');
                    emojiDestino = null;
                }
            });

            window.guardarChips = async function(btn) {
                const bloque = btn.closest('[data-superficie]');
                const estado = bloque.querySelector('.chips-estado');
                const chips = [...bloque.querySelectorAll('.chip-fila')].map(f => ({
                    emoji: f.querySelector('.chip-emoji').value.trim(),
                    label: f.querySelector('.chip-label').value.trim(),
                    q: f.querySelector('.chip-q').value.trim(),
                })).filter(c => c.label && c.q);

                // Una fila a medias no se guarda en silencio: o la completa o la saca.
                const incompletas = [...bloque.querySelectorAll('.chip-fila')].filter(f => {
                    const l = f.querySelector('.chip-label').value.trim();
                    const q = f.querySelector('.chip-q').value.trim();
                    return (l && !q) || (!l && q);
                });
                if (incompletas.length) {
                    showError('Cada atajo necesita la frase que se ve Y la pregunta que ejecuta');
                    return;
                }

                btn.disabled = true;
                estado.textContent = 'Guardando...';

                try {
                    const response = await fetch(`{{ route('menus.update.chips', ['comercio' => $comercio->slug]) }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ superficie: bloque.dataset.superficie, chips: chips })
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        showError(data.error || 'No se pudieron guardar los atajos');
                        estado.textContent = '';
                        return;
                    }

                    estado.textContent = data.automaticos ? 'Sin atajos propios: vuelven los automáticos' : '✓ Guardado';
                    showTemporaryMessage('Atajos guardados', 'success');
                    setTimeout(() => { estado.textContent = ''; }, 4000);
                } catch (error) {
                    console.error('Error al guardar atajos:', error);
                    showError('Error al guardar los atajos');
                    estado.textContent = '';
                } finally {
                    btn.disabled = false;
                }
            };

            // Prende/apaga el pedido de reseña. Guarda al toque, sin botón aparte: es un
            // solo booleano y esperar un "Guardar" para un interruptor confunde.
            // Con qué abre el menú clásico. Se guarda al toque, sin botón de confirmar:
            // es una preferencia de dos opciones y no hay nada que revisar antes.
            // ── El botón que procesa (sólo al crear) ───────────────────────────────
            //
            // Manda el archivo a la IA. Arranca apagado y se prende cuando están el nombre
            // y el archivo; apagado explica qué falta, así nadie lo aprieta para averiguarlo.
            // En edición no existe: el archivo no se reemplaza, se sube una carta nueva.
            window.actualizarBotonProcesar = function () {
                const boton = document.getElementById('btn-actualizar-menu');
                if (!boton) return;

                const ayuda = document.getElementById('procesar-ayuda');
                const campoNombre = document.getElementById('nombre');
                const campoArchivo = document.getElementById('archivo');

                const faltaNombre = campoNombre && campoNombre.value.trim() === '';
                const hayArchivo = !!(campoArchivo && campoArchivo.files && campoArchivo.files.length > 0);

                let motivo = '';
                if (faltaNombre) {
                    motivo = 'Poné un nombre para el menú.';
                } else if (!hayArchivo) {
                    motivo = 'Elegí el archivo del menú para poder procesarlo.';
                }

                boton.disabled = motivo !== '';
                boton.title = motivo;
                if (ayuda) ayuda.textContent = motivo;
            };

            // ── Nombre del menú ────────────────────────────────────────────────────
            //
            // Es obligatorio y se guarda solo.
            (function () {
                const campo = document.getElementById('nombre');
                const estado = document.getElementById('nombre-estado');
                if (!campo) return;

                let ultimoGuardado = campo.value.trim();

                function pintarFalta(falta) {
                    campo.classList.toggle('border-danger', falta);
                    campo.classList.toggle('border-accent-gray', !falta);
                    window.actualizarBotonProcesar();
                    if (falta) estado.textContent = 'Poné un nombre para identificar este menú.';
                }

                campo.addEventListener('input', () => {
                    const vacio = campo.value.trim() === '';
                    pintarFalta(vacio);
                    if (!vacio) estado.textContent = '';
                });

                campo.addEventListener('blur', async () => {
                    const valor = campo.value.trim();
                    const url = campo.dataset.guardarEn;

                    // Sin url estamos creando el menú: el nombre viaja con el formulario.
                    if (!url || valor === '' || valor === ultimoGuardado) return;

                    estado.textContent = 'Guardando…';
                    try {
                        const r = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ nombre: valor })
                        });
                        const data = await r.json();

                        if (data.success) {
                            ultimoGuardado = data.nombre;
                            estado.textContent = '✓ Guardado';
                            setTimeout(() => { if (estado.textContent === '✓ Guardado') estado.textContent = ''; }, 2500);
                        } else {
                            estado.textContent = data.error || 'No se pudo guardar el nombre.';
                        }
                    } catch (e) {
                        estado.textContent = 'No se pudo guardar el nombre. Revisá la conexión.';
                    }
                });

                pintarFalta(campo.value.trim() === '');
            })();

            // Estado inicial del botón, aunque el campo de nombre no esté en la pantalla.
            window.actualizarBotonProcesar();

            window.guardarVistaClasica = async function(vista) {
                const estado = document.getElementById('vista-clasica-estado');
                estado.textContent = 'Guardando…';

                try {
                    const r = await fetch(`{{ route('menus.update.vista.clasica', ['comercio' => $comercio->slug]) }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ vista: vista })
                    });
                    const data = await r.json();

                    estado.textContent = data.success
                        ? (vista === 'lista'
                            ? 'Listo: el menú clásico abre con la carta en lista.'
                            : 'Listo: el menú clásico abre con tu archivo.')
                        : (data.error || 'No se pudo guardar.');
                } catch (e) {
                    estado.textContent = 'No se pudo guardar. Probá de nuevo.';
                }
            };

            {{-- Igual que el bloque de reseñas, que sólo se renderiza con la feature del
                 plan. Sin este @if, el JS nombraba elementos que no existen y el panel
                 del plan básico venía con código muerto. --}}
            @if($comercio->tieneFeature('analytics'))
            window.alternarResenas = async function(btn) {
                const activo = btn.getAttribute('aria-checked') === 'true';
                const nuevo = !activo;
                const ayuda = document.getElementById('resenas-ayuda');
                const perilla = btn.querySelector('span');

                // Se pinta antes de que responda el servidor y se revierte si falla: el
                // interruptor tiene que sentirse inmediato.
                const pintar = (on) => {
                    btn.setAttribute('aria-checked', on ? 'true' : 'false');
                    btn.classList.toggle('bg-accent-green', on);
                    btn.classList.toggle('bg-accent-gray', !on);
                    perilla.classList.toggle('translate-x-5', on);
                    ayuda.textContent = on
                        ? 'Activo — se le muestra a quien cumpla las condiciones.'
                        : 'Listo para activar.';
                };

                pintar(nuevo);
                btn.disabled = true;

                try {
                    const response = await fetch(`{{ route('menus.update.resenas', ['comercio' => $comercio->slug]) }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ activo: nuevo })
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        pintar(activo);
                        showError(data.error || 'No se pudo cambiar la configuración');
                        return;
                    }

                    pintar(data.activo);
                    showTemporaryMessage(data.activo ? 'Reseñas activadas' : 'Reseñas desactivadas', 'success');
                } catch (error) {
                    console.error('Error al cambiar reseñas:', error);
                    pintar(activo);
                    showError('Error al cambiar la configuración');
                } finally {
                    btn.disabled = false;
                }
            };

            // Guarda los textos del pedido de reseña. Van con botón propio y no al toque
            // como el interruptor: escribiendo, un guardado por tecla sería absurdo.
            window.guardarTextosResena = async function(btn) {
                const estado = document.getElementById('resena-estado');
                btn.disabled = true;
                estado.textContent = 'Guardando...';

                try {
                    const response = await fetch(`{{ route('menus.update.resenas', ['comercio' => $comercio->slug]) }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            titulo: document.getElementById('resena-titulo').value.trim(),
                            texto: document.getElementById('resena-texto').value.trim(),
                        })
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        showError(data.error || 'No se pudieron guardar los textos');
                        estado.textContent = '';
                        return;
                    }

                    estado.textContent = data.por_defecto ? 'Sin textos propios: vuelve el de fábrica' : '✓ Guardado';
                    showTemporaryMessage('Textos guardados', 'success');
                    setTimeout(() => { estado.textContent = ''; }, 4000);
                } catch (error) {
                    console.error('Error al guardar textos de reseña:', error);
                    showError('Error al guardar los textos');
                    estado.textContent = '';
                } finally {
                    btn.disabled = false;
                }
            };

            // Guarda el link de reseñas y habilita el interruptor en el acto. Antes el
            // campo vivía en "datos del comercio" y había que ir, volver y recargar.
            window.guardarLinkResenas = async function(btn) {
                const campo = document.getElementById('google-review-url');
                const estado = document.getElementById('link-resenas-estado');
                const sw = document.getElementById('resenas-switch');
                const caja = document.getElementById('resenas-caja');
                const ayuda = document.getElementById('resenas-ayuda');

                btn.disabled = true;
                estado.textContent = 'Guardando...';

                try {
                    const response = await fetch(`{{ route('menus.update.resenas', ['comercio' => $comercio->slug]) }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ google_review_url: campo.value.trim() })
                    });

                    // Una sesión vencida (419) contesta HTML, no JSON: sin esto reventaba el
                    // `.json()` y el motivo real quedaba tapado por un "Error al guardar".
                    let data = {};
                    try {
                        data = await response.json();
                    } catch (e) {
                        if (response.status === 419) {
                            showError('Se venció la sesión. Recargá la página y volvé a intentar.');
                            estado.textContent = '⚠️ Sesión vencida';
                            estado.classList.add('text-danger');
                            return;
                        }
                    }
                    if (!response.ok) {
                        // El motivo queda TAMBIÉN al lado del campo: el cartel flotante se va
                        // solo y el comercio se quedaba mirando un link que no se guardó sin
                        // saber por qué. En un 422 el detalle está en `errors`, no en `error`.
                        const motivo = data.error
                            || Object.values(data.errors || {}).flat()[0]
                            || data.message
                            || 'No se pudo guardar el link';
                        showError(motivo);
                        estado.textContent = '⚠️ No se guardó';
                        estado.classList.add('text-danger');
                        return;
                    }
                    estado.classList.remove('text-danger');

                    sw.disabled = !data.tiene_link;
                    caja.classList.toggle('opacity-60', !data.tiene_link);

                    if (!data.tiene_link) {
                        // Sin link el servidor apaga el interruptor: se refleja acá para
                        // que no quede pintado en verde sobre un dato que ya no está.
                        sw.setAttribute('aria-checked', 'false');
                        sw.classList.remove('bg-accent-green');
                        sw.classList.add('bg-accent-gray');
                        sw.querySelector('span').classList.remove('translate-x-5');
                        ayuda.textContent = 'Cargá el link acá arriba para poder activarlo.';
                    } else if (!data.activo) {
                        ayuda.textContent = 'Listo para activar.';
                    }

                    estado.textContent = '✓ Guardado';
                    showTemporaryMessage(data.tiene_link ? 'Link guardado' : 'Link borrado', 'success');
                    setTimeout(() => { estado.textContent = ''; }, 4000);
                } catch (error) {
                    console.error('Error al guardar el link de reseñas:', error);
                    showError('No se pudo guardar el link. Revisá la conexión y probá de nuevo.');
                    estado.textContent = '⚠️ No se guardó';
                    estado.classList.add('text-danger');
                } finally {
                    // En `finally` y no al final: las salidas por error hacían `return` antes
                    // de llegar acá, así que el botón quedaba deshabilitado para siempre y el
                    // segundo intento no hacía nada.
                    btn.disabled = false;
                }
            };
            @endif

            // ── Portada ──────────────────────────────────────────────────────
            // Todo lo de la portada guarda contra su propio endpoint y de a pedazos:
            // el encuadre al soltar el marcador, la frase con su botón, la foto con
            // el suyo. Por eso no cuelga del formulario de esta pantalla.
            (function () {
                const bloque = document.getElementById('bloque-portada');
                if (!bloque) return;

                const URL_GUARDAR = bloque.dataset.urlGuardar;
                const URL_ESTADO = bloque.dataset.urlEstado;
                const URL_REINTENTAR = bloque.dataset.urlReintentar;
                const TOKEN = '{{ csrf_token() }}';

                /** Envía al endpoint de portada. `extra` arma el FormData. */
                async function guardar(extra) {
                    const fd = new FormData();
                    fd.append('_token', TOKEN);
                    extra(fd);

                    const r = await fetch(URL_GUARDAR, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': TOKEN, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        body: fd
                    });

                    const data = await r.json().catch(() => ({}));
                    if (!r.ok) throw new Error(data.error || data.message || 'No se pudo guardar');
                    return data;
                }

                // ── Punto focal ──────────────────────────────────────────────
                // Mover esto no regenera ninguna imagen: son dos números que van a
                // `object-position`, así que se guardan solos al soltar.
                (function () {
                    const picker = document.getElementById('focoPicker');
                    if (!picker) return;

                    const marca = document.getElementById('focoMarca');
                    const preview = document.getElementById('focoPreview');
                    const valor = document.getElementById('focoValor');
                    const estado = document.getElementById('focoEstado');

                    let x = {{ $comercio->portada_focal_x ?? 50 }};
                    let y = {{ $comercio->portada_focal_y ?? 50 }};

                    function aplicar(nx, ny) {
                        x = Math.max(0, Math.min(100, Math.round(nx)));
                        y = Math.max(0, Math.min(100, Math.round(ny)));

                        marca.style.left = x + '%';
                        marca.style.top = y + '%';
                        preview.style.objectPosition = x + '% ' + y + '%';
                        valor.textContent = x + '% · ' + y + '%';
                    }

                    async function persistir() {
                        estado.textContent = 'Guardando…';
                        try {
                            await guardar(fd => {
                                fd.append('portada_focal_x', x);
                                fd.append('portada_focal_y', y);
                            });
                            estado.textContent = '✓ Guardado';
                            setTimeout(() => { estado.textContent = ''; }, 2500);
                        } catch (e) {
                            estado.textContent = 'No se pudo guardar';
                        }
                    }

                    function desdeEvento(e) {
                        const r = picker.getBoundingClientRect();
                        const p = e.touches ? e.touches[0] : e;
                        aplicar(((p.clientX - r.left) / r.width) * 100, ((p.clientY - r.top) / r.height) * 100);
                    }

                    let arrastrando = false;

                    picker.addEventListener('pointerdown', function (e) {
                        arrastrando = true;
                        picker.setPointerCapture(e.pointerId);
                        desdeEvento(e);
                    });
                    picker.addEventListener('pointermove', function (e) {
                        if (arrastrando) desdeEvento(e);
                    });
                    // Se persiste al soltar y no en cada `pointermove`: arrastrar el
                    // marcador dispara decenas de eventos por segundo.
                    picker.addEventListener('pointerup', function () {
                        if (!arrastrando) return;
                        arrastrando = false;
                        persistir();
                    });
                    picker.addEventListener('pointercancel', function () { arrastrando = false; });

                    document.getElementById('focoCentrar')?.addEventListener('click', function () {
                        aplicar(50, 50);
                        persistir();
                    });
                })();

                // ── Seguimiento del procesado ────────────────────────────────
                const progreso = document.getElementById('portadaProgreso');
                const errorBox = document.getElementById('portadaError');
                const segundosTxt = document.getElementById('portadaSegundos');
                const demora = document.getElementById('portadaDemora');

                let desde = null;
                let timer = null;

                function tick() {
                    if (!desde) return;
                    const seg = Math.round((Date.now() - desde) / 1000);
                    if (segundosTxt) segundosTxt.textContent = seg + 's';

                    // A los 30s dejamos de prometer que ya viene: casi siempre
                    // significa que la cola no está corriendo.
                    if (seg >= 30 && demora) demora.classList.remove('hidden');
                }

                function detener() {
                    clearInterval(timer);
                    timer = null;
                    progreso.classList.add('hidden');
                }

                async function consultar() {
                    try {
                        const r = await fetch(URL_ESTADO, { headers: { 'Accept': 'application/json' } });
                        if (!r.ok) return;

                        const data = await r.json();

                        if (data.estado === 'listo') {
                            detener();
                            window.location.reload();   // la tarjeta se arma en el server
                            return;
                        }

                        if (data.estado === 'error') {
                            detener();
                            errorBox.classList.remove('hidden');
                        }
                    } catch (e) {
                        // Sin conexión: se reintenta en el próximo ciclo.
                    }
                }

                function arrancar() {
                    if (timer) return;
                    desde = Date.now();
                    progreso.classList.remove('hidden');
                    errorBox.classList.add('hidden');
                    if (demora) demora.classList.add('hidden');
                    tick();
                    timer = setInterval(function () { tick(); consultar(); }, 2000);
                }

                async function reintentar() {
                    detener();
                    try {
                        await fetch(URL_REINTENTAR, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': TOKEN, 'Accept': 'application/json' },
                        });
                        arrancar();
                    } catch (e) {
                        errorBox.classList.remove('hidden');
                    }
                }

                document.getElementById('portadaReintentar')?.addEventListener('click', reintentar);
                document.getElementById('portadaReintentar2')?.addEventListener('click', reintentar);

                if (bloque.dataset.estado === 'procesando') arrancar();
                if (bloque.dataset.estado === 'error') errorBox.classList.remove('hidden');

                // ── Elegir y subir la foto ───────────────────────────────────
                const input = document.getElementById('portada');
                const botonSubir = document.getElementById('portadaSubir');

                if (input && botonSubir) {
                    const MIN_ANCHO = {{ \App\Services\BrandThemeService::ANCHO_RECOMENDADO }};
                    const MIN_ALTO  = {{ \App\Services\BrandThemeService::ALTO_RECOMENDADO }};

                    const wrap = document.getElementById('portadaPreviewWrap');
                    const img = document.getElementById('portadaPreview');
                    const medidasTxt = document.getElementById('portadaMedidas');
                    const aviso = document.getElementById('portadaAviso');
                    const avisoDetalle = document.getElementById('portadaAvisoDetalle');
                    const estadoSubir = document.getElementById('portadaSubirEstado');

                    input.addEventListener('change', function () {
                        const archivo = this.files && this.files[0];
                        botonSubir.disabled = !archivo;
                        aviso.classList.add('hidden');

                        if (!archivo || !archivo.type.startsWith('image/')) {
                            wrap.classList.add('hidden');
                            return;
                        }

                        // Se libera la URL anterior para no acumular blobs si el
                        // usuario prueba varias imágenes seguidas.
                        if (img.dataset.blob) URL.revokeObjectURL(img.dataset.blob);

                        const url = URL.createObjectURL(archivo);
                        img.dataset.blob = url;
                        img.src = url;
                        wrap.classList.remove('hidden');

                        img.onload = function () {
                            medidasTxt.textContent =
                                img.naturalWidth + '×' + img.naturalHeight + ' · ' +
                                (archivo.size / 1024 / 1024).toFixed(1) + ' MB';

                            // Informativo: nunca impide subirla.
                            if (img.naturalWidth < MIN_ANCHO || img.naturalHeight < MIN_ALTO) {
                                avisoDetalle.textContent =
                                    'La imagen es de ' + img.naturalWidth + '×' + img.naturalHeight +
                                    ' y se recomienda ' + MIN_ANCHO + '×' + MIN_ALTO + '.';
                                aviso.classList.remove('hidden');
                            }
                        };
                    });

                    botonSubir.addEventListener('click', async function () {
                        const archivo = input.files && input.files[0];
                        if (!archivo) return;

                        botonSubir.disabled = true;
                        estadoSubir.textContent = 'Subiendo…';

                        try {
                            const data = await guardar(fd => fd.append('portada', archivo));

                            // Una foto de celular normal se procesa en el mismo request y
                            // vuelve 'listo'; las gigantes van a la cola y ahí sí hay que
                            // esperar con la barra.
                            if (data.estado === 'listo') {
                                window.location.reload();
                                return;
                            }

                            estadoSubir.textContent = '';
                            arrancar();
                        } catch (e) {
                            estadoSubir.textContent = '';
                            botonSubir.disabled = false;
                            showError(e.message);
                        }
                    });
                }

                // ── Eliminar ─────────────────────────────────────────────────
                window.eliminarPortada = function () {
                    showConfirmWithLoader('¿Eliminar la portada? El menú público vuelve al diseño estándar.', async r => {
                        if (!r) return;
                        try {
                            await guardar(fd => fd.append('delete_portada', '1'));
                            window.location.reload();
                        } catch (e) {
                            showError(e.message);
                        }
                    }, 'Eliminando...');
                };

                // ── Frase del menú ───────────────────────────────────────────
                window.guardarTagline = async function (btn) {
                    const estado = document.getElementById('tagline-estado');
                    btn.disabled = true;
                    estado.textContent = 'Guardando...';

                    try {
                        await guardar(fd => fd.append('tagline', document.getElementById('tagline').value.trim()));
                        estado.textContent = '✓ Guardado';
                        showTemporaryMessage('Frase guardada', 'success');
                        setTimeout(() => { estado.textContent = ''; }, 4000);
                    } catch (e) {
                        estado.textContent = '';
                        showError(e.message);
                    }

                    btn.disabled = false;
                };
            })();

            // Marca/desmarca un plato como estrella desde su propia tarjeta.
            // Se guarda en el JSON del menú, junto al plato, no en una lista aparte.
            window.togglePlatoEstrella = async function(btn, categoriaIndex, platoIndex) {
                @if(!isset($isEdit) || !$isEdit || !isset($menu) || !$menu)
                    showTemporaryMessage('Guardá el menú antes de marcar platos estrella', 'error');
                    return;
                @endif

                const marcado = btn.dataset.estrella === '1';
                btn.disabled = true;

                try {
                    const url = `{{ route('menus.update.plato.estrella', ['comercio' => $comercio->slug, 'menu' => $menu->id ?? '0']) }}`;
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            estrella: !marcado,
                            categoria_index: categoriaIndex,
                            plato_index: platoIndex
                        })
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        // 422 = llegó al tope de estrellas; el mensaje lo arma el backend.
                        showTemporaryMessage(data.error || 'No se pudo marcar el plato', 'error');
                        return;
                    }

                    pintarEstrella(btn, data.estrella);
                    refrescarResumenEstrellas();
                    showTemporaryMessage(data.estrella ? 'Plato estrella marcado' : 'Ya no es plato estrella', 'success');
                } catch (error) {
                    console.error('Error al marcar plato estrella:', error);
                    showTemporaryMessage('Error al marcar el plato', 'error');
                } finally {
                    btn.disabled = false;
                }
            };

            function pintarEstrella(btn, marcado) {
                btn.dataset.estrella = marcado ? '1' : '0';
                btn.querySelector('svg').setAttribute('fill', marcado ? 'currentColor' : 'none');
                btn.classList.toggle('text-warning', marcado);
                btn.classList.toggle('bg-warning/20', marcado);
                btn.classList.toggle('text-text-secondary', !marcado);
                btn.classList.toggle('hover:text-warning', !marcado);
                btn.classList.toggle('hover:bg-warning/10', !marcado);
                btn.title = marcado ? 'Es plato estrella: el asistente lo recomienda primero' : 'Marcar como plato estrella';
            }

            // El resumen de arriba se arma leyendo qué estrellas están encendidas en las
            // tarjetas, así no hay que recargar la página para verlo actualizado.
            function refrescarResumenEstrellas() {
                const resumen = document.getElementById('estrellas-resumen');
                const vacio = document.getElementById('estrellas-vacio');
                const contador = document.getElementById('estrellas-contador');
                if (!resumen) return;

                const marcados = [...document.querySelectorAll('.plato-estrella-btn[data-estrella="1"]')].map(btn => {
                    const item = btn.closest('.plato-item');
                    const nombre = item?.querySelector('h5');
                    return nombre ? nombre.textContent.trim() : '';
                }).filter(Boolean);

                resumen.innerHTML = marcados.map(n => {
                    const span = document.createElement('span');
                    span.className = 'inline-block px-3 py-1.5 rounded-full border border-warning bg-warning/20 text-sm text-warning';
                    span.textContent = '⭐ ' + n;
                    return span.outerHTML;
                }).join('');

                if (contador) contador.textContent = marcados.length;
                if (vacio) vacio.classList.toggle('hidden', marcados.length > 0);
            }

            
            // Inicializar drag & drop para platos
            initializeSortable();
            
            // Función para guardar promociones automáticamente (global para que pueda ser llamada desde otras funciones)
            let promocionesTimeout;
            window.savePromocionesAuto = function() {
                clearTimeout(promocionesTimeout);
                promocionesTimeout = setTimeout(async () => {
                    try {
                        @if(!isset($isEdit) || !$isEdit || !isset($menu) || !$menu)
                            return; // Menú nuevo, no guardar aún
                        @endif
                        
                        const promociones = collectPromocionesData();
                        const saveUrl = `{{ route('menus.save.promociones', ['comercio' => $comercio->slug, 'menu' => $menu->id ?? '0']) }}`;
                        
                        const response = await fetch(saveUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                promociones: promociones,
                                frontend_logs: window.frontendLogs || [] // Enviar logs al backend
                            })
                        });
                        
                        if (!response.ok) {
                            const errorData = await response.json();
                            throw new Error(errorData.error || `Error HTTP ${response.status}`);
                        }
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            showTemporaryMessage('Promociones guardadas', 'success');
                        } else {
                            throw new Error(data.error || 'Error al guardar las promociones');
                        }
                    } catch (error) {
                        console.error('Error al guardar promociones:', error);
                        showTemporaryMessage('Error al guardar promociones', 'error');
                    }
                }, 1500); // Guardar 1.5 segundos después de dejar de escribir
            }
            
            // Agregar event listeners a todos los campos de promociones
            function setupPromocionesAutoSave() {
                const container = document.getElementById('promociones-container');
                if (!container) return;
                
                // Usar delegación de eventos para capturar cambios en cualquier campo de promoción
                container.addEventListener('input', function(e) {
                    if (e.target.hasAttribute('data-promo-field')) {
                        savePromocionesAuto();
                    }
                });
                
                // También escuchar cambios en textareas
                container.addEventListener('change', function(e) {
                    if (e.target.hasAttribute('data-promo-field')) {
                        savePromocionesAuto();
                    }
                });
            }
            
            // Inicializar auto-guardado de promociones
            setupPromocionesAutoSave();
            
            // Observar cuando se agregan nuevas promociones al DOM
            const promoObserver = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes.length) {
                        // Se agregó una nueva promoción, guardar automáticamente
                        setTimeout(savePromocionesAuto, 500);
                    }
                });
            });
            
            const promocionesContainer = document.getElementById('promociones-container');
            if (promocionesContainer) {
                promoObserver.observe(promocionesContainer, {
                    childList: true,
                    subtree: true
                });
            }
        });
        

        
        
        // Variables globales para el modal de iconos
        let currentIconInput = null;
        
        // Funciones para el modal de iconos
        function openIconModal(inputElement) {
            currentIconInput = inputElement;
            document.getElementById('iconModal').style.display = 'flex';
        }
        
        function closeIconModal() {
            document.getElementById('iconModal').style.display = 'none';
            currentIconInput = null;
        }
        
        function selectIcon(icon) {
            if (currentIconInput) {
                currentIconInput.value = icon;
                closeIconModal();
            }
        }
        
        // Cerrar modal al hacer click fuera
        document.addEventListener('click', function(event) {
            const iconModal = document.getElementById('iconModal');
            const fileModal = document.getElementById('fileModal');
            if (event.target === iconModal) {
                closeIconModal();
            }
            if (event.target === fileModal) {
                closeFileModal();
            }
        });
        
        // Funciones para el modal de archivos
        function openFileModal(fileUrl, fileType) {
            const modal = document.getElementById('fileModal');
            const modalBody = document.getElementById('fileModalBody');
            
            modalBody.innerHTML = '';
            
            if (fileType === 'pdf') {
                // Para PDFs usar iframe
                modalBody.innerHTML = `<iframe src="${fileUrl}" title="Vista previa del PDF"></iframe>`;
            } else {
                // Para imágenes usar img
                modalBody.innerHTML = `<img src="${fileUrl}" alt="Vista previa del archivo" />`;
            }
            
            modal.style.display = 'flex';
        }
        
        function closeFileModal() {
            const modal = document.getElementById('fileModal');
            modal.style.display = 'none';
        }
        
        // ========================================
        // FUNCIONES PARA PROCESAR PDFs CON PDF.JS
        // ========================================
        
        
        
        
    </script>

    <!-- Modal de selección de iconos -->
    <div id="iconModal" class="icon-modal">
        <div class="icon-modal-content">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-semibold text-text-primary">Seleccionar Icono</h3>
                <button onclick="closeIconModal()" class="text-text-primary hover:text-text-secondary text-2xl">&times;</button>
            </div>
            
            <div class="icon-grid" id="iconGrid">
                <!-- Menú y comida -->
                <div class="icon-option" onclick="selectIcon('📋')">📋</div>
                <div class="icon-option" onclick="selectIcon('🍽️')">🍽️</div>
                <div class="icon-option" onclick="selectIcon('🍴')">🍴</div>
                <div class="icon-option" onclick="selectIcon('🥘')">🥘</div>
                <div class="icon-option" onclick="selectIcon('🍳')">🍳</div>
                <div class="icon-option" onclick="selectIcon('🥗')">🥗</div>
                <div class="icon-option" onclick="selectIcon('🥙')">🥙</div>
                <div class="icon-option" onclick="selectIcon('🌯')">🌯</div>
                
                <!-- Platos específicos -->
                <div class="icon-option" onclick="selectIcon('🍕')">🍕</div>
                <div class="icon-option" onclick="selectIcon('🍔')">🍔</div>
                <div class="icon-option" onclick="selectIcon('🌮')">🌮</div>
                <div class="icon-option" onclick="selectIcon('🍜')">🍜</div>
                <div class="icon-option" onclick="selectIcon('🍲')">🍲</div>
                <div class="icon-option" onclick="selectIcon('🍱')">🍱</div>
                <div class="icon-option" onclick="selectIcon('🥟')">🥟</div>
                <div class="icon-option" onclick="selectIcon('🍤')">🍤</div>
                
                <!-- Bebidas -->
                <div class="icon-option" onclick="selectIcon('🍺')">🍺</div>
                <div class="icon-option" onclick="selectIcon('🍻')">🍻</div>
                <div class="icon-option" onclick="selectIcon('🍷')">🍷</div>
                <div class="icon-option" onclick="selectIcon('🥂')">🥂</div>
                <div class="icon-option" onclick="selectIcon('🍸')">🍸</div>
                <div class="icon-option" onclick="selectIcon('🥃')">🥃</div>
                <div class="icon-option" onclick="selectIcon('☕')">☕</div>
                <div class="icon-option" onclick="selectIcon('🥤')">🥤</div>
                
                <!-- Postres y snacks -->
                <div class="icon-option" onclick="selectIcon('🍰')">🍰</div>
                <div class="icon-option" onclick="selectIcon('🧁')">🧁</div>
                <div class="icon-option" onclick="selectIcon('🍪')">🍪</div>
                <div class="icon-option" onclick="selectIcon('🍩')">🍩</div>
                <div class="icon-option" onclick="selectIcon('🍫')">🍫</div>
                <div class="icon-option" onclick="selectIcon('🍭')">🍭</div>
                <div class="icon-option" onclick="selectIcon('🍬')">🍬</div>
                <div class="icon-option" onclick="selectIcon('🍯')">🍯</div>
                
                <!-- Precios y pagos -->
                <div class="icon-option" onclick="selectIcon('💰')">💰</div>
                <div class="icon-option" onclick="selectIcon('💵')">💵</div>
                <div class="icon-option" onclick="selectIcon('💳')">💳</div>
                <div class="icon-option" onclick="selectIcon('💸')">💸</div>
                <div class="icon-option" onclick="selectIcon('💴')">💴</div>
                <div class="icon-option" onclick="selectIcon('🏷️')">🏷️</div>
                
                <!-- Promociones y descuentos -->
                <div class="icon-option" onclick="selectIcon('🎯')">🎯</div>
                <div class="icon-option" onclick="selectIcon('🎪')">🎪</div>
                <div class="icon-option" onclick="selectIcon('🎊')">🎊</div>
                <div class="icon-option" onclick="selectIcon('🎉')">🎉</div>
                <div class="icon-option" onclick="selectIcon('🏆')">🏆</div>
                <div class="icon-option" onclick="selectIcon('🥇')">🥇</div>
                <div class="icon-option" onclick="selectIcon('⭐')">⭐</div>
                <div class="icon-option" onclick="selectIcon('🔥')">🔥</div>
                
                <!-- Horarios y ubicación -->
                <div class="icon-option" onclick="selectIcon('🕐')">🕐</div>
                <div class="icon-option" onclick="selectIcon('⏰')">⏰</div>
                <div class="icon-option" onclick="selectIcon('📅')">📅</div>
                <div class="icon-option" onclick="selectIcon('📍')">📍</div>
                <div class="icon-option" onclick="selectIcon('🏪')">🏪</div>
                <div class="icon-option" onclick="selectIcon('🏬')">🏬</div>
                
                <!-- Contacto -->
                <div class="icon-option" onclick="selectIcon('📞')">📞</div>
                <div class="icon-option" onclick="selectIcon('📱')">📱</div>
                <div class="icon-option" onclick="selectIcon('✉️')">✉️</div>
                <div class="icon-option" onclick="selectIcon('💬')">💬</div>
                
                <!-- General -->
                <div class="icon-option" onclick="selectIcon('❓')">❓</div>
                <div class="icon-option" onclick="selectIcon('💡')">💡</div>
                <div class="icon-option" onclick="selectIcon('❤️')">❤️</div>
                <div class="icon-option" onclick="selectIcon('👍')">👍</div>
            </div>
        </div>
    </div>

    <!-- Modal de visualización de archivos -->
    <div id="fileModal" class="file-modal">
        <div class="file-modal-content">
            <div class="file-modal-header">
                <h3 class="text-xl font-semibold text-text-primary">Vista Previa del Archivo</h3>
                <button onclick="closeFileModal()" class="text-text-primary hover:text-text-secondary text-2xl ml-4">&times;</button>
            </div>
            
            <div class="file-modal-body" id="fileModalBody">
                <!-- El contenido se carga dinámicamente aquí -->
            </div>
        </div>
    </div>

    <script>
        // Peso y tipo de una imagen de plato, antes de mandarla.
        //
        // El tope sale del mismo lugar que la validación del backend
        // (ImagenMenuService::MAX_KB). Estaba fijo en 2 MB acá, así que subir el límite
        // del servidor no servía de nada: el navegador cortaba antes de mandar el archivo,
        // y una foto de celular actual no entraba.
        function validarImagenPlato(file, avisar = showError) {
            const maxKb = {{ \App\Services\ImagenMenuService::MAX_KB }};

            if (file.size > maxKb * 1024) {
                avisar(`La imagen no puede pesar más de ${Math.round(maxKb / 1024)} MB`);
                return false;
            }

            if (!file.type.match('image.*')) {
                avisar('Solo se permiten archivos de imagen (JPEG, PNG, GIF o WEBP)');
                return false;
            }

            return true;
        }

        // Función para subir imagen de plato
        async function uploadPlatoImagen(event, categoriaIndex, platoIndex) {
            const file = event.target.files[0];
            if (!file) return;

            if (!validarImagenPlato(file)) {
                event.target.value = '';
                return;
            }

            // Preview inmediato (antes de subir)
            const container = document.getElementById(`plato-imagen-container-${categoriaIndex}-${platoIndex}`);
            const reader = new FileReader();
            
            reader.onload = function(e) {
                // Mostrar preview mientras se sube
                const previewUrl = e.target.result;
                
                // Si ya existe una imagen, solo actualizar el src
                const existingImg = document.getElementById(`plato-imagen-${categoriaIndex}-${platoIndex}`);
                if (existingImg) {
                    existingImg.src = previewUrl;
                    existingImg.style.opacity = '0.6';
                    return;
                }
                
                // Si no hay imagen, eliminar todo el contenido del contenedor (incluyendo label)
                // y reemplazar con la estructura de imagen
                while (container.firstChild) {
                    container.removeChild(container.firstChild);
                }
                container.innerHTML = `
                    <div class="relative group/image">
                        <div class="w-20 h-20 rounded-lg overflow-hidden border-2 border-accent-blue/30 shadow-md bg-secondary-dark">
                            <img src="${previewUrl}" 
                                 alt="Vista previa"
                                 class="w-full h-full object-cover"
                                 id="plato-imagen-${categoriaIndex}-${platoIndex}"
                                 style="opacity: 0.6;">
                        </div>
                        <!-- Overlay con botones al hover -->
                        <div class="absolute inset-0 bg-black/70 opacity-0 group-hover/image:opacity-100 transition-opacity duration-200 rounded-lg flex items-center justify-center gap-2">
                            <label class="cursor-pointer">
                                <input type="file" 
                                       accept="image/jpeg,image/png,image/jpg,image/gif"
                                       class="hidden"
                                       onchange="uploadPlatoImagen(event, ${categoriaIndex}, ${platoIndex})"
                                       id="imagen-input-${categoriaIndex}-${platoIndex}">
                                <span class="inline-flex items-center px-2.5 py-1 bg-accent-blue hover:opacity-80 text-black text-xs font-semibold rounded transition-opacity cursor-pointer shadow-xl">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Cambiar
                                </span>
                            </label>
                            <button type="button"
                                    onclick="removePlatoImagen(${categoriaIndex}, ${platoIndex}, null)"
                                    class="inline-flex items-center justify-center w-7 h-7 bg-danger hover:brightness-95 text-white rounded transition-colors cursor-pointer shadow-xl"
                                    title="Eliminar imagen">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                `;
            };
            
            reader.readAsDataURL(file);

            // Preparar FormData
            const formData = new FormData();
            formData.append('imagen', file);
            formData.append('categoria_index', categoriaIndex);
            formData.append('plato_index', platoIndex);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

            // Deshabilitar el input mientras se sube
            event.target.disabled = true;
            
            // Mostrar indicador de carga en la imagen (ya se muestra con opacity en el preview)

            try {
                const menuId = {{ $menu->id ?? 'null' }};
                if (!menuId) {
                    alert('Error: El menú no está disponible');
                    return;
                }
                
                // Usar la ruta con nombre de Laravel
                const uploadUrl = `{{ route('menus.upload.plato.imagen', ['comercio' => $comercio->slug, 'menu' => $menu->id ?? '0']) }}`;
                const response = await fetch(uploadUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                // Verificar si la respuesta es JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    throw new Error(`Respuesta del servidor no válida (${response.status}): ${text.substring(0, 200)}`);
                }

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || `Error HTTP ${response.status}`);
                }

                if (data.success) {
                    // Actualizar imagen con URL del servidor
                    const img = document.getElementById(`plato-imagen-${categoriaIndex}-${platoIndex}`);
                    if (img) {
                        img.src = data.imagen_url;
                        img.style.opacity = '1';
                        img.alt = img.alt || 'Imagen del plato';
                    } else {
                        // Si no existe la imagen, crear toda la estructura
                        container.innerHTML = `
                            <div class="relative group/image">
                                <div class="w-20 h-20 rounded-lg overflow-hidden border-2 border-accent-blue/30 shadow-md bg-secondary-dark">
                                    <img src="${data.imagen_url}" 
                                         alt="Imagen del plato"
                                         class="w-full h-full object-cover"
                                         id="plato-imagen-${categoriaIndex}-${platoIndex}">
                                </div>
                                <!-- Overlay con botones al hover -->
                                <div class="absolute inset-0 bg-black/70 opacity-0 group-hover/image:opacity-100 transition-opacity duration-200 rounded-lg flex items-center justify-center gap-2">
                                    <label class="cursor-pointer">
                                        <input type="file" 
                                               accept="image/jpeg,image/png,image/jpg,image/gif"
                                               class="hidden"
                                               onchange="uploadPlatoImagen(event, ${categoriaIndex}, ${platoIndex})"
                                               id="imagen-input-${categoriaIndex}-${platoIndex}">
                                        <span class="inline-flex items-center px-2.5 py-1 bg-accent-blue hover:opacity-80 text-black text-xs font-semibold rounded transition-opacity cursor-pointer shadow-xl">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            Cambiar
                                        </span>
                                    </label>
                                    <button type="button"
                                            onclick="removePlatoImagen(${categoriaIndex}, ${platoIndex}, null)"
                                            class="inline-flex items-center justify-center w-7 h-7 bg-danger hover:brightness-95 text-white rounded transition-colors cursor-pointer shadow-xl"
                                            title="Eliminar imagen">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        `;
                    }
                    
                    showNotification('✅ Imagen subida correctamente', 'success');
                } else {
                    throw new Error(data.error || 'Error al subir la imagen');
                }
            } catch (error) {
                console.error('Error completo al subir imagen:', error);
                console.error('Stack:', error.stack);
                showNotification('❌ Error al subir la imagen: ' + (error.message || 'Error desconocido'), 'error');
                
                // Si falló y no había imagen antes, restaurar placeholder
                const img = document.getElementById(`plato-imagen-${categoriaIndex}-${platoIndex}`);
                if (img) {
                    const imageWrapper = img.closest('.relative.group\\/image');
                    if (imageWrapper && !imageWrapper.querySelector('input[type="file"]')) {
                        // Restaurar placeholder
                        container.innerHTML = `
                            <label class="cursor-pointer">
                                <input type="file" 
                                       accept="image/jpeg,image/png,image/jpg,image/gif"
                                       class="hidden"
                                       onchange="uploadPlatoImagen(event, ${categoriaIndex}, ${platoIndex})"
                                       id="imagen-input-${categoriaIndex}-${platoIndex}">
                                <div class="w-20 h-20 rounded-lg border-2 border-dashed border-accent-blue/40 bg-secondary-dark/80 flex flex-col items-center justify-center text-text-secondary hover:border-accent-blue hover:bg-secondary-dark transition-all duration-200">
                                    <svg class="w-5 h-5 mb-0.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="text-[9px] font-semibold text-accent-blue">Imagen</span>
                                </div>
                            </label>
                        `;
                    } else {
                        // Si había imagen antes, restaurar opacidad
                        img.style.opacity = '1';
                    }
                }
            } finally {
                // Restaurar input
                event.target.disabled = false;
                event.target.value = '';
            }
        }

        // Función para eliminar imagen de plato
        async function removePlatoImagen(categoriaIndex, platoIndex, imagenPath) {
            showConfirm('¿Estás seguro de que quieres eliminar esta imagen?', async function(confirmed) {
                if (!confirmed) {
                    return;
                }
                
                await deletePlatoImagen(categoriaIndex, platoIndex);
            });
        }

        // Función auxiliar que realiza la eliminación
        async function deletePlatoImagen(categoriaIndex, platoIndex) {
            try {
                const menuId = {{ $menu->id ?? 'null' }};
                if (!menuId) {
                    showError('El menú no está disponible');
                    return;
                }

                // Preparar datos para la petición
                const formData = new FormData();
                formData.append('categoria_index', categoriaIndex);
                formData.append('plato_index', platoIndex);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                formData.append('_method', 'DELETE');

                // Llamar al endpoint de eliminación
                const deleteUrl = `{{ route('menus.delete.plato.imagen', ['comercio' => $comercio->slug, 'menu' => $menu->id ?? '0']) }}`;
                const response = await fetch(deleteUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                // Verificar si la respuesta es JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    throw new Error(`Respuesta del servidor no válida (${response.status}): ${text.substring(0, 200)}`);
                }

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || `Error HTTP ${response.status}`);
                }

                if (data.success) {
                    // Eliminar del DOM
                    const container = document.getElementById(`plato-imagen-container-${categoriaIndex}-${platoIndex}`);
                    const img = document.getElementById(`plato-imagen-${categoriaIndex}-${platoIndex}`);
                    
                    if (img) {
                        // Buscar el contenedor padre (puede ser wrapper o div.relative.inline-block)
                        const wrapper = img.closest(`#plato-imagen-wrapper-${categoriaIndex}-${platoIndex}`);
                        const relativeDiv = img.closest('div.relative.inline-block');
                        
                        // Eliminar el contenedor que corresponda
                        if (wrapper) {
                            wrapper.remove();
                        } else if (relativeDiv) {
                            relativeDiv.remove();
                        } else {
                            // Si no encuentra contenedor, eliminar directamente la imagen y su botón
                            img.remove();
                            // Buscar y eliminar el botón de eliminar si está cerca
                            const removeBtn = container?.querySelector('button[onclick*="removePlatoImagen"]');
                            if (removeBtn) {
                                removeBtn.remove();
                            }
                        }
                    }

                    // Dejar el contenedor como lo arma Blade cuando el plato no tiene imagen:
                    // el recuadro punteado "Imagen" que vuelve a abrir el selector de archivo.
                    //
                    // Antes se metía acá un placeholder con la clase `hidden`, así que el
                    // contenedor quedaba visualmente VACÍO y no había forma de subir otra
                    // imagen sin recargar la página. Y el input de subir vive DENTRO de este
                    // mismo contenedor, así que el innerHTML = '' se lo llevaba puesto: el
                    // código de abajo lo buscaba por id y ya no existía.
                    if (container) {
                        container.innerHTML = `
                            <label class="cursor-pointer">
                                <input type="file"
                                       accept="image/jpeg,image/png,image/jpg,image/gif"
                                       class="hidden"
                                       onchange="uploadPlatoImagen(event, ${categoriaIndex}, ${platoIndex})"
                                       id="imagen-input-${categoriaIndex}-${platoIndex}">
                                <div class="w-20 h-20 rounded-lg border-2 border-dashed border-accent-blue/40 bg-secondary-dark/80 flex flex-col items-center justify-center text-text-secondary hover:border-accent-blue hover:bg-secondary-dark transition-all duration-200">
                                    <svg class="w-5 h-5 mb-0.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="text-[9px] font-semibold text-accent-blue">Imagen</span>
                                </div>
                            </label>
                        `;
                    }

                    showSuccess('Imagen eliminada exitosamente');
                } else {
                    throw new Error(data.error || 'Error al eliminar la imagen');
                }
            } catch (error) {
                console.error('Error completo al eliminar imagen:', error);
                console.error('Stack:', error.stack);
                showError('Error al eliminar la imagen: ' + (error.message || 'Error desconocido'));
            }
        }

        // Función para actualizar el precio de un plato
        async function updatePlatoPrecio(categoriaIndex, platoIndex, nuevoPrecio) {
            const statusElement = document.getElementById(`precio-status-${categoriaIndex}-${platoIndex}`);
            const inputElement = document.getElementById(`precio-input-${categoriaIndex}-${platoIndex}`);
            
            // Se manda TAL CUAL lo escribió el comercio: el backend decide si hay que
            // formatearlo (Support\Precio). Antes acá se le quitaban los separadores y se
            // reenviaba "$ " + el número pelado, así que "$5.700,00" volvía como "$ 570000".
            const precioEscrito = nuevoPrecio.toString().trim();

            // Si está vacío, no hacer nada (permitir precios vacíos)
            if (precioEscrito === '' && inputElement.value === '') {
                return;
            }

            // Guardar el valor original por si hay error
            const valorOriginal = inputElement.dataset.originalValue || inputElement.value;
            
            // Mostrar indicador de carga
            if (statusElement) {
                statusElement.textContent = '⏳';
                statusElement.className = 'text-accent-blue text-xs opacity-70';
            }
            
            // Deshabilitar input mientras se guarda
            if (inputElement) {
                inputElement.disabled = true;
                inputElement.style.opacity = '0.7';
            }

            try {
                const menuId = {{ $menu->id ?? 'null' }};
                if (!menuId) {
                    throw new Error('El menú no está disponible');
                }

                // Preparar datos para la petición
                const formData = new FormData();
                formData.append('precio', precioEscrito);
                formData.append('categoria_index', categoriaIndex);
                formData.append('plato_index', platoIndex);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

                // Llamar al endpoint de actualización
                const updateUrl = `{{ route('menus.update.plato.precio', ['comercio' => $comercio->slug, 'menu' => $menu->id ?? '0']) }}`;
                const response = await fetch(updateUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                // Verificar si la respuesta es JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    throw new Error(`Respuesta del servidor no válida (${response.status}): ${text.substring(0, 200)}`);
                }

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || `Error HTTP ${response.status}`);
                }

                if (data.success) {
                    // Mostrar éxito
                    if (statusElement) {
                        statusElement.textContent = '✓';
                        statusElement.className = 'text-accent-green text-xs';
                    }
                    
                    // El backend devuelve el precio ya formateado ("$4.500"): el input muestra
                    // eso mismo sin el símbolo, así lo que ves coincide con lo que quedó guardado.
                    if (inputElement && data.precio !== undefined) {
                        const mostrado = String(data.precio).replace('$', '').trim();
                        inputElement.dataset.originalValue = mostrado;
                        inputElement.value = mostrado;
                    }
                    
                    // Ocultar el check después de 2 segundos
                    setTimeout(() => {
                        if (statusElement) {
                            statusElement.textContent = '';
                        }
                    }, 2000);
                } else {
                    throw new Error(data.error || 'Error al actualizar el precio');
                }
            } catch (error) {
                console.error('Error al actualizar precio:', error);
                
                // Restaurar valor original numérico
                if (inputElement) {
                    const valorOriginalNumerico = valorOriginal.replace(/[^0-9]/g, '');
                    inputElement.value = valorOriginalNumerico;
                }
                
                // Mostrar error
                if (statusElement) {
                    statusElement.textContent = '✗';
                    statusElement.className = 'text-danger text-xs';
                    setTimeout(() => {
                        statusElement.textContent = '';
                    }, 3000);
                }
                
                showError('Error al actualizar el precio: ' + (error.message || 'Error desconocido'));
            } finally {
                // Rehabilitar input
                if (inputElement) {
                    inputElement.disabled = false;
                    inputElement.style.opacity = '1';
                }
            }
        }

        // Detectar si es dispositivo móvil
        function isMobileDevice() {
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || 
                   (window.innerWidth <= 768);
        }

        // Manejar clicks para edición (móvil: un toque, desktop: doble clic)
        let lastClickTime = {};
        let lastClickTarget = {};
        
        function handleEditClick(event, categoriaIndex, platoIndex, tipo) {
            const isMobile = isMobileDevice();
            const clickKey = `${categoriaIndex}-${platoIndex}-${tipo}`;
            const currentTime = Date.now();
            
            if (isMobile) {
                // En móvil, un solo toque permite editar
                event.preventDefault();
                event.stopPropagation();
                
                if (tipo === 'nombre') {
                    enableEditNombre(categoriaIndex, platoIndex);
                } else if (tipo === 'descripcion') {
                    enableEditDescripcion(categoriaIndex, platoIndex);
                }
            } else {
                // En desktop, solo doble clic (ondblclick se encarga)
                // Esta función solo previene el comportamiento por defecto en desktop
                return;
            }
        }

        // Función para habilitar edición inline del nombre
        function enableEditNombre(categoriaIndex, platoIndex) {
            const displayElement = document.getElementById(`nombre-display-${categoriaIndex}-${platoIndex}`);
            if (!displayElement) return;
            
            const currentValue = displayElement.textContent.trim();
            
            // Crear input
            const input = document.createElement('input');
            input.type = 'text';
            input.value = currentValue;
            input.className = 'text-text-primary font-semibold text-base leading-tight bg-secondary-dark border border-accent-blue rounded px-2 py-1 w-full focus:outline-none focus:ring-2 focus:ring-accent-blue';
            input.id = `nombre-input-${categoriaIndex}-${platoIndex}`;
            
            // Reemplazar display con input
            displayElement.style.display = 'none';
            displayElement.parentNode.insertBefore(input, displayElement);
            
            // Enfocar y seleccionar texto
            input.focus();
            input.select();
            
            // Guardar al perder foco o Enter
            const save = async () => {
                const newValue = input.value.trim();
                input.remove();
                displayElement.style.display = '';
                
                if (newValue !== currentValue && newValue !== '') {
                    await updatePlatoNombre(categoriaIndex, platoIndex, newValue);
                }
            };
            
            input.addEventListener('blur', save);
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    input.blur();
                } else if (e.key === 'Escape') {
                    input.remove();
                    displayElement.style.display = '';
                }
            });
        }

        // Función para actualizar nombre de plato
        async function updatePlatoNombre(categoriaIndex, platoIndex, nuevoNombre) {
            const displayElement = document.getElementById(`nombre-display-${categoriaIndex}-${platoIndex}`);
            const statusElement = document.getElementById(`nombre-status-${categoriaIndex}-${platoIndex}`) || createStatusElement(`nombre-status-${categoriaIndex}-${platoIndex}`, displayElement.parentNode);
            
            if (!displayElement) return;
            
            const valorOriginal = displayElement.textContent.trim();
            
            // Guardar en historial para undo
            undoHistory.save({
                type: 'nombre',
                categoriaIndex: categoriaIndex,
                platoIndex: platoIndex
            }, valorOriginal);
            
            // Mostrar indicador de carga
            if (statusElement) {
                statusElement.textContent = '⏳';
                statusElement.className = 'text-accent-blue text-xs opacity-70';
            }
            
            try {
                const menuId = {{ $menu->id ?? 'null' }};
                if (!menuId) {
                    throw new Error('El menú no está disponible');
                }

                const formData = new FormData();
                formData.append('nombre', nuevoNombre);
                formData.append('categoria_index', categoriaIndex);
                formData.append('plato_index', platoIndex);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

                const updateUrl = `{{ route('menus.update.plato.nombre', ['comercio' => $comercio->slug, 'menu' => $menu->id ?? '0']) }}`;
                const response = await fetch(updateUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    throw new Error(`Respuesta del servidor no válida (${response.status}): ${text.substring(0, 200)}`);
                }

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.error || `Error HTTP ${response.status}`);
                }

                // Transformar y actualizar display (normalizar capitalización)
                const nombreTransformado = nuevoNombre
                    .replace(/[-_]/g, ' ')
                    .split(' ')
                    .map(word => {
                        if (word.length === 0) return word;
                        // Primera letra en mayúscula, resto en minúscula
                        return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
                    })
                    .filter(word => word.length > 0)
                    .join(' ')
                    .trim();
                displayElement.textContent = nombreTransformado;
                
                // Mostrar éxito
                if (statusElement) {
                    statusElement.textContent = '✓';
                    statusElement.className = 'text-accent-green text-xs';
                    setTimeout(() => {
                        statusElement.textContent = '';
                    }, 2000);
                }
            } catch (error) {
                console.error('Error al actualizar nombre:', error);
                
                // Restaurar valor original
                displayElement.textContent = valorOriginal;
                
                // Mostrar error
                if (statusElement) {
                    statusElement.textContent = '✗';
                    statusElement.className = 'text-danger text-xs';
                    setTimeout(() => {
                        statusElement.textContent = '';
                    }, 3000);
                }
                
                showError('Error al actualizar el nombre: ' + (error.message || 'Error desconocido'));
            }
        }

        // Función para habilitar edición inline de la descripción
        function enableEditDescripcion(categoriaIndex, platoIndex) {
            const displayElement = document.getElementById(`descripcion-display-${categoriaIndex}-${platoIndex}`);
            if (!displayElement) return;
            
            const currentValue = displayElement.textContent.trim();
            const isPlaceholder = displayElement.classList.contains('opacity-50');
            
            // Crear textarea
            const textarea = document.createElement('textarea');
            textarea.value = isPlaceholder ? '' : currentValue;
            textarea.className = 'text-text-secondary text-sm leading-relaxed bg-secondary-dark border border-accent-blue rounded px-2 py-1 w-full min-h-[60px] focus:outline-none focus:ring-2 focus:ring-accent-blue resize-y';
            textarea.id = `descripcion-input-${categoriaIndex}-${platoIndex}`;
            textarea.style.fontFamily = 'inherit';
            
            // Reemplazar display con textarea
            displayElement.style.display = 'none';
            displayElement.parentNode.insertBefore(textarea, displayElement);
            
            // Enfocar
            textarea.focus();
            
            // Guardar al perder foco o Ctrl+Enter
            const save = async () => {
                const newValue = textarea.value.trim();
                textarea.remove();
                displayElement.style.display = '';
                
                if (newValue !== currentValue) {
                    await updatePlatoDescripcion(categoriaIndex, platoIndex, newValue);
                }
            };
            
            textarea.addEventListener('blur', save);
            textarea.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && e.ctrlKey) {
                    e.preventDefault();
                    textarea.blur();
                } else if (e.key === 'Escape') {
                    textarea.remove();
                    displayElement.style.display = '';
                }
            });
        }

        // Función para actualizar descripción de plato
        async function updatePlatoDescripcion(categoriaIndex, platoIndex, nuevaDescripcion) {
            const displayElement = document.getElementById(`descripcion-display-${categoriaIndex}-${platoIndex}`);
            const containerElement = document.getElementById(`descripcion-container-${categoriaIndex}-${platoIndex}`);
            
            if (!displayElement || !containerElement) return;
            
            const valorOriginal = displayElement.textContent.trim();
            const isPlaceholder = displayElement.classList.contains('opacity-50');
            
            // Guardar en historial para undo (solo si no es placeholder)
            if (!isPlaceholder && valorOriginal) {
                undoHistory.save({
                    type: 'descripcion',
                    categoriaIndex: categoriaIndex,
                    platoIndex: platoIndex
                }, valorOriginal);
            }
            
            try {
                const menuId = {{ $menu->id ?? 'null' }};
                if (!menuId) {
                    throw new Error('El menú no está disponible');
                }

                const formData = new FormData();
                formData.append('descripcion', nuevaDescripcion);
                formData.append('categoria_index', categoriaIndex);
                formData.append('plato_index', platoIndex);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

                const updateUrl = `{{ route('menus.update.plato.descripcion', ['comercio' => $comercio->slug, 'menu' => $menu->id ?? '0']) }}`;
                const response = await fetch(updateUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    throw new Error(`Respuesta del servidor no válida (${response.status}): ${text.substring(0, 200)}`);
                }

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.error || `Error HTTP ${response.status}`);
                }

                // Actualizar display
                if (nuevaDescripcion) {
                    displayElement.textContent = nuevaDescripcion;
                    displayElement.classList.remove('opacity-50', 'italic');
                    displayElement.classList.add('whitespace-pre-wrap');
                } else {
                    displayElement.textContent = 'Click para agregar descripción...';
                    displayElement.classList.add('opacity-50', 'italic');
                    displayElement.classList.remove('whitespace-pre-wrap');
                }
                
                // Mostrar feedback visual
                containerElement.classList.add('opacity-50');
                setTimeout(() => {
                    containerElement.classList.remove('opacity-50');
                }, 300);
            } catch (error) {
                console.error('Error al actualizar descripción:', error);
                
                // Restaurar valor original
                displayElement.textContent = isPlaceholder ? 'Click para agregar descripción...' : valorOriginal;
                
                showError('Error al actualizar la descripción: ' + (error.message || 'Error desconocido'));
            }
        }

        // Función para manejar input de tags
        function handleTagInput(event, categoriaIndex, platoIndex) {
            if (event.key === 'Enter' || event.key === ',') {
                event.preventDefault();
                const input = event.target;
                const tagValue = input.value.trim();
                
                if (tagValue) {
                    addTag(categoriaIndex, platoIndex, tagValue);
                    input.value = '';
                }
            }
        }

        // Función para agregar tag
        async function addTag(categoriaIndex, platoIndex, tagValue) {
            // Obtener tags actuales
            const tagsList = document.getElementById(`tags-list-${categoriaIndex}-${platoIndex}`);
            if (!tagsList) return;
            
            const currentTags = Array.from(tagsList.querySelectorAll('.tag-item')).map(tag => tag.textContent.trim().replace('×', '').trim());
            
            // Verificar si el tag ya existe
            if (currentTags.includes(tagValue)) {
                return;
            }
            
            // Agregar tag al DOM
            const tagElement = document.createElement('span');
            tagElement.className = 'inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-accent-blue/20 text-accent-blue border border-accent-blue/30 tag-item';
            tagElement.innerHTML = `
                ${tagValue}
                <button type="button" 
                        onclick="removeTag(${categoriaIndex}, ${platoIndex}, '${tagValue.replace(/'/g, "\\'")}')"
                        class="ml-1 hover:text-accent-blue transition-colors"
                        title="Eliminar tag">
                    ×
                </button>
            `;
            tagsList.appendChild(tagElement);
            
            // Actualizar tags en el servidor
            await updatePlatoTags(categoriaIndex, platoIndex, [...currentTags, tagValue]);
        }

        // Función para eliminar tag
        async function removeTag(categoriaIndex, platoIndex, tagValue) {
            const tagsList = document.getElementById(`tags-list-${categoriaIndex}-${platoIndex}`);
            if (!tagsList) return;
            
            // Eliminar tag del DOM
            const tagElements = tagsList.querySelectorAll('.tag-item');
            tagElements.forEach(tagEl => {
                const text = tagEl.textContent.trim().replace('×', '').trim();
                if (text === tagValue) {
                    tagEl.remove();
                }
            });
            
            // Obtener tags restantes
            const remainingTags = Array.from(tagsList.querySelectorAll('.tag-item')).map(tag => tag.textContent.trim().replace('×', '').trim());
            
            // Actualizar tags en el servidor
            await updatePlatoTags(categoriaIndex, platoIndex, remainingTags);
        }

        // Función para actualizar tags de plato
        async function updatePlatoTags(categoriaIndex, platoIndex, tags) {
            try {
                const menuId = {{ $menu->id ?? 'null' }};
                if (!menuId) {
                    throw new Error('El menú no está disponible');
                }

                const formData = new FormData();
                tags.forEach(tag => {
                    formData.append('tags[]', tag);
                });
                formData.append('categoria_index', categoriaIndex);
                formData.append('plato_index', platoIndex);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

                const updateUrl = `{{ route('menus.update.plato.tags', ['comercio' => $comercio->slug, 'menu' => $menu->id ?? '0']) }}`;
                const response = await fetch(updateUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    throw new Error(`Respuesta del servidor no válida (${response.status}): ${text.substring(0, 200)}`);
                }

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.error || `Error HTTP ${response.status}`);
                }

                // Mostrar feedback visual
                const container = document.getElementById(`tags-container-${categoriaIndex}-${platoIndex}`);
                if (container) {
                    container.classList.add('opacity-50');
                    setTimeout(() => {
                        container.classList.remove('opacity-50');
                    }, 300);
                }
            } catch (error) {
                console.error('Error al actualizar tags:', error);
                showError('Error al actualizar los tags: ' + (error.message || 'Error desconocido'));
            }
        }

        // Inicializar SortableJS para drag & drop de platos
        function initializeSortable() {
            const categoriaContainers = document.querySelectorAll('[id^="platos-container-"]');
            
            categoriaContainers.forEach(container => {
                const categoriaIndex = container.dataset.categoriaIndex;
                
                new Sortable(container, {
                    animation: 150,
                    ghostClass: 'opacity-50',
                    chosenClass: 'bg-secondary-dark/50',
                    dragClass: 'bg-accent-blue/20',
                    filter: 'input, textarea, button, label',
                    preventOnFilter: false,
                    onEnd: async function(evt) {
                        const oldIndex = evt.oldIndex;
                        const newIndex = evt.newIndex;
                        
                        if (oldIndex === newIndex) return;
                        
                        // Obtener el nuevo orden de índices basado en la posición original
                        const items = Array.from(container.querySelectorAll('.plato-item'));
                        const newOrder = items.map((item, index) => {
                            // Obtener el índice original del plato
                            return parseInt(item.dataset.platoIndex);
                        });
                        
                        // Reordenar en el servidor
                        await reordenarPlatos(categoriaIndex, newOrder);
                    }
                });
            });
        }

        // Función para reordenar platos
        async function reordenarPlatos(categoriaIndex, nuevoOrden) {
            try {
                const menuId = {{ $menu->id ?? 'null' }};
                if (!menuId) {
                    throw new Error('El menú no está disponible');
                }

                const formData = new FormData();
                formData.append('categoria_index', categoriaIndex);
                nuevoOrden.forEach(index => {
                    formData.append('platos_order[]', index);
                });
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

                const updateUrl = `{{ route('menus.reordenar.platos', ['comercio' => $comercio->slug, 'menu' => $menu->id ?? '0']) }}`;
                const response = await fetch(updateUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    throw new Error(`Respuesta del servidor no válida (${response.status}): ${text.substring(0, 200)}`);
                }

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.error || `Error HTTP ${response.status}`);
                }

                // Actualizar data-plato-index de cada item según el nuevo orden
                const container = document.getElementById(`platos-container-${categoriaIndex}`);
                if (container) {
                    const items = container.querySelectorAll('.plato-item');
                    items.forEach((item, index) => {
                        item.dataset.platoIndex = nuevoOrden[index];
                    });
                }

                // Mostrar feedback visual
                showSuccessMessage('Platos reordenados exitosamente');
            } catch (error) {
                console.error('Error al reordenar platos:', error);
                showError('Error al reordenar los platos: ' + (error.message || 'Error desconocido'));
            }
        }

        // Agregar un producto nuevo a una categoría
        async function agregarPlato(categoriaIndex, nombreCategoria) {
            const menuId = {{ $menu->id ?? 'null' }};
            if (!menuId) {
                showError('El menú no está disponible');
                return;
            }

            const { value: datos } = await Swal.fire({
                ...swalConfig,
                title: 'Nuevo producto',
                html: `
                    <div style="display: flex; flex-direction: column; gap: 0.75rem; text-align: left;">
                        <p style="color: rgb(var(--c-ink-soft)); font-size: 0.85rem; margin: 0;">
                            Se agrega en <strong style="color: rgb(var(--c-ink));">${nombreCategoria}</strong>
                        </p>
                        <input id="nuevo-plato-nombre" class="swal2-input" style="margin: 0; width: 100%;"
                               placeholder="Nombre del producto" maxlength="255" autocomplete="off">
                        <input id="nuevo-plato-precio" class="swal2-input" style="margin: 0; width: 100%;"
                               placeholder="Precio (opcional)" inputmode="decimal" maxlength="50" autocomplete="off">
                        <textarea id="nuevo-plato-descripcion" class="swal2-textarea" style="margin: 0; width: 100%;"
                                  placeholder="Descripción (opcional)" maxlength="1000"></textarea>
                        <label for="nuevo-plato-imagen" style="display: flex; align-items: center; gap: 0.75rem; margin: 0; padding: 0.6rem 0.75rem; border: 2px dashed rgb(var(--c-accent) / 0.4); border-radius: 0.5rem; color: rgb(var(--c-accent-deep)); font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                            <img id="nuevo-plato-imagen-preview" alt=""
                                 style="display: none; width: 48px; height: 48px; object-fit: cover; border-radius: 0.375rem; flex-shrink: 0;">
                            <span id="nuevo-plato-imagen-texto">📷 Agregar foto (opcional)</span>
                        </label>
                        <input type="file" id="nuevo-plato-imagen" style="display: none;"
                               accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                    </div>
                `,
                icon: 'question',
                iconColor: 'rgb(var(--c-accent))',
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Agregar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: 'rgb(var(--c-ok))',
                cancelButtonColor: 'rgb(var(--c-danger))',
                didOpen: () => {
                    const input = document.getElementById('nuevo-plato-nombre');
                    if (input) input.focus();

                    const imagenInput = document.getElementById('nuevo-plato-imagen');
                    imagenInput.addEventListener('change', () => {
                        const preview = document.getElementById('nuevo-plato-imagen-preview');
                        const texto = document.getElementById('nuevo-plato-imagen-texto');
                        const file = imagenInput.files[0];

                        if (!file || !validarImagenPlato(file, (msg) => Swal.showValidationMessage(msg))) {
                            imagenInput.value = '';
                            preview.style.display = 'none';
                            texto.textContent = '📷 Agregar foto (opcional)';
                            return;
                        }

                        Swal.resetValidationMessage();
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            preview.src = e.target.result;
                            preview.style.display = 'block';
                            texto.textContent = file.name;
                        };
                        reader.readAsDataURL(file);
                    });
                },
                preConfirm: () => {
                    const nombre = document.getElementById('nuevo-plato-nombre').value.trim();
                    if (!nombre) {
                        Swal.showValidationMessage('El producto necesita un nombre');
                        return false;
                    }

                    const imagen = document.getElementById('nuevo-plato-imagen').files[0] || null;
                    if (imagen && !validarImagenPlato(imagen, (msg) => Swal.showValidationMessage(msg))) {
                        return false;
                    }

                    return {
                        nombre: nombre,
                        precio: document.getElementById('nuevo-plato-precio').value.trim(),
                        descripcion: document.getElementById('nuevo-plato-descripcion').value.trim(),
                        imagen: imagen
                    };
                }
            });

            if (!datos) return;

            Swal.fire({
                ...swalConfig,
                title: 'Guardando...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const formData = new FormData();
                formData.append('categoria_index', categoriaIndex);
                formData.append('nombre', datos.nombre);
                formData.append('precio', datos.precio);
                formData.append('descripcion', datos.descripcion);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

                const url = `{{ route('menus.agregar.plato', ['comercio' => $comercio->slug, 'menu' => $menu->id ?? '0']) }}`;
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.error || data.message || `Error HTTP ${response.status}`);
                }

                // La foto va en un segundo paso, contra el endpoint que ya usa la tarjeta:
                // recién ahora existe el plato al que pertenece.
                if (datos.imagen && ! await subirImagenPlato(categoriaIndex, data.plato_index, datos.imagen)) {
                    Swal.close();
                    // El producto ya quedó guardado, así que no se rehace el alta: se avisa
                    // y se recarga para que puedan reintentar la foto desde la tarjeta.
                    showError('El producto se agregó, pero la foto no se pudo subir. Probá de nuevo desde la tarjeta.');
                    setTimeout(() => window.location.reload(), 2000);
                    return;
                }

                Swal.close();
                showSuccessMessage('Producto agregado');
                // La pantalla se rearma desde el servidor: el índice ES la identidad del
                // plato en todos los endpoints de edición, y un alta o una baja los corre.
                setTimeout(() => window.location.reload(), 700);
            } catch (error) {
                Swal.close();
                console.error('Error al agregar plato:', error);
                showError('Error al agregar el producto: ' + (error.message || 'Error desconocido'));
            }
        }

        // Sube la foto de un plato recién creado (mismo endpoint que la tarjeta)
        async function subirImagenPlato(categoriaIndex, platoIndex, file) {
            try {
                const formData = new FormData();
                formData.append('imagen', file);
                formData.append('categoria_index', categoriaIndex);
                formData.append('plato_index', platoIndex);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

                const url = `{{ route('menus.upload.plato.imagen', ['comercio' => $comercio->slug, 'menu' => $menu->id ?? '0']) }}`;
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                return response.ok && data.success === true;
            } catch (error) {
                console.error('Error al subir la imagen del plato nuevo:', error);

                return false;
            }
        }

        // Eliminar un producto de una categoría
        function eliminarPlato(categoriaIndex, platoIndex, nombrePlato) {
            const menuId = {{ $menu->id ?? 'null' }};
            if (!menuId) {
                showError('El menú no está disponible');
                return;
            }

            showConfirm(`¿Eliminar "${nombrePlato}" del menú?`, async function(confirmed) {
                if (!confirmed) return;

                try {
                    const formData = new FormData();
                    formData.append('categoria_index', categoriaIndex);
                    formData.append('plato_index', platoIndex);
                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                    formData.append('_method', 'DELETE');

                    const url = `{{ route('menus.delete.plato', ['comercio' => $comercio->slug, 'menu' => $menu->id ?? '0']) }}`;
                    const response = await fetch(url, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        throw new Error(data.error || data.message || `Error HTTP ${response.status}`);
                    }

                    showSuccessMessage('Producto eliminado');
                    setTimeout(() => window.location.reload(), 700);
                } catch (error) {
                    console.error('Error al eliminar plato:', error);
                    showError('Error al eliminar el producto: ' + (error.message || 'Error desconocido'));
                }
            });
        }

        // Helper para crear elemento de estado
        function createStatusElement(id, parent) {
            const status = document.createElement('span');
            status.id = id;
            status.className = 'text-accent-blue text-xs opacity-70';
            parent.appendChild(status);
            return status;
        }

        // Sistema de notificaciones mejorado
        function showSuccessMessage(message, duration = 2000) {
            console.log('✅', message);
            
            // Crear notificación visual
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-accent-green text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2 animate-fade-in';
            notification.innerHTML = `
                <span>✓</span>
                <span>${message}</span>
            `;
            document.body.appendChild(notification);
            
            // Remover después de la duración
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                notification.style.transition = 'all 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }, duration);
        }

        // Función para mostrar errores mejorada
        function showError(message, duration = 4000) {
            console.error('❌', message);
            
            // Crear notificación de error
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-danger text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2 animate-fade-in';
            notification.innerHTML = `
                <span>✗</span>
                <span>${message}</span>
            `;
            document.body.appendChild(notification);
            
            // Remover después de la duración
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                notification.style.transition = 'all 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }, duration);
        }

        // Sistema de historial simple para undo básico (último cambio)
        const undoHistory = {
            lastAction: null,
            lastState: null,
            
            save: function(action, state) {
                this.lastAction = action;
                this.lastState = state;
                
                // Mostrar botón de undo temporalmente
                this.showUndoButton();
            },
            
            undo: async function() {
                if (!this.lastAction || !this.lastState) {
                    showError('No hay acciones para deshacer');
                    return;
                }
                
                try {
                    // Restaurar estado anterior según el tipo de acción
                    switch(this.lastAction.type) {
                        case 'nombre':
                            await updatePlatoNombre(this.lastAction.categoriaIndex, this.lastAction.platoIndex, this.lastState);
                            break;
                        case 'descripcion':
                            await updatePlatoDescripcion(this.lastAction.categoriaIndex, this.lastAction.platoIndex, this.lastState);
                            break;
                        case 'precio':
                            await updatePlatoPrecio(this.lastAction.categoriaIndex, this.lastAction.platoIndex, this.lastState);
                            break;
                    }
                    
                    showSuccessMessage('Cambio deshecho');
                    this.clear();
                } catch (error) {
                    showError('Error al deshacer el cambio');
                }
            },
            
            showUndoButton: function() {
                // Remover botón anterior si existe
                const existingBtn = document.getElementById('undo-btn');
                if (existingBtn) {
                    existingBtn.remove();
                }
                
                // Crear botón de undo
                const btn = document.createElement('button');
                btn.id = 'undo-btn';
                btn.className = 'fixed bottom-20 right-6 bg-accent-gray hover:bg-accent-gray text-white px-4 py-2 rounded-lg shadow-lg z-50 flex items-center gap-2 transition-all';
                btn.innerHTML = `
                    <span>↶</span>
                    <span>Deshacer</span>
                `;
                btn.onclick = () => this.undo();
                document.body.appendChild(btn);
                
                // Ocultar después de 5 segundos
                setTimeout(() => {
                    if (btn.parentNode) {
                        btn.style.opacity = '0';
                        setTimeout(() => btn.remove(), 300);
                    }
                }, 5000);
            },
            
            clear: function() {
                this.lastAction = null;
                this.lastState = null;
                const btn = document.getElementById('undo-btn');
                if (btn) btn.remove();
            }
        };
        
        // Agregar atajo de teclado Ctrl+Z para undo
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'z' && !e.target.tagName.match(/INPUT|TEXTAREA/)) {
                e.preventDefault();
                undoHistory.undo();
            }
        });

        // Detectar mensajes de sesión y mostrarlos en modal mejorado
        @if (session('success'))
            document.addEventListener('DOMContentLoaded', function() {
                const successMessage = @json(session('success'));
                const isEdit = @json(isset($isEdit) && $isEdit);
                const menuName = @json($menu->nombre ?? 'el menú');
                
                // Determinar mensaje personalizado según el contexto
                let title = '¡Éxito!';
                let message = successMessage;
                
                if (isEdit) {
                    title = 'Menú Actualizado';
                    message = `El menú "${menuName}" ha sido actualizado exitosamente. Los cambios se han guardado correctamente.`;
                } else {
                    title = 'Menú Guardado';
                    message = successMessage || 'El menú ha sido guardado exitosamente.';
                }
                
                // Mostrar modal mejorado con tema oscuro del sistema
                Swal.fire({
                    ...swalConfig,
                    title: title,
                    html: `
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 1.5rem; padding: 1rem 0;">
                            <p style="color: rgb(var(--c-ink)); font-size: 1.125rem; font-weight: 500; text-align: center; margin: 0; line-height: 1.6;">
                                ${message}
                            </p>
                        </div>
                    `,
                    icon: 'success',
                    iconColor: 'rgb(var(--c-ok))',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: 'rgb(var(--c-ok))',
                    width: '520px',
                    padding: '2rem',
                    showClass: {
                        popup: 'animate-fade-in-down'
                    },
                    hideClass: {
                        popup: 'animate-fade-out-up'
                    },
                    timer: 4000,
                    timerProgressBar: true,
                    timerProgressBarColor: 'rgb(var(--c-ok))',
                    allowOutsideClick: true,
                    allowEscapeKey: true,
                    backdrop: true,
                    customClass: {
                        popup: 'dark-modal',
                        content: 'text-text-secondary',
                        confirmButton: 'bg-accent-green hover:brightness-95'
                    }
                }).then(() => {
                    // Limpiar el mensaje de sesión para que no se muestre de nuevo
                    // Esto se hace automáticamente al recargar, pero por si acaso
                });
            });
        @endif

        @if (session('error'))
            document.addEventListener('DOMContentLoaded', function() {
                const errorMessage = @json(session('error'));
                
                Swal.fire({
                    ...swalConfig,
                    title: 'Error',
                    html: `
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 1.5rem; padding: 1rem 0;">
                            <p style="color: rgb(var(--c-ink)); font-size: 1.125rem; font-weight: 500; text-align: center; margin: 0; line-height: 1.6;">
                                ${errorMessage}
                            </p>
                        </div>
                    `,
                    icon: 'error',
                    iconColor: 'rgb(var(--c-danger))',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: 'rgb(var(--c-danger))',
                    width: '520px',
                    padding: '2rem',
                    customClass: {
                        popup: 'dark-modal',
                        content: 'text-text-secondary',
                        confirmButton: 'bg-danger hover:brightness-95'
                    }
                });
            });
        @endif

        // Variables para promociones
        @if($isEdit && $menu && $menu->procesado && $menu->contexto_gpt)
            @php
                $jsonContent = $menu->contexto_gpt;
                $clean = trim($jsonContent);
                if (strpos($clean, '```json') !== false) {
                    $clean = preg_replace('/```json\s*/', '', $clean);
                    $clean = preg_replace('/```\s*$/', '', $clean);
                }
                $contenido = json_decode(trim($clean), true);
                
                $promocionesCategoria = null;
                if ($contenido && isset($contenido['categorias']) && is_array($contenido['categorias'])) {
                    foreach ($contenido['categorias'] as $cat) {
                        if (isset($cat['nombre']) && strtolower(trim($cat['nombre'])) === 'promociones del día') {
                            $promocionesCategoria = $cat;
                            break;
                        }
                    }
                }
            @endphp
            let promocionIndex = {{ isset($promocionesCategoria['platos']) && is_array($promocionesCategoria['platos']) ? count($promocionesCategoria['platos']) : 0 }};
        @else
            let promocionIndex = 0;
        @endif
                
        // Función para agregar nueva promoción
        function addPromocion() {
            const container = document.getElementById('promociones-container');
            // Generar ID único para la nueva promoción (buscar el máximo existente + 1)
            let maxId = 0;
            const existingItems = container.querySelectorAll('[data-promo-id]');
            existingItems.forEach(item => {
                const id = parseInt(item.getAttribute('data-promo-id')) || 0;
                if (id > maxId) maxId = id;
            });
            const nuevoId = maxId + 1;
            const newPromo = document.createElement('div');
            newPromo.className = 'promocion-item bg-secondary-dark rounded-lg p-4 border border-accent-gray';
            newPromo.setAttribute('data-promo-index', promocionIndex);
            newPromo.setAttribute('data-promo-id', nuevoId);
            
            newPromo.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">
                    <!-- Nombre -->
                    <div class="md:col-span-3">
                        <label class="block text-xs text-text-secondary mb-1.5">Nombre</label>
                        <input type="text" 
                               data-promo-field="nombre"
                               data-promo-index="${promocionIndex}"
                               data-promo-id="${nuevoId}"
                               value=""
                               placeholder="Nombre de la promoción"
                               class="w-full px-3 py-2 bg-background border border-accent-gray rounded-lg text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-blue">
                    </div>
                    
                    <!-- Precio -->
                    <div class="md:col-span-2">
                        <label class="block text-xs text-text-secondary mb-1.5">Precio</label>
                        <div class="flex items-center bg-background border border-accent-gray rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-accent-blue">
                            <span class="text-text-primary font-medium text-sm px-3 py-2 border-r border-accent-gray">$</span>
                            <input type="text" 
                                   data-promo-field="precio"
                                   data-promo-index="${promocionIndex}"
                                   data-promo-id="${nuevoId}"
                                   value=""
                                   placeholder="0"
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                   class="w-full px-3 py-2 bg-transparent text-text-primary focus:outline-none flex-1 text-right">
                        </div>
                    </div>
                    
                    <!-- Descripción -->
                    <div class="md:col-span-4">
                        <label class="block text-xs text-text-secondary mb-1.5">Descripción</label>
                        <textarea data-promo-field="descripcion"
                                  data-promo-index="${promocionIndex}"
                                  data-promo-id="${nuevoId}"
                                  rows="2"
                                  placeholder="Descripción de la promoción"
                                  class="w-full px-3 py-2 bg-background border border-accent-gray rounded-lg text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-blue resize-none"></textarea>
                    </div>
                    
                    <!-- Imagen -->
                    <div class="md:col-span-2">
                        <label class="block text-xs text-text-secondary mb-1.5">Imagen</label>
                        <div id="promo-imagen-container-${nuevoId}">
                            <label class="cursor-pointer">
                                <input type="file" 
                                       accept="image/jpeg,image/png,image/jpg,image/gif"
                                       class="hidden"
                                       onchange="uploadPromocionImagen(event, ${nuevoId})"
                                       id="promo-imagen-input-${nuevoId}">
                                <span class="inline-flex items-center px-3 py-2 bg-accent-blue hover:bg-accent-blue text-white text-sm font-medium rounded-lg transition-colors cursor-pointer">
                                    📷 Subir
                                </span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Botón Eliminar -->
                    <div class="md:col-span-1 flex items-end">
                        <button type="button" 
                                onclick="removePromocion(${nuevoId})"
                                class="w-full bg-danger hover:brightness-95 text-white px-3 py-2 rounded-lg text-sm transition-colors">
                            🗑️
                        </button>
                    </div>
                </div>
            `;
            
            // Insertar al principio del contenedor (arriba)
            if (container.firstChild) {
                container.insertBefore(newPromo, container.firstChild);
            } else {
                container.appendChild(newPromo);
            }
            
            promocionIndex++;
        }
        
        // Función para guardar promociones vía AJAX
        // Almacenar imágenes originales de promociones para preservarlas si no se cargan en el DOM (GLOBAL)
        // Indexar por ID (método más confiable) y también por índice/nombre para compatibilidad
        window.promocionesImagenesOriginales = {};
        @if(isset($promocionesCategoria['platos']) && is_array($promocionesCategoria['platos']))
            @foreach($promocionesCategoria['platos'] as $promoIndex => $promo)
                @php
                    $promoId = $promo['id'] ?? ($promoIndex + 1000000);
                @endphp
                @if(isset($promo['imagen']) && !empty($promo['imagen']))
                    // Indexar SOLO por ID (método más confiable y seguro)
                    // NO indexar por índice/nombre para evitar asignaciones incorrectas
                    window.promocionesImagenesOriginales[{{ $promoId }}] = '{{ $promo['imagen'] }}';
                @endif
            @endforeach
        @endif
        
        // Array para almacenar logs del frontend que se enviarán al backend
        window.frontendLogs = [];
        
        // Función auxiliar para agregar logs que se enviarán al backend
        function addFrontendLog(message) {
            const timestamp = new Date().toISOString();
            window.frontendLogs.push(`[${timestamp}] ${message}`);
            console.log(message); // También mostrar en consola
        }
        
        // Función auxiliar para recopilar datos de promociones
        function collectPromocionesData() {
            // Limpiar logs anteriores
            window.frontendLogs = [];
            
            addFrontendLog('🔍 ========== INICIO collectPromocionesData ==========');
            const container = document.getElementById('promociones-container');
            if (!container) {
                addFrontendLog('⚠️ No existe contenedor de promociones (menú nuevo)');
                return [];
            }
            
            const allItems = Array.from(container.querySelectorAll('.promocion-item'));
            addFrontendLog(`📋 Total items encontrados en DOM: ${allItems.length}`);
            const promociones = [];
            
            allItems.forEach((item, idx) => {
                addFrontendLog(`\n📦 Procesando item ${idx}:`);
                
                // Verificar que está en el DOM y es visible
                if (!item.isConnected || !container.contains(item)) {
                    addFrontendLog(`  ⚠️ Item ${idx} no está conectado al DOM, saltando`);
                    return;
                }
                
                const style = window.getComputedStyle(item);
                if (style.display === 'none' || style.visibility === 'hidden' || !item.offsetParent) {
                    addFrontendLog(`  ⚠️ Item ${idx} no es visible, saltando`);
                    return;
                }
                
                const index = item.getAttribute('data-promo-index');
                const promoId = item.getAttribute('data-promo-id');
                const imagenBorrada = item.getAttribute('data-imagen-borrada') === 'true';
                
                addFrontendLog(`  ID: ${promoId}, Index: ${index}, Imagen borrada: ${imagenBorrada}`);
                
                if (!index && !promoId) {
                    console.warn(`  ⚠️ Item ${idx} no tiene data-promo-index ni data-promo-id, saltando`);
                    return;
                }
                
                const promo = {
                    id: promoId ? parseInt(promoId) : null, // ID único
                    nombre: '',
                    precio: '',
                    descripcion: ''
                };
                
                // Recopilar datos de los inputs
                const inputsInItem = item.querySelectorAll('[data-promo-field]');
                
                inputsInItem.forEach(input => {
                    const field = input.getAttribute('data-promo-field');
                    if (field) {
                        let value = input.value || '';
                        
                        // Si es el campo precio, formatear con signo $ y solo números
                        if (field === 'precio') {
                            // Extraer solo números
                            const valorNumerico = value.toString().replace(/[^0-9]/g, '');
                            // Formatear con signo $ si hay números
                            value = valorNumerico ? `$ ${valorNumerico}` : '';
                        }
                        
                        promo[field] = value;
                    }
                });
                
                // Buscar imagen - primero en el DOM, luego en originales SOLO por ID
                let imagenEncontrada = '';
                const img = item.querySelector('img[id^="promo-imagen-"]');
                
                addFrontendLog(`  🔍 Buscando imagen para promoción ID ${promo.id}:`);
                addFrontendLog(`    - Img en DOM: ${img ? 'SÍ' : 'NO'}`);
                if (img) {
                    addFrontendLog(`    - Img src: ${img.src}`);
                }
                
                // 1. Buscar en el DOM primero
                if (img && img.src) {
                    const imgPath = img.src.replace(window.location.origin, '');
                    addFrontendLog(`    - Img path procesado: ${imgPath}`);
                    // Solo agregar imagen si tiene una ruta válida y no es un placeholder
                    // VALIDACIÓN ESTRICTA: Debe ser una ruta válida de storage
                    // IMPORTANTE: Si la imagen está en el DOM, confiar en ella y enviarla al backend
                    // El backend verificará si existe físicamente antes de asignarla
                    if (imgPath && 
                        !imgPath.includes('placeholder') && 
                        !imgPath.includes('data:') && 
                        imgPath.startsWith('/storage/') &&
                        imgPath.length > 10) { // Validar que tenga contenido real
                        imagenEncontrada = imgPath;
                        addFrontendLog(`    ✅ Imagen encontrada en DOM: ${imagenEncontrada}`);
                        addFrontendLog(`    ℹ️ NOTA: El backend verificará si existe físicamente antes de asignarla`);
                    } else {
                        addFrontendLog(`    ⚠️ Imagen en DOM no es válida (placeholder o formato incorrecto)`);
                    }
                }
                
                // 2. Si NO se encontró en el DOM, buscar en originales SOLO por ID
                // IMPORTANTE: Solo buscar si el ID existe en originales (promoción existente)
                if (!imagenEncontrada && promo.id) {
                    const originales = window.promocionesImagenesOriginales || {};
                    addFrontendLog(`    - Buscando en originales por ID ${promo.id}:`);
                    addFrontendLog(`    - Total originales disponibles: ${Object.keys(originales).length}`);
                    addFrontendLog(`    - IDs en originales: ${Object.keys(originales).map(k => `ID ${k}`).join(', ') || 'NINGUNO'}`);
                    if (originales[promo.id]) {
                        addFrontendLog(`    - Imagen para ID ${promo.id}: ${originales[promo.id]}`);
                    }
                    // Buscar SOLO por ID - si el ID existe en originales, es una promoción existente
                    if (originales[promo.id] && originales[promo.id].trim()) {
                        imagenEncontrada = originales[promo.id];
                        addFrontendLog(`    ✅ Imagen encontrada en originales: ${imagenEncontrada}`);
                    } else {
                        // ID no existe en originales = promoción nueva, no tiene imagen
                        addFrontendLog(`    ⚠️ ID ${promo.id} NO existe en originales (es nueva o no tiene imagen)`);
                        addFrontendLog(`    ⚠️ Esto significa que la imagen NO se preservará si no está en el DOM`);
                    }
                }
                
                // LÓGICA DE IMAGEN:
                // 1. Si se encontró imagen válida → enviar la ruta
                // 2. Si el item tiene data-imagen-borrada="true" → enviar imagen: '' (usuario la borró)
                // 3. Si no hay imagen y no está marcada como borrada → NO enviar el campo (preservar si existe)
                
                const existeEnOriginales = promo.id && window.promocionesImagenesOriginales && window.promocionesImagenesOriginales[promo.id];
                addFrontendLog(`  🔍 Estado final de imagen para promoción ID ${promo.id}:`);
                addFrontendLog(`    - imagenEncontrada: ${imagenEncontrada || 'NO'}`);
                addFrontendLog(`    - imagenBorrada (data-imagen-borrada): ${imagenBorrada}`);
                addFrontendLog(`    - existe en originales: ${existeEnOriginales ? 'SÍ (' + window.promocionesImagenesOriginales[promo.id] + ')' : 'NO'}`);
                
                if (imagenEncontrada && imagenEncontrada.trim() && imagenEncontrada.startsWith('/storage/')) {
                    // Caso 1: Se encontró imagen válida → enviar la ruta
                    promo.imagen = imagenEncontrada;
                    addFrontendLog(`  📸 RESULTADO: Promoción tiene imagen → ${promo.imagen}`);
                } else if (imagenBorrada) {
                    // Caso 2: Usuario borró la imagen explícitamente → enviar imagen: '' para que NO se preserve
                    promo.imagen = '';
                    addFrontendLog(`  🗑️ RESULTADO: Imagen fue borrada → enviando imagen: '' (NO preservar)`);
                } else {
                    // Caso 3: No hay imagen y no fue borrada → NO enviar el campo
                    // El backend preservará si existe en existentes
                    addFrontendLog(`  ℹ️ RESULTADO: NO tiene imagen → campo 'imagen' NO se enviará (backend preservará si existe)`);
                }
                
                // Verificar si tiene nombre o precio (requisitos del backend)
                const tieneNombre = promo.nombre && promo.nombre.trim() !== '';
                const tienePrecio = promo.precio && promo.precio.trim() !== '';
                
                // Agregar si tiene nombre O precio (el backend requiere esto)
                if (tieneNombre || tienePrecio) {
                    // Asegurar que todos los campos básicos existan (aunque estén vacíos)
                    if (!promo.hasOwnProperty('nombre')) promo.nombre = '';
                    if (!promo.hasOwnProperty('precio')) promo.precio = '';
                    if (!promo.hasOwnProperty('descripcion')) promo.descripcion = '';
                    
                    addFrontendLog(`  ✅ Promoción agregada: ID=${promo.id}, nombre="${promo.nombre}", precio="${promo.precio}", tieneImagen=${promo.hasOwnProperty('imagen')}, imagen=${promo.hasOwnProperty('imagen') ? promo.imagen : 'NO ENVIADA'}`);
                    promociones.push(promo);
                } else {
                    addFrontendLog(`  ⚠️ Promoción sin nombre ni precio, NO se agregará`);
                }
            });
            
            addFrontendLog(`\n📊 Total promociones a enviar: ${promociones.length}`);
            addFrontendLog(`📤 Promociones finales: ${JSON.stringify(promociones, null, 2)}`);
            addFrontendLog('🔍 ========== FIN collectPromocionesData ==========\n');
            
            return promociones;
        }
        
        
        // Función para eliminar promoción
        async function removePromocion(promoId) {
            showConfirm('¿Estás seguro de que quieres eliminar esta promoción?', async function(result) {
                if (!result) return;
                
                // Buscar por ID primero, luego por índice como fallback
                let promoItem = document.querySelector(`#promociones-container [data-promo-id="${promoId}"]`);
                if (!promoItem) {
                    // Fallback: buscar por índice (compatibilidad)
                    promoItem = document.querySelector(`#promociones-container [data-promo-index="${promoId}"]`);
                }
                
                if (!promoItem) {
                    console.warn(`⚠️ No se encontró la promoción con ID ${promoId}`);
                    return;
                }
                
                const promoIndex = promoItem.getAttribute('data-promo-index');
                addFrontendLog(`🗑️ ========== INICIO ELIMINACIÓN PROMOCIÓN ==========`);
                addFrontendLog(`🗑️ ELIMINANDO PROMOCIÓN ID ${promoId} (índice ${promoIndex})...`);
                
                // Log del estado actual de promocionesImagenesOriginales ANTES de eliminar
                const originalesAntes = window.promocionesImagenesOriginales || {};
                addFrontendLog(`📋 Estado de originales ANTES de eliminar: ${JSON.stringify(Object.keys(originalesAntes).map(k => `ID ${k}`).join(', '))}`);
                
                // Verificar si es una promoción nueva (vacía, sin guardar en el backend)
                const nombreInput = promoItem.querySelector('[data-promo-field="nombre"]');
                const precioInput = promoItem.querySelector('[data-promo-field="precio"]');
                const descripcionInput = promoItem.querySelector('[data-promo-field="descripcion"]');
                const img = promoItem.querySelector(`img[id^="promo-imagen-"]`);
                
                const tieneNombre = nombreInput && nombreInput.value && nombreInput.value.trim() !== '';
                const tienePrecio = precioInput && precioInput.value && precioInput.value.trim() !== '';
                const tieneDescripcion = descripcionInput && descripcionInput.value && descripcionInput.value.trim() !== '';
                const tieneImagen = img && img.src && !img.src.includes('placeholder');
                
                // Si no tiene ningún dato, es una promoción nueva que aún no existe en el backend
                // Solo eliminarla del DOM sin llamar al backend
                if (!tieneNombre && !tienePrecio && !tieneDescripcion && !tieneImagen) {
                    console.log(`⚠️ Promoción ID ${promoId} (índice ${promoIndex}) está vacía, eliminando solo del DOM`);
                    
                    // Eliminar inputs relacionados
                    const allPromoInputs = document.querySelectorAll('input[name^="promociones"], textarea[name^="promociones"]');
                    allPromoInputs.forEach(input => {
                        const match = input.name.match(/promociones\[(\d+)\]/);
                        if (match && match[1] === String(promoIndex)) {
                            input.remove();
                        }
                    });
                    
                    // Eliminar del DOM
                    promoItem.style.opacity = '0';
                    promoItem.style.transform = 'scale(0.95)';
                    promoItem.style.transition = 'all 0.3s ease-out';
                    setTimeout(() => {
                        promoItem.remove();
                        console.log(`✅ PROMOCIÓN ID ${promoId} (índice ${promoIndex}) ELIMINADA DEL DOM (era nueva y vacía)`);
                    }, 300);
                    
                    return;
                }
                
                // Si tiene datos, intentar eliminar del backend
                try {
                    const deleteUrl = `{{ route('menus.delete.promocion', ['comercio' => $comercio->slug, 'menu' => $menu->id ?? '0']) }}`;
                    const response = await fetch(deleteUrl, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            promo_id: promoId,
                            promo_index: promoIndex
                        })
                    });
                    
                    const data = await response.json();
                    
                    // Si la promoción no existe en el backend (error "no encontrada"), 
                    // simplemente eliminarla del DOM sin mostrar error
                    if (!response.ok || !data.success) {
                        const errorMsg = data.error || '';
                        if (errorMsg.includes('no encontrada') || errorMsg.includes('not found')) {
                            console.log(`⚠️ Promoción ID ${promoId} (índice ${promoIndex}) no existe en el backend, eliminando solo del DOM`);
                            
                            // Eliminar inputs relacionados
                            const allPromoInputs = document.querySelectorAll('input[name^="promociones"], textarea[name^="promociones"]');
                            allPromoInputs.forEach(input => {
                                const match = input.name.match(/promociones\[(\d+)\]/);
                                if (match && match[1] === String(promoIndex)) {
                                    input.remove();
                                }
                            });
                            
                            // Eliminar del DOM
                            promoItem.style.opacity = '0';
                            promoItem.style.transform = 'scale(0.95)';
                            promoItem.style.transition = 'all 0.3s ease-out';
                            setTimeout(() => {
                                promoItem.remove();
                                console.log(`✅ PROMOCIÓN ID ${promoId} (índice ${promoIndex}) ELIMINADA DEL DOM`);
                            }, 300);
                            
                            return;
                        }
                        
                        // Si es otro tipo de error, sí mostrarlo
                        throw new Error(data.error || 'Error al eliminar la promoción del servidor');
                    }
                    
                    addFrontendLog(`✓ Promoción ID ${promoId} (índice ${promoIndex}) eliminada del JSON en el servidor`);
                    
                    // IMPORTANTE: Eliminar SOLO la promoción eliminada de promocionesImagenesOriginales
                    // NO eliminar otras promociones - esto es CRÍTICO
                    if (window.promocionesImagenesOriginales && promoId) {
                        const imagenEliminada = window.promocionesImagenesOriginales[promoId];
                        const originalesAntes = Object.keys(window.promocionesImagenesOriginales).map(k => `ID ${k}`).join(', ');
                        delete window.promocionesImagenesOriginales[promoId];
                        addFrontendLog(`✅ Imagen eliminada de originales por ID ${promoId}: ${imagenEliminada || 'NO TENÍA'}`);
                        
                        // Log del estado DESPUÉS de eliminar
                        const originalesDespues = window.promocionesImagenesOriginales || {};
                        const originalesDespuesList = Object.keys(originalesDespues).map(k => `ID ${k}`).join(', ');
                        addFrontendLog(`📋 Estado de originales ANTES de eliminar: ${originalesAntes || 'NINGUNO'}`);
                        addFrontendLog(`📋 Estado de originales DESPUÉS de eliminar: ${originalesDespuesList || 'NINGUNO'}`);
                        addFrontendLog(`⚠️ VERIFICAR: Las otras promociones deben mantener sus imágenes en originales`);
                    } else {
                        addFrontendLog(`⚠️ No se pudo eliminar de originales: promoId=${promoId}, originales existe=${!!window.promocionesImagenesOriginales}`);
                    }
                    
                    // IMPORTANTE: NO eliminar la imagen del servidor cuando se elimina una promoción
                    // Razones:
                    // 1. La imagen podría ser reutilizada por otra promoción
                    // 2. Si se recarga la página, el navegador la muestra desde caché pero el archivo ya no existe
                    // 3. El usuario puede querer restaurar la promoción más tarde
                    // La imagen se eliminará automáticamente cuando se elimine explícitamente desde la UI
                    if (img && img.src) {
                        addFrontendLog(`ℹ️ Imagen encontrada para promoción ID ${promoId}, pero NO se eliminará del servidor`);
                        addFrontendLog(`ℹ️ La imagen permanecerá en el servidor por si se necesita más tarde`);
                    }
                    
                    // Eliminar TODOS los inputs relacionados con esta promoción (visibles Y ocultos)
                    const allPromoInputs = document.querySelectorAll('input[name^="promociones"], textarea[name^="promociones"]');
                    allPromoInputs.forEach(input => {
                        const match = input.name.match(/promociones\[(\d+)\]/);
                        if (match && match[1] === String(promoIndex)) {
                            input.remove();
                        }
                    });
                    
                    // Ocultar/eliminar la card del DOM
                    promoItem.style.opacity = '0';
                    promoItem.style.transform = 'scale(0.95)';
                    promoItem.style.transition = 'all 0.3s ease-out';
                    setTimeout(() => {
                        promoItem.remove();
                        addFrontendLog(`✅ PROMOCIÓN ID ${promoId} (índice ${promoIndex}) ELIMINADA COMPLETAMENTE (JSON + DOM)`);
                        addFrontendLog(`🗑️ ========== FIN ELIMINACIÓN PROMOCIÓN ==========`);
                        // Guardar automáticamente después de eliminar (con logs)
                        setTimeout(() => {
                            addFrontendLog(`💾 Guardando promociones después de eliminar promoción ID ${promoId}...`);
                            savePromocionesAuto();
                        }, 500);
                    }, 300);
                    
                } catch (error) {
                    console.error('❌ Error al eliminar promoción:', error);
                    // Solo mostrar error si no es porque no existe
                    if (!error.message.includes('no encontrada') && !error.message.includes('not found')) {
                        showError('Error al eliminar la promoción: ' + error.message);
                    } else {
                        // Si no existe, simplemente eliminarla del DOM
                        promoItem.style.opacity = '0';
                        promoItem.style.transform = 'scale(0.95)';
                        promoItem.style.transition = 'all 0.3s ease-out';
                        setTimeout(() => {
                            promoItem.remove();
                            console.log(`✅ PROMOCIÓN ${index} ELIMINADA DEL DOM (no existía en backend)`);
                        }, 300);
                    }
                }
            });
        }
        
        // Función para eliminar imagen sin confirmación (cuando se elimina toda la promoción)
        async function removePromocionImagenSilent(promoId, imagenPath) {
            try {
                // Obtener el índice del elemento si es necesario para compatibilidad
                const item = document.querySelector(`[data-promo-id="${promoId}"]`);
                const promoIndex = item ? item.getAttribute('data-promo-index') : null;
                
                const deleteUrl = `{{ route('menus.delete.promo.imagen', ['comercio' => $comercio->slug, 'menu' => $menu->id ?? '0']) }}`;
                const response = await fetch(deleteUrl, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        promo_id: promoId,
                        promo_index: promoIndex,
                        imagen_path: imagenPath
                    })
                });
                
                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        console.log(`Imagen de promoción ${promoIndex} eliminada del servidor`);
                    }
                }
            } catch (error) {
                console.error('Error al eliminar imagen del servidor:', error);
                // No mostrar error al usuario, solo log
            }
        }
        
        // Función para subir imagen de promoción
        async function uploadPromocionImagen(event, promoId) {
            const file = event.target.files[0];
            if (!file) return;
            
            // Obtener el índice del elemento si es necesario para compatibilidad
            const item = event.target.closest('.promocion-item');
            const promoIndex = item ? item.getAttribute('data-promo-index') : null;
            
            const formData = new FormData();
            formData.append('imagen', file);
            formData.append('promo_id', promoId);
            if (promoIndex) formData.append('promo_index', promoIndex);
            formData.append('_token', '{{ csrf_token() }}');
            
            const container = document.getElementById(`promo-imagen-container-${promoId}`);
            if (!container) {
                showError('No se encontró el contenedor de imagen');
                return;
            }
            
            const uploadUrl = `{{ route('menus.upload.promo.imagen', ['comercio' => $comercio->slug, 'menu' => $menu->id ?? '0']) }}`;
            
            try {
                container.innerHTML = '<div class="text-text-secondary text-xs">Subiendo...</div>';
                
                const response = await fetch(uploadUrl, {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error('Error al subir la imagen');
                }
                
                const data = await response.json();
                
                if (data.success) {
                    container.innerHTML = `
                        <div class="relative inline-block">
                            <img src="${data.imagen_url}" 
                                 alt="Promoción"
                                 class="w-20 h-20 object-cover rounded-lg border border-accent-gray"
                                 id="promo-imagen-${promoId}">
                            <button type="button"
                                    onclick="removePromocionImagen(${promoId}, '${data.imagen_path}')"
                                    class="absolute -top-2 -right-2 bg-danger hover:brightness-95 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">
                                ×
                            </button>
                        </div>
                    `;
                    // Actualizar imagen en el objeto de imágenes originales SOLO por ID
                    // Esto asegura que si se guarda después, se preserve correctamente
                    if (window.promocionesImagenesOriginales && promoId) {
                        window.promocionesImagenesOriginales[promoId] = data.imagen_path;
                        console.log(`✅ Imagen actualizada en originales por ID ${promoId}: ${data.imagen_path} - se preservará en el próximo guardado`);
                    } else {
                        console.warn(`⚠️ No se pudo actualizar originales: promoId=${promoId}, originales existe=${!!window.promocionesImagenesOriginales}`);
                    }
                    
                    // Eliminar el marcador de "imagen borrada" si existe (se subió una nueva imagen)
                    const item = document.querySelector(`[data-promo-id="${promoId}"]`);
                    if (item && item.getAttribute('data-imagen-borrada') === 'true') {
                        item.removeAttribute('data-imagen-borrada');
                        console.log(`✅ Marcador de imagen borrada eliminado para promoción ID ${promoId}`);
                    }
                    
                    // Guardar automáticamente después de subir imagen
                    setTimeout(() => {
                        if (typeof savePromocionesAuto === 'function') {
                            savePromocionesAuto();
                        }
                    }, 500);
                } else {
                    throw new Error(data.error || 'Error desconocido');
                }
            } catch (error) {
                showError('Error al subir la imagen: ' + error.message);
                // OJO: va el ID, no el índice. El contenedor lo nombra Blade con el ID
                // (promo-imagen-container-{id}), y uploadPromocionImagen espera un ID.
                container.innerHTML = `
                    <label class="cursor-pointer">
                        <input type="file"
                               accept="image/jpeg,image/png,image/jpg,image/gif"
                               class="hidden"
                               onchange="uploadPromocionImagen(event, ${promoId})"
                               id="promo-imagen-input-${promoId}">
                        <span class="inline-flex items-center px-3 py-2 bg-accent-blue hover:bg-accent-blue text-white text-sm font-medium rounded-lg transition-colors cursor-pointer">
                            📷 Subir
                        </span>
                    </label>
                `;
            }
        }
        
        // Función para eliminar imagen de promoción
        async function removePromocionImagen(promoId, imagenPath) {
            showConfirm('¿Estás seguro de que quieres eliminar esta imagen?', async function(result) {
                if (result) {
                    try {
                        // Obtener el índice del elemento si es necesario para compatibilidad
                        const item = document.querySelector(`[data-promo-id="${promoId}"]`);
                        const promoIndex = item ? item.getAttribute('data-promo-index') : null;
                        
                        const deleteUrl = `{{ route('menus.delete.promo.imagen', ['comercio' => $comercio->slug, 'menu' => $menu->id ?? '0']) }}`;
                        const response = await fetch(deleteUrl, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                promo_id: promoId,
                                promo_index: promoIndex,
                                imagen_path: imagenPath
                            })
                        });
                        
                        if (!response.ok) {
                            throw new Error('Error al eliminar la imagen');
                        }
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            // IMPORTANTE: Eliminar imagen de originales ANTES de guardar
                            // Esto previene que se preserve una imagen que fue borrada
                            if (window.promocionesImagenesOriginales && promoId) {
                                delete window.promocionesImagenesOriginales[promoId];
                                console.log(`✅ Imagen eliminada de originales por ID ${promoId} - NO se preservará en el próximo guardado`);
                            }
                            
                            // Marcar el item como "imagen borrada" para que collectPromocionesData envíe imagen: ''
                            const item = document.querySelector(`[data-promo-id="${promoId}"]`);
                            if (item) {
                                item.setAttribute('data-imagen-borrada', 'true');
                                console.log(`✅ Item marcado como imagen borrada para promoción ID ${promoId}`);
                            }
                            
                            const container = document.getElementById(`promo-imagen-container-${promoId}`);
                            if (container) {
                                // OJO: va el ID, no el índice. Con el índice, el botón que queda
                                // acá le pasaba a uploadPromocionImagen un número que buscaba un
                                // contenedor inexistente ("No se encontró el contenedor de imagen"),
                                // y si el índice coincidía con OTRO id, subía la imagen a la promo
                                // equivocada.
                                container.innerHTML = `
                                    <label class="cursor-pointer">
                                        <input type="file"
                                               accept="image/jpeg,image/png,image/jpg,image/gif"
                                               class="hidden"
                                               onchange="uploadPromocionImagen(event, ${promoId})"
                                               id="promo-imagen-input-${promoId}">
                                        <span class="inline-flex items-center px-3 py-2 bg-accent-blue hover:bg-accent-blue text-white text-sm font-medium rounded-lg transition-colors cursor-pointer">
                                            📷 Subir
                                        </span>
                                    </label>
                                `;
                                showSuccess('Imagen eliminada exitosamente');
                                // Guardar automáticamente después de eliminar imagen
                                setTimeout(() => {
                                    if (typeof savePromocionesAuto === 'function') {
                                        savePromocionesAuto();
                                    }
                                }, 500);
                            } else {
                                console.warn(`Contenedor promo-imagen-container-${promoId} no encontrado, pero la imagen fue eliminada del servidor`);
                                showSuccess('Imagen eliminada exitosamente');
                                // Guardar automáticamente después de eliminar imagen
                                setTimeout(() => {
                                    if (typeof savePromocionesAuto === 'function') {
                                        savePromocionesAuto();
                                    }
                                }, 500);
                            }
                        } else {
                            throw new Error(data.error || 'Error desconocido');
                        }
                    } catch (error) {
                        console.error('Error al eliminar imagen:', error);
                        showError('Error al eliminar la imagen: ' + error.message);
                    }
                }
            });
        }

        // Script para el dropdown de Gestión Excel
        document.addEventListener('DOMContentLoaded', function() {
            const dropdown = document.getElementById('excelDropdown');
            const dropdownButton = document.getElementById('excelDropdownButton');
            const dropdownMenu = document.getElementById('excelDropdownMenu');
            const dropdownChevron = document.getElementById('excelDropdownChevron');
            const importFileDropdown = document.getElementById('importFileDropdown');
            const importFormDropdown = document.getElementById('importFormDropdown');
            
            let isOpen = false;
            
            // Toggle dropdown
            if (dropdownButton && dropdownMenu) {
                dropdownButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    isOpen = !isOpen;
                    
                    if (isOpen) {
                        dropdownMenu.classList.remove('hidden');
                        dropdownChevron.style.transform = 'rotate(180deg)';
                    } else {
                        dropdownMenu.classList.add('hidden');
                        dropdownChevron.style.transform = 'rotate(0deg)';
                    }
                });
                
                // Cerrar al hacer clic fuera
                document.addEventListener('click', function(e) {
                    if (isOpen && !dropdown.contains(e.target)) {
                        isOpen = false;
                        dropdownMenu.classList.add('hidden');
                        dropdownChevron.style.transform = 'rotate(0deg)';
                    }
                });
            }
            
            // Manejar importación
            if (importFileDropdown && importFormDropdown) {
                importFileDropdown.addEventListener('change', function(e) {
                    if (this.files && this.files.length > 0) {
                        // Cerrar el dropdown
                        isOpen = false;
                        dropdownMenu.classList.add('hidden');
                        dropdownChevron.style.transform = 'rotate(0deg)';
                        // Enviar formulario
                        importFormDropdown.submit();
                    }
                });
            }
        });
    </script>
</x-app-layout>

