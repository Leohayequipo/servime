<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                @if($comercio->logo_url)
                    <img src="{{ asset('storage/' . $comercio->logo_url) }}" 
                         alt="{{ $comercio->nombre }}" 
                         class="w-8 h-8 rounded-full object-cover">
                @endif
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        🎨 Personalizar - {{ $comercio->nombre }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Configurar tema, colores y branding</p>
                </div>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('comercios.index') }}" 
                   class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    ← Volver a Comercios
                </a>
                <a href="{{ route('comercios.preview', $comercio) }}" 
                   target="_blank"
                   class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    👁️ Vista Previa
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            
            @if (session('success'))
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('comercios.customize.update', $comercio) }}">
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    
                    <!-- Columna Izquierda: Configuración -->
                    <div class="space-y-8">
                        
                        <!-- Configuración del Chatbot -->
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">🤖 Configuración del Chatbot</h3>
                                
                                <div class="space-y-4">
                                    <!-- Nombre del Chatbot -->
                                    <div>
                                        <label for="chatbot_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Nombre del Chatbot
                                        </label>
                                        <input type="text" 
                                               id="chatbot_name" 
                                               name="chatbot_name" 
                                               value="{{ $comercio->chatbot_name }}"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                               placeholder="Asistente IA">
                                    </div>

                                    <!-- Mensaje de Bienvenida -->
                                    <div>
                                        <label for="welcome_message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Mensaje de Bienvenida
                                        </label>
                                        <textarea id="welcome_message" 
                                                  name="welcome_message" 
                                                  rows="3"
                                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                  placeholder="¡Hola! Soy tu asistente virtual. ¿En qué puedo ayudarte?">{{ $comercio->welcome_message }}</textarea>
                                        <p class="text-xs text-gray-500 mt-1">Máximo 500 caracteres</p>
                                    </div>

                                    <!-- Configuraciones -->
                                    <div class="space-y-3">
                                        <div class="flex items-center">
                                            <input type="checkbox" 
                                                   id="enable_ai_responses" 
                                                   name="enable_ai_responses" 
                                                   value="1"
                                                   {{ $comercio->enable_ai_responses ? 'checked' : '' }}
                                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                            <label for="enable_ai_responses" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                                Habilitar respuestas con IA
                                            </label>
                                        </div>

                                        <div class="flex items-center">
                                            <input type="checkbox" 
                                                   id="show_contact_info" 
                                                   name="show_contact_info" 
                                                   value="1"
                                                   {{ $comercio->show_contact_info ? 'checked' : '' }}
                                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                            <label for="show_contact_info" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                                Mostrar información de contacto
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información de Contacto -->
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">📞 Información de Contacto</h3>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label for="contact_info" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Información de Contacto
                                        </label>
                                        <textarea id="contact_info" 
                                                  name="contact_info" 
                                                  rows="3"
                                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                  placeholder="Teléfono: +54 11 1234-5678&#10;Email: contacto@restaurante.com&#10;Dirección: Av. Principal 123">{{ $comercio->contact_info }}</textarea>
                                        <p class="text-xs text-gray-500 mt-1">Información que se mostrará en la página pública</p>
                                    </div>

                                    <div>
                                        <label for="menu_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Descripción del Menú
                                        </label>
                                        <textarea id="menu_description" 
                                                  name="menu_description" 
                                                  rows="3"
                                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                  placeholder="Descripción especial del menú o promociones...">{{ $comercio->menu_description }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha: Vista Previa -->
                    <div class="space-y-8">
                        
                        <!-- Colores del Tema -->
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">🎨 Colores del Tema</h3>
                                
                                <!-- Combinaciones Predefinidas -->
                                <div class="mb-6">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                        Combinaciones Predefinidas
                                    </label>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                        <!-- Combinación 1: Azul Profesional -->
                                        <div class="theme-preset cursor-pointer border-2 border-gray-200 rounded-lg p-3 hover:border-blue-500 transition-all" data-theme="professional">
                                            <div class="flex space-x-1 mb-2">
                                                <div class="w-6 h-6 rounded" style="background-color: #3B82F6;"></div>
                                                <div class="w-6 h-6 rounded" style="background-color: #6B7280;"></div>
                                                <div class="w-6 h-6 rounded" style="background-color: #10B981;"></div>
                                                <div class="w-6 h-6 rounded border border-gray-300" style="background-color: #FFFFFF;"></div>
                                            </div>
                                            <p class="text-xs text-gray-600 text-center font-medium">Profesional</p>
                                        </div>
                                        
                                        <!-- Combinación 2: Verde Natural -->
                                        <div class="theme-preset cursor-pointer border-2 border-gray-200 rounded-lg p-3 hover:border-green-500 transition-all" data-theme="natural">
                                            <div class="flex space-x-1 mb-2">
                                                <div class="w-6 h-6 rounded" style="background-color: #10B981;"></div>
                                                <div class="w-6 h-6 rounded" style="background-color: #84CC16;"></div>
                                                <div class="w-6 h-6 rounded" style="background-color: #F59E0B;"></div>
                                                <div class="w-6 h-6 rounded border border-gray-300" style="background-color: #F9FAFB;"></div>
                                            </div>
                                            <p class="text-xs text-gray-600 text-center font-medium">Natural</p>
                                        </div>
                                        
                                        <!-- Combinación 3: Púrpura Elegante -->
                                        <div class="theme-preset cursor-pointer border-2 border-gray-200 rounded-lg p-3 hover:border-purple-500 transition-all" data-theme="elegant">
                                            <div class="flex space-x-1 mb-2">
                                                <div class="w-6 h-6 rounded" style="background-color: #8B5CF6;"></div>
                                                <div class="w-6 h-6 rounded" style="background-color: #A78BFA;"></div>
                                                <div class="w-6 h-6 rounded" style="background-color: #EC4899;"></div>
                                                <div class="w-6 h-6 rounded border border-gray-300" style="background-color: #FAFAFA;"></div>
                                            </div>
                                            <p class="text-xs text-gray-600 text-center font-medium">Elegante</p>
                                        </div>
                                        
                                        <!-- Combinación 4: Naranja Energético -->
                                        <div class="theme-preset cursor-pointer border-2 border-gray-200 rounded-lg p-3 hover:border-orange-500 transition-all" data-theme="energetic">
                                            <div class="flex space-x-1 mb-2">
                                                <div class="w-6 h-6 rounded" style="background-color: #F97316;"></div>
                                                <div class="w-6 h-6 rounded" style="background-color: #EF4444;"></div>
                                                <div class="w-6 h-6 rounded" style="background-color: #F59E0B;"></div>
                                                <div class="w-6 h-6 rounded border border-gray-300" style="background-color: #FFFBEB;"></div>
                                            </div>
                                            <p class="text-xs text-gray-600 text-center font-medium">Energético</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Campos ocultos para mantener los valores -->
                                <input type="hidden" id="primary_color" name="primary_color" value="{{ $comercio->primary_color }}">
                                <input type="hidden" id="secondary_color" name="secondary_color" value="{{ $comercio->secondary_color }}">
                                <input type="hidden" id="accent_color" name="accent_color" value="{{ $comercio->accent_color }}">
                                <input type="hidden" id="background_color" name="background_color" value="{{ $comercio->background_color }}">
                            </div>
                        </div>
                        
                        <!-- Vista Previa del Tema -->
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">👁️ Vista Previa</h3>
                                
                                <!-- Preview realista del chatbot móvil -->
                                <div class="bg-gray-900 rounded-lg p-4 max-w-sm mx-auto">
                                    <!-- Header del comercio -->
                                    <div class="bg-white rounded-t-lg p-3 mb-2">
                                        <div class="flex items-center space-x-3">
                                            @if($comercio->logo_url)
                                                <img src="{{ asset('storage/' . $comercio->logo_url) }}" 
                                                     alt="{{ $comercio->nombre }}" 
                                                     class="w-8 h-8 rounded-full object-cover">
                                            @endif
                                            <div>
                                                <h3 class="font-semibold text-gray-900 text-sm">{{ $comercio->nombre }}</h3>
                                                <p class="text-xs text-gray-600">Menú Digital</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Chatbot header -->
                                    <div class="bg-white border-b px-3 py-2">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-2">
                                                <div class="w-6 h-6 rounded-full flex items-center justify-center" 
                                                     id="preview-chatbot-icon" 
                                                     style="background-color: {{ $comercio->primary_color }}20;">
                                                    <i class="fas fa-robot text-xs" 
                                                       style="color: {{ $comercio->primary_color }};"></i>
                                                </div>
                                                <span class="font-medium text-gray-900 text-sm" id="preview-chatbot-name">{{ $comercio->chatbot_name }}</span>
                                            </div>
                                            <i class="fas fa-chevron-up text-gray-500 text-xs"></i>
                                        </div>
                                    </div>
                                    
                                    <!-- Chat messages -->
                                    <div class="bg-white p-3 space-y-3">
                                        <div class="flex items-start space-x-2">
                                            <div class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0" 
                                                 style="background-color: {{ $comercio->primary_color }}20;">
                                                <i class="fas fa-robot text-xs" 
                                                   style="color: {{ $comercio->primary_color }};"></i>
                                            </div>
                                            <div class="bg-gray-100 rounded-lg p-2 max-w-xs">
                                                <p class="text-xs text-gray-800" id="preview-welcome-message">{{ $comercio->welcome_message }}</p>
                                            </div>
                                        </div>
                                        
                                        <!-- Mensaje de ejemplo del usuario -->
                                        <div class="flex items-start space-x-2 justify-end">
                                            <div class="rounded-lg p-2 max-w-xs" 
                                                 style="background-color: {{ $comercio->primary_color }};">
                                                <p class="text-xs text-white">¿Qué platos vegetarianos tienen?</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Chat input -->
                                    <div class="bg-white rounded-b-lg p-3 border-t">
                                        <div class="flex space-x-2">
                                            <input type="text" 
                                                   placeholder="Escribe tu pregunta..." 
                                                   class="flex-1 border border-gray-300 rounded-lg px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                   style="border-color: {{ $comercio->primary_color }};">
                                            <button class="px-3 py-1 rounded-lg text-white text-xs font-medium" 
                                                    style="background-color: {{ $comercio->primary_color }};">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Acciones -->
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">⚡ Acciones</h3>
                                
                                <div class="space-y-4">
                                    <button type="submit" 
                                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200">
                                        💾 Guardar Configuración
                                    </button>
                                    
                                    <a href="{{ route('comercios.preview', $comercio) }}" 
                                       target="_blank"
                                       class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 text-center block">
                                        👁️ Ver Página Pública
                                    </a>
                                    
                                    <button type="button" 
                                            onclick="confirmReset()"
                                            class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200">
                                        🔄 Resetear a Valores por Defecto
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Formulario oculto para reset -->
    <form id="resetForm" method="POST" action="{{ route('comercios.reset', $comercio) }}" style="display: none;">
        @csrf
    </form>

    <script>
        // Combinaciones predefinidas
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

        // Aplicar tema predefinido
        function applyTheme(themeName) {
            console.log('Aplicando tema:', themeName);
            const theme = themes[themeName];
            if (!theme) {
                console.log('Tema no encontrado:', themeName);
                return;
            }

            console.log('Tema encontrado:', theme);

            // Actualizar inputs ocultos
            document.getElementById('primary_color').value = theme.primary;
            document.getElementById('secondary_color').value = theme.secondary;
            document.getElementById('accent_color').value = theme.accent;
            document.getElementById('background_color').value = theme.background;

            console.log('Inputs actualizados');

            // Actualizar vista previa
            updatePreview();

            // Remover selección anterior
            document.querySelectorAll('.theme-preset').forEach(preset => {
                preset.classList.remove('border-blue-500', 'bg-blue-50');
                preset.classList.add('border-gray-200');
            });

            // Marcar como seleccionado
            const selectedPreset = document.querySelector(`[data-theme="${themeName}"]`);
            if (selectedPreset) {
                selectedPreset.classList.remove('border-gray-200');
                selectedPreset.classList.add('border-blue-500', 'bg-blue-50');
                console.log('Preset seleccionado visualmente');
            } else {
                console.log('No se encontró el preset para seleccionar');
            }
        }

        // Event listeners para combinaciones predefinidas
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM cargado, configurando event listeners...');
            
            const presets = document.querySelectorAll('.theme-preset');
            console.log('Presets encontrados:', presets.length);
            
            presets.forEach((preset, index) => {
                preset.addEventListener('click', function(e) {
                    e.preventDefault();
                    const themeName = this.getAttribute('data-theme');
                    console.log('Preset clickeado:', themeName, 'index:', index);
                    applyTheme(themeName);
                });
                
                // Agregar hover effect
                preset.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.02)';
                });
                
                preset.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            });
            
            // Ejecutar updatePreview una vez al cargar
            setTimeout(updatePreview, 100);
        });

        // Actualizar vista previa en tiempo real
        function updatePreview() {
            const primaryColor = document.getElementById('primary_color').value;
            const secondaryColor = document.getElementById('secondary_color').value;
            const accentColor = document.getElementById('accent_color').value;
            const backgroundColor = document.getElementById('background_color').value;
            const chatbotName = document.getElementById('chatbot_name').value;
            const welcomeMessage = document.getElementById('welcome_message').value;

            // Actualizar chatbot icon en header
            const chatbotIcon = document.getElementById('preview-chatbot-icon');
            if (chatbotIcon) {
                chatbotIcon.style.backgroundColor = primaryColor + '20';
                const icon = chatbotIcon.querySelector('i');
                if (icon) {
                    icon.style.color = primaryColor;
                }
            }

            // Actualizar chatbot icon en mensajes
            const messageIcons = document.querySelectorAll('.w-5.h-5.rounded-full');
            messageIcons.forEach(icon => {
                icon.style.backgroundColor = primaryColor + '20';
                const iconElement = icon.querySelector('i');
                if (iconElement) {
                    iconElement.style.color = primaryColor;
                }
            });

            // Actualizar mensaje del usuario (botón)
            const userMessage = document.querySelector('.justify-end .rounded-lg');
            if (userMessage) {
                userMessage.style.backgroundColor = primaryColor;
            }

            // Actualizar input border
            const chatInput = document.querySelector('input[placeholder="Escribe tu pregunta..."]');
            if (chatInput) {
                chatInput.style.borderColor = primaryColor;
            }

            // Actualizar botón de envío
            const sendButton = document.querySelector('button[style*="background-color"]');
            if (sendButton) {
                sendButton.style.backgroundColor = primaryColor;
            }

            // Actualizar texto
            const chatbotNameElement = document.getElementById('preview-chatbot-name');
            if (chatbotNameElement) {
                chatbotNameElement.textContent = chatbotName;
            }

            const welcomeMessageElement = document.getElementById('preview-welcome-message');
            if (welcomeMessageElement) {
                welcomeMessageElement.textContent = welcomeMessage;
            }

            console.log('Vista previa actualizada:', { primaryColor, chatbotName, welcomeMessage });
        }

        // Event listeners para actualizar vista previa
        document.getElementById('chatbot_name').addEventListener('input', updatePreview);
        document.getElementById('welcome_message').addEventListener('input', updatePreview);

        // Confirmar reset
        function confirmReset() {
            if (confirm('¿Estás seguro de que quieres resetear toda la configuración a valores por defecto? Esta acción no se puede deshacer.')) {
                document.getElementById('resetForm').submit();
            }
        }
    </script>
</x-app-layout>
