<x-app-layout>
    <style>
        /* Forzar estilos para inputs - mismo color que el contenedor */
        input, select, textarea {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(147, 51, 234, 0.2) 100%) !important;
            color: white !important;
            border-color: rgba(59, 130, 246, 0.3) !important;
        }
        
        /* Forzar gradiente en FAQ */
        .faq-container {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.4) 0%, rgba(147, 51, 234, 0.4) 100%) !important;
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
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.9) 0%, rgba(147, 51, 234, 0.9) 100%);
            border-radius: 1rem;
            padding: 2rem;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            border: 1px solid rgba(59, 130, 246, 0.5);
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
        
        /* Estilos para select y options */
        select {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(147, 51, 234, 0.2) 100%) !important;
            color: white !important;
            border-color: rgba(59, 130, 246, 0.3) !important;
        }
        
        select option {
            background-color: #1e293b !important;
            color: white !important;
        }
        
        select:focus {
            outline: none !important;
            border-color: rgba(59, 130, 246, 0.5) !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2) !important;
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
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.95) 0%, rgba(147, 51, 234, 0.95) 100%);
            border-radius: 1rem;
            padding: 2rem;
            max-width: 90vw;
            max-height: 90vh;
            overflow: hidden;
            border: 1px solid rgba(59, 130, 246, 0.5);
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
    </style>
    <div class="min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            
            <!-- Header Section -->
            <div class="flex items-center justify-between mb-12">
                <div>
                    <h1 class="text-5xl font-bold text-text-primary mb-2">
                        📋 {{ $isEdit ? 'Editar Menú' : 'Crear Menú' }}
                    </h1>
                    <p class="text-xl text-text-secondary">{{ $comercio->nombre }}</p>
                    <p class="text-sm text-text-secondary mt-1">Sube tu menú y personaliza la experiencia</p>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('menus.index', $comercio) }}" 
                       class="bg-secondary-dark hover:bg-accent-gray text-text-primary font-medium py-3 px-6 rounded-xl transition-all duration-300 border border-accent-gray">
                        ← Volver
                    </a>
                    <a href="{{ route('comercios.preview', $comercio) }}" 
                       target="_blank"
                       class="btn-primary">
                        👁️ Vista Previa
                    </a>
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

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                
                <!-- Columna Izquierda: Subir Menú -->
                <div class="xl:col-span-1 space-y-6">
                    
                    <!-- 📋 Subir Menú -->
                    <div class="metric-card">
                        <div class="flex items-center mb-6">
                            <div class="w-10 h-10 bg-accent-blue rounded-xl flex items-center justify-center mr-3 neon-glow">
                                <span class="text-white text-lg">📋</span>
                            </div>
                            <h3 class="text-xl font-semibold text-text-primary">Subir Menú</h3>
                        </div>
                        
                        <form method="POST" action="{{ $isEdit ? route('menus.update', [$comercio, $menu]) : route('menus.store', $comercio) }}" enctype="multipart/form-data" class="space-y-6">
                            @csrf
                            @if($isEdit)
                                @method('PUT')
                <!-- Campo oculto para mantener el formato actual -->
                <input type="hidden" name="formato" value="{{ $menu->formato }}">
                            @endif
            
            <!-- Campos ocultos del chatbot para que se envíen con el formulario del menú -->
            <input type="hidden" name="chatbot_name" id="chatbot_name_hidden" value="{{ $comercio->chatbot_name ?? 'Asistente IA' }}">
            <input type="hidden" name="welcome_message" id="welcome_message_hidden" value="{{ $comercio->welcome_message ?? '¡Hola! Soy tu asistente virtual. Puedo ayudarte con información sobre nuestro menú. ¿En qué puedo ayudarte?' }}">
            
            <!-- Campos ocultos de colores para que se envíen con el formulario del menú -->
            <input type="hidden" id="primary_color" name="primary_color" value="{{ $comercio->primary_color }}">
            <input type="hidden" id="secondary_color" name="secondary_color" value="{{ $comercio->secondary_color }}">
            <input type="hidden" id="accent_color" name="accent_color" value="{{ $comercio->accent_color }}">
            <input type="hidden" id="background_color" name="background_color" value="{{ $comercio->background_color }}">
                            
                            <!-- Información del Comercio -->
                            <div class="bg-secondary-dark p-4 rounded-xl mb-6">
                                <h3 class="text-lg font-medium text-text-primary mb-2">Comercio: {{ $comercio->nombre }}</h3>
                                @if($comercio->descripcion)
                                    <p class="text-sm text-text-secondary">{{ $comercio->descripcion }}</p>
                                @endif
                            </div>

                            <!-- Nombre del Menú -->
                            <div>
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
                                <div class="bg-secondary-dark border border-accent-gray rounded-xl p-5 mb-6">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-4">
                                            @if($menu->formato === 'pdf')
                                                <div class="w-12 h-12 bg-red-500/20 border border-red-500/30 rounded-lg flex items-center justify-center">
                                                    <span class="text-red-400 text-xl">📄</span>
                                                </div>
                                            @else
                                                <div class="w-12 h-12 bg-blue-500/20 border border-blue-500/30 rounded-lg flex items-center justify-center">
                                                    <span class="text-blue-400 text-xl">📷</span>
                                                </div>
                                            @endif
                                            <div>
                                                <h4 class="text-text-primary font-medium">Menú Actual</h4>
                                                <p class="text-text-secondary text-sm">{{ $menu->created_at->format('d/m/Y H:i') }}</p>
                                            </div>
                                        </div>
                                        <div class="flex space-x-3">
                                            <button type="button" 
                                                    onclick="openFileModal('{{ asset('storage/' . $menu->archivo_original) }}', '{{ $menu->formato }}')"
                                                    class="bg-accent-blue hover:bg-blue-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 shadow-lg hover:shadow-xl">
                                                Ver
                                            </button>
                                            <a href="{{ asset('storage/' . $menu->archivo_original) }}" 
                                               download
                                               class="bg-gray-600 hover:bg-gray-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 shadow-lg hover:shadow-xl">
                                                Descargar
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="bg-secondary-dark border border-accent-gray rounded-xl p-5 mb-6 opacity-50">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-4">
                                            <div class="w-12 h-12 bg-blue-500/20 border border-blue-500/30 rounded-lg flex items-center justify-center">
                                                <span class="text-blue-400 text-xl">📷</span>
                                            </div>
                                            <div>
                                                <h4 class="text-text-primary font-medium">Menú Actual</h4>
                                                <p class="text-text-secondary text-sm">-</p>
                                            </div>
                                        </div>
                                        <div class="flex space-x-3">
                                            <button type="button" 
                                                    disabled
                                                    class="bg-accent-blue text-white px-4 py-2 rounded-lg text-sm font-medium cursor-not-allowed">
                                                Ver
                                            </button>
                                            <button type="button" 
                                                    disabled
                                                    class="bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-medium cursor-not-allowed">
                                                Descargar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Archivo del Menú -->
                            <div>
                                <label for="archivo" class="block text-sm font-medium text-text-secondary mb-3">
                                    {{ $isEdit ? 'Nuevo Archivo (opcional)' : 'Archivo del Menú' }}
                                </label>
                                <input type="file" 
                                       id="archivo" 
                                       name="archivo" 
                                       accept=".pdf,.jpeg,.jpg,.png,.gif" 
                                       {{ $isEdit ? '' : 'required' }} 
                                       class="w-full px-4 py-3 bg-secondary-dark border border-accent-gray rounded-xl text-text-primary file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-accent-blue file:text-white hover:file:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent transition-all duration-200" />
                                <x-input-error :messages="$errors->get('archivo')" class="mt-2" />
                                <p class="mt-2 text-sm text-text-secondary">
                                    @if($isEdit)
                                        Deja vacío para mantener el archivo actual. Formatos permitidos: PDF, JPEG, PNG, JPG, GIF. Tamaño máximo: 10MB
                                    @else
                                        Formatos permitidos: PDF, JPEG, PNG, JPG, GIF. Tamaño máximo: 10MB
                                    @endif
                                </p>
                            </div>

                            <!-- Información sobre el procesamiento -->
                            <div class="bg-accent-blue/10 border border-accent-blue/30 rounded-xl p-4">
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

                            <!-- Botones de Subir Menú -->
                        </form>
                    </div>

                    <!-- 🤖 Configuración del Chatbot -->
                    <div class="metric-card">
                        <div class="flex items-center mb-6">
                            <div class="w-10 h-10 bg-gradient-to-r from-accent-blue to-blue-600 rounded-xl flex items-center justify-center mr-3 neon-glow">
                                <span class="text-white text-lg">🤖</span>
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
                                       name="chatbot_name"
                                       value="{{ $comercio->chatbot_name ?? 'Asistente IA' }}"
                                       class="w-full px-4 py-3 bg-secondary-dark border border-accent-gray rounded-xl text-text-primary placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent transition-all duration-200">
                            </div>
                            
                            <!-- Mensaje de Bienvenida -->
                            <div>
                                <label for="welcome_message" class="block text-sm font-medium text-text-secondary mb-3">
                                    Mensaje de Bienvenida
                                </label>
                                <textarea id="welcome_message" 
                                          name="welcome_message"
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
                <div class="xl:col-span-2 space-y-6">
                    
                    <!-- 🎨 Colores del Tema -->
                    <div class="metric-card">
                        <div class="flex items-center mb-6">
                            <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl flex items-center justify-center mr-3 neon-glow">
                                <span class="text-white text-lg">🎨</span>
                            </div>
                            <h3 class="text-xl font-semibold text-text-primary">Colores del Tema</h3>
                        </div>
                        
                        <!-- Combinaciones Predefinidas -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-text-secondary mb-4">
                                Combinaciones Predefinidas
                            </label>
                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                                <!-- Combinación 1: Azul Profesional -->
                                <div class="theme-preset cursor-pointer metric-card hover-lift rounded-xl p-4 transition-all duration-300 hover:scale-105" data-theme="professional">
                                    <div class="flex space-x-2 mb-3">
                                        <div class="w-6 h-6 rounded-lg shadow-lg" style="background-color: #3B82F6;"></div>
                                        <div class="w-6 h-6 rounded-lg shadow-lg" style="background-color: #6B7280;"></div>
                                        <div class="w-6 h-6 rounded-lg shadow-lg" style="background-color: #10B981;"></div>
                                        <div class="w-6 h-6 rounded-lg shadow-lg border border-accent-gray" style="background-color: #FFFFFF;"></div>
                                    </div>
                                    <p class="text-sm font-medium text-text-secondary text-center">Profesional</p>
                                </div>
                                
                                <!-- Combinación 2: Verde Natural -->
                                <div class="theme-preset cursor-pointer metric-card hover-lift rounded-xl p-4 transition-all duration-300 hover:scale-105" data-theme="natural">
                                    <div class="flex space-x-2 mb-3">
                                        <div class="w-6 h-6 rounded-lg shadow-lg" style="background-color: #10B981;"></div>
                                        <div class="w-6 h-6 rounded-lg shadow-lg" style="background-color: #84CC16;"></div>
                                        <div class="w-6 h-6 rounded-lg shadow-lg" style="background-color: #F59E0B;"></div>
                                        <div class="w-6 h-6 rounded-lg shadow-lg border border-accent-gray" style="background-color: #F9FAFB;"></div>
                                    </div>
                                    <p class="text-sm font-medium text-text-secondary text-center">Natural</p>
                                </div>
                                
                                <!-- Combinación 3: Púrpura Elegante -->
                                <div class="theme-preset cursor-pointer metric-card hover-lift rounded-xl p-4 transition-all duration-300 hover:scale-105" data-theme="elegant">
                                    <div class="flex space-x-2 mb-3">
                                        <div class="w-6 h-6 rounded-lg shadow-lg" style="background-color: #8B5CF6;"></div>
                                        <div class="w-6 h-6 rounded-lg shadow-lg" style="background-color: #A78BFA;"></div>
                                        <div class="w-6 h-6 rounded-lg shadow-lg" style="background-color: #EC4899;"></div>
                                        <div class="w-6 h-6 rounded-lg shadow-lg border border-accent-gray" style="background-color: #FAFAFA;"></div>
                                    </div>
                                    <p class="text-sm font-medium text-text-secondary text-center">Elegante</p>
                                </div>
                                
                                <!-- Combinación 4: Naranja Energético -->
                                <div class="theme-preset cursor-pointer metric-card hover-lift rounded-xl p-4 transition-all duration-300 hover:scale-105" data-theme="energetic">
                                    <div class="flex space-x-2 mb-3">
                                        <div class="w-6 h-6 rounded-lg shadow-lg" style="background-color: #F97316;"></div>
                                        <div class="w-6 h-6 rounded-lg shadow-lg" style="background-color: #EF4444;"></div>
                                        <div class="w-6 h-6 rounded-lg shadow-lg" style="background-color: #F59E0B;"></div>
                                        <div class="w-6 h-6 rounded-lg shadow-lg border border-accent-gray" style="background-color: #FFFBEB;"></div>
                                    </div>
                                    <p class="text-sm font-medium text-text-secondary text-center">Energético</p>
                                </div>
                                
                                <!-- Combinación 5: Noche Azul -->
                                <div class="theme-preset cursor-pointer metric-card hover-lift rounded-xl p-4 transition-all duration-300 hover:scale-105" data-theme="midnight-blue">
                                    <div class="flex space-x-2 mb-3">
                                        <div class="w-6 h-6 rounded-lg shadow-lg border border-accent-gray" style="background-color: #1E293B;"></div>
                                        <div class="w-6 h-6 rounded-lg shadow-lg" style="background-color: #334155;"></div>
                                        <div class="w-6 h-6 rounded-lg shadow-lg" style="background-color: #06B6D4;"></div>
                                        <div class="w-6 h-6 rounded-lg shadow-lg border border-accent-gray" style="background-color: #0F172A;"></div>
                                    </div>
                                    <p class="text-sm font-medium text-text-secondary text-center">Noche Azul</p>
                                </div>
                                
                                <!-- Combinación 6: Oro Oscuro -->
                                <div class="theme-preset cursor-pointer metric-card hover-lift rounded-xl p-4 transition-all duration-300 hover:scale-105" data-theme="dark-gold">
                                    <div class="flex space-x-2 mb-3">
                                        <div class="w-6 h-6 rounded-lg shadow-lg border border-accent-gray" style="background-color: #1C1917;"></div>
                                        <div class="w-6 h-6 rounded-lg shadow-lg" style="background-color: #292524;"></div>
                                        <div class="w-6 h-6 rounded-lg shadow-lg" style="background-color: #FBBF24;"></div>
                                        <div class="w-6 h-6 rounded-lg shadow-lg border border-accent-gray" style="background-color: #0C0A09;"></div>
                                    </div>
                                    <p class="text-sm font-medium text-text-secondary text-center">Oro Oscuro</p>
                                </div>
                                
                                <!-- Combinación 7: Púrpura Profundo -->
                                <div class="theme-preset cursor-pointer metric-card hover-lift rounded-xl p-4 transition-all duration-300 hover:scale-105" data-theme="deep-purple">
                                    <div class="flex space-x-2 mb-3">
                                        <div class="w-6 h-6 rounded-lg shadow-lg border border-accent-gray" style="background-color: #1E1B4B;"></div>
                                        <div class="w-6 h-6 rounded-lg shadow-lg" style="background-color: #312E81;"></div>
                                        <div class="w-6 h-6 rounded-lg shadow-lg" style="background-color: #A78BFA;"></div>
                                        <div class="w-6 h-6 rounded-lg shadow-lg border border-accent-gray" style="background-color: #0F0D2B;"></div>
                                    </div>
                                    <p class="text-sm font-medium text-text-secondary text-center">Púrpura Profundo</p>
                                </div>
                                
                                <!-- Combinación 8: Obsidiana -->
                                <div class="theme-preset cursor-pointer metric-card hover-lift rounded-xl p-4 transition-all duration-300 hover:scale-105" data-theme="obsidian">
                                    <div class="flex space-x-2 mb-3">
                                        <div class="w-6 h-6 rounded-lg shadow-lg border border-accent-gray" style="background-color: #111827;"></div>
                                        <div class="w-6 h-6 rounded-lg shadow-lg" style="background-color: #1F2937;"></div>
                                        <div class="w-6 h-6 rounded-lg shadow-lg" style="background-color: #14B8A6;"></div>
                                        <div class="w-6 h-6 rounded-lg shadow-lg border border-accent-gray" style="background-color: #030712;"></div>
                                    </div>
                                    <p class="text-sm font-medium text-text-secondary text-center">Obsidiana</p>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                    
                    <!-- 👁️ Vista Previa del Tema -->
                    <div class="metric-card">
                        <div class="flex items-center mb-6">
                            <div class="w-10 h-10 bg-gradient-to-r from-accent-blue to-cyan-500 rounded-xl flex items-center justify-center mr-3 neon-glow">
                                <span class="text-white text-lg">👁️</span>
                            </div>
                            <h3 class="text-xl font-semibold text-text-primary">Vista Previa</h3>
                        </div>
                        
                        <!-- Preview realista del chatbot móvil -->
                        <div class="border border-accent-gray rounded-2xl p-6 max-w-sm mx-auto min-h-[500px] flex flex-col" style="background-color: #1e293b !important; color: white !important; border-color: #374151 !important;">
                            <!-- Header del comercio -->
                            <div class="flex items-center mb-4">
                                @if($comercio->logo_url)
                                    <img src="{{ asset('storage/' . $comercio->logo_url) }}" 
                                         alt="{{ $comercio->nombre }}" 
                                         class="w-8 h-8 rounded-full object-cover mr-3">
                                @else
                                    <img src="{{ asset('images/logo_empresa_default.png') }}" 
                                         alt="Logo por defecto de {{ $comercio->nombre }}" 
                                         class="w-8 h-8 rounded-full object-cover mr-3">
                                @endif
                                <div>
                                    <h4 class="text-text-primary font-semibold text-sm">{{ $comercio->nombre }}</h4>
                                    <p class="text-text-secondary text-xs">Menú Digital</p>
                                </div>
                            </div>
                            
                            <!-- Chatbot header -->
                            <div class="bg-gradient-to-r from-accent-blue to-blue-600 rounded-xl p-3 mb-4 neon-glow">
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
                                <button class="bg-accent-blue hover:bg-blue-600 px-3 py-2 rounded-r-lg text-white text-xs font-medium transition-colors duration-200">
                                    Enviar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 📋 Contenido Extraído del Menú - Sección de ancho completo -->
            @if($menu && $menu->procesado && ($menu->texto_extraido || $menu->contexto_gpt))
                <div class="mt-12">
                    <div class="metric-card">
                        <div class="flex items-center mb-6">
                            <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-emerald-500 rounded-xl flex items-center justify-center mr-3 neon-glow">
                                <span class="text-white text-lg">📋</span>
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
                                @if(isset($contenido['nombre_del_restaurante']) && $contenido['nombre_del_restaurante'] !== 'No especificado')
                                    <div class="bg-gradient-to-r from-blue-900/20 to-purple-900/20 border border-blue-500/30 rounded-lg p-4">
                                        <h3 class="text-lg font-semibold text-blue-300 mb-2">🏪 {{ $contenido['nombre_del_restaurante'] }}</h3>
                                    </div>
                                @endif
                                
                                <!-- Categorías y platos -->
                                @php
                                    // Buscar categorías en diferentes formatos
                                    $categorias = null;
                                    if (isset($contenido['categorías']) && is_array($contenido['categorías'])) {
                                        $categorias = $contenido['categorías'];
                                    } elseif (isset($contenido['categorias']) && is_array($contenido['categorias'])) {
                                        $categorias = $contenido['categorias'];
                                    } elseif (isset($contenido['categorías_de_platos']) && is_array($contenido['categorías_de_platos'])) {
                                        $categorias = $contenido['categorías_de_platos'];
                                    }
                                @endphp
                                
                                @if($categorias)
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                        @foreach($categorias as $categoria => $platos)
                                            <div class="bg-secondary-dark rounded-lg p-6">
                                                <h4 class="text-lg font-semibold text-text-primary mb-4 border-b border-accent-gray pb-3">
                                                    {{ $categoria }}
                                                </h4>
                                                <div class="space-y-3">
                                                    @if(is_array($platos))
                                                        @foreach($platos as $plato)
                                                            <div class="flex justify-between items-center py-2 border-b border-accent-gray last:border-b-0">
                                                                <span class="text-text-secondary font-medium">
                                                                    {{ $plato['nombre'] ?? 'Sin nombre' }}
                                                                </span>
                                                                <span class="text-accent-blue font-bold">
                                                                    ${{ $plato['precio'] ?? 'Sin precio' }}
                                                                </span>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif(isset($contenido['categorías_de_platos']) && is_array($contenido['categorías_de_platos']))
                                    <!-- Fallback para la estructura anterior -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                        @foreach($contenido['categorías_de_platos'] as $categoria => $platos)
                                            <div class="bg-secondary-dark rounded-lg p-6">
                                                <h4 class="text-lg font-semibold text-text-primary mb-4 border-b border-accent-gray pb-3">
                                                    {{ $categoria }}
                                                </h4>
                                                <div class="space-y-3">
                                                    @if(is_array($platos))
                                                        @foreach($platos as $plato)
                                                            <div class="flex justify-between items-center py-2 border-b border-accent-gray last:border-b-0">
                                                                <span class="text-text-secondary font-medium">
                                                                    {{ $plato['nombre'] ?? 'Sin nombre' }}
                                                                </span>
                                                                <span class="text-accent-blue font-bold">
                                                                    ${{ $plato['precio'] ?? 'Sin precio' }}
                                                                </span>
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
                                    <div class="bg-gradient-to-r from-green-900/20 to-emerald-900/20 border border-green-500/30 rounded-lg p-4">
                                        <h4 class="text-lg font-semibold text-green-300 mb-2">ℹ️ Información Adicional</h4>
                                        <p class="text-green-200">{{ $contenido['informacion_adicional'] }}</p>
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
                            <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-emerald-500 rounded-xl flex items-center justify-center mr-3 neon-glow">
                                <span class="text-white text-lg">📋</span>
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
                            <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-emerald-500 rounded-xl flex items-center justify-center mr-3 neon-glow">
                                <span class="text-white text-lg">📋</span>
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
                            <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-emerald-500 rounded-xl flex items-center justify-center mr-3 neon-glow">
                                <span class="text-white text-lg">📋</span>
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

            <!-- Preguntas Frecuentes - Sección de ancho completo -->
            <div class="mt-12">
                <div class="flex flex-col min-h-[500px] faq-container" style="border: 1px solid rgba(59, 130, 246, 0.5) !important; border-radius: 1rem !important; padding: 1.5rem !important; backdrop-filter: blur(10px) !important;">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-gradient-to-r from-accent-blue to-purple-600 rounded-xl flex items-center justify-center mr-3 neon-glow">
                                <span class="text-white text-lg">💡</span>
                            </div>
                            <h3 class="text-xl font-semibold text-text-primary">Preguntas Frecuentes</h3>
                        </div>
                        <button type="button" 
                                onclick="generateQuestionsWithAI()"
                                class="bg-accent-blue hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 neon-glow">
                            🤖 Generar con IA
                        </button>
                    </div>
                    
                    <div id="quick-questions-container" class="space-y-4 flex-grow overflow-y-auto pr-2">
                        @foreach($comercio->quick_questions as $index => $question)
                            <div class="question-item" data-index="{{ $index }}" style="background: rgba(75, 85, 99, 0.4) !important; border: 1px solid rgba(107, 114, 128, 0.6) !important; border-radius: 0.75rem !important; padding: 1rem !important; margin-bottom: 1rem !important;">
                                <div class="grid grid-cols-12 gap-4 items-start">
                                    <!-- Ícono -->
                                    <div class="col-span-1">
                                        <input type="text" 
                                               name="quick_questions[{{ $index }}][icon]" 
                                               value="{{ $question['icon'] }}"
                                               onclick="openIconModal(this)"
                                               class="w-full px-2 py-2 style="background-color: #1e293b !important; color: white !important; border-color: #374151 !important;" border border-accent-gray rounded-lg text-text-primary text-center text-lg focus:outline-none focus:ring-2 focus:ring-accent-blue cursor-pointer">
                                    </div>
                                    
                                    <!-- Pregunta -->
                                    <div class="col-span-4">
                                        <input type="text" 
                                               id="question_{{ $index }}"
                                               name="quick_questions[{{ $index }}][question]" 
                                               value="{{ $question['question'] }}"
                                               placeholder="¿Pregunta frecuente?"
                                               class="w-full px-3 py-2 style="background-color: #1e293b !important; color: white !important; border-color: #374151 !important;" border border-accent-gray rounded-lg text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-blue">
                                    </div>
                                    
                                    <!-- Categoría -->
                                    <div class="col-span-2">
                                        @php($cat = $question['category'] ?? 'general')
                                        <select name="quick_questions[{{ $index }}][category]" 
                                                class="w-full px-3 py-2 style="background-color: #1e293b !important; color: white !important; border-color: #374151 !important;" border border-accent-gray rounded-lg text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-blue">
                                            @if(isset($categories) && $categories->count() > 0)
                                            @foreach($categories as $category)
                                                <option value="{{ $category->slug }}" {{ $cat === $category->slug ? 'selected' : '' }}>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                            @else
                                                <option value="general" {{ $cat === 'general' ? 'selected' : '' }}>
                                                    General
                                                </option>
                                            @endif
                                        </select>
                                    </div>
                                    
                                    <!-- Respuesta -->
                                    <div class="col-span-4">
                                        <div class="flex gap-2">
                                            <textarea name="quick_questions[{{ $index }}][answer]" 
                                                      id="answer_{{ $index }}"
                                                      rows="2"
                                                      placeholder="Respuesta automática..."
                                                      class="flex-1 px-3 py-2 style="background-color: #1e293b !important; color: white !important; border-color: #374151 !important;" border border-accent-gray rounded-lg text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-blue resize-none">{{ $question['answer'] ?? '' }}</textarea>
                                            <button type="button" 
                                                    onclick="improveAnswerWithAI({{ $index }})"
                                                    class="bg-accent-green hover:bg-green-600 text-white px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 neon-glow-green flex-shrink-0"
                                                    title="Mejorar respuesta con IA">
                                                🤖
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Botones -->
                                    <div class="col-span-1 flex flex-col space-y-2">
                                        <input type="hidden" name="quick_questions[{{ $index }}][auto_generated]" value="{{ ($question['auto_generated'] ?? false) ? '1' : '0' }}">
                                        <button type="button" 
                                                onclick="removeQuestion({{ $index }})"
                                                class="bg-red-600 hover:bg-red-700 text-white p-2 rounded-lg text-sm transition-colors duration-200">
                                            🗑️
                                        </button>
                                        @if($question['auto_generated'] ?? false)
                                            <span class="text-xs text-accent-blue text-center">IA</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <!-- Botón Agregar Pregunta -->
                    <div class="mt-4">
                        <button type="button" 
                                onclick="addNewQuestion()"
                                class="w-full bg-secondary-dark hover:bg-accent-gray border-2 border-dashed border-accent-gray hover:border-accent-blue text-text-secondary hover:text-accent-blue py-4 rounded-xl transition-all duration-200 flex items-center justify-center space-x-2">
                            <span class="text-lg">➕</span>
                            <span>Agregar Nueva Pregunta</span>
                        </button>
                    </div>
                    
                    <p class="text-xs text-text-secondary mt-4">
                        💡 Las preguntas marcadas con "IA" fueron generadas automáticamente. Puedes editarlas o agregar nuevas.
                    </p>
                </div>
            </div>

            <!-- Botón Flotante Guardar Cambios -->
            <div class="fixed bottom-6 right-6 z-50">
                <button type="button" 
                        onclick="saveCustomization()"
                        class="btn-primary text-lg px-6 py-4 rounded-xl shadow-2xl hover:shadow-3xl transform hover:scale-105 transition-all duration-300 flex items-center space-x-2">
                    <span>💾</span>
                    <span>Guardar Cambios</span>
                </button>
            </div>
        </div>
    </div>

    <!-- JavaScript para vista previa en tiempo real -->
    <script>
        // Variables globales para preguntas frecuentes
        let questionIndex = {{ count($comercio->quick_questions) }};
        
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
                    console.log('Aplicando tema:', theme, selectedTheme);
                    document.getElementById('primary_color').value = selectedTheme.primary;
                    document.getElementById('secondary_color').value = selectedTheme.secondary;
                    document.getElementById('accent_color').value = selectedTheme.accent;
                    document.getElementById('background_color').value = selectedTheme.background;
                    
                    console.log('Valores actualizados:', {
                        primary: document.getElementById('primary_color').value,
                        secondary: document.getElementById('secondary_color').value,
                        accent: document.getElementById('accent_color').value,
                        background: document.getElementById('background_color').value
                    });
                    
                    updatePreview();
                }
            }
            
            // Actualizar vista previa
            function updatePreview() {
                const primaryColor = document.getElementById('primary_color').value;
                const chatbotName = document.getElementById('chatbot_name').value;
                const welcomeMessage = document.getElementById('welcome_message').value;
                
                // Actualizar colores
                const previewElements = document.querySelectorAll('#preview-chatbot-name, .bg-gradient-to-r');
                previewElements.forEach(el => {
                    if (el.classList.contains('bg-gradient-to-r')) {
                        el.style.background = `linear-gradient(to right, ${primaryColor}, #0099CC)`;
                    }
                });
                
                // Actualizar texto
                document.getElementById('preview-chatbot-name').textContent = chatbotName;
                document.getElementById('preview-welcome-message').textContent = welcomeMessage;
            }
            
            // Event listeners
            document.querySelectorAll('.theme-preset').forEach(preset => {
                preset.addEventListener('click', function(e) {
                    e.preventDefault();
                    const theme = this.dataset.theme;
                    applyTheme(theme);
                });
            });
            
            document.getElementById('chatbot_name').addEventListener('input', updatePreview);
            document.getElementById('welcome_message').addEventListener('input', updatePreview);
            
            // Inicializar vista previa
            setTimeout(updatePreview, 100);
        });
        
        // Funciones para manejar preguntas frecuentes
        function addNewQuestion() {
            const container = document.getElementById('quick-questions-container');
            const newQuestion = document.createElement('div');
            newQuestion.className = 'question-item';
            newQuestion.style.setProperty('background', 'rgba(75, 85, 99, 0.4)', 'important');
            newQuestion.style.setProperty('border', '1px solid rgba(107, 114, 128, 0.6)', 'important');
            newQuestion.style.setProperty('border-radius', '0.75rem', 'important');
            newQuestion.style.setProperty('padding', '1rem', 'important');
            newQuestion.style.setProperty('margin-bottom', '1rem', 'important');
            newQuestion.setAttribute('data-index', questionIndex);
            
            newQuestion.innerHTML = `
                <div class="grid grid-cols-12 gap-4 items-start">
                    <!-- Ícono -->
                    <div class="col-span-1">
                        <input type="text" 
                               name="quick_questions[${questionIndex}][icon]" 
                               value="❓"
                               onclick="openIconModal(this)"
                               class="w-full px-2 py-2 style="background-color: #1e293b !important; color: white !important; border-color: #374151 !important;" border border-accent-gray rounded-lg text-text-primary text-center text-lg focus:outline-none focus:ring-2 focus:ring-accent-blue cursor-pointer">
                    </div>
                    
                    <!-- Pregunta -->
                    <div class="col-span-4">
                        <input type="text" 
                               id="question_${questionIndex}"
                               name="quick_questions[${questionIndex}][question]" 
                               value=""
                               placeholder="¿Pregunta frecuente?"
                               class="w-full px-3 py-2 style="background-color: #1e293b !important; color: white !important; border-color: #374151 !important;" border border-accent-gray rounded-lg text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-blue">
                    </div>
                    
                    <!-- Categoría -->
                    <div class="col-span-2">
                        <select name="quick_questions[${questionIndex}][category]" 
                                class="w-full px-3 py-2 style="background-color: #1e293b !important; color: white !important; border-color: #374151 !important;" border border-accent-gray rounded-lg text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-blue">
                            @foreach($categories as $category)
                                <option value="{{ $category->slug }}" {{ $category->slug === 'general' ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Respuesta -->
                    <div class="col-span-4">
                        <div class="flex gap-2">
                            <textarea name="quick_questions[${questionIndex}][answer]" 
                                      id="answer_${questionIndex}"
                                      rows="2"
                                      placeholder="Respuesta automática..."
                                      class="flex-1 px-3 py-2 style="background-color: #1e293b !important; color: white !important; border-color: #374151 !important;" border border-accent-gray rounded-lg text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-blue resize-none"></textarea>
                            <button type="button" 
                                    onclick="improveAnswerWithAI(${questionIndex})"
                                    class="bg-accent-green hover:bg-green-600 text-white px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 neon-glow-green flex-shrink-0"
                                    title="Mejorar respuesta con IA">
                                🤖
                            </button>
                        </div>
                    </div>
                    
                    <!-- Botones -->
                    <div class="col-span-1 flex flex-col space-y-2">
                        <input type="hidden" name="quick_questions[${questionIndex}][auto_generated]" value="0">
                        <button type="button" 
                                onclick="removeQuestion(${questionIndex})"
                                class="bg-red-600 hover:bg-red-700 text-white p-2 rounded-lg text-sm transition-colors duration-200">
                            🗑️
                        </button>
                    </div>
                </div>
            `;
            
            container.appendChild(newQuestion);
            questionIndex++;
        }
        
        function removeQuestion(index) {
            const questionItem = document.querySelector(`[data-index="${index}"]`);
            if (questionItem) {
                questionItem.remove();
            }
        }
        
        function generateQuestionsWithAI() {
            showConfirm('¿Estás seguro de que quieres generar nuevas preguntas con IA? Esto reemplazará las preguntas actuales.', function(result) {
                if (result) {
                // Mostrar indicador de carga
                const button = document.querySelector('button[onclick="generateQuestionsWithAI()"]');
                const originalText = button.innerHTML;
                button.innerHTML = '⏳ Generando...';
                button.disabled = true;
                
                // Enviar petición AJAX
                fetch('{{ route("comercios.generate.questions", $comercio) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Mostrar mensaje de éxito
                        alert('Preguntas generadas exitosamente: ' + data.message);
                        // Recargar la página para mostrar las nuevas preguntas
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al generar preguntas con IA');
                })
                .finally(() => {
                    // Restaurar botón
                    button.innerHTML = originalText;
                    button.disabled = false;
                });
                }
            });
        }
        
        function improveAnswerWithAI(questionIndex) {
            const answerTextarea = document.getElementById(`answer_${questionIndex}`);
            const questionTextarea = document.getElementById(`question_${questionIndex}`);
            const currentAnswer = answerTextarea.value.trim();
            const questionText = questionTextarea ? questionTextarea.value.trim() : '';
            
            if (!currentAnswer) {
                alert('Por favor, escribe una respuesta antes de mejorarla con IA.');
                return;
            }
            
            if (!questionText) {
                alert('Por favor, escribe una pregunta antes de mejorar la respuesta con IA.');
                return;
            }
            
            showConfirm('¿Quieres mejorar esta respuesta con IA?', function(result) {
                if (result) {
                // Mostrar indicador de carga en el botón
                const button = document.querySelector(`button[onclick="improveAnswerWithAI(${questionIndex})"]`);
                const originalText = button.innerHTML;
                button.innerHTML = '⏳';
                button.disabled = true;
                
                // Preparar datos para envío
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('question_text', questionText);
                formData.append('current_answer', currentAnswer);
                
                // Solo agregar question_index si es una pregunta existente
                const existingQuestionsCount = {{ count($comercio->quick_questions) }};
                if (questionIndex < existingQuestionsCount) {
                    formData.append('question_index', questionIndex);
                }
                
                // Enviar petición AJAX
                fetch('{{ route("comercios.improve.answer", $comercio) }}', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('Error: ' + data.error);
                    } else {
                        // Actualizar el textarea con la respuesta mejorada
                        answerTextarea.value = data.improved_answer;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al mejorar la respuesta con IA.');
                })
                .finally(() => {
                    // Restaurar botón
                    button.innerHTML = originalText;
                    button.disabled = false;
                });
                }
            });
        }

        // Función para guardar personalización
        function saveCustomization() {
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
            const menuForm = document.querySelector('form[action*="menus"]');
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
            
            // Agregar preguntas frecuentes
            const questions = document.querySelectorAll('.question-item');
            questions.forEach((question, index) => {
                const icon = question.querySelector('input[name*="[icon]"]');
                const questionText = question.querySelector('input[name*="[question]"]');
                const category = question.querySelector('select[name*="[category]"]');
                const answer = question.querySelector('textarea[name*="[answer]"]');
                const autoGenerated = question.querySelector('input[name*="[auto_generated]"]');
                
                if (icon && questionText && category) {
                    form.appendChild(createHiddenInput(`quick_questions[${index}][icon]`, icon.value));
                    form.appendChild(createHiddenInput(`quick_questions[${index}][question]`, questionText.value));
                    form.appendChild(createHiddenInput(`quick_questions[${index}][category]`, category.value));
                    form.appendChild(createHiddenInput(`quick_questions[${index}][answer]`, answer ? answer.value : ''));
                    form.appendChild(createHiddenInput(`quick_questions[${index}][auto_generated]`, autoGenerated ? autoGenerated.value : '0'));
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
</x-app-layout>
