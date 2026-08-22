<x-app-layout>
    <div class="min-h-screen bg-background">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            
            <!-- Header Section -->
            <div class="flex items-center justify-between mb-12">
                <div>
                    <h1 class="text-5xl font-bold text-text-primary mb-2">
                        ✏️ Editar Comercio
                    </h1>
                    <p class="text-xl text-text-secondary">{{ $comercio->nombre }}</p>
                    <p class="text-sm text-text-secondary mt-1">Configurar información, tema, colores y branding</p>
                </div>
            </div>

            @if (session('success'))
                <div class="mb-8 bg-accent-green/20 border border-accent-green text-accent-green px-6 py-4 rounded-xl neon-glow-green">
                    <div class="flex items-center">
                        <div class="w-5 h-5 mr-3">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-8 bg-red-500/20 border border-red-500 text-red-400 px-6 py-4 rounded-xl">
                    <div class="flex items-center">
                        <div class="w-5 h-5 mr-3">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        {{ session('error') }}
                    </div>
                </div>
            @endif

            <form id="comercio-form" method="POST" action="{{ route('comercios.update', $comercio) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                
                <!-- Layout principal con 2 columnas -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Columna principal: Información básica -->
                    <div class="lg:col-span-2 space-y-6">
                        
                        <!-- Información del Comercio -->
                        <div class="metric-card">
                            <div class="flex items-center mb-6">
                                <div class="w-8 h-8 bg-accent-blue rounded-lg flex items-center justify-center mr-3">
                                    <span class="text-white text-sm">🧾</span>
                                </div>
                                <h3 class="text-lg font-semibold text-text-primary">Información del Comercio</h3>
                            </div>
                            
                            <!-- Grid de 2 columnas para inputs -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Nombre -->
                                <div class="md:col-span-2">
                                    <label for="nombre" class="block text-sm font-medium text-text-secondary mb-2">
                                        Nombre del Comercio
                                    </label>
                                    <input type="text" 
                                           id="nombre" 
                                           name="nombre" 
                                           value="{{ old('nombre', $comercio->nombre) }}"
                                           required
                                           class="w-full px-3 py-2 bg-secondary-dark border border-accent-gray rounded-lg text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent transition-all duration-200">
                                    <x-input-error :messages="$errors->get('nombre')" class="mt-1" />
                                </div>

                                {{-- La URL pública, visible siempre: al renombrar cambia, y el
                                     comerciante tiene que ver qué está tocando. Las URLs viejas
                                     quedan como alias, así que los QR impresos siguen andando. --}}
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-text-secondary mb-2">
                                        URL pública del menú
                                    </label>

                                    @if(auth()->user()->isSuperAdmin())
                                        <div class="flex items-center gap-1">
                                            <span class="text-sm text-text-secondary whitespace-nowrap">{{ url('/menu') }}/</span>
                                            <input type="text"
                                                   id="slug"
                                                   name="slug"
                                                   value="{{ old('slug', $comercio->slug) }}"
                                                   pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                                                   class="flex-1 px-3 py-2 bg-secondary-dark border border-accent-gray rounded-lg text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent transition-all duration-200">
                                        </div>
                                        <p class="text-xs text-text-secondary mt-1">
                                            Sólo minúsculas, números y guiones. Si la cambiás acá queda fija:
                                            renombrar el comercio ya no la va a tocar.
                                        </p>
                                    @else
                                        <p class="px-3 py-2 bg-secondary-dark border border-accent-gray rounded-lg text-text-secondary text-sm break-all">
                                            {{ url('/menu/'.$comercio->slug) }}
                                        </p>
                                        <p class="text-xs text-text-secondary mt-1">
                                            @if($comercio->slug_manual)
                                                Esta dirección está fijada. Cambiar el nombre no la modifica.
                                            @else
                                                Si cambiás el nombre, esta dirección se actualiza. Los códigos QR
                                                que ya imprimiste siguen funcionando.
                                            @endif
                                        </p>
                                    @endif

                                    @if($comercio->slugsAnteriores()->exists())
                                        <p class="text-xs text-text-secondary mt-1">
                                            {{ $comercio->slugsAnteriores()->count() }} dirección(es) anterior(es)
                                            siguen redirigiendo acá.
                                        </p>
                                    @endif

                                    <x-input-error :messages="$errors->get('slug')" class="mt-1" />
                                </div>

                                <!-- Dirección -->
                                <div>
                                    <label for="direccion" class="block text-sm font-medium text-text-secondary mb-2">
                                        Dirección
                                    </label>
                                    <input type="text" 
                                           id="direccion" 
                                           name="direccion" 
                                           value="{{ old('direccion', $comercio->direccion) }}"
                                           class="w-full px-3 py-2 bg-secondary-dark border border-accent-gray rounded-lg text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent transition-all duration-200">
                                    <x-input-error :messages="$errors->get('direccion')" class="mt-1" />
                                </div>

                                <!-- Horario -->
                                <div>
                                    <label for="horario" class="block text-sm font-medium text-text-secondary mb-2">
                                        Horario de Atención
                                    </label>
                                    <input type="text" 
                                           id="horario" 
                                           name="horario" 
                                           value="{{ old('horario', $comercio->horario) }}"
                                           placeholder="Ej: Lunes a Viernes 9:00 - 18:00"
                                           class="w-full px-3 py-2 bg-secondary-dark border border-accent-gray rounded-lg text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent transition-all duration-200">
                                    <p class="text-xs text-text-secondary mt-1">Texto libre, como querés que se lea.</p>
                                    <x-input-error :messages="$errors->get('horario')" class="mt-1" />
                                </div>

                                {{-- Horarios reales. Van aparte del texto libre porque de acá
                                     sale el "abierto / cerrado" del menú público, y el texto de
                                     arriba es impredecible (emojis, "de 19 a 2AM", etc.).
                                     Sin cargar nada, el menú no muestra estado — no inventa. --}}
                                <div class="md:col-span-2">
                                    @php $hs = old('horarios', $comercio->horarios ?? []); @endphp

                                    <div class="flex items-center justify-between mb-2">
                                        <label class="block text-sm font-medium text-text-secondary">
                                            ¿Cuándo abren? <span class="text-text-secondary/60 font-normal">(opcional)</span>
                                        </label>
                                        <button type="button" id="horariosLimpiarTodo"
                                                class="text-xs text-text-secondary hover:text-red-400 underline transition-colors duration-200">
                                            Limpiar todo
                                        </button>
                                    </div>

                                    <div id="horariosGrilla" class="rounded-lg border border-accent-gray bg-secondary-dark/40 p-3 space-y-2">
                                        @foreach(\App\Models\Comercio::DIAS as $nro => $nombreDia)
                                            @php
                                                $turnos = $hs[$nro] ?? $hs[(string) $nro] ?? [];
                                                $t1 = $turnos[0] ?? [null, null];
                                                $t2 = $turnos[1] ?? [null, null];
                                                $v = fn ($t, $i) => is_array($t) ? ($t[$i] ?? '') : '';
                                            @endphp
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="w-20 shrink-0 text-xs text-text-secondary">{{ $nombreDia }}</span>

                                                <input type="time" name="horarios[{{ $nro }}][0][desde]" value="{{ $v($t1, 0) }}"
                                                       class="px-2 py-1 bg-primary-dark border border-accent-gray rounded text-text-primary text-xs">
                                                <span class="text-text-secondary text-xs">a</span>
                                                <input type="time" name="horarios[{{ $nro }}][0][hasta]" value="{{ $v($t1, 1) }}"
                                                       class="px-2 py-1 bg-primary-dark border border-accent-gray rounded text-text-primary text-xs">

                                                <span class="text-text-secondary/50 text-xs ml-2">y</span>
                                                <input type="time" name="horarios[{{ $nro }}][1][desde]" value="{{ $v($t2, 0) }}"
                                                       class="px-2 py-1 bg-primary-dark border border-accent-gray rounded text-text-primary text-xs">
                                                <span class="text-text-secondary text-xs">a</span>
                                                <input type="time" name="horarios[{{ $nro }}][1][hasta]" value="{{ $v($t2, 1) }}"
                                                       class="px-2 py-1 bg-primary-dark border border-accent-gray rounded text-text-primary text-xs">

                                                {{-- Vacía la fila = ese día queda cerrado. --}}
                                                <button type="button" data-limpiar-dia
                                                        title="Marcar {{ $nombreDia }} como cerrado"
                                                        class="ml-auto px-2 py-1 rounded text-text-secondary hover:text-red-400 hover:bg-red-500/10 text-xs transition-colors duration-200">
                                                    ✕
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>

                                    <p class="text-xs text-text-secondary mt-1">
                                        Un día vacío = cerrado. El segundo par es para cortar al mediodía.
                                        Si cierran pasada la medianoche, poné la hora igual (ej. 19:00 a 02:00).
                                        Con esto el menú muestra si están abiertos; sin esto no muestra nada.
                                    </p>
                                    <x-input-error :messages="$errors->get('horarios')" class="mt-1" />
                                </div>

                                <!-- Descripción -->
                                <div class="md:col-span-2">
                                    <label for="descripcion" class="block text-sm font-medium text-text-secondary mb-2">
                                        Descripción
                                    </label>
                                    <textarea id="descripcion" 
                                              name="descripcion" 
                                              rows="2"
                                              class="w-full px-3 py-2 bg-secondary-dark border border-accent-gray rounded-lg text-text-primary resize-none focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent transition-all duration-200">{{ old('descripcion', $comercio->descripcion) }}</textarea>
                                    <x-input-error :messages="$errors->get('descripcion')" class="mt-1" />
                                </div>

                                {{-- Frase corta bajo el nombre en el menú público. Va aparte de
                                     la descripción porque recortar un párrafo largo deja la
                                     frase cortada al medio. --}}
                                <div class="md:col-span-2">
                                    <label for="tagline" class="block text-sm font-medium text-text-secondary mb-2">
                                        Frase del menú
                                    </label>
                                    <input type="text"
                                           id="tagline"
                                           name="tagline"
                                           maxlength="80"
                                           value="{{ old('tagline', $comercio->tagline) }}"
                                           placeholder="Ej: Experiencia, sabor e inteligencia en tu mesa."
                                           class="w-full px-3 py-2 bg-secondary-dark border border-accent-gray rounded-lg text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent transition-all duration-200">
                                    <p class="text-xs text-text-secondary mt-1">
                                        Aparece bajo el nombre en el menú público. Máx. 80 caracteres.
                                    </p>
                                    <x-input-error :messages="$errors->get('tagline')" class="mt-1" />
                                </div>

                                {{-- Link de reseñas de Google. Vive acá, en el comercio, y no en
                                     el menú: es de la ficha del negocio y no cambia cuando suben
                                     una carta nueva. El interruptor para mostrarlo está en el
                                     editor de menú, junto a los atajos. --}}
                                <div class="md:col-span-2">
                                    <label for="google_review_url" class="block text-sm font-medium text-text-secondary mb-2">
                                        Link de reseñas de Google
                                    </label>
                                    <input type="url"
                                           id="google_review_url"
                                           name="google_review_url"
                                           maxlength="255"
                                           value="{{ old('google_review_url', $comercio->google_review_url) }}"
                                           placeholder="https://g.page/r/..."
                                           class="w-full px-3 py-2 bg-secondary-dark border border-accent-gray rounded-lg text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent transition-all duration-200">
                                    <p class="text-xs text-text-secondary mt-1">
                                        Se saca del perfil del negocio en Google, en «Pedir reseñas».
                                        Cargado el link, se activa desde el editor del menú.
                                    </p>
                                    <x-input-error :messages="$errors->get('google_review_url')" class="mt-1" />
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Sidebar derecho: Logo, Contacto e Info adicional -->
                    <div class="space-y-4">

                        <!-- Logo del Comercio -->
                        <div class="metric-card">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center">
                                    <div class="w-6 h-6 bg-accent-green rounded-lg flex items-center justify-center mr-2">
                                        <span class="text-white text-xs">🖼️</span>
                                    </div>
                                    <h3 class="text-sm font-semibold text-text-primary">Logo</h3>
                                </div>
                                @if($comercio->logo_url)
                                    <button type="button" onclick="confirmDeleteLogo()" class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-xs font-medium transition-all duration-200">
                                        🗑️
                                    </button>
                                @endif
                            </div>
                            
                            {{-- onerror: si el archivo ya no está en disco, el <img> roto queda
                                 peor que no mostrar nada. --}}
                            @if($comercio->logo_url)
                                <div class="mb-4" id="logoActual">
                                    <img src="{{ asset('storage/' . $comercio->logo_url) }}"
                                         alt="Logo actual"
                                         onerror="document.getElementById('logoActual').remove()"
                                         class="w-16 h-16 object-cover rounded-lg border border-accent-gray mx-auto">
                                </div>
                            @endif

                            {{-- Vista previa de lo que se acaba de elegir, antes de guardar. --}}
                            <div class="mb-4 hidden" id="logoPreviewWrap">
                                <img id="logoPreview" alt="Logo nuevo"
                                     class="w-16 h-16 object-cover rounded-lg border-2 border-accent-blue mx-auto">
                                <p class="text-xs text-accent-blue text-center mt-1">Nuevo — se aplica al guardar</p>
                            </div>

                            <div>
                                <input type="file"
                                       id="logo"
                                       name="logo"
                                       accept="image/*" 
                                       class="w-full px-2 py-2 bg-secondary-dark border border-accent-gray rounded-lg text-text-primary text-xs file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-accent-blue file:text-white hover:file:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-accent-blue transition-all duration-200">
                                <p class="text-xs text-text-secondary mt-1">JPEG, PNG, JPG, GIF. Máx: 2MB</p>
                                <x-input-error :messages="$errors->get('logo')" class="mt-1" />
                            </div>
                        </div>

                        {{-- Portada: la foto de la que se deriva el tema del menú público.
                             Va separada del logo a propósito: el logo es el isotipo y se
                             recorta cuadrado, la portada es una foto del lugar y se usa entera.

                             Feature de plan. El super admin siempre la ve, y un comercio que
                             ya tiene portada la sigue viendo aunque haya bajado de plan, para
                             poder borrarla. --}}
                        @if(auth()->user()->isSuperAdmin() || $comercio->puedePersonalizarPortada() || $comercio->portada_url)
                        <div class="metric-card">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center">
                                    <div class="w-6 h-6 bg-accent-blue rounded-lg flex items-center justify-center mr-2">
                                        <span class="text-white text-xs">🏪</span>
                                    </div>
                                    <h3 class="text-sm font-semibold text-text-primary">Portada</h3>
                                </div>
                                @if($comercio->portada_url)
                                    {{-- Marca el flag y manda el formulario completo: update() exige
                                         nombre, colores y demás, así que un form suelto no pasaría
                                         la validación. --}}
                                    <button type="button"
                                            onclick="showConfirmWithLoader('¿Eliminar la portada? El menú público vuelve al diseño estándar.', r => { if (r) { document.getElementById('delete_portada').value = '1'; this.closest('form').submit(); } }, 'Eliminando...');"
                                            class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-xs font-medium transition-all duration-200">
                                        🗑️
                                    </button>
                                @endif
                            </div>

                            {{-- Estado del procesado. Se consulta solo: antes decía
                                 "recargá en unos segundos" para siempre, y si la cola estaba
                                 caída el comerciante no tenía forma de enterarse. --}}
                            <div class="mb-3 hidden" id="portadaProgreso">
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

                            <div class="mb-3 hidden rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2"
                                 id="portadaError">
                                <p class="text-xs text-red-300">
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

                            @if($comercio->tienePortada())
                                {{-- Encuadre. La imagen se guarda entera y acá se elige qué parte
                                     queda a la vista: es sólo un par de números que van a
                                     `object-position`, así que mover esto no regenera nada. --}}
                                <div id="portadaActual" class="mb-3">
                                    <p class="text-xs text-text-secondary mb-1">Así se ve en el celular</p>
                                    <div class="w-full overflow-hidden rounded-lg border border-accent-gray"
                                         style="aspect-ratio: 376 / 238;">
                                        <img id="focoPreview"
                                             src="{{ asset('storage/' . $comercio->portada_hero_url) }}"
                                             alt="Vista previa del encuadre"
                                             onerror="document.getElementById('portadaActual').remove()"
                                             class="w-full h-full object-cover"
                                             style="object-position: {{ $comercio->focoPortada() }};">
                                    </div>

                                    <p class="text-xs text-text-secondary mt-2 mb-1">
                                        Tocá lo que no puede faltar (el cartel, un plato…)
                                    </p>
                                    <div id="focoPicker" class="relative w-full cursor-crosshair select-none rounded-lg overflow-hidden border border-accent-gray">
                                        <img src="{{ asset('storage/' . $comercio->portada_hero_url) }}"
                                             alt="Elegir el punto focal" draggable="false"
                                             class="w-full block opacity-80">
                                        <span id="focoMarca"
                                              class="pointer-events-none absolute h-5 w-5 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white shadow-[0_0_0_2px_rgba(0,0,0,.5)]"
                                              style="left: {{ $comercio->portada_focal_x ?? 50 }}%; top: {{ $comercio->portada_focal_y ?? 50 }}%;"></span>
                                    </div>

                                    <div class="mt-1 flex items-center justify-between">
                                        <span id="focoValor" class="text-xs text-text-secondary">
                                            {{ $comercio->portada_focal_x ?? 50 }}% · {{ $comercio->portada_focal_y ?? 50 }}%
                                        </span>
                                        <button type="button" id="focoCentrar"
                                                class="text-xs text-text-secondary hover:text-accent-blue underline transition-colors duration-200">
                                            Centrar
                                        </button>
                                    </div>

                                    <input type="hidden" name="portada_focal_x" id="portada_focal_x" value="{{ $comercio->portada_focal_x ?? 50 }}">
                                    <input type="hidden" name="portada_focal_y" id="portada_focal_y" value="{{ $comercio->portada_focal_y ?? 50 }}">
                                </div>

                                {{-- Aviso persistente: la subió igual, pero conviene que sepa
                                     por qué se ve borrosa cada vez que entra acá. --}}
                                @if($comercio->tema_derivado['baja_calidad'] ?? false)
                                    <div class="mb-3 rounded-lg border border-warning/40 bg-warning/10 px-3 py-2">
                                        <p class="text-xs text-warning">
                                            <strong>Calidad baja.</strong>
                                            La imagen es de {{ $comercio->tema_derivado['ancho'] }}×{{ $comercio->tema_derivado['alto'] }}
                                            y se recomienda 900×600 o más. Se ve borrosa en el menú.
                                            Podés reemplazarla cuando quieras.
                                        </p>
                                    </div>
                                @endif
                                <div class="mb-3 flex items-center gap-2">
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
                                            onclick="document.getElementById('primary_color').value='{{ $comercio->tema_derivado['acento_sugerido'] }}'; document.getElementById('primary_color').dispatchEvent(new Event('input'));"
                                            class="w-full mb-3 px-2 py-1.5 rounded-lg border border-accent-gray text-xs text-text-primary hover:bg-white/5 transition-all duration-200">
                                        Usar {{ $comercio->tema_derivado['acento_sugerido'] }} como acento
                                    </button>
                                @endif
                            @endif

                            <input type="hidden" name="delete_portada" id="delete_portada" value="0">

                            @if(auth()->user()->isSuperAdmin() || $comercio->puedePersonalizarPortada())
                                {{-- Vista previa + aviso de calidad. No bloquea: se puede
                                     guardar igual y cambiarla después. --}}
                                <div class="mb-3 hidden" id="portadaPreviewWrap">
                                    <img id="portadaPreview" alt="Portada nueva"
                                         class="w-full h-24 object-cover rounded-lg border-2 border-accent-blue">
                                    <p class="text-xs text-accent-blue mt-1" id="portadaMedidas"></p>
                                </div>

                                <div class="mb-3 hidden rounded-lg border border-warning/40 bg-warning/10 px-3 py-2"
                                     id="portadaAviso">
                                    <p class="text-xs text-warning">
                                        <strong>Calidad baja.</strong>
                                        <span id="portadaAvisoDetalle"></span>
                                        Se va a ver borrosa en el menú. Podés guardarla igual y cambiarla cuando quieras.
                                    </p>
                                </div>

                                <div>
                                    <input type="file"
                                           id="portada"
                                           name="portada"
                                           accept="image/*"
                                           class="w-full px-2 py-2 bg-secondary-dark border border-accent-gray rounded-lg text-text-primary text-xs file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-accent-blue file:text-white hover:file:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-accent-blue transition-all duration-200">
                                    <p class="text-xs text-text-secondary mt-1">
                                        Una foto <strong>del local o de un plato</strong> — de ahí salen el fondo
                                        y los colores del menú. No subas el menú escaneado.
                                        Recomendado: 900×600 o más.
                                    </p>
                                    <x-input-error :messages="$errors->get('portada')" class="mt-1" />
                                </div>
                            @else
                                <p class="text-xs text-text-secondary">
                                    Cambiar la portada requiere un plan que incluya personalización.
                                </p>
                            @endif
                        </div>
                        @endif

                        <!-- Contacto -->
                        <div class="metric-card">
                            <div class="flex items-center mb-4">
                                <div class="w-6 h-6 bg-accent-green hover:brightness-95 rounded-lg flex items-center justify-center mr-2">
                                    <span class="text-white text-xs">📞</span>
                                </div>
                                <h3 class="text-sm font-semibold text-text-primary">Contacto</h3>
                                <span class="ml-2 bg-green-500/20 text-green-400 text-xs px-2 py-1 rounded">Público</span>
                            </div>
                            
                            <div class="space-y-2 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="text-text-secondary flex items-center">
                                        <span class="mr-2">📞</span>
                                        WhatsApp
                                    </span>
                                    <span class="text-text-primary font-medium">{{ $comercio->user->whatsapp ?? 'N/A' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-text-secondary flex items-center">
                                        <span class="mr-2">✉️</span>
                                        Email
                                    </span>
                                    <span class="text-text-primary font-medium text-xs">{{ $comercio->user->email ?? 'N/A' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-text-secondary flex items-center">
                                        <span class="mr-2">📍</span>
                                        Dirección
                                    </span>
                                    <span class="text-text-primary font-medium text-xs">{{ $comercio->direccion ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Información del Sistema -->
                        <div class="metric-card">
                            <div class="flex items-center mb-4">
                                <div class="w-6 h-6 bg-danger hover:brightness-95 rounded-lg flex items-center justify-center mr-2">
                                    <span class="text-white text-xs">ℹ️</span>
                                </div>
                                <h3 class="text-sm font-semibold text-text-primary">Sistema</h3>
                            </div>
                            
                            <div class="space-y-2 text-xs">
                                <div class="flex justify-between items-center">
                                    <span class="text-text-secondary">ID:</span>
                                    <span class="text-text-primary font-mono bg-secondary-dark px-2 py-1 rounded">{{ $comercio->id }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-text-secondary">Creado:</span>
                                    <span class="text-text-primary">{{ $comercio->created_at->format('d/m/Y') }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-text-secondary">Actualizado:</span>
                                    <span class="text-text-primary">{{ $comercio->updated_at->format('d/m') }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-text-secondary">Menús:</span>
                                    <span class="text-text-primary bg-accent-blue/20 text-accent-blue px-2 py-1 rounded">{{ $comercio->menus->count() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Campos ocultos requeridos por el controlador.
                     Los ids son necesarios: applyTheme() y el botón de acento sugerido
                     los buscan por getElementById y sin ellos tiran error. --}}
                <input type="hidden" id="primary_color" name="primary_color" value="{{ $comercio->primary_color ?? '#3B82F6' }}">
                <input type="hidden" id="secondary_color" name="secondary_color" value="{{ $comercio->secondary_color ?? '#1E40AF' }}">
                <input type="hidden" id="accent_color" name="accent_color" value="{{ $comercio->accent_color ?? '#F59E0B' }}">
                <input type="hidden" id="background_color" name="background_color" value="{{ $comercio->background_color ?? '#FFFFFF' }}">
                <input type="hidden" name="chatbot_name" value="{{ $comercio->chatbot_name ?? 'Asistente IA' }}">
                <input type="hidden" name="welcome_message" value="{{ $comercio->welcome_message ?? '¡Hola! Soy tu asistente virtual.' }}">
                <input type="hidden" name="menu_description" value="{{ $comercio->menu_description ?? '' }}">
                <input type="hidden" name="contact_info" value="{{ $comercio->contact_info ?? '' }}">
                <input type="hidden" name="page_title" value="{{ $comercio->page_title ?? '' }}">
                <input type="hidden" name="page_description" value="{{ $comercio->page_description ?? '' }}">
                <input type="hidden" name="favicon_url" value="{{ $comercio->favicon_url ?? '' }}">
                <input type="hidden" name="show_contact_info" value="{{ $comercio->show_contact_info ? '1' : '0' }}">
                <input type="hidden" name="enable_ai_responses" value="{{ $comercio->enable_ai_responses ? '1' : '0' }}">

                    </form>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar logo -->
    <div id="deleteLogoModal" class="fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 w-96">
            <div class="glass-effect rounded-2xl p-6">
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-500/20 mb-4">
                        <span class="text-2xl">🗑️</span>
                    </div>
                    <h3 class="text-xl font-bold text-text-primary mb-2">¿Eliminar logo?</h3>
                    <div class="mb-6">
                        <p class="text-sm text-text-secondary">
                            ¿Estás seguro de que quieres eliminar el logo de este comercio? Esta acción no se puede deshacer.
                        </p>
                    </div>
                    <div class="flex justify-center space-x-4">
                        <button onclick="closeDeleteModal()" class="btn-secondary">
                            Cancelar
                        </button>
                        <button onclick="deleteLogo()" class="btn-danger">
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Vista previa de las imágenes elegidas, antes de guardar. El aviso de
         calidad es informativo: nunca impide guardar. --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const MIN_ANCHO = {{ \App\Services\BrandThemeService::ANCHO_RECOMENDADO }};
            const MIN_ALTO  = {{ \App\Services\BrandThemeService::ALTO_RECOMENDADO }};

            /**
             * Muestra el archivo elegido sin subirlo.
             * onMedidas recibe el ancho/alto reales para poder avisar.
             */
            function vistaPrevia(inputId, imgId, wrapId, onMedidas) {
                const input = document.getElementById(inputId);
                const img = document.getElementById(imgId);
                const wrap = document.getElementById(wrapId);
                if (!input || !img || !wrap) return;

                input.addEventListener('change', function () {
                    const archivo = this.files && this.files[0];

                    if (!archivo || !archivo.type.startsWith('image/')) {
                        wrap.classList.add('hidden');
                        if (onMedidas) onMedidas(null);
                        return;
                    }

                    // Se libera la URL anterior para no acumular blobs si el
                    // usuario prueba varias imágenes seguidas.
                    if (img.dataset.blob) URL.revokeObjectURL(img.dataset.blob);

                    const url = URL.createObjectURL(archivo);
                    img.dataset.blob = url;
                    img.src = url;
                    wrap.classList.remove('hidden');

                    if (onMedidas) {
                        img.onload = () => onMedidas({
                            ancho: img.naturalWidth,
                            alto: img.naturalHeight,
                            peso: archivo.size,
                        });
                    }
                });
            }

            vistaPrevia('logo', 'logoPreview', 'logoPreviewWrap');

            // ── Punto focal de la portada ────────────────────────────────
            // Sólo mueve dos números; la vista previa se actualiza en vivo
            // porque el encuadre lo hace object-position, no un recorte.
            (function () {
                const picker = document.getElementById('focoPicker');
                if (!picker) return;

                const marca = document.getElementById('focoMarca');
                const preview = document.getElementById('focoPreview');
                const valor = document.getElementById('focoValor');
                const inputX = document.getElementById('portada_focal_x');
                const inputY = document.getElementById('portada_focal_y');

                function aplicar(x, y) {
                    x = Math.max(0, Math.min(100, Math.round(x)));
                    y = Math.max(0, Math.min(100, Math.round(y)));

                    inputX.value = x;
                    inputY.value = y;
                    marca.style.left = x + '%';
                    marca.style.top = y + '%';
                    preview.style.objectPosition = x + '% ' + y + '%';
                    valor.textContent = x + '% · ' + y + '%';
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
                picker.addEventListener('pointerup', function () { arrastrando = false; });
                picker.addEventListener('pointercancel', function () { arrastrando = false; });

                document.getElementById('focoCentrar')?.addEventListener('click', function () {
                    aplicar(50, 50);
                });
            })();

            // ── Limpiar horarios ─────────────────────────────────────────
            // Vaciar los campos alcanza: armarHorarios() descarta los turnos
            // incompletos, así que un día sin horas queda como cerrado.
            (function () {
                const grilla = document.getElementById('horariosGrilla');
                if (!grilla) return;

                function vaciar(contenedor) {
                    contenedor.querySelectorAll('input[type="time"]').forEach(i => { i.value = ''; });
                }

                grilla.addEventListener('click', function (e) {
                    const boton = e.target.closest('[data-limpiar-dia]');
                    if (boton) vaciar(boton.parentElement);
                });

                document.getElementById('horariosLimpiarTodo')?.addEventListener('click', function () {
                    showConfirm('¿Borrar todos los horarios? El menú público va a dejar de mostrar si están abiertos.', function (ok) {
                        if (ok) vaciar(grilla);
                    });
                });
            })();

            // ── Seguimiento del procesado de la portada ──────────────────
            (function () {
                const progreso = document.getElementById('portadaProgreso');
                const errorBox = document.getElementById('portadaError');
                if (!progreso || !errorBox) return;

                const segundosTxt = document.getElementById('portadaSegundos');
                const demora = document.getElementById('portadaDemora');
                const URL_ESTADO = '{{ route('comercios.portada.estado', $comercio) }}';
                const URL_REINTENTO = '{{ route('comercios.portada.reintentar', $comercio) }}';
                const TOKEN = '{{ csrf_token() }}';

                let desde = null;
                let timer = null;

                function mostrar(estado) {
                    progreso.classList.toggle('hidden', estado !== 'procesando');
                    errorBox.classList.toggle('hidden', estado !== 'error');
                }

                function tick() {
                    if (!desde) return;
                    const seg = Math.round((Date.now() - desde) / 1000);
                    if (segundosTxt) segundosTxt.textContent = seg + 's';

                    // A los 30s dejamos de prometer que ya viene: casi siempre
                    // significa que la cola no está corriendo.
                    if (seg >= 30 && demora) demora.classList.remove('hidden');
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
                            mostrar('error');
                        }
                    } catch (e) {
                        // Sin conexión: se reintenta en el próximo ciclo.
                    }
                }

                function arrancar() {
                    if (timer) return;
                    desde = Date.now();
                    mostrar('procesando');
                    if (demora) demora.classList.add('hidden');
                    tick();
                    timer = setInterval(function () { tick(); consultar(); }, 2000);
                }

                function detener() {
                    clearInterval(timer);
                    timer = null;
                    progreso.classList.add('hidden');
                }

                async function reintentar() {
                    detener();
                    try {
                        await fetch(URL_REINTENTO, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': TOKEN, 'Accept': 'application/json' },
                        });
                        errorBox.classList.add('hidden');
                        arrancar();
                    } catch (e) {
                        errorBox.classList.remove('hidden');
                    }
                }

                document.getElementById('portadaReintentar')?.addEventListener('click', reintentar);
                document.getElementById('portadaReintentar2')?.addEventListener('click', reintentar);

                @if($comercio->portada_estado === 'procesando')
                    arrancar();
                @elseif($comercio->portada_estado === 'error')
                    mostrar('error');
                @endif
            })();

            const aviso = document.getElementById('portadaAviso');
            const avisoDetalle = document.getElementById('portadaAvisoDetalle');
            const medidasTxt = document.getElementById('portadaMedidas');

            vistaPrevia('portada', 'portadaPreview', 'portadaPreviewWrap', function (m) {
                if (!aviso) return;

                if (!m) {
                    aviso.classList.add('hidden');
                    return;
                }

                if (medidasTxt) {
                    medidasTxt.textContent =
                        m.ancho + '×' + m.alto + ' · ' + (m.peso / 1024 / 1024).toFixed(1) + ' MB';
                }

                if (m.ancho < MIN_ANCHO || m.alto < MIN_ALTO) {
                    avisoDetalle.textContent =
                        'La imagen es de ' + m.ancho + '×' + m.alto +
                        ' y se recomienda ' + MIN_ANCHO + '×' + MIN_ALTO + ' o más.';
                    aviso.classList.remove('hidden');
                } else {
                    aviso.classList.add('hidden');
                }
            });
        });
    </script>

    <!-- JavaScript para vista previa en tiempo real -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Aplicar tema predefinido
            function applyTheme(theme) {
                const themes = {
                    professional: {
                        primary: '#3B82F6',
                        secondary: '#6B7280', 
                        accent: '#10B981',
                        background: '#FFFFFF'
                    },
                    natural: {
                        primary: '#10B981',
                        secondary: '#84CC16',
                        accent: '#F59E0B', 
                        background: '#F9FAFB'
                    },
                    elegant: {
                        primary: '#8B5CF6',
                        secondary: '#A78BFA',
                        accent: '#EC4899',
                        background: '#FAFAFA'
                    },
                    energetic: {
                        primary: '#F97316',
                        secondary: '#EF4444',
                        accent: '#F59E0B',
                        background: '#FFFBEB'
                    }
                };
                
                const selectedTheme = themes[theme];
                if (selectedTheme) {
                    document.getElementById('primary_color').value = selectedTheme.primary;
                    document.getElementById('secondary_color').value = selectedTheme.secondary;
                    document.getElementById('accent_color').value = selectedTheme.accent;
                    document.getElementById('background_color').value = selectedTheme.background;
                    
                    updatePreview();
                }
            }
            
            // Actualizar vista previa
            function updatePreview() {
                const primaryColorElement = document.getElementById('primary_color');
                const chatbotNameElement = document.getElementById('chatbot_name');
                const welcomeMessageElement = document.getElementById('welcome_message');
                
                const primaryColor = primaryColorElement ? primaryColorElement.value : '#3B82F6';
                const chatbotName = chatbotNameElement ? chatbotNameElement.value : 'Asistente IA';
                const welcomeMessage = welcomeMessageElement ? welcomeMessageElement.value : '¡Hola! Soy tu asistente virtual.';
                
                // Actualizar colores
                const previewElements = document.querySelectorAll('#preview-chatbot-name, .bg-gradient-to-r');
                previewElements.forEach(el => {
                    if (el.classList.contains('bg-gradient-to-r')) {
                        el.style.background = `linear-gradient(to right, ${primaryColor}, #0099CC)`;
                    }
                });
                
                // Actualizar texto (solo si los elementos existen)
                const previewChatbotName = document.getElementById('preview-chatbot-name');
                const previewWelcomeMessage = document.getElementById('preview-welcome-message');
                
                if (previewChatbotName) {
                    previewChatbotName.textContent = chatbotName;
                }
                if (previewWelcomeMessage) {
                    previewWelcomeMessage.textContent = welcomeMessage;
                }
            }
            
            // Event listeners
            document.querySelectorAll('.theme-preset').forEach(preset => {
                preset.addEventListener('click', function(e) {
                    e.preventDefault();
                    const theme = this.dataset.theme;
                    applyTheme(theme);
                });
            });
            
            // Solo agregar event listeners si los elementos existen
            const chatbotNameElement = document.getElementById('chatbot_name');
            const welcomeMessageElement = document.getElementById('welcome_message');
            
            if (chatbotNameElement) {
                chatbotNameElement.addEventListener('input', updatePreview);
            }
            if (welcomeMessageElement) {
                welcomeMessageElement.addEventListener('input', updatePreview);
            }
            
            // Inicializar vista previa
            setTimeout(updatePreview, 100);
        });
        
        // Funciones para manejar logo
        function confirmDeleteLogo() {
            document.getElementById('deleteLogoModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteLogoModal').classList.add('hidden');
        }

        function deleteLogo() {
            console.log('deleteLogo() called');
            
            // Mostrar indicador de carga
            const deleteButton = document.querySelector('button[onclick="deleteLogo()"]');
            LoadingManager.showButtonLoader(deleteButton, 'Eliminando...');
            
            // Crear FormData para enviar datos como formulario
            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('_method', 'PUT');
            formData.append('delete_logo', '1');
            formData.append('nombre', document.querySelector('input[name="nombre"]')?.value || '{{ $comercio->nombre }}');
            formData.append('descripcion', document.querySelector('textarea[name="descripcion"]')?.value || '{{ $comercio->descripcion }}');
            formData.append('direccion', document.querySelector('input[name="direccion"]')?.value || '{{ $comercio->direccion }}');
            formData.append('horario', document.querySelector('input[name="horario"]')?.value || '{{ $comercio->horario }}');
            
            // Agregar campos requeridos por el controlador
            formData.append('primary_color', '{{ $comercio->primary_color ?? "#3B82F6" }}');
            formData.append('secondary_color', '{{ $comercio->secondary_color ?? "#1E40AF" }}');
            formData.append('accent_color', '{{ $comercio->accent_color ?? "#F59E0B" }}');
            formData.append('background_color', '{{ $comercio->background_color ?? "#FFFFFF" }}');
            formData.append('chatbot_name', '{{ $comercio->chatbot_name ?? "Asistente IA" }}');
            formData.append('welcome_message', '{{ $comercio->welcome_message ?? "¡Hola! Soy tu asistente virtual." }}');
            
            // Agregar campos opcionales del controlador
            formData.append('menu_description', '{{ $comercio->menu_description ?? "" }}');
            formData.append('contact_info', '{{ $comercio->contact_info ?? "" }}');
            formData.append('page_title', '{{ $comercio->page_title ?? "" }}');
            formData.append('page_description', '{{ $comercio->page_description ?? "" }}');
            formData.append('favicon_url', '{{ $comercio->favicon_url ?? "" }}');
            formData.append('show_contact_info', '{{ $comercio->show_contact_info ? "1" : "0" }}');
            formData.append('enable_ai_responses', '{{ $comercio->enable_ai_responses ? "1" : "0" }}');
            
            // Usar fetch para hacer la petición AJAX
            fetch('{{ route("comercios.update", $comercio) }}', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (response.ok) {
                    console.log('Logo eliminado exitosamente');
                    // Cerrar modal
                    closeDeleteModal();
                    // Recargar página para mostrar cambios
                    window.location.reload();
                } else {
                    console.error('Error al eliminar logo:', response.statusText);
                    response.text().then(text => {
                        console.error('Response body:', text);
                        alert('Error al eliminar el logo: ' + response.statusText);
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al eliminar el logo: ' + error.message);
            })
            .finally(() => {
                // Restaurar botón usando LoadingManager
                LoadingManager.hideButtonLoader(deleteButton);
            });
        }
    </script>

    <!-- Botón Flotante Guardar Cambios -->
    <div class="fixed bottom-6 right-6 z-50">
        <button type="submit" 
                form="comercio-form"
                class="btn-primary text-lg px-6 py-4 rounded-xl shadow-2xl hover:shadow-3xl transform hover:scale-105 transition-all duration-300 flex items-center space-x-2">
            <span>💾</span>
            <span>Guardar Cambios</span>
        </button>
    </div>
</x-app-layout>
