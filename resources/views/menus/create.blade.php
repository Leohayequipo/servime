<x-app-layout>
    <style>
        /* Botón Actualizar Menú - Verde forzado */
        #btn-actualizar-menu,
        .btn-actualizar-menu,
        button[type="submit"][id="btn-actualizar-menu"] {
            background-color: #10b981 !important;
            border: 2px solid rgba(16, 185, 129, 0.3) !important;
        }
        
        #btn-actualizar-menu:hover,
        .btn-actualizar-menu:hover {
            background-color: #059669 !important;
        }
        
        /* Forzar estilos para inputs */
        input:not([type="checkbox"]):not([type="radio"]):not([type="file"]):not([type="range"]),
        select,
        textarea {
            background-color: #1c1f26 !important;
            color: white !important;
            border-color: #374151 !important;
        }

        .faq-container {
            background-color: #1c1f26 !important;
            border: 1px solid #374151 !important;
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
            background: rgba(0, 0, 0, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .icon-modal-content {
            background: #1c1f26;
            border-radius: 1rem;
            padding: 2rem;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            border: 1px solid #374151;
        }
        
        .icon-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .icon-option {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 0.5rem;
            padding: 0.75rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 1.5rem;
        }
        
        .icon-option:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.1);
        }
        
        select option {
            background-color: #1c1f26 !important;
            color: white !important;
        }

        select:focus {
            outline: none !important;
            border-color: rgba(245, 158, 11, 0.5) !important;
            box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.15) !important;
        }
        
        /* Modal de archivos */
        .file-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .file-modal-content {
            background: #1c1f26;
            border-radius: 1rem;
            padding: 2rem;
            max-width: 90vw;
            max-height: 90vh;
            overflow: hidden;
            border: 1px solid #374151;
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
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            
            <!-- Header Section -->
            <div class="flex items-center justify-between mb-12">
                <div>
                    <h1 class="text-3xl font-bold text-text-primary mb-2">
                        {{ $isEdit ? 'Editar Menú' : 'Crear Menú' }}
                    </h1>
                    <p class="text-text-secondary mt-0.5">{{ $comercio->nombre }}</p>
                </div>
                <div class="flex space-x-3">
                    <!-- Dropdown Gestión Excel -->
                    <div class="relative" id="excelDropdown">
                        <button id="excelDropdownButton" 
                                class="bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white font-medium py-3 px-6 rounded-xl transition-all duration-300 shadow-lg inline-flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M3 14h18M10 3v18"/>
                            </svg>
                            Gestión Excel
                            <svg id="excelDropdownChevron" class="w-4 h-4 ml-2 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <div id="excelDropdownMenu" 
                             class="absolute right-0 mt-2 w-56 rounded-xl shadow-lg bg-secondary-dark border border-accent-gray z-50 hidden">
                            <div class="py-2">
                                <!-- Importar desde Excel -->
                                <form id="importFormDropdown" method="POST" action="{{ route('menus.preview.import', $comercio) }}" enctype="multipart/form-data">
                                    @csrf
                                    @if($isEdit && $menu)
                                        <input type="hidden" name="menu_id" value="{{ $menu->id }}">
                                    @endif
                                    <label for="importFileDropdown" class="cursor-pointer block">
                                        <div class="px-4 py-3 text-sm text-text-primary hover:bg-accent-gray/50 transition-colors duration-200 flex items-center">
                                            <svg class="w-5 h-5 mr-3 text-accent-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                            </svg>
                                            <span>⬆️ Importar desde Excel</span>
                                        </div>
                                        <input type="file" 
                                               id="importFileDropdown" 
                                               name="archivo" 
                                               accept=".xlsx,.xls" 
                                               required 
                                               class="hidden">
                                    </label>
                                </form>
                                
                                <!-- Exportar a Excel -->
                                @if($isEdit && $menu && $menu->procesado)
                                    <a href="{{ route('menus.export', [$comercio, $menu]) }}" 
                                       class="block px-4 py-3 text-sm text-text-primary hover:bg-accent-gray/50 transition-colors duration-200 flex items-center">
                                        <svg class="w-5 h-5 mr-3 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        <span>⬇️ Exportar a Excel</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    
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

            <!-- Formulario Principal del Menú -->
            @php
                // Determinar la ruta correcta según si estamos en admin o en comercio
                $updateRoute = $isEdit 
                    ? (request()->is('admin/*') 
                        ? route('admin.menus.update', $menu) 
                        : route('menus.update', [$comercio, $menu]))
                    : route('menus.store', $comercio);
            @endphp
            <form method="POST" action="{{ $updateRoute }}" enctype="multipart/form-data" class="space-y-6 mb-8" id="menu-form">
                            @csrf
                            @if($isEdit)
                                @method('PUT')
                <!-- Campo oculto para mantener el formato actual -->
                @if($menu->formato)
                    <input type="hidden" name="formato" value="{{ $menu->formato }}">
                @endif
                            @endif
            
            <!-- Campos ocultos del chatbot (solo para lectura, NO se envían en el formulario - se guardan automáticamente) -->
            <input type="hidden" id="chatbot_name_hidden" value="{{ $comercio->chatbot_name ?? 'Asistente IA' }}">
            <input type="hidden" id="welcome_message_hidden" value="{{ $comercio->welcome_message ?? '¡Hola! Soy tu asistente virtual. Puedo ayudarte con información sobre nuestro menú. ¿En qué puedo ayudarte?' }}">
            
            <!-- Campos ocultos de colores (solo para lectura, NO se envían en el formulario - se guardan automáticamente) -->
            <input type="hidden" id="primary_color" value="{{ $comercio->primary_color }}">
            <input type="hidden" id="secondary_color" value="{{ $comercio->secondary_color }}">
            <input type="hidden" id="accent_color" value="{{ $comercio->accent_color }}">
            <input type="hidden" id="background_color" value="{{ $comercio->background_color }}">
                            
                            <!-- Información del Comercio -->
                <div class="metric-card">
                            <div class="bg-secondary-dark p-4 rounded-xl mb-6">
                                <h3 class="text-lg font-medium text-text-primary mb-2">Comercio: {{ $comercio->nombre }}</h3>
                                @if($comercio->descripcion)
                                    <p class="text-sm text-text-secondary">{{ $comercio->descripcion }}</p>
                                @endif
                            </div>

                            <!-- Nombre del Menú -->
                    <div class="mb-6">
                                <label for="nombre" class="block text-sm font-medium text-text-secondary mb-3">
                                    Nombre del Menú
                                </label>
                                <input type="text" 
                                       id="nombre" 
                                       name="nombre"
                                       value="{{ $menu->nombre ?? '' }}"
                                       placeholder="Ej: Menú Principal, Menú de Temporada, etc."
                                       class="w-full px-4 py-3 bg-secondary-dark border border-accent-gray rounded-xl text-text-primary placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent transition-all duration-200">
                                <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                            </div>

                            <!-- Menú Actual (solo en edición) -->
                            @if($isEdit && $menu && $menu->archivo_original)
                                <div class="bg-secondary-dark border border-accent-gray rounded-xl p-6 mb-6 shadow-lg hover:shadow-xl transition-shadow duration-300">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div class="flex items-center space-x-4 flex-1 min-w-0">
                                            @if($menu->formato === 'pdf')
                                                <div class="w-14 h-14 bg-gradient-to-br from-red-500/20 to-red-600/20 border border-red-500/40 rounded-xl flex items-center justify-center flex-shrink-0 shadow-md">
                                                    <span class="text-red-400 text-2xl">📄</span>
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

                            <!-- Archivo del Menú -->
                    <div class="mb-6">
                                <label for="archivo" class="block text-sm font-medium text-text-secondary mb-3">
                                    {{ $isEdit ? 'Nuevo Archivo (opcional)' : 'Archivo del Menú' }}
                                </label>
                                <input type="file" 
                                       id="archivo" 
                                       name="archivo" 
                                       accept=".pdf,.jpeg,.jpg,.png,.gif" 
                                       {{ $isEdit ? '' : 'required' }} 
                                       class="w-full px-4 py-3 bg-secondary-dark border border-accent-gray rounded-xl text-text-primary file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-accent-blue file:text-black hover:file:opacity-80 focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent transition-all duration-200" />
                                <x-input-error :messages="$errors->get('archivo')" class="mt-2" />
                                <p class="mt-2 text-sm text-text-secondary">
                                    @if($isEdit)
                                        Deja vacío para mantener el archivo actual. Formatos permitidos: PDF, JPEG, PNG, JPG, GIF. Tamaño máximo: 10MB
                                    @else
                                        Formatos permitidos: PDF, JPEG, PNG, JPG, GIF. Tamaño máximo: 10MB.
                                    @endif
                                </p>
                            </div>

                            <!-- Información sobre el procesamiento -->
                    <div class="bg-accent-blue/10 border border-accent-blue/30 rounded-xl p-4 mb-6">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <div class="w-6 h-6 bg-accent-blue/20 rounded-full flex items-center justify-center">
                                            <span class="text-accent-blue text-sm">ℹ️</span>
                                        </div>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-accent-blue">
                                            Procesamiento Automático
                                        </h3>
                                        <div class="mt-2 text-sm text-text-secondary">
                                            <p>Una vez subido el archivo, nuestro sistema procesará automáticamente el menú para extraer la información y estructurarla.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if($isEdit)
                                <div id="pedido-card" class="relative overflow-hidden rounded-2xl mb-6 border pedido-card {{ old('pedido_habilitado', $comercio->pedido_habilitado) ? 'bg-accent-green/10 border-accent-green/40 shadow-lg shadow-accent-green/10' : 'bg-secondary-dark border-accent-gray shadow-lg' }} transition-all duration-300 hover:shadow-xl">
                                    <div class="relative p-6">
                                        <!-- Texto arriba -->
                                        <div class="mb-5">
                                            <div class="flex items-center gap-3 mb-2">
                                                <h3 class="text-white font-bold text-xl tracking-tight">Pedidos en Línea</h3>
                                                @if(old('pedido_habilitado', $comercio->pedido_habilitado))
                                                    <span class="pedido-active-badge inline-flex items-center gap-1.5 px-2.5 py-1 bg-green-500/30 backdrop-blur-sm border border-green-400/50 rounded-lg">
                                                        <span class="pedido-status-dot w-1.5 h-1.5 bg-green-400 rounded-full animate-pulse"></span>
                                                        <span class="text-xs font-semibold text-green-300">ACTIVO</span>
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="text-gray-300 text-sm leading-relaxed">Los clientes podrán agregar platos al pedido directamente desde el menú digital</p>
                                        </div>
                                        
                                        <!-- Icono y botón abajo -->
                                        <div class="flex items-center justify-between gap-4 pt-4 border-t border-white/10">
                                            <div class="relative flex-shrink-0">
                                                <div class="pedido-icon-container w-14 h-14 {{ old('pedido_habilitado', $comercio->pedido_habilitado) ? 'bg-gradient-to-br from-green-400 to-emerald-500 shadow-lg shadow-green-500/50' : 'bg-gradient-to-br from-gray-600 to-gray-700' }} rounded-xl flex items-center justify-center transform transition-all duration-300 hover:scale-110 hover:rotate-3">
                                                    <span class="text-2xl filter drop-shadow-lg">🛒</span>
                                                    @if(old('pedido_habilitado', $comercio->pedido_habilitado))
                                                        <div class="pedido-status-dot absolute -top-1 -right-1 w-4 h-4 bg-green-400 rounded-full border-3 border-secondary-dark animate-pulse"></div>
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
                                                           onchange="updatePedidoStatus(this.checked); savePedidoStatus(this.checked);">
                                                    <div class="w-16 h-9 bg-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-accent-blue/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-1 after:left-[5px] after:bg-white after:border-gray-300 after:border-2 after:rounded-full after:h-7 after:w-7 after:transition-all after:shadow-lg peer-checked:bg-gradient-to-r peer-checked:from-green-500 peer-checked:to-emerald-500 peer-checked:shadow-lg peer-checked:shadow-green-500/50 transition-all duration-300"></div>
                                                </label>
                                                <div id="pedido-status" class="flex items-center justify-center min-w-[100px] h-9 px-4 rounded-xl font-semibold text-sm transition-all duration-300 {{ old('pedido_habilitado', $comercio->pedido_habilitado) ? 'bg-green-500/20 text-green-300 border-2 border-green-400/50 backdrop-blur-sm' : 'bg-gray-600/30 text-gray-400 border-2 border-gray-500/30' }}">
                                                    {{ old('pedido_habilitado', $comercio->pedido_habilitado) ? '✓ Activo' : '○ Inactivo' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                    <!-- Botón de Guardar -->
                    <div class="flex justify-end">
                        <button type="submit" 
                                id="btn-actualizar-menu"
                                style="background-color: #10b981 !important; border: 2px solid rgba(16, 185, 129, 0.3) !important;"
                                class="btn-actualizar-menu text-white font-bold text-lg py-4 px-10 rounded-xl transition-all duration-300 shadow-2xl hover:shadow-green-500/50 hover:scale-105 active:scale-95 transform flex items-center gap-3 hover:opacity-90"
                                onmouseover="this.style.backgroundColor='#059669'"
                                onmouseout="this.style.backgroundColor='#10b981'">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>{{ $isEdit ? 'Actualizar Menú' : 'Crear Menú' }}</span>
                        </button>
                    </div>
                </div>
            </form>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                
                <!-- Columna Izquierda: Configuración del Chatbot -->
                <div class="space-y-6">
                    <div class="metric-card">
                        <div class="flex items-center mb-6">
                            <div class="w-10 h-10 bg-accent-blue/10 rounded-xl flex items-center justify-center mr-3">
                                <span class="text-lg">🤖</span>
                            </div>
                            <h3 class="text-xl font-semibold text-text-primary">Chatbot</h3>
                        </div>
                        
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
                            
                            <!-- Configuraciones -->
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Personalización -->
                <div class="space-y-6">
                    
                    <!-- 👁️ Vista Previa del Tema -->
                    <div class="metric-card">
                        <div class="flex items-center mb-6">
                            <div class="w-10 h-10 bg-accent-blue/10 rounded-xl flex items-center justify-center mr-3">
                                <span class="text-lg">👁️</span>
                            </div>
                            <h3 class="text-xl font-semibold text-text-primary">Vista Previa</h3>
                        </div>
                        
                        <!-- Selector de Temas (Swatches) -->
                        <div class="mb-6">
                            <div class="flex items-center justify-center gap-3 flex-wrap">
                                <!-- Profesional - Azul profesional -->
                                <button type="button" 
                                        class="theme-swatch w-10 h-10 rounded-full transition-all duration-200 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-secondary-dark" 
                                        style="background-color: #3B82F6 !important; border: none !important;"
                                        data-theme="professional"
                                        title="Profesional">
                                </button>
                                
                                <!-- Natural - Verde natural -->
                                <button type="button" 
                                        class="theme-swatch w-10 h-10 rounded-full transition-all duration-200 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-secondary-dark" 
                                        style="background-color: #10B981 !important; border: none !important;"
                                        data-theme="natural"
                                        title="Natural">
                                </button>
                                
                                <!-- Elegante - Púrpura elegante -->
                                <button type="button" 
                                        class="theme-swatch w-10 h-10 rounded-full transition-all duration-200 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-secondary-dark" 
                                        style="background-color: #8B5CF6 !important; border: none !important;"
                                        data-theme="elegant"
                                        title="Elegante">
                                </button>
                                
                                <!-- Energético - Naranja energético -->
                                <button type="button" 
                                        class="theme-swatch w-10 h-10 rounded-full transition-all duration-200 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-secondary-dark" 
                                        style="background-color: #F97316 !important; border: none !important;"
                                        data-theme="energetic"
                                        title="Energético">
                                </button>
                                
                                <!-- Noche Azul - Cyan brillante -->
                                <button type="button" 
                                        class="theme-swatch w-10 h-10 rounded-full transition-all duration-200 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-secondary-dark" 
                                        style="background-color: #06B6D4 !important; border: none !important;"
                                        data-theme="midnight-blue"
                                        title="Noche Azul">
                                </button>
                                
                                <!-- Oro Oscuro - Dorado -->
                                <button type="button" 
                                        class="theme-swatch w-10 h-10 rounded-full transition-all duration-200 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-secondary-dark" 
                                        style="background-color: #FBBF24 !important; border: none !important;"
                                        data-theme="dark-gold"
                                        title="Oro Oscuro">
                                </button>
                                
                                <!-- Púrpura Profundo - Púrpura claro -->
                                <button type="button" 
                                        class="theme-swatch w-10 h-10 rounded-full transition-all duration-200 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-secondary-dark" 
                                        style="background-color: #A78BFA !important; border: none !important;"
                                        data-theme="deep-purple"
                                        title="Púrpura Profundo">
                                </button>
                                
                                <!-- Obsidiana - Turquesa -->
                                <button type="button" 
                                        class="theme-swatch w-10 h-10 rounded-full transition-all duration-200 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-secondary-dark" 
                                        style="background-color: #14B8A6 !important; border: none !important;"
                                        data-theme="obsidian"
                                        title="Obsidiana">
                                </button>
                                    </div>
                        </div>
                        
                        <!-- Preview realista del chatbot móvil -->
                        <div class="border border-accent-gray rounded-2xl p-6 max-w-sm mx-auto min-h-[500px] flex flex-col bg-primary-dark">
                            <!-- Header del comercio -->
                            <div class="mb-4">
                                <!-- Logo centrado -->
                                <div class="flex justify-center mb-3">
                                @if($comercio->logo_url)
                                    <img src="{{ asset('storage/' . $comercio->logo_url) }}" 
                                         alt="{{ $comercio->nombre }}" 
                                             class="w-16 h-16 rounded-full object-cover shadow-2xl">
                                @else
                                    <img src="{{ asset('images/logo_empresa_default.png') }}" 
                                         alt="Logo por defecto de {{ $comercio->nombre }}" 
                                             class="w-16 h-16 rounded-full object-cover shadow-2xl">
                                @endif
                                </div>
                                <!-- Nombre del comercio centrado -->
                                <div class="text-center">
                                    <h4 class="text-text-primary font-semibold text-base leading-tight mb-1">{{ $comercio->nombre }}</h4>
                                    <p class="text-text-secondary text-sm">Menú Digital</p>
                                </div>
                            </div>
                            
                            <!-- Chatbot header -->
                            <div id="preview-chatbot-header" class="bg-accent-blue/20 border border-accent-blue/30 rounded-xl p-3 mb-4">
                                <div class="flex items-center">
                                    <div class="w-6 h-6 bg-white/20 rounded-full flex items-center justify-center mr-2">
                                        <span class="text-white text-xs">🤖</span>
                                    </div>
                                    <span class="text-white font-medium text-sm" id="preview-chatbot-name">
                                        {{ $comercio->chatbot_name ?? 'Asistente IA' }}
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Mensajes de ejemplo -->
                            <div class="space-y-3 mb-4 flex-grow">
                                <div class="bg-secondary-dark rounded-lg p-3">
                                    <p class="text-text-secondary text-xs" id="preview-welcome-message">
                                        {{ $comercio->welcome_message ?? '¡Hola! Soy tu asistente virtual...' }}
                                    </p>
                                </div>
                                <div class="flex justify-end">
                                    <div class="bg-accent-blue rounded-lg p-3 max-w-xs">
                                        <p class="text-white text-xs">¿Qué platos tienen?</p>
                                    </div>
                                </div>
                                <div class="bg-secondary-dark rounded-lg p-3">
                                    <p class="text-text-secondary text-xs">Tenemos una variedad de platos deliciosos...</p>
                                </div>
                                <!-- Mensajes adicionales para llenar espacio -->
                                <div class="flex justify-end">
                                    <div class="bg-accent-blue rounded-lg p-3 max-w-xs">
                                        <p class="text-white text-xs">¿Cuáles son los precios?</p>
                                    </div>
                                </div>
                                <div class="bg-secondary-dark rounded-lg p-3">
                                    <p class="text-text-secondary text-xs">Los precios van desde $50 hasta $115, dependiendo del plato...</p>
                                </div>
                                <div class="flex justify-end">
                                    <div class="bg-accent-blue rounded-lg p-3 max-w-xs">
                                        <p class="text-white text-xs">¿Tienen opciones vegetarianas?</p>
                                    </div>
                                </div>
                                <div class="bg-secondary-dark rounded-lg p-3">
                                    <p class="text-text-secondary text-xs">Sí, tenemos varias opciones vegetarianas disponibles...</p>
                                </div>
                            </div>
                            
                            <!-- Input del chat -->
                            <div class="flex mt-auto">
                                <input type="text" 
                                       placeholder="Escribe tu mensaje..." 
                                       class="flex-1 bg-secondary-dark border border-accent-gray rounded-l-lg px-3 py-2 text-text-primary text-xs placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-accent-blue">
                                <button class="bg-accent-blue hover:opacity-80 px-3 py-2 rounded-r-lg text-black text-xs font-medium transition-opacity duration-200">
                                    Enviar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección de Promociones del Día - ARRIBA DE CONTENIDO EXTRAÍDO, ANCHO COMPLETO -->
            @if($isEdit && $menu)
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
                
                <div class="mt-12">
                    <div class="metric-card">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-orange-500/20 border border-orange-500/30 rounded-xl flex items-center justify-center mr-3">
                                    <span class="text-lg">🔥</span>
                                </div>
                                <h3 class="text-xl font-semibold text-text-primary">Promociones del Día</h3>
                            </div>
                            <div class="flex items-center gap-3">
                                <button type="button" 
                                        onclick="addPromocion()"
                                        class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center gap-2">
                                    <span>➕</span>
                                    <span>Agregar Promoción</span>
                                </button>
                            </div>
                        </div>
                        <div class="bg-orange-900/10 border border-orange-500/30 rounded-xl p-6">
                            
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
                                                           class="w-full px-3 py-2 bg-background border border-accent-gray rounded-lg text-text-primary focus:outline-none focus:ring-2 focus:ring-orange-500">
                                                </div>
                                                
                                                <!-- Precio -->
                                                <div class="md:col-span-2">
                                                    <label class="block text-xs text-text-secondary mb-1.5">Precio</label>
                                                    <div class="flex items-center bg-background border border-accent-gray rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-orange-500">
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
                                                              class="w-full px-3 py-2 bg-background border border-accent-gray rounded-lg text-text-primary focus:outline-none focus:ring-2 focus:ring-orange-500 resize-none">{{ old("promociones.{$promoIndex}.descripcion", $promo['descripcion'] ?? '') }}</textarea>
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
                                                                        class="absolute -top-2 -right-2 bg-red-500 hover:bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">
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
                                                                <span class="inline-flex items-center px-3 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition-colors cursor-pointer">
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
                                                            class="w-full bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-lg text-sm transition-colors">
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

            <!-- 📋 Contenido Extraído del Menú - Sección de ancho completo -->
            @if($menu && $menu->procesado && ($menu->texto_extraido || $menu->contexto_gpt))
                <div class="mt-12">
                    <div class="metric-card">
                        <div class="flex items-center mb-6">
                            <div class="w-10 h-10 bg-accent-blue/10 rounded-xl flex items-center justify-center mr-3">
                                <span class="text-lg">📋</span>
                            </div>
                            <h3 class="text-xl font-semibold text-text-primary">Contenido Extraído</h3>
                        </div>
                        
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
                                <!-- Información del restaurante -->
                                @if(isset($contenido['restaurante']['nombre']) && $contenido['restaurante']['nombre'] !== 'No especificado')
                                    <div class="bg-secondary-dark border border-accent-gray rounded-lg p-4">
                                        <h3 class="text-lg font-semibold text-text-primary mb-2">🏪 {{ $contenido['restaurante']['nombre'] }}</h3>
                                        @if(isset($contenido['restaurante']['descripcion']) && !empty($contenido['restaurante']['descripcion']))
                                            <p class="text-text-secondary">{{ $contenido['restaurante']['descripcion'] }}</p>
                                        @endif
                                    </div>
                                @endif
                                
                                <!-- Categorías y platos -->
                                @php
                                    // Obtener categorías del formato unificado
                                    $categorias = $contenido['categorias'] ?? null;
                                @endphp
                                
                                @if($categorias && is_array($categorias))
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                        @foreach($categorias as $categoriaIndex => $categoria)
                                            @php
                                                // Filtrar "Promociones del Día" ya que tiene su propia sección dedicada
                                                $nombreCategoria = strtolower(trim($categoria['nombre'] ?? ''));
                                                if ($nombreCategoria === 'promociones del día') {
                                                    continue;
                                                }
                                            @endphp
                                            <div class="bg-secondary-dark rounded-lg p-6">
                                                <h4 class="text-lg font-semibold text-text-primary mb-4 border-b border-accent-gray pb-3">
                                                    {{ $categoria['nombre'] ?? 'Sin nombre' }}
                                                </h4>
                                                <div class="space-y-4" id="platos-container-{{ $categoriaIndex }}" data-categoria-index="{{ $categoriaIndex }}">
                                                    @if(isset($categoria['platos']) && is_array($categoria['platos']))
                                                        @foreach($categoria['platos'] as $platoIndex => $plato)
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
                                                            <div class="group plato-item bg-primary-dark border-l-4 border-l-accent-blue border-r border-t border-b border-accent-gray/50 rounded-r-lg p-4 mb-3 hover:bg-primary-dark/90 hover:shadow-xl hover:border-accent-blue/60 transition-all duration-300 cursor-move shadow-lg" 
                                                                 data-plato-index="{{ $platoIndex }}">
                                                                <!-- Nombre del producto (arriba, línea completa) -->
                                                                <div class="flex items-center gap-2 mb-3">
                                                                    <!-- Drag Handle -->
                                                                    <div class="flex items-center justify-center w-5 h-5 text-accent-blue opacity-50 group-hover:opacity-100 transition-opacity cursor-grab active:cursor-grabbing flex-shrink-0">
                                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                                            <path d="M9 5h2v2H9V5zm0 6h2v2H9v-2zm0 6h2v2H9v-2zm4-12h2v2h-2V5zm0 6h2v2h-2v-2zm0 6h2v2h-2v-2z"></path>
                                                                        </svg>
                                                                    </div>
                                                                    <h5 class="text-white font-bold text-lg leading-tight cursor-text hover:text-accent-blue transition-colors capitalize whitespace-normal flex-1"
                                                                        ondblclick="enableEditNombre({{ $categoriaIndex }}, {{ $platoIndex }})"
                                                                        onclick="handleEditClick(event, {{ $categoriaIndex }}, {{ $platoIndex }}, 'nombre')"
                                                                        id="nombre-display-{{ $categoriaIndex }}-{{ $platoIndex }}">
                                                                        {{ $nombrePlato }}
                                                                    </h5>
                                                                    <!-- Botón de edición visible en móvil -->
                                                                    <button type="button"
                                                                            onclick="event.stopPropagation(); enableEditNombre({{ $categoriaIndex }}, {{ $platoIndex }})"
                                                                            class="md:hidden flex-shrink-0 inline-flex items-center justify-center w-8 h-8 bg-accent-blue/20 hover:bg-accent-blue/40 text-accent-blue rounded-lg transition-colors"
                                                                            title="Editar nombre">
                                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                                        </svg>
                                                                    </button>
                                                                </div>
                                                                
                                                                <!-- Foto y Precio (misma línea) -->
                                                                <div class="flex flex-row items-center gap-4 mb-4 pb-3 border-b border-accent-gray/40">
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
                                                                                            class="inline-flex items-center justify-center w-7 h-7 bg-red-600 hover:bg-red-700 text-white rounded transition-colors cursor-pointer shadow-xl"
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
                                                                    
                                                                    <!-- Precio (alineado a la derecha) -->
                                                                    <div class="flex items-center gap-2 flex-shrink-0 ml-auto">
                                                                        <div class="flex items-center bg-secondary-dark border border-accent-blue/50 rounded-lg overflow-hidden shadow-md hover:border-accent-blue transition-colors">
                                                                            <span class="text-accent-blue font-bold text-sm px-2.5 py-1.5 border-r border-accent-blue/40 bg-accent-blue/10">$</span>
                                                                            <input type="text" 
                                                                                   value="{{ preg_replace('/[^0-9]/', '', $plato['precio'] ?? '') }}" 
                                                                                   placeholder="0"
                                                                                   data-original-value="{{ preg_replace('/[^0-9]/', '', $plato['precio'] ?? '') }}"
                                                                                   oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                                                                   onblur="updatePlatoPrecio({{ $categoriaIndex }}, {{ $platoIndex }}, this.value)"
                                                                                   class="text-accent-blue font-bold text-base bg-transparent px-2.5 py-1.5 focus:outline-none focus:ring-2 focus:ring-accent-blue/50 transition-all w-20 text-right placeholder:text-accent-gray/50"
                                                                                   id="precio-input-{{ $categoriaIndex }}-{{ $platoIndex }}">
                                                                        </div>
                                                                        <span class="text-accent-blue text-xs min-w-[16px]" id="precio-status-{{ $categoriaIndex }}-{{ $platoIndex }}"></span>
                                                                    </div>
                                                                </div>
                                                                
                                                                <!-- Bloque 2: Cuerpo (mt-2 o mt-4) -->
                                                                <div class="mt-4 space-y-3">
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
                                                                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-orange-500/20 text-orange-300 border border-orange-500/40 hover:bg-orange-500/30 transition-colors tag-item" 
                                                                                              data-tag-index="{{ $tagIndex }}">
                                                                                            {{ $tag }}
                                                                                            <button type="button" 
                                                                                                    onclick="removeTag({{ $categoriaIndex }}, {{ $platoIndex }}, '{{ $tag }}')"
                                                                                                    class="hover:text-orange-100 transition-colors p-0.5 rounded"
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
                                                                                   class="text-xs bg-secondary-dark border border-accent-gray/70 rounded-md px-2.5 py-1.5 text-text-secondary placeholder:text-accent-gray focus:outline-none focus:ring-2 focus:ring-orange-500/50 focus:border-orange-500/70 hover:border-accent-gray transition-all w-full"
                                                                                   id="tag-input-{{ $categoriaIndex }}-{{ $platoIndex }}"
                                                                                   onkeydown="handleTagInput(event, {{ $categoriaIndex }}, {{ $platoIndex }})"
                                                                                   autocomplete="off">
                                                                            <p class="text-[10px] text-text-secondary mt-1 ml-0.5">💡 Presiona Enter o coma (,) para agregar</p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
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
                <div class="mt-12">
                    <div class="metric-card">
                        <div class="flex items-center mb-6">
                            <div class="w-10 h-10 bg-accent-blue/10 rounded-xl flex items-center justify-center mr-3">
                                <span class="text-lg">📋</span>
                            </div>
                            <h3 class="text-xl font-semibold text-text-primary">Contenido Extraído</h3>
                        </div>
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
                <div class="mt-12">
                    <div class="metric-card">
                        <div class="flex items-center mb-6">
                            <div class="w-10 h-10 bg-accent-blue/10 rounded-xl flex items-center justify-center mr-3">
                                <span class="text-lg">📋</span>
                            </div>
                            <h3 class="text-xl font-semibold text-text-primary">Contenido Extraído</h3>
                        </div>
                        <div class="text-center py-12">
                            <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-accent-blue mx-auto mb-6"></div>
                            <h4 class="text-xl font-medium text-text-primary mb-3">Procesando menú...</h4>
                            <p class="text-text-secondary">El menú se está procesando automáticamente. Vuelve más tarde para ver el contenido extraído.</p>
                        </div>
                    </div>
                </div>
            @else
                <div class="mt-12">
                    <div class="metric-card">
                        <div class="flex items-center mb-6">
                            <div class="w-10 h-10 bg-accent-blue/10 rounded-xl flex items-center justify-center mr-3">
                                <span class="text-lg">📋</span>
                            </div>
                            <h3 class="text-xl font-semibold text-text-primary">Contenido Extraído</h3>
                        </div>
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

        </div>
    </div>

    <!-- PDF.js Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        // Configurar PDF.js
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    </script>
    
    <!-- SortableJS for drag & drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

    <!-- JavaScript para vista previa en tiempo real -->
    <script>
        // Variable global para almacenar el archivo convertido
        let convertedImageFile = null;
        
        // Función global para actualizar el estado visual del toggle de pedidos
        function updatePedidoStatus(isChecked) {
            const pedidoStatus = document.getElementById('pedido-status');
            const card = document.querySelector('.pedido-card') || document.querySelector('[class*="bg-gradient-to-br"][class*="from-green"]');
            
            if (pedidoStatus) {
                if (isChecked) {
                    pedidoStatus.textContent = '✓ Activo';
                    pedidoStatus.className = 'flex items-center justify-center min-w-[100px] h-9 px-4 rounded-xl font-semibold text-sm transition-all duration-300 bg-green-500/20 text-green-300 border-2 border-green-400/50 backdrop-blur-sm';
                } else {
                    pedidoStatus.textContent = '○ Inactivo';
                    pedidoStatus.className = 'flex items-center justify-center min-w-[100px] h-9 px-4 rounded-xl font-semibold text-sm transition-all duration-300 bg-gray-600/30 text-gray-400 border-2 border-gray-500/30';
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
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar estado si el toggle existe
            const pedidoToggle = document.querySelector('.pedido-toggle');
            if (pedidoToggle) {
                updatePedidoStatus(pedidoToggle.checked);
                pedidoToggle.addEventListener('change', function() {
                    updatePedidoStatus(this.checked);
                });
            }
            
            // Interceptar el input de archivo para procesar PDFs
            const fileInput = document.getElementById('archivo');
            if (fileInput) {
                fileInput.addEventListener('change', handleFileSelection);
            }
            
            // Interceptar el envío del formulario
            const menuForm = document.getElementById('menu-form');
            if (menuForm) {
                menuForm.addEventListener('submit', function(event) {
                    // PREVENIR el envío automático
                    event.preventDefault();
                    
                    // Sincronizar campos del chatbot con los campos ocultos ANTES de enviar
                    const chatbotName = document.getElementById('chatbot_name');
                    const chatbotNameHidden = document.getElementById('chatbot_name_hidden');
                    if (chatbotName && chatbotNameHidden) {
                        chatbotNameHidden.value = chatbotName.value;
                    }
                    
                    const welcomeMessage = document.getElementById('welcome_message');
                    const welcomeMessageHidden = document.getElementById('welcome_message_hidden');
                    if (welcomeMessage && welcomeMessageHidden) {
                        welcomeMessageHidden.value = welcomeMessage.value;
                    }
                    
                    // Guardar promociones primero vía AJAX antes de enviar el formulario
                    savePromocionesSilently().then(() => {
                        // Esperar un momento para asegurar que el DOM se actualizó completamente
                        setTimeout(() => {
                            // Sincronizar promociones al formulario (por si acaso)
                            syncPromocionesToForm();
                            
                            // Continuar con el envío normal del formulario
                            setTimeout(() => {
                                handleFormSubmit(event);
                            }, 100);
                        }, 100);
                    }).catch((error) => {
                        console.error('Error al guardar promociones:', error);
                        showError('Error al guardar las promociones: ' + error.message);
                        // No continuar con el envío si falla el guardado de promociones
                    });
                });
            }
            
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
                    },
                    'midnight-blue': {
                        primary: '#1E293B',
                        secondary: '#334155',
                        accent: '#06B6D4',
                        background: '#0F172A'
                    },
                    'dark-gold': {
                        primary: '#1C1917',
                        secondary: '#292524',
                        accent: '#FBBF24',
                        background: '#0C0A09'
                    },
                    'deep-purple': {
                        primary: '#1E1B4B',
                        secondary: '#312E81',
                        accent: '#A78BFA',
                        background: '#0F0D2B'
                    },
                    obsidian: {
                        primary: '#111827',
                        secondary: '#1F2937',
                        accent: '#14B8A6',
                        background: '#030712'
                    }
                };
                
                const selectedTheme = themes[theme];
                if (selectedTheme) {
                    document.getElementById('primary_color').value = selectedTheme.primary;
                    document.getElementById('secondary_color').value = selectedTheme.secondary;
                    document.getElementById('accent_color').value = selectedTheme.accent;
                    document.getElementById('background_color').value = selectedTheme.background;
                    
                    updatePreview();
                    // Actualizar el estado activo del swatch
                    updateActiveSwatch(theme);
                    
                    // Guardar automáticamente en la base de datos
                    saveThemeColors(selectedTheme);
                }
            }
            
            // Función para guardar los colores automáticamente
            async function saveThemeColors(colors) {
                try {
                    const url = '{{ route("menus.update.theme.colors", $comercio) }}';
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    
                    // Crear FormData en lugar de JSON
                    const formData = new FormData();
                    formData.append('primary_color', colors.primary);
                    formData.append('secondary_color', colors.secondary);
                    formData.append('accent_color', colors.accent);
                    formData.append('background_color', colors.background);
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
                    messageEl.className = 'fixed top-4 right-4 z-50 px-4 py-2 rounded-lg shadow-lg bg-green-500 text-white';
                } else {
                    messageEl.className = 'fixed top-4 right-4 z-50 px-4 py-2 rounded-lg shadow-lg bg-red-500 text-white';
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
                    notification.className = 'fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg bg-green-500 text-white flex items-center gap-2 animate-fade-in';
                } else {
                    notification.className = 'fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg bg-red-500 text-white flex items-center gap-2 animate-fade-in';
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
            
            // Actualizar vista previa
            function updatePreview() {
                const primaryColor = document.getElementById('primary_color').value;
                const chatbotName = document.getElementById('chatbot_name').value;
                const welcomeMessage = document.getElementById('welcome_message').value;
                
                // Actualizar color del header del chatbot en la vista previa
                const chatbotHeader = document.getElementById('preview-chatbot-header');
                if (chatbotHeader) {
                    chatbotHeader.style.background = `linear-gradient(to right, ${primaryColor}, #0099CC)`;
                }
                
                // Actualizar texto
                document.getElementById('preview-chatbot-name').textContent = chatbotName;
                document.getElementById('preview-welcome-message').textContent = welcomeMessage;
            }
            
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
            
            // Inicializar el estado activo basado en el tema actual
            function initializeActiveTheme() {
                const currentPrimary = document.getElementById('primary_color').value;
                const themeMap = {
                    '#3B82F6': 'professional',     // Azul
                    '#10B981': 'natural',          // Verde
                    '#8B5CF6': 'elegant',          // Púrpura
                    '#F97316': 'energetic',        // Naranja
                    '#1E293B': 'midnight-blue',    // Azul oscuro
                    '#1C1917': 'dark-gold',        // Negro
                    '#1E1B4B': 'deep-purple',      // Púrpura oscuro
                    '#111827': 'obsidian'          // Negro
                };
                const activeTheme = themeMap[currentPrimary] || 'professional';
                updateActiveSwatch(activeTheme);
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
            
            // Event listeners para guardar automáticamente y actualizar preview
            let chatbotNameTimeout;
            document.getElementById('chatbot_name').addEventListener('input', function() {
                updatePreview();
                // Guardar automáticamente después de 1 segundo sin escribir
                clearTimeout(chatbotNameTimeout);
                chatbotNameTimeout = setTimeout(() => {
                    saveChatbotConfig();
                }, 1000);
            });
            
            let welcomeMessageTimeout;
            document.getElementById('welcome_message').addEventListener('input', function() {
                updatePreview();
                // Guardar automáticamente después de 1 segundo sin escribir
                clearTimeout(welcomeMessageTimeout);
                welcomeMessageTimeout = setTimeout(() => {
                    saveChatbotConfig();
                }, 1000);
            });
            
            // Inicializar vista previa
            setTimeout(updatePreview, 100);
            
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
        
        // Función para sincronizar promociones al form principal
        function syncPromocionesToForm() {
            const menuForm = document.getElementById('menu-form');
            if (!menuForm) {
                console.error('Formulario menu-form no encontrado');
                return;
            }
            
            
            // PASO 1: Obtener SOLO los items de promociones que están VISIBLES en el contenedor
            // IMPORTANTE: Hacer esto ANTES de eliminar inputs para asegurar que tenemos los datos
            const container = document.getElementById('promociones-container');
            if (!container) {
                // Si no existe el contenedor, es un menú nuevo sin promociones aún
                return;
            }
            
            // Obtener TODOS los items del contenedor y verificar que realmente existen en el DOM
            // IMPORTANTE: Hacer una consulta FRESCA del DOM en este momento para asegurar que tenemos items actualizados
            const allItems = Array.from(container.querySelectorAll('.promocion-item'));
            
            // FILTRAR items que realmente están en el DOM ANTES de procesarlos
            // Esto elimina cualquier referencia obsoleta a items que fueron eliminados
            const realItems = allItems.filter(item => {
                if (!item.isConnected) {
                    console.warn(`⚠️ Item con índice ${item.getAttribute('data-promo-index')} NO está conectado al DOM - será ignorado`);
                    return false;
                }
                if (!container.contains(item)) {
                    console.warn(`⚠️ Item con índice ${item.getAttribute('data-promo-index')} NO está dentro del contenedor - será ignorado`);
                    return false;
                }
                return true;
            });
            
            console.log(`Items REALMENTE en el DOM: ${realItems.length} de ${allItems.length}`);
            
            // PASO 2: Recopilar datos de los items VISIBLES Y DESHABILITAR sus inputs para que no se envíen
            const promocionesData = {};
            
            // Iterar sobre cada item REAL y recopilar sus datos
            realItems.forEach(item => {
                const index = item.getAttribute('data-promo-index');
                if (!index) {
                    console.warn('Item de promoción sin data-promo-index:', item);
                    return;
                }
                
                // Verificar que es visible
                const style = window.getComputedStyle(item);
                const isVisible = style.display !== 'none' && style.visibility !== 'hidden' && item.offsetParent !== null;
                
                if (!isVisible) {
                    console.warn(`⚠️ Item ${index} NO es visible - será ignorado`);
                    return;
                }
                
                console.log(`  ✓ Procesando item VISIBLE con índice: ${index}`);
                
                // Inicializar objeto para esta promoción
                if (!promocionesData[index]) {
                    promocionesData[index] = {};
                }
                
                // Buscar TODOS los inputs dentro de este item (solo los que están VISIBLES)
                const inputsInItem = item.querySelectorAll('[data-promo-field]');
                console.log(`    - Inputs encontrados en item ${index}: ${inputsInItem.length}`);
                
                inputsInItem.forEach(input => {
                    const field = input.getAttribute('data-promo-field');
                    if (!field) {
                        console.warn(`Input sin data-promo-field en item ${index}:`, input);
                        return;
                    }
                    
                    // IMPORTANTE: Recopilar el valor ANTES de deshabilitar
                    // Leer el valor directamente del input visible
                    let rawValue = input.value;
                    
                    // Si es el campo precio, formatear con signo $ y solo números
                    if (field === 'precio') {
                        // Extraer solo números
                        const valorNumerico = rawValue.toString().replace(/[^0-9]/g, '');
                        // Formatear con signo $ si hay números
                        rawValue = valorNumerico ? `$ ${valorNumerico}` : '';
                    }
                    const value = rawValue !== null && rawValue !== undefined ? String(rawValue) : '';
                    
                    // Guardar el valor (aunque esté vacío, lo guardamos para que el backend lo reciba)
                    promocionesData[index][field] = value;
                    
                    console.log(`    - Campo ${field}: tipo="${input.tagName}", valor raw="${rawValue}", valor guardado="${value}"`);
                    console.log(`    - Input completo:`, {
                        name: input.name,
                        id: input.id,
                        value: input.value,
                        type: input.type,
                        disabled: input.disabled
                    });
                    
                    // NO deshabilitar aquí - deshabilitar DESPUÉS de crear los inputs ocultos
                    // para asegurar que los valores se lean correctamente
                });
                
                // Buscar imagen en este item VISIBLE
                const img = item.querySelector('img[id^="promo-imagen-"]');
                if (img && img.src) {
                    // Extraer la ruta relativa de la imagen
                    let imgPath = img.src.replace(window.location.origin, '');
                    
                    // Normalizar: si no empieza con /storage/, intentar extraer solo la ruta
                    if (imgPath && !imgPath.startsWith('/storage/')) {
                        // Intentar extraer la ruta desde /storage/ si existe en la URL
                        const storageMatch = imgPath.match(/\/storage\/.*/);
                        if (storageMatch) {
                            imgPath = storageMatch[0];
                        } else {
                            // Si no se puede normalizar, descartar (no es una ruta válida)
                            console.warn(`    ⚠️ Imagen con ruta inválida, descartada: ${imgPath}`);
                            imgPath = null;
                        }
                    }
                    
                    // Solo guardar si es una ruta válida que empieza con /storage/
                    if (imgPath && imgPath.startsWith('/storage/') && !promocionesData[index]['imagen']) {
                        promocionesData[index]['imagen'] = imgPath;
                        console.log(`    - Imagen encontrada y normalizada: ${imgPath}`);
                    }
                }
            });
            
            console.log(`📊 Datos de promociones recopilados: ${Object.keys(promocionesData).length} promociones`);
            console.log(`📊 Detalle de datos recopilados:`, JSON.stringify(promocionesData, null, 2));
            
            // AHORA deshabilitar todos los inputs visibles para que no se envíen
            realItems.forEach(item => {
                const inputsInItem = item.querySelectorAll('[data-promo-field]');
                inputsInItem.forEach(input => {
                    input.disabled = true;
                    console.log(`  - Input ${input.getAttribute('data-promo-field')} deshabilitado`);
                });
            });
            
            // PASO 3: AHORA eliminar TODOS los inputs de promociones existentes (ocultos) del form
            // Solo eliminar inputs OCULTOS (los visibles están dentro de items que ya procesamos)
            const existingPromoInputs = menuForm.querySelectorAll('input[name^="promociones"][type="hidden"], textarea[name^="promociones"][type="hidden"], input[name^="promociones"]:not([data-promo-field]), textarea[name^="promociones"]:not([data-promo-field])');
            console.log(`Eliminando inputs ocultos antiguos del formulario: ${existingPromoInputs.length}`);
            
            // Crear un array de los inputs a eliminar para evitar problemas con NodeList
            const inputsToRemove = Array.from(existingPromoInputs);
            inputsToRemove.forEach(input => {
                // Solo eliminar si NO está dentro de un item visible (para no eliminar inputs visibles)
                const isInVisibleItem = realItems.some(item => item.contains(input));
                if (!isInVisibleItem) {
                    console.log(`  - Eliminando input oculto: ${input.name}`);
                    input.remove();
                }
            });
            
            // Verificar que se eliminaron correctamente - hacer múltiples verificaciones solo para inputs ocultos
            let attempts = 0;
            let remainingInputs = menuForm.querySelectorAll('input[name^="promociones"][type="hidden"], textarea[name^="promociones"][type="hidden"]');
            while (remainingInputs.length > 0 && attempts < 5) {
                const remainingArray = Array.from(remainingInputs);
                remainingArray.forEach(input => {
                    const isInVisibleItem = realItems.some(item => item.contains(input));
                    if (!isInVisibleItem) {
                        console.warn(`  - Eliminando input huérfano: ${input.name}`);
                        input.remove();
                    }
                });
                attempts++;
                remainingInputs = menuForm.querySelectorAll('input[name^="promociones"][type="hidden"], textarea[name^="promociones"][type="hidden"]');
            }
            
            console.log(`✓ Inputs ocultos antiguos eliminados correctamente`);
            
            // PASO 4: Crear inputs ocultos basados en los datos recopilados
            console.log(`📊 Datos de promociones recopilados: ${Object.keys(promocionesData).length} promociones`);
            
            // VERIFICAR que hay datos antes de continuar
            if (Object.keys(promocionesData).length === 0) {
                console.log('ℹ️ No hay promociones para sincronizar (esto es válido si se eliminaron todas)');
                return; // Salir temprano si no hay datos (esto es válido si no hay promociones)
            }
            
            // Crear inputs ocultos en el form principal
            // IMPORTANTE: Guardar TODAS las promociones que están en el DOM, incluso si están vacías
            // porque el usuario puede estar en proceso de editarlas
            let promocionesAgregadas = 0;
            Object.keys(promocionesData).sort((a, b) => parseInt(a) - parseInt(b)).forEach(index => {
                const promo = promocionesData[index];
                
                console.log(`Procesando promoción índice ${index}:`, promo);
                console.log(`  DEBUG - nombre: "${promo.nombre}" (type: ${typeof promo.nombre})`);
                console.log(`  DEBUG - precio: "${promo.precio}" (type: ${typeof promo.precio})`);
                console.log(`  DEBUG - descripcion: "${promo.descripcion}" (type: ${typeof promo.descripcion})`);
                console.log(`  DEBUG - imagen: "${promo.imagen}" (type: ${typeof promo.imagen})`);
                
                // Agregar TODAS las promociones que están en el DOM, incluso si están vacías
                // Solo saltar si no tiene NADA (ni nombre, ni precio, ni descripción, ni imagen)
                const tieneDatos = (promo.nombre !== undefined && String(promo.nombre).trim() !== '') ||
                                   (promo.precio !== undefined && String(promo.precio).trim() !== '') ||
                                   (promo.descripcion !== undefined && String(promo.descripcion).trim() !== '') ||
                                   (promo.imagen !== undefined && String(promo.imagen).trim() !== '');
                
                console.log(`  DEBUG - tieneDatos: ${tieneDatos}`);
                
                if (tieneDatos) {
                    // IMPORTANTE: Siempre agregar los campos básicos (nombre, precio, descripción)
                    // aunque estén vacíos, para que el backend los reciba correctamente
                    // Asegurar que los valores sean strings, no undefined
                    const campos = ['nombre', 'precio', 'descripcion'];
                    campos.forEach(field => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `promociones[${promocionesAgregadas}][${field}]`;
                        // Asegurar que el valor sea string, no undefined o null
                        const fieldValue = (promo[field] !== undefined && promo[field] !== null) ? String(promo[field]) : '';
                        input.value = fieldValue;
                        menuForm.appendChild(input);
                        console.log(`Agregado: promociones[${promocionesAgregadas}][${field}] = "${fieldValue}"`);
                    });
                    
                    // Agregar imagen solo si existe
                    if (promo.imagen && promo.imagen.trim() !== '') {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `promociones[${promocionesAgregadas}][imagen]`;
                        input.value = promo.imagen;
                        menuForm.appendChild(input);
                        console.log(`Agregado: promociones[${promocionesAgregadas}][imagen] = "${promo.imagen}"`);
                    }
                    
                    promocionesAgregadas++;
                } else {
                    console.log(`Saltando promoción ${index} porque no tiene ningún dato (está completamente vacía)`);
                }
            });
            
            console.log(`✅ Promociones sincronizadas: ${promocionesAgregadas}`);
            
            // Verificar que los inputs se agregaron
            const hiddenInputs = menuForm.querySelectorAll('input[name^="promociones"]');
            console.log('📝 Inputs ocultos creados:', hiddenInputs.length);
            hiddenInputs.forEach(input => {
                const valuePreview = input.value.length > 50 ? input.value.substring(0, 50) + '...' : input.value;
                console.log(`  - ${input.name} = "${valuePreview}"`);
            });
            
            // ALERT si no se agregaron inputs
            if (hiddenInputs.length === 0 && Object.keys(promocionesData).length > 0) {
                console.error('❌ ERROR: No se crearon inputs ocultos aunque había datos de promociones');
                alert(`ERROR: No se crearon inputs ocultos. Promociones encontradas: ${Object.keys(promocionesData).length}, Promociones agregadas: ${promocionesAgregadas}`);
            }
        }

        // Función para guardar personalización
        function saveCustomization() {
            // ESPERAR un momento para asegurar que el DOM se actualizó completamente
            setTimeout(() => {
                // Sincronizar promociones al form principal
                syncPromocionesToForm();
                
                // Esperar un momento más y luego enviar
                setTimeout(() => {
                    const menuForm = document.getElementById('menu-form');
                    if (menuForm) {
                        menuForm.submit();
                    }
                }, 100);
            }, 100);
            return;
            
            // Sincronizar campos del chatbot con los campos ocultos
            const chatbotName = document.getElementById('chatbot_name');
            const chatbotNameHidden = document.getElementById('chatbot_name_hidden');
            if (chatbotName && chatbotNameHidden) {
                chatbotNameHidden.value = chatbotName.value;
            }
            
            const welcomeMessage = document.getElementById('welcome_message');
            const welcomeMessageHidden = document.getElementById('welcome_message_hidden');
            if (welcomeMessage && welcomeMessageHidden) {
                welcomeMessageHidden.value = welcomeMessage.value;
            }
            
            // Los campos de colores ya están sincronizados por el JavaScript de los temas
            
            // Debug: verificar valores de colores antes de enviar
            console.log('Valores de colores antes de enviar:', {
                primary: document.getElementById('primary_color').value,
                secondary: document.getElementById('secondary_color').value,
                accent: document.getElementById('accent_color').value,
                background: document.getElementById('background_color').value
            });
            
            // Primero, enviar el formulario del menú directamente
            const menuForm = document.getElementById('menu-form');
            if (menuForm) {
                // Simplemente enviar el formulario original
                menuForm.submit();
                return;
            }
            
            // Crear formulario temporal para enviar POST de personalización
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("comercios.update", $comercio) }}';
            
            // Agregar CSRF token
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            
            // Agregar método PUT
            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'PUT';
            form.appendChild(methodField);
            
            // Agregar todos los campos de personalización
            const fields = [
                'primary_color', 'secondary_color', 'accent_color', 'background_color',
                'chatbot_name', 'welcome_message'
            ];
            
            fields.forEach(fieldName => {
                const element = document.getElementById(fieldName);
                if (element) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = fieldName;
                    input.value = element.value || (element.checked ? '1' : '0');
                    form.appendChild(input);
                }
            });
            
            // Agregar al DOM y enviar
            document.body.appendChild(form);
            form.submit();
        }
        
        function createHiddenInput(name, value) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            return input;
        }
        
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
        
        async function handleFileSelection(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            // NO convertir PDF a imagen - procesarlo directamente
            // El backend procesará el PDF completo directamente con OpenAI Vision
            convertedImageFile = null;
        }
        
        let isSubmitting = false;
        
        function handleFormSubmit(event) {
            // Prevenir envíos dobles
            if (isSubmitting) {
                return false;
            }
            
            isSubmitting = true;
            
            // Obtener el formulario
            const menuForm = document.getElementById('menu-form');
            if (!menuForm) {
                console.error('❌ No se encontró el formulario menu-form');
                isSubmitting = false;
                return false;
            }
            
            // Verificar que el archivo esté presente SOLO si es un menú nuevo
            @if(!isset($isEdit) || !$isEdit)
                // Es un menú nuevo, el archivo es obligatorio
                const fileInput = document.getElementById('archivo');
                if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                    if (!convertedImageFile) {
                        console.error('❌ No hay archivo para enviar');
                        alert('Por favor, selecciona un archivo de menú antes de guardar.');
                        isSubmitting = false;
                        return false;
                    }
                }
            @else
                // Es una edición, el archivo es opcional
            @endif
            
            // Verificar si hay archivo convertido
            if (convertedImageFile) {
                // Mostrar loader
                LoadingManager.showGlobalLoader('Subiendo menú...');
                
                // El archivo convertido se enviará a través del FormData en el botón de guardar
                // Aquí solo permitimos que continúe el flujo
            } else {
                // Mostrar loader
                LoadingManager.showGlobalLoader('Subiendo menú...');
            }
            
            // Enviar el formulario
            // Agregar un pequeño delay para asegurar que el loader se muestre
            setTimeout(() => {
                menuForm.submit();
            }, 100);
            
            return false;
        }
        
        async function pdfToSingleImage(pdfFile) {
            // 1. Cargar el PDF
            const arrayBuffer = await pdfFile.arrayBuffer();
            const pdf = await pdfjsLib.getDocument(arrayBuffer).promise;
            const numPages = pdf.numPages;
            
            LoadingManager.showGlobalLoader(`Procesando PDF: 0/${numPages} páginas...`);
            
            // 2. Renderizar cada página en canvas separados
            const canvases = [];
            let totalHeight = 0;
            let maxWidth = 0;
            
            for (let i = 1; i <= numPages; i++) {
                LoadingManager.showGlobalLoader(`Procesando PDF: ${i}/${numPages} páginas...`);
                
                const page = await pdf.getPage(i);
                const viewport = page.getViewport({ scale: 1.5 }); // Reducido de 2.0 a 1.5 para archivos más pequeños
                
                const canvas = document.createElement('canvas');
                canvas.width = viewport.width;
                canvas.height = viewport.height;
                
                await page.render({
                    canvasContext: canvas.getContext('2d'),
                    viewport: viewport
                }).promise;
                
                canvases.push(canvas);
                totalHeight += viewport.height;
                maxWidth = Math.max(maxWidth, viewport.width);
            }
            
            LoadingManager.showGlobalLoader('Combinando páginas...');
            
            // 3. Combinar todos los canvas en uno solo (vertical)
            const finalCanvas = document.createElement('canvas');
            finalCanvas.width = maxWidth;
            finalCanvas.height = totalHeight;
            const ctx = finalCanvas.getContext('2d');
            
            // Fondo blanco
            ctx.fillStyle = 'white';
            ctx.fillRect(0, 0, maxWidth, totalHeight);
            
            // Pegar cada página una debajo de la otra
            let currentY = 0;
            for (const canvas of canvases) {
                ctx.drawImage(canvas, 0, currentY);
                currentY += canvas.height;
            }
            
            // 4. Convertir a imagen JPEG (más comprimido que PNG)
            const blob = await new Promise(resolve => 
                finalCanvas.toBlob(resolve, 'image/jpeg', 0.85) // JPEG con 85% de calidad
            );
            
            // Crear un nuevo archivo de imagen
            const imageFile = new File([blob], 'menu.jpg', { type: 'image/jpeg' });
            imageFile.pageCount = numPages; // Agregar información de páginas procesadas
            
            return imageFile;
        }
    </script>

    <!-- Modal de selección de iconos -->
    <div id="iconModal" class="icon-modal">
        <div class="icon-modal-content">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-semibold text-white">Seleccionar Icono</h3>
                <button onclick="closeIconModal()" class="text-white hover:text-gray-300 text-2xl">&times;</button>
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
                <h3 class="text-xl font-semibold text-white">Vista Previa del Archivo</h3>
                <button onclick="closeFileModal()" class="text-white hover:text-gray-300 text-2xl ml-4">&times;</button>
            </div>
            
            <div class="file-modal-body" id="fileModalBody">
                <!-- El contenido se carga dinámicamente aquí -->
            </div>
        </div>
    </div>

    <script>
        // Función para subir imagen de plato
        async function uploadPlatoImagen(event, categoriaIndex, platoIndex) {
            const file = event.target.files[0];
            if (!file) return;

            // Validar tamaño (2MB máximo)
            if (file.size > 2 * 1024 * 1024) {
                alert('La imagen no puede ser mayor a 2MB');
                event.target.value = '';
                return;
            }

            // Validar tipo
            if (!file.type.match('image.*')) {
                alert('Solo se permiten archivos de imagen (JPEG, PNG, GIF)');
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
                                    class="inline-flex items-center justify-center w-7 h-7 bg-red-600 hover:bg-red-700 text-white rounded transition-colors cursor-pointer shadow-xl"
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
                                            class="inline-flex items-center justify-center w-7 h-7 bg-red-600 hover:bg-red-700 text-white rounded transition-colors cursor-pointer shadow-xl"
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

                    // Limpiar el container completamente
                    if (container) {
                        container.innerHTML = '';
                        
                        // Crear placeholder
                        const placeholder = document.createElement('div');
                        placeholder.id = `plato-imagen-placeholder-${categoriaIndex}-${platoIndex}`;
                        placeholder.className = 'w-24 h-24 bg-secondary-dark border border-accent-gray rounded-lg flex items-center justify-center text-text-secondary text-xs text-center p-2 hidden';
                        placeholder.textContent = 'Sin imagen';
                        container.appendChild(placeholder);
                    }

                    // Actualizar texto del botón
                    const inputElement = document.getElementById(`imagen-input-${categoriaIndex}-${platoIndex}`);
                    if (inputElement) {
                        inputElement.value = ''; // Limpiar el input también
                        const button = inputElement.closest('label')?.querySelector('span');
                        if (button) {
                            button.innerHTML = '<span class="mr-2">📷</span>Subir';
                        }
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
            
            // Limpiar el valor: solo números
            const valorNumerico = nuevoPrecio.toString().replace(/[^0-9]/g, '');
            
            // Si está vacío, no hacer nada (permitir precios vacíos)
            if (valorNumerico === '' && inputElement.value === '') {
                return;
            }
            
            // Guardar el valor original por si hay error
            const valorOriginal = inputElement.dataset.originalValue || inputElement.value;
            
            // Actualizar el valor del input para mostrar solo números
            if (inputElement) {
                inputElement.value = valorNumerico;
            }
            
            // Preparar precio con signo $ para guardar
            const precioConSigno = valorNumerico ? `$ ${valorNumerico}` : '';
            
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
                formData.append('precio', precioConSigno);
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
                        statusElement.className = 'text-green-500 text-xs';
                    }
                    
                    // Guardar el nuevo valor numérico como original
                    if (inputElement) {
                        inputElement.dataset.originalValue = valorNumerico;
                        // Asegurar que el input solo muestre números
                        inputElement.value = valorNumerico;
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
                    statusElement.className = 'text-red-500 text-xs';
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
                    statusElement.className = 'text-green-500 text-xs';
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
                    statusElement.className = 'text-red-500 text-xs';
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
            tagElement.className = 'inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-orange-500/20 text-orange-300 border border-orange-500/30 tag-item';
            tagElement.innerHTML = `
                ${tagValue}
                <button type="button" 
                        onclick="removeTag(${categoriaIndex}, ${platoIndex}, '${tagValue.replace(/'/g, "\\'")}')"
                        class="ml-1 hover:text-orange-100 transition-colors"
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
            notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2 animate-fade-in';
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
            notification.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2 animate-fade-in';
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
                btn.className = 'fixed bottom-20 right-6 bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg shadow-lg z-50 flex items-center gap-2 transition-all';
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
                            <p style="color: #CCCCCC; font-size: 1.125rem; font-weight: 500; text-align: center; margin: 0; line-height: 1.6;">
                                ${message}
                            </p>
                        </div>
                    `,
                    icon: 'success',
                    iconColor: '#10B981',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#10B981',
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
                    timerProgressBarColor: '#10B981',
                    allowOutsideClick: true,
                    allowEscapeKey: true,
                    backdrop: true,
                    customClass: {
                        popup: 'dark-modal',
                        title: 'text-white',
                        content: 'text-gray-300',
                        confirmButton: 'bg-green-600 hover:bg-green-700'
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
                            <p style="color: #CCCCCC; font-size: 1.125rem; font-weight: 500; text-align: center; margin: 0; line-height: 1.6;">
                                ${errorMessage}
                            </p>
                        </div>
                    `,
                    icon: 'error',
                    iconColor: '#EF4444',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#EF4444',
                    width: '520px',
                    padding: '2rem',
                    customClass: {
                        popup: 'dark-modal',
                        title: 'text-white',
                        content: 'text-gray-300',
                        confirmButton: 'bg-red-600 hover:bg-red-700'
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
                               class="w-full px-3 py-2 bg-background border border-accent-gray rounded-lg text-text-primary focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                    
                    <!-- Precio -->
                    <div class="md:col-span-2">
                        <label class="block text-xs text-text-secondary mb-1.5">Precio</label>
                        <div class="flex items-center bg-background border border-accent-gray rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-orange-500">
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
                                  class="w-full px-3 py-2 bg-background border border-accent-gray rounded-lg text-text-primary focus:outline-none focus:ring-2 focus:ring-orange-500 resize-none"></textarea>
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
                                <span class="inline-flex items-center px-3 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition-colors cursor-pointer">
                                    📷 Subir
                                </span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Botón Eliminar -->
                    <div class="md:col-span-1 flex items-end">
                        <button type="button" 
                                onclick="removePromocion(${nuevoId})"
                                class="w-full bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-lg text-sm transition-colors">
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
        
        // Función para guardar promociones vía AJAX (sin mostrar mensaje)
        async function savePromocionesSilently() {
            try {
                // Si es un menú nuevo, no intentar guardar promociones (aún no existe el menú)
                @if(!isset($isEdit) || !$isEdit || !isset($menu) || !$menu)
                    return true;
                @endif
                
                const promociones = collectPromocionesData();
                
                if (promociones.length === 0) {
                    // Si no hay promociones, no hacer nada (pero no fallar)
                    return true;
                }
                
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
                    console.log('✅ Promociones guardadas silenciosamente');
                    return true;
                } else {
                    throw new Error(data.error || 'Error al guardar las promociones');
                }
                
            } catch (error) {
                console.error('❌ Error al guardar promociones:', error);
                throw error; // Re-lanzar para que el manejador principal lo capture
            }
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
                                    class="absolute -top-2 -right-2 bg-red-500 hover:bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">
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
                container.innerHTML = `
                    <label class="cursor-pointer">
                        <input type="file" 
                               accept="image/jpeg,image/png,image/jpg,image/gif"
                               class="hidden"
                               onchange="uploadPromocionImagen(event, ${promoIndex})"
                               id="promo-imagen-input-${promoIndex}">
                        <span class="inline-flex items-center px-3 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition-colors cursor-pointer">
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
                                container.innerHTML = `
                                    <label class="cursor-pointer">
                                        <input type="file" 
                                               accept="image/jpeg,image/png,image/jpg,image/gif"
                                               class="hidden"
                                               onchange="uploadPromocionImagen(event, ${promoIndex})"
                                               id="promo-imagen-input-${promoIndex}">
                                        <span class="inline-flex items-center px-3 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition-colors cursor-pointer">
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
                                console.warn(`Contenedor promo-imagen-container-${promoIndex} no encontrado, pero la imagen fue eliminada del servidor`);
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

