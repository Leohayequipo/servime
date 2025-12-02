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
                                    <x-input-error :messages="$errors->get('horario')" class="mt-1" />
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
                            
                            @if($comercio->logo_url)
                                <div class="mb-4">
                                    <img src="{{ asset('storage/' . $comercio->logo_url) }}" alt="Logo actual" class="w-16 h-16 object-cover rounded-lg border border-accent-gray mx-auto">
                                </div>
                            @endif
                            
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

                        <!-- Contacto -->
                        <div class="metric-card">
                            <div class="flex items-center mb-4">
                                <div class="w-6 h-6 bg-gradient-to-r from-accent-green to-green-600 rounded-lg flex items-center justify-center mr-2">
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
                                <div class="w-6 h-6 bg-gradient-to-r from-orange-500 to-red-500 rounded-lg flex items-center justify-center mr-2">
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

                <!-- Campos ocultos requeridos por el controlador -->
                <input type="hidden" name="primary_color" value="{{ $comercio->primary_color ?? '#3B82F6' }}">
                <input type="hidden" name="secondary_color" value="{{ $comercio->secondary_color ?? '#1E40AF' }}">
                <input type="hidden" name="accent_color" value="{{ $comercio->accent_color ?? '#F59E0B' }}">
                <input type="hidden" name="background_color" value="{{ $comercio->background_color ?? '#FFFFFF' }}">
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

    <!-- JavaScript para vista previa en tiempo real -->
    <script>
        // Variables globales para preguntas frecuentes
        @php
            // Asegurar que quick_questions siempre sea un array
            $quickQuestions = $comercio->quick_questions ?? null;
            if ($quickQuestions === null || !is_array($quickQuestions)) {
                // Si es null o no es array, intentar decodificar si es JSON string
                if (is_string($quickQuestions)) {
                    $quickQuestions = json_decode($quickQuestions, true) ?? [];
                } else {
                    $quickQuestions = [];
                }
            }
        @endphp
        let questionIndex = {{ count($quickQuestions) }};
        
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
            showConfirm('¿Estás seguro de que quieres generar nuevas preguntas con IA? Esto reemplazará las preguntas actuales.', function(result) {
                if (result) {
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
                const existingQuestionsCount = {{ count($quickQuestions) }};
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