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
                <div class="flex space-x-3">
                    <a href="{{ route('comercios.index') }}" 
                       class="bg-secondary-dark hover:bg-accent-gray text-text-primary font-medium py-3 px-6 rounded-xl transition-all duration-300 border border-accent-gray">
                        ← Volver
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

            <form method="POST" action="{{ route('comercios.update', $comercio) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                    
                    <!-- Columna Izquierda: Información del Comercio -->
                    <div class="xl:col-span-1 space-y-6">
                        
                        <!-- 🧾 Información del Comercio -->
                        <div class="metric-card">
                            <div class="flex items-center mb-6">
                                <div class="w-10 h-10 bg-accent-blue rounded-xl flex items-center justify-center mr-3 neon-glow">
                                    <span class="text-white text-lg">🧾</span>
                                </div>
                                <h3 class="text-xl font-semibold text-text-primary">Información del Comercio</h3>
                            </div>
                            
                            <div class="space-y-6">
                        <!-- Nombre del Comercio -->
                        <div>
                                    <label for="nombre" class="block text-sm font-medium text-text-secondary mb-3">
                                        Nombre del Comercio
                                    </label>
                                    <input type="text" 
                                           id="nombre" 
                                           name="nombre" 
                                           value="{{ old('nombre', $comercio->nombre) }}"
                                           required
                                           class="w-full px-4 py-3 bg-secondary-dark border border-accent-gray rounded-xl text-text-primary placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent transition-all duration-200">
                            <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                        </div>

                        <!-- Descripción -->
                        <div>
                                    <label for="descripcion" class="block text-sm font-medium text-text-secondary mb-3">
                                        Descripción
                                    </label>
                                    <textarea id="descripcion" 
                                              name="descripcion" 
                                              rows="3"
                                              class="w-full px-4 py-3 bg-secondary-dark border border-accent-gray rounded-xl text-text-primary placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent transition-all duration-200 resize-none">{{ old('descripcion', $comercio->descripcion) }}</textarea>
                            <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
                                </div>

                                <!-- Dirección -->
                                <div>
                                    <label for="direccion" class="block text-sm font-medium text-text-secondary mb-3">
                                        Dirección
                                    </label>
                                    <input type="text" 
                                           id="direccion" 
                                           name="direccion" 
                                           value="{{ old('direccion', $comercio->direccion) }}"
                                           class="w-full px-4 py-3 bg-secondary-dark border border-accent-gray rounded-xl text-text-primary placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent transition-all duration-200">
                                    <x-input-error :messages="$errors->get('direccion')" class="mt-2" />
                                </div>

                                <!-- Horario -->
                                <div>
                                    <label for="horario" class="block text-sm font-medium text-text-secondary mb-3">
                                        Horario de Atención
                                    </label>
                                    <input type="text" 
                                           id="horario" 
                                           name="horario" 
                                           value="{{ old('horario', $comercio->horario) }}"
                                           placeholder="Ej: Lunes a Viernes 9:00 - 18:00"
                                           class="w-full px-4 py-3 bg-secondary-dark border border-accent-gray rounded-xl text-text-primary placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent transition-all duration-200">
                                    <x-input-error :messages="$errors->get('horario')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- 🖼️ Logo del Comercio -->
                        <div class="metric-card">
                            <div class="flex items-center mb-6">
                                <div class="w-10 h-10 bg-accent-green rounded-xl flex items-center justify-center mr-3 neon-glow-green">
                                    <span class="text-white text-lg">🖼️</span>
                                </div>
                                <h3 class="text-xl font-semibold text-text-primary">Logo del Comercio</h3>
                            </div>
                            
                            @if($comercio->logo_url)
                                <div class="mb-6">
                                    <div class="flex items-center space-x-4 p-4 bg-secondary-dark rounded-xl">
                                        <img src="{{ asset('storage/' . $comercio->logo_url) }}" alt="Logo actual" class="w-20 h-20 object-cover rounded-lg border border-accent-gray">
                                        <div>
                                            <p class="text-sm text-text-secondary mb-2">Logo actual</p>
                                            <button type="button" onclick="confirmDeleteLogo()" class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200">
                                                🗑️ Eliminar Logo
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            
                            <div>
                                <label for="logo" class="block text-sm font-medium text-text-secondary mb-3">
                                    Subir Nuevo Logo
                                </label>
                                <input type="file" 
                                       id="logo" 
                                       name="logo" 
                                       accept="image/*" 
                                       class="w-full px-4 py-3 bg-secondary-dark border border-accent-gray rounded-xl text-text-primary file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-accent-blue file:text-white hover:file:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent transition-all duration-200" />
                            <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                                <p class="mt-2 text-sm text-text-secondary">Formatos permitidos: JPEG, PNG, JPG, GIF. Tamaño máximo: 2MB</p>
                            </div>
                        </div>


                        <!-- 📞 Información de Contacto -->
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
                                    <span>{{ $comercio->user->whatsapp ?? 'No especificado' }}</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="w-5 h-5 mr-3 text-accent-blue">✉️</span>
                                    <span>{{ $comercio->user->email ?? 'No especificado' }}</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="w-5 h-5 mr-3 text-accent-blue">📍</span>
                                    <span>{{ $comercio->direccion ?? 'No especificado' }}</span>
                                </div>
                            </div>
                            <p class="text-xs text-text-secondary mt-4">Información que se mostrará en la página pública</p>
                        </div>
                    </div>

                    <!-- Columna Derecha: Personalización -->
                    <div class="xl:col-span-2 space-y-6">
                        

                    </div>
                </div>


                <!-- Botones de Acción -->
                <div class="flex justify-center space-x-4 mt-12">
                    <button type="submit" 
                            class="btn-primary text-lg px-8 py-4 rounded-xl">
                        💾 Guardar Cambios
                    </button>
                    <a href="{{ route('comercios.index') }}" 
                       class="bg-secondary-dark hover:bg-accent-gray text-text-primary font-semibold text-lg px-8 py-4 rounded-xl transition-all duration-300 border border-accent-gray">
                        🔄 Cancelar
                    </a>
                        </div>
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

        // Funciones para manejar logo
        function confirmDeleteLogo() {
            document.getElementById('deleteLogoModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteLogoModal').classList.add('hidden');
        }

        function deleteLogo() {
            // Crear un formulario oculto para eliminar el logo
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("comercios.update", $comercio) }}';
            
            // Agregar token CSRF
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
            
            // Agregar campo para eliminar logo
            const deleteLogoField = document.createElement('input');
            deleteLogoField.type = 'hidden';
            deleteLogoField.name = 'delete_logo';
            deleteLogoField.value = '1';
            form.appendChild(deleteLogoField);
            
            // Agregar todos los campos del formulario original
            const originalForm = document.querySelector('form');
            const inputs = originalForm.querySelectorAll('input:not([type="file"]), textarea');
            inputs.forEach(input => {
                if (input.name !== '_token' && input.name !== '_method') {
                    const newInput = document.createElement('input');
                    newInput.type = 'hidden';
                    newInput.name = input.name;
                    newInput.value = input.value;
                    form.appendChild(newInput);
                }
            });
            
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</x-app-layout>