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
        
        /* Animaciones personalizadas */
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slide-in-left {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes slide-in-right {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .animate-fade-in {
            animation: fade-in 0.5s ease-out;
        }

        .animate-slide-in-left {
            animation: slide-in-left 0.4s ease-out;
        }

        .animate-slide-in-right {
            animation: slide-in-right 0.4s ease-out;
        }
        
        html, body { height: 100%; }
        body {
            background: #000000;
            color: var(--text-primary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            overscroll-behavior-y: contain;
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
        
        /* Fullscreen mobile app layout */
        .app { min-height: 100dvh; display: flex; flex-direction: column; }
        .app-header { position: sticky; top: 0; z-index: 10; padding: 12px 16px; background: #000000; }
        .chat-scroll { flex: 1; overflow-y: auto; -webkit-overflow-scrolling: touch; padding: 12px 16px; padding-bottom: 96px; }
        .input-fixed { position: fixed; left: 0; right: 0; bottom: 0; padding: 8px 12px calc(8px + env(safe-area-inset-bottom)); background: #000000; border-top: 1px solid rgba(255,255,255,0.1); }
        .chips { display: flex; gap: 8px; flex-wrap: wrap; }
        .chips-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 4px; }
        
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
<body class="bg-background">
    <div class="app">
        <!-- Header fijo -->
        <div class="app-header">
            <div class="max-w-md mx-auto px-4">
                <!-- Logo centrado -->
                <div class="flex justify-center mb-3">
                    <img src="{{ $comercio->logo_url ? asset('storage/' . $comercio->logo_url) : 'http://127.0.0.1:8000/storage/logos/7KSRtWP0gxvJn8aclINW6dS3QYHAVRf0uAe1XVc4.jpg' }}" alt="{{ $comercio->nombre }}" class="w-20 h-20 rounded-full object-cover shadow-2xl neon-glow">
                </div>
                <!-- Nombre del comercio centrado -->
                <div class="text-center">
                    <h4 class="text-text-primary font-semibold text-xl leading-tight mb-1">{{ $comercio->nombre }}</h4>
                    <p class="text-text-secondary text-base">Menú Digital</p>
                </div>
            </div>
            <div class="max-w-md mx-auto mt-3">
                <div class="bg-gradient-to-r from-accent-blue to-blue-600 rounded-xl p-3 neon-glow" style="background: linear-gradient(to right, {{ $comercio->primary_color ?? '#3b82f6' }}, {{ $comercio->secondary_color ?? '#334155' }});">
                    <div class="flex items-center">
                        <div class="w-6 h-6 bg-white/20 rounded-full flex items-center justify-center mr-2">
                            <span class="text-white text-xs">🤖</span>
                        </div>
                        <span class="text-white font-medium text-base" id="preview-chatbot-name">{{ $comercio->chatbot_name ?? 'Asistente IA' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Área de conversación -->
        <div id="chatScroll" class="chat-scroll">
            <div id="chatMessages" class="max-w-md mx-auto space-y-4 px-4">
                <div class="bg-gradient-to-r from-secondary-dark to-secondary-dark/80 rounded-xl p-4 shadow-lg animate-fade-in">
                    <div class="flex items-start space-x-3">
                        <div class="w-8 h-8 bg-gradient-to-br from-accent-blue to-blue-500 rounded-full flex items-center justify-center flex-shrink-0 shadow-md">
                            <span class="text-white text-sm">🤖</span>
                        </div>
                        <div class="flex-1">
                            <p class="text-text-primary text-base leading-relaxed" id="preview-welcome-message">{{ $comercio->welcome_message ?? '¡Hola! Soy tu asistente virtual. Puedo ayudarte con información sobre nuestro menú. ¿En qué puedo ayudarte?' }}</p>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>

        <!-- Input fijo abajo -->
        <div class="input-fixed">
            <div class="max-w-md mx-auto flex">
                <input type="text" 
                       id="messageInput"
                       placeholder="Escribe tu mensaje..." 
                       class="flex-1 bg-secondary-dark border border-accent-gray rounded-l-lg px-3 py-3 text-text-primary text-base placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-accent-blue">
                <button id="sendButton" class="bg-accent-blue hover:bg-blue-600 px-5 py-3 rounded-r-lg text-white text-base font-medium transition-colors duration-200">
                    Enviar
                </button>
            </div>
        </div>
    </div>

    <!-- FAB Preguntas Rápidas -->
    <div style="position: fixed; bottom: 84px; right: 16px; z-index: 50;">
        <button id="fabFaq" title="Preguntas rápidas" style="width: 56px; height: 56px; background: var(--accent-color); border: none; border-radius: 50%; box-shadow: 0 10px 24px rgba(0,0,0,0.35); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;">
            <span style="font-size: 22px;">💡</span>
        </button>
    </div>

    <!-- Panel de Preguntas Rápidas -->
    <div id="faqPanel" style="display:none; position: fixed; right: 16px; bottom: 148px; z-index: 50; max-width: 320px;">
        <div style="background: #0b0b0b; border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 12px; box-shadow: 0 10px 24px rgba(0,0,0,0.45); padding: 12px;">
            <h4 style="font-size: 13px; font-weight: 600; color: var(--text-primary); margin: 0 0 8px 0;">Preguntas rápidas</h4>
            <div style="display: flex; flex-wrap: wrap; gap: 6px; max-height: 260px; overflow-y: auto;">
                @foreach($comercio->quick_questions as $quickQuestion)
                    <button onclick="askQuestion('{{ $quickQuestion['question'] }}'); hideFaq();" style="padding: 10px 12px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.12); background: #141414; color: #ffffff; font-size: 13px; cursor: pointer;">
                        {{ $quickQuestion['icon'] ?? '❓' }} {{ $quickQuestion['question'] }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <script>
        let isLoading = false;

        // Enviar mensaje
        function sendMessage(message) {
            if (isLoading) return;
            
            isLoading = true;
            
            // Mostrar indicador de carga en el botón de envío
            const sendButton = document.getElementById('sendButton');
            const originalContent = sendButton.innerHTML;
            sendButton.innerHTML = `
                <div class="flex items-center justify-center space-x-1">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                    <span class="text-xs">Enviando...</span>
                </div>
            `;
            sendButton.disabled = true;
            
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
                // Restaurar botón de envío
                sendButton.innerHTML = originalContent;
                sendButton.disabled = false;
            });
        }

        // Convertir Markdown básico a HTML
        function parseMarkdown(text) {
            // Convertir **texto** a <strong>texto</strong>
            text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
            // Convertir *texto* a <em>texto</em>
            text = text.replace(/\*(.+?)\*/g, '<em>$1</em>');
            return text;
        }

        // Agregar mensaje al chat
        function addMessage(text, sender) {
            const chatMessages = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            
            // Parsear Markdown para el bot
            const parsedText = sender === 'bot' ? parseMarkdown(text) : text;
            
            if (sender === 'user') {
                messageDiv.innerHTML = `
                    <div class="flex justify-end animate-slide-in-right">
                        <div class="bg-gradient-to-r from-accent-blue to-blue-500 rounded-xl p-4 max-w-xs text-white text-base shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105" style="white-space: pre-wrap;">${parsedText}</div>
                    </div>
                `;
            } else {
                messageDiv.innerHTML = `
                    <div class="bg-gradient-to-r from-secondary-dark to-secondary-dark/80 rounded-xl p-4 shadow-lg animate-slide-in-left">
                        <div class="flex items-start space-x-3">
                            <div class="w-8 h-8 bg-gradient-to-br from-accent-blue to-blue-500 rounded-full flex items-center justify-center flex-shrink-0 shadow-md">
                                <span class="text-white text-sm">🤖</span>
                            </div>
                            <div class="flex-1">
                                <p class="text-text-primary text-base leading-relaxed" style="white-space: pre-wrap;">${parsedText}</p>
                            </div>
                        </div>
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
                <div class="bg-gradient-to-r from-secondary-dark to-secondary-dark/80 rounded-xl p-4 shadow-lg animate-slide-in-left">
                    <div class="flex items-start space-x-3">
                        <div class="w-8 h-8 bg-gradient-to-br from-accent-blue to-blue-500 rounded-full flex items-center justify-center flex-shrink-0 shadow-md">
                            <span class="text-white text-sm">🤖</span>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center space-x-3">
                                <div class="flex space-x-1">
                                    <div class="w-2 h-2 bg-accent-blue rounded-full animate-bounce"></div>
                                    <div class="w-2 h-2 bg-accent-blue rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                                    <div class="w-2 h-2 bg-accent-blue rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                                </div>
                                <span class="text-text-primary text-sm font-medium">Escribiendo...</span>
                            </div>
                        </div>
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
                const chatScroll = document.getElementById('chatScroll');
                if (chatScroll) chatScroll.scrollTop = chatScroll.scrollHeight;
            }, 100);
        }

        // FAB FAQ
        const fab = document.getElementById('fabFaq');
        const faqPanel = document.getElementById('faqPanel');
        function showFaq(){ faqPanel.style.display = 'block'; }
        function hideFaq(){ faqPanel.style.display = 'none'; }
        function toggleFaq(){ faqPanel.style.display = faqPanel.style.display === 'block' ? 'none' : 'block'; }
        if (fab) fab.addEventListener('click', toggleFaq);

        // Hacer pregunta rápida
        function askQuestion(question) {
            sendMessage(question);
            hideFaq();
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