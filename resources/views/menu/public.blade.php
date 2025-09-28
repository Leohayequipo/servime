<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $comercio->page_title ?? config('app.name', 'ServiME') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/ServiME_IA.ico') }}">
    <link rel="shortcut icon" href="{{ asset('images/ServiME_IA.ico') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Estilos personalizados del comercio -->
    <style>
        :root {
            --primary-color: {{ $comercio->primary_color ?? '#1e293b' }};
            --secondary-color: {{ $comercio->secondary_color ?? '#334155' }};
            --accent-color: {{ $comercio->accent_color ?? '#06b6d4' }};
            --background-color: {{ $comercio->background_color ?? '#0f172a' }};
            --text-primary: #ffffff;
            --text-secondary: #94a3b8;
            --accent-blue: {{ $comercio->primary_color ?? '#3b82f6' }};
            --accent-green: #10b981;
            --accent-purple: #8b5cf6;
            --accent-pink: #ec4899;
            --secondary-dark: {{ $comercio->secondary_color ?? '#334155' }};
            --accent-gray: #6b7280;
        }
        
        body {
            background: #000000; /* Fondo negro para igualar la vista previa */
            color: var(--text-primary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        /* Mapeo de clases usadas en la vista previa del administrador */
        .bg-background { background: #000000 !important; }
        .bg-secondary-dark { background: var(--secondary-color) !important; }
        .bg-accent-blue { background: var(--accent-blue) !important; }
        /* Tipografía exacta de la previa */
        .text-text-primary { color: #ffffff !important; }
        .text-text-secondary { color: #cbd5e1 !important; }
        .placeholder-text-secondary::placeholder { color: #cbd5e1 !important; }
        .border-accent-gray { border-color: rgba(255, 255, 255, 0.2) !important; }
        .placeholder-text-secondary::placeholder { color: var(--text-secondary) !important; }
        .focus\:ring-accent-blue:focus { box-shadow: 0 0 0 2px var(--accent-blue) !important; outline: none; }
        
        .neon-glow {
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
        }
        
        .typing-indicator {
            animation: bounce 1.4s infinite ease-in-out both;
        }
        
        @keyframes bounce {
            0%, 80%, 100% {
                transform: scale(0);
            }
            40% {
                transform: scale(1);
            }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-background">
    <div class="bg-background border border-accent-gray rounded-2xl p-6 max-w-sm mx-auto">
        <!-- Header del comercio -->
        <div class="flex items-center mb-4">
            <img src="{{ $comercio->logo_url ? asset('storage/' . $comercio->logo_url) : 'http://127.0.0.1:8000/storage/logos/7KSRtWP0gxvJn8aclINW6dS3QYHAVRf0uAe1XVc4.jpg' }}" alt="{{ $comercio->nombre }}" class="w-16 h-16 rounded-full object-cover mr-3 shadow-2xl neon-glow">
            <div>
                <h4 class="text-text-primary font-semibold text-sm">{{ $comercio->nombre }}</h4>
                <p class="text-text-secondary text-xs">Menú Digital</p>
            </div>
        </div>
        
        <!-- Chatbot header -->
        <div class="bg-gradient-to-r from-accent-blue to-blue-600 rounded-xl p-3 mb-4 neon-glow" style="background: linear-gradient(to right, {{ $comercio->primary_color ?? '#3b82f6' }}, {{ $comercio->secondary_color ?? '#334155' }});">
            <div class="flex items-center">
                <div class="w-6 h-6 bg-white/20 rounded-full flex items-center justify-center mr-2">
                    <span class="text-white text-xs">🤖</span>
                </div>
                <span class="text-white font-medium text-sm" id="preview-chatbot-name">{{ $comercio->chatbot_name ?? 'Asistente IA' }}</span>
            </div>
        </div>
        
        <!-- Mensajes de ejemplo -->
        <div class="space-y-3 mb-4" id="chatMessages">
            <div class="bg-secondary-dark rounded-lg p-3">
                <p class="text-text-secondary text-xs" id="preview-welcome-message">{{ $comercio->welcome_message ?? '¡Hola! Soy tu asistente virtual. Puedo ayudarte con información sobre nuestro menú. ¿En qué puedo ayudarte?' }}</p>
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
                   id="messageInput"
                   placeholder="Escribe tu mensaje..." 
                   class="flex-1 bg-secondary-dark border border-accent-gray rounded-l-lg px-3 py-2 text-text-primary text-xs placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-accent-blue">
            <button id="sendButton" class="bg-accent-blue hover:bg-blue-600 px-3 py-2 rounded-r-lg text-white text-xs font-medium transition-colors duration-200">
                Enviar
            </button>
        </div>
    </div>

    <!-- Preguntas Rápidas Flotantes -->
    <div style="position: fixed; bottom: 80px; right: 16px; z-index: 50;">
        <div id="quickQuestions" style="display: none;">
            <div style="background: var(--secondary-color); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); padding: 12px; max-width: 280px; margin-bottom: 8px;">
                <h4 style="font-size: 14px; font-weight: 600; color: var(--text-primary); margin: 0 0 8px 0;">Preguntas frecuentes:</h4>
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    @foreach($comercio->quick_questions as $quickQuestion)
                    <button onclick="askQuestion('{{ $quickQuestion['question'] }}')" style="display: block; width: 100%; text-align: left; padding: 8px 12px; background: var(--background-color); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 6px; font-size: 13px; color: var(--text-primary); cursor: pointer; transition: background-color 0.2s;">
                        {{ $quickQuestion['icon'] }} {{ $quickQuestion['question'] }}
                    </button>
                    @endforeach
                </div>
            </div>
        </div>
        <button onclick="toggleQuickQuestions()" style="width: 48px; height: 48px; background: var(--accent-color); border: none; border-radius: 50%; box-shadow: 0 4px 12px rgba(0,0,0,0.15); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background-color 0.2s;">
            <span style="font-size: 18px;">💡</span>
        </button>
    </div>

    <script>
        let isLoading = false;

        // Enviar mensaje
        function sendMessage(message) {
            if (isLoading) return;
            
            isLoading = true;
            
            // Agregar mensaje del usuario
            addMessage(message, 'user');
            
            // Mostrar indicador de escritura
            showTypingIndicator();
            
            // Enviar al servidor
            fetch('{{ route("chat.api", $comercio) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ message: message })
            })
            .then(response => response.json())
            .then(data => {
                hideTypingIndicator();
                if (data.response) {
                    addMessage(data.response, 'bot');
                } else {
                    addMessage('Lo siento, no pude procesar tu mensaje. Inténtalo de nuevo.', 'bot');
                }
            })
            .catch(error => {
                hideTypingIndicator();
                addMessage('Error de conexión. Verifica tu internet e inténtalo de nuevo.', 'bot');
                console.error('Error:', error);
            })
            .finally(() => {
                isLoading = false;
            });
        }

        // Agregar mensaje al chat
        function addMessage(text, sender) {
            const chatMessages = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            
            if (sender === 'user') {
                messageDiv.innerHTML = `
                    <div class="flex justify-end">
                        <div class="bg-accent-blue rounded-lg p-3 max-w-xs">
                            <p class="text-white text-xs">${text}</p>
                        </div>
                    </div>
                `;
            } else {
                messageDiv.innerHTML = `
                    <div class="bg-secondary-dark rounded-lg p-3">
                        <p class="text-text-secondary text-xs">${text}</p>
                    </div>
                `;
            }
            
            chatMessages.appendChild(messageDiv);
            scrollToBottom();
        }

        // Mostrar indicador de escritura
        function showTypingIndicator() {
            const chatMessages = document.getElementById('chatMessages');
            const typingDiv = document.createElement('div');
            typingDiv.id = 'typingIndicator';
            typingDiv.innerHTML = `
                <div class="bg-secondary-dark rounded-lg p-3">
                    <div class="flex items-center space-x-1">
                        <div class="typing-indicator w-2 h-2 bg-gray-400 rounded-full"></div>
                        <div class="typing-indicator w-2 h-2 bg-gray-400 rounded-full" style="animation-delay: 0.2s"></div>
                        <div class="typing-indicator w-2 h-2 bg-gray-400 rounded-full" style="animation-delay: 0.4s"></div>
                    </div>
                </div>
            `;
            chatMessages.appendChild(typingDiv);
            scrollToBottom();
        }

        // Ocultar indicador de escritura
        function hideTypingIndicator() {
            const typingIndicator = document.getElementById('typingIndicator');
            if (typingIndicator) {
                typingIndicator.remove();
                scrollToBottom();
            }
        }

        // Scroll al final
        function scrollToBottom() {
            setTimeout(() => {
                const chatMessages = document.getElementById('chatMessages');
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }, 100);
        }

        // Toggle preguntas rápidas
        function toggleQuickQuestions() {
            const quickQuestions = document.getElementById('quickQuestions');
            if (quickQuestions.style.display === 'none') {
                quickQuestions.style.display = 'block';
            } else {
                quickQuestions.style.display = 'none';
            }
        }

        // Hacer pregunta rápida
        function askQuestion(question) {
            sendMessage(question);
            document.getElementById('quickQuestions').style.display = 'none';
        }

        // Event listeners
        document.getElementById('sendButton').addEventListener('click', function() {
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
            if (message) {
                sendMessage(message);
                messageInput.value = '';
            }
        });

        document.getElementById('messageInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const message = this.value.trim();
                if (message) {
                    sendMessage(message);
                    this.value = '';
                }
            }
        });

        // Auto-focus
        document.getElementById('messageInput').focus();
    </script>
</body>
</html>