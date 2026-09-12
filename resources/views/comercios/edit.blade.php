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

            <x-flash />

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
                                {{-- Nombre, descripción, dirección y horario: los mismos campos
                                     que en el alta, desde un solo archivo. --}}
                                <x-comercios.datos-basicos :comercio="$comercio" />

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
                                                class="text-xs text-text-secondary hover:text-danger underline transition-colors duration-200">
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
                                                        class="ml-auto px-2 py-1 rounded text-text-secondary hover:text-danger hover:bg-danger/10 text-xs transition-colors duration-200">
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

                                {{-- La portada, la frase del menú y el link de reseñas NO están
                                     acá: se configuran en la pantalla del menú, junto con los
                                     colores, los atajos y todo lo demás que ve el cliente. Los
                                     campos siguen siendo del comercio, pero repartidos entre dos
                                     pantallas obligaban a ir y volver para dejar una sola cosa
                                     lista (cargar el link acá para poder activar el interruptor
                                     allá, por ejemplo). --}}
                                <div class="md:col-span-2">
                                    <div class="rounded-lg border border-accent-gray bg-secondary-dark px-4 py-3">
                                        <p class="text-sm text-text-primary">
                                            🏪 La <strong>portada</strong>, la <strong>frase del menú</strong> y las
                                            <strong>reseñas de Google</strong> se configuran en la pantalla del menú.
                                        </p>
                                        <a href="{{ route('menus.index', $comercio) }}"
                                           class="mt-2 inline-block text-sm text-accent-blue hover:underline">
                                            Ir a los menús del comercio →
                                        </a>
                                    </div>
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
                                    <button type="button" onclick="confirmDeleteLogo()" class="bg-danger hover:brightness-95 text-white px-2 py-1 rounded text-xs font-medium transition-all duration-200">
                                        🗑️
                                    </button>
                                @endif
                            </div>
                            
                            <x-comercios.logo :comercio="$comercio" />
                        </div>

                        <!-- Contacto -->
                        <div class="metric-card">
                            <div class="flex items-center mb-4">
                                <div class="w-6 h-6 bg-accent-green hover:brightness-95 rounded-lg flex items-center justify-center mr-2">
                                    <span class="text-white text-xs">📞</span>
                                </div>
                                <h3 class="text-sm font-semibold text-text-primary">Contacto</h3>
                                <span class="ml-2 bg-accent-green/20 text-accent-green text-xs px-2 py-1 rounded">Público</span>
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
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-danger/20 mb-4">
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

    {{-- Vista previa del logo elegido, antes de guardar. Lo de la portada se fue
         a la pantalla del menú y se lleva su propio JS. --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
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
