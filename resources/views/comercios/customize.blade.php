<x-app-layout>
    <div class="min-h-screen bg-background">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            
            <!-- Header Section -->
            <div class="flex items-center justify-between mb-12">
                <div>
                    <h1 class="text-5xl font-bold text-text-primary mb-2">
                        🎨 Personalizar
                    </h1>
                    <p class="text-xl text-text-secondary">{{ $comercio->nombre }}</p>
                    <p class="text-sm text-text-secondary mt-1">Configurar tema, colores y branding</p>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('comercios.index') }}" 
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

            <form method="POST" action="{{ route('comercios.customize.update', $comercio) }}">
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                    
                    <!-- Columna Izquierda: Configuración -->
                    <div class="xl:col-span-1 space-y-6">
                        
                        <!-- Configuración del Chatbot -->
                        <div class="metric-card">
                            <div class="flex items-center mb-6">
                                <div class="w-10 h-10 bg-gradient-to-r from-accent-blue to-blue-600 rounded-xl flex items-center justify-center mr-3 neon-glow">
                                    <span class="text-white text-lg">🤖</span>
                                </div>
                                <h3 class="text-xl font-semibold text-text-primary">Chatbot</h3>
                            </div>
                            
                            <div class="space-y-6">
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
                                <div class="space-y-4">
                                    <div class="flex items-center">
                                        <input type="checkbox" 
                                               id="enable_ai_responses" 
                                               name="enable_ai_responses" 
                                               value="1"
                                               {{ $comercio->enable_ai_responses ? 'checked' : '' }}
                                               class="w-4 h-4 text-accent-blue bg-secondary-dark border-accent-gray rounded focus:ring-accent-blue focus:ring-2">
                                        <label for="enable_ai_responses" class="ml-3 text-sm text-text-secondary">
                                            Habilitar respuestas con IA
                                        </label>
                                    </div>
                                    
                                    <div class="flex items-center">
                                        <input type="checkbox" 
                                               id="show_contact_info" 
                                               name="show_contact_info" 
                                               value="1"
                                               {{ $comercio->show_contact_info ? 'checked' : '' }}
                                               class="w-4 h-4 text-accent-blue bg-secondary-dark border-accent-gray rounded focus:ring-accent-blue focus:ring-2">
                                        <label for="show_contact_info" class="ml-3 text-sm text-text-secondary">
                                            Mostrar información de contacto
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información de Contacto -->
                        <div class="metric-card">
                            <div class="flex items-center mb-6">
                                <div class="w-10 h-10 bg-gradient-to-r from-accent-green to-green-600 rounded-xl flex items-center justify-center mr-3 neon-glow-green">
                                    <span class="text-white text-lg">📞</span>
                                </div>
                                <h3 class="text-xl font-semibold text-text-primary">Contacto</h3>
                            </div>
                            
                            <div class="space-y-4 text-sm text-text-secondary">
                                <div class="flex items-center">
                                    <span class="w-5 h-5 mr-3 text-accent-blue">📞</span>
                                    <span>{{ $comercio->telefono ?? 'No especificado' }}</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="w-5 h-5 mr-3 text-accent-blue">✉️</span>
                                    <span>{{ $comercio->email ?? 'No especificado' }}</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="w-5 h-5 mr-3 text-accent-blue">📍</span>
                                    <span>{{ $comercio->direccion ?? 'No especificado' }}</span>
                                </div>
                            </div>
                            <p class="text-xs text-text-secondary mt-4">Información que se mostrará en la página pública</p>
                        </div>
                    </div>

                    <!-- Columna Derecha: Vista Previa -->
                    <div class="xl:col-span-2 space-y-6">
                        
                        <!-- Colores del Tema -->
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
                                </div>
                            </div>
                            
                            <!-- Campos ocultos para mantener los valores -->
                            <input type="hidden" id="primary_color" name="primary_color" value="{{ $comercio->primary_color }}">
                            <input type="hidden" id="secondary_color" name="secondary_color" value="{{ $comercio->secondary_color }}">
                            <input type="hidden" id="accent_color" name="accent_color" value="{{ $comercio->accent_color }}">
                            <input type="hidden" id="background_color" name="background_color" value="{{ $comercio->background_color }}">
                        </div>
                        
                        <!-- Vista Previa del Tema -->
                        <div class="metric-card">
                            <div class="flex items-center mb-6">
                                <div class="w-10 h-10 bg-gradient-to-r from-accent-blue to-cyan-500 rounded-xl flex items-center justify-center mr-3 neon-glow">
                                    <span class="text-white text-lg">👁️</span>
                                </div>
                                <h3 class="text-xl font-semibold text-text-primary">Vista Previa</h3>
                            </div>
                            
                            <!-- Preview realista del chatbot móvil -->
                            <div class="bg-background border border-accent-gray rounded-2xl p-6 max-w-sm mx-auto">
                                <!-- Header del comercio -->
                                <div class="mb-4">
                                    <!-- Logo centrado -->
                                    <div class="flex justify-center mb-3">
                                        @if($comercio->logo_url)
                                            <img src="{{ asset('storage/' . $comercio->logo_url) }}" 
                                                 alt="{{ $comercio->nombre }}" 
                                                 class="w-16 h-16 rounded-full object-cover shadow-2xl neon-glow">
                                        @else
                                            <img src="{{ asset('images/logo_empresa_default.png') }}" 
                                                 alt="Logo por defecto de {{ $comercio->nombre }}" 
                                                 class="w-16 h-16 rounded-full object-cover shadow-2xl neon-glow">
                                        @endif
                                    </div>
                                    <!-- Nombre del comercio centrado -->
                                    <div class="text-center">
                                        <h4 class="text-text-primary font-semibold text-base leading-tight mb-1">{{ $comercio->nombre }}</h4>
                                        <p class="text-text-secondary text-sm">Menú Digital</p>
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
                                <div class="space-y-3 mb-4">
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
                                </div>
                                
                                <!-- Input del chat -->
                                <div class="flex">
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

                <!-- Preguntas Frecuentes - Sección de ancho completo -->
                <div class="mt-12">
                    <div class="metric-card flex flex-col min-h-[500px]">
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
                                <div class="bg-secondary-dark border border-accent-gray rounded-xl p-4 question-item" data-index="{{ $index }}">
                                    <div class="grid grid-cols-12 gap-4 items-start">
                                        <!-- Ícono -->
                                        <div class="col-span-1">
                                            <input type="text" 
                                                   name="quick_questions[{{ $index }}][icon]" 
                                                   value="{{ $question['icon'] }}"
                                                   class="w-full px-2 py-2 bg-background border border-accent-gray rounded-lg text-text-primary text-center text-lg focus:outline-none focus:ring-2 focus:ring-accent-blue">
                                        </div>
                                        
                                        <!-- Pregunta -->
                                        <div class="col-span-4">
                                            <input type="text" 
                                                   id="question_{{ $index }}"
                                                   name="quick_questions[{{ $index }}][question]" 
                                                   value="{{ $question['question'] }}"
                                                   placeholder="¿Pregunta frecuente?"
                                                   class="w-full px-3 py-2 bg-background border border-accent-gray rounded-lg text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-blue">
                                        </div>
                                        
                                        <!-- Categoría -->
                                        <div class="col-span-2">
                                            @php($cat = $question['category'] ?? 'general')
                                            <select name="quick_questions[{{ $index }}][category]" 
                                                    class="w-full px-3 py-2 bg-background border border-accent-gray rounded-lg text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-blue">
                                                <option value="menu" {{ $cat === 'menu' ? 'selected' : '' }}>Menú</option>
                                                <option value="precios" {{ $cat === 'precios' ? 'selected' : '' }}>Precios</option>
                                                <option value="horarios" {{ $cat === 'horarios' ? 'selected' : '' }}>Horarios</option>
                                                <option value="ubicacion" {{ $cat === 'ubicacion' ? 'selected' : '' }}>Ubicación</option>
                                                <option value="contacto" {{ $cat === 'contacto' ? 'selected' : '' }}>Contacto</option>
                                                <option value="general" {{ $cat === 'general' ? 'selected' : '' }}>General</option>
                                            </select>
                                        </div>
                                        
                                            <!-- Respuesta -->
                                            <div class="col-span-4">
                                                <div class="flex gap-2">
                                                    <textarea name="quick_questions[{{ $index }}][answer]" 
                                                              id="answer_{{ $index }}"
                                                              rows="2"
                                                              placeholder="Respuesta automática..."
                                                              class="flex-1 px-3 py-2 bg-background border border-accent-gray rounded-lg text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-blue resize-none">{{ $question['answer'] ?? '' }}</textarea>
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

                <!-- Botones de Acción -->
                <div class="flex justify-center space-x-4 mt-12">
                    <button type="submit" 
                            class="btn-primary text-lg px-8 py-4 rounded-xl">
                        💾 Guardar Cambios
                    </button>
                    <a href="{{ route('comercios.customize.reset', $comercio) }}" 
                       onclick="return confirm('¿Estás seguro de que quieres resetear la personalización?')"
                       class="bg-secondary-dark hover:bg-accent-gray text-text-primary font-semibold text-lg px-8 py-4 rounded-xl transition-all duration-300 border border-accent-gray">
                        🔄 Resetear
                    </a>
                </div>
            </form>
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
            newQuestion.className = 'bg-secondary-dark border border-accent-gray rounded-xl p-4 question-item';
            newQuestion.setAttribute('data-index', questionIndex);
            
            newQuestion.innerHTML = `
                <div class="grid grid-cols-12 gap-4 items-start">
                    <!-- Ícono -->
                    <div class="col-span-1">
                        <input type="text" 
                               name="quick_questions[${questionIndex}][icon]" 
                               value="❓"
                               class="w-full px-2 py-2 bg-background border border-accent-gray rounded-lg text-text-primary text-center text-lg focus:outline-none focus:ring-2 focus:ring-accent-blue">
                    </div>
                    
                    <!-- Pregunta -->
                    <div class="col-span-4">
                        <input type="text" 
                               id="question_${questionIndex}"
                               name="quick_questions[${questionIndex}][question]" 
                               value=""
                               placeholder="¿Pregunta frecuente?"
                               class="w-full px-3 py-2 bg-background border border-accent-gray rounded-lg text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-blue">
                    </div>
                    
                    <!-- Categoría -->
                    <div class="col-span-2">
                        <select name="quick_questions[${questionIndex}][category]" 
                                class="w-full px-3 py-2 bg-background border border-accent-gray rounded-lg text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-blue">
                            <option value="menu">Menú</option>
                            <option value="precios">Precios</option>
                            <option value="horarios">Horarios</option>
                            <option value="ubicacion">Ubicación</option>
                            <option value="contacto">Contacto</option>
                            <option value="general" selected>General</option>
                        </select>
                    </div>
                    
                    <!-- Respuesta -->
                    <div class="col-span-4">
                        <div class="flex gap-2">
                            <textarea name="quick_questions[${questionIndex}][answer]" 
                                      id="answer_${questionIndex}"
                                      rows="2"
                                      placeholder="Respuesta automática..."
                                      class="flex-1 px-3 py-2 bg-background border border-accent-gray rounded-lg text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-blue resize-none"></textarea>
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
            if (confirm('¿Estás seguro de que quieres generar nuevas preguntas con IA? Esto reemplazará las preguntas actuales.')) {
                // Mostrar indicador de carga
                const button = document.querySelector('button[onclick="generateQuestionsWithAI()"]');
                const originalText = button.innerHTML;
                button.innerHTML = '⏳ Generando...';
                button.disabled = true;
                
                // Crear formulario temporal para enviar POST
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("comercios.generate.questions", $comercio) }}';
                
                // Agregar CSRF token
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                form.appendChild(csrfToken);
                
                // Agregar al DOM y enviar
                document.body.appendChild(form);
                form.submit();
            }
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
            
            if (confirm('¿Quieres mejorar esta respuesta con IA?')) {
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
        }
    </script>
</x-app-layout>