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
            --accent-blue: #3b82f6;
            --accent-green: #10b981;
            --accent-purple: #8b5cf6;
            --accent-pink: #ec4899;
        }
        
        body {
            background: var(--background-color);
            color: var(--text-primary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        /* Efectos modernos */
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .neon-glow {
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
        }
        
        .neon-glow-green {
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.3);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hover-lift {
            transition: all 0.3s ease;
        }
        
        .hover-lift:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        /* Animaciones del chatbot */
        .message {
            animation: slideInUp 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        .message-user {
            animation: slideInRight 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        .message-bot {
            animation: slideInLeft 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(20px) scale(0.95); }
            to { opacity: 1; transform: translateX(0) scale(1); }
        }
        
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-20px) scale(0.95); }
            to { opacity: 1; transform: translateX(0) scale(1); }
        }
        
        .typing-indicator {
            animation: pulse 1.5s infinite;
        }
        
        .floating {
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        /* Botones modernos */
        .btn-modern {
            background: #4fc3f7;
            border: none;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-modern:hover {
            background: #29b6f6;
            transform: translateY(-1px);
        }
        
        .btn-modern:active {
            background: #0288d1;
            transform: translateY(0);
        }
        
        /* Input moderno */
        .input-modern {
            background: #ffffff;
            border: 1px solid #e0e0e0;
            color: #303030;
            transition: all 0.3s ease;
        }
        
        .input-modern:focus {
            border-color: #4fc3f7;
            box-shadow: 0 0 0 2px rgba(79, 195, 247, 0.2);
            background: #ffffff;
            outline: none;
        }
        
        .input-modern::placeholder {
            color: #9e9e9e;
        }
        
        /* Chat container */
        .chat-container {
            height: calc(100vh - 100px);
            background: #e5ddd5;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23000000' fill-opacity='0.02'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            padding-bottom: 200px;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        /* Espaciado entre mensajes para las puntitas */
        .message {
            margin-bottom: 16px;
        }
        
        /* Header que se oculta al hacer scroll */
        #header {
            transition: transform 0.3s ease;
        }
        
        #header.hidden {
            transform: translateY(-100%);
        }
        
        /* Mensajes del chatbot */
        .message-bubble {
            max-width: 85%;
            padding: 12px 16px;
            border-radius: 18px;
            margin-bottom: 8px;
            position: relative;
            word-wrap: break-word;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .message-bubble p {
            margin: 0 0 8px 0;
        }
        
        .message-bubble p:last-child {
            margin-bottom: 0;
        }
        
        /* Preguntas frecuentes flotantes */
        #quickQuestions {
            transition: all 0.3s ease;
        }
        
        #quickQuestions .bg-white {
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        #quickQuestions button {
            border: none;
            cursor: pointer;
        }
        
        #quickQuestions button:hover {
            background-color: #f3f4f6 !important;
        }
        
        .message-user {
            background: #dcf8c6;
            color: #303030;
            margin-left: auto;
            margin-right: 0;
            border-bottom-right-radius: 4px;
        }
        
        .message-user::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: -8px;
            width: 0;
            height: 0;
            border: 8px solid transparent;
            border-left-color: #dcf8c6;
            border-bottom: 0;
        }
        
        .message-bot {
            background: #ffffff;
            color: #303030;
            margin-left: 0;
            margin-right: auto;
            border-bottom-left-radius: 4px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }
        
        .message-bot::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: -8px;
            width: 0;
            height: 0;
            border: 8px solid transparent;
            border-right-color: #ffffff;
            border-bottom: 0;
        }
        
        /* Emoji animations */
        .emoji-bounce {
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }
        
        /* Responsive */
        @media (max-width: 640px) {
            .chat-container {
                height: calc(100vh - 160px);
            }
            .message-bubble {
                max-width: 90%;
                padding: 10px 14px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body class="h-full">
    <!-- Header con logo ocupando toda la parte superior -->
    <div class="relative" id="header">
        @if($comercio->logo_url)
            <!-- Logo como fondo de toda la sección -->
            <div class="h-20 bg-cover bg-center bg-no-repeat relative" style="background-image: url('{{ asset('storage/' . $comercio->logo_url) }}');">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="text-center">
                        <h1 class="text-xl font-bold text-white mb-1 drop-shadow-lg">{{ $comercio->nombre }}</h1>
                        @if($comercio->descripcion)
                            <p class="text-xs text-white/90 drop-shadow">{{ $comercio->descripcion }}</p>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <!-- Fondo con gradiente si no hay logo -->
            <div class="h-20 bg-gradient-to-r from-accent-blue to-accent-purple relative">
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="text-center">
                        <div class="w-12 h-12 mx-auto mb-1 rounded-full bg-white/20 flex items-center justify-center">
                            <span class="text-2xl text-white">🏪</span>
                        </div>
                        <h1 class="text-xl font-bold text-white mb-1">{{ $comercio->nombre }}</h1>
                        @if($comercio->descripcion)
                            <p class="text-xs text-white/90">{{ $comercio->descripcion }}</p>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>


    <!-- Chat Container -->
    <div class="chat-container overflow-y-auto px-4 py-6">
        <div id="chatMessages" class="max-w-md mx-auto space-y-4">
            <!-- Mensaje de bienvenida -->
            <div class="message-bot message">
                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center flex-shrink-0">
                        <span class="text-white text-sm">💬</span>
                    </div>
                    <div class="flex-1">
                        <div class="message-bubble message-bot">
                            <p>{{ $comercio->welcome_message ?? '¡Hola! Soy tu asistente virtual. Puedo ayudarte con información sobre nuestro menú. ¿En qué puedo ayudarte?' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Input del chat -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-3">
        <div class="max-w-md mx-auto">
            <form id="chatForm" class="flex space-x-3">
                <div class="flex-1">
                    <input type="text" id="messageInput" placeholder="Escribe tu pregunta..." class="input-modern w-full px-4 py-3 rounded-xl focus:outline-none">
                </div>
                <button type="submit" class="btn-modern px-4 py-3 rounded-xl flex items-center justify-center hover-lift">
                    <span class="text-lg emoji-bounce">🚀</span>
                </button>
            </form>
            
        </div>
    </div>

    <!-- Preguntas frecuentes flotantes -->
    <div style="position: fixed; bottom: 80px; right: 16px; z-index: 50;">
        <div id="quickQuestions" style="display: none;">
            <div style="background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); padding: 12px; max-width: 280px; border: 1px solid #e5e7eb; margin-bottom: 8px;">
                <h4 style="font-size: 14px; font-weight: 600; color: #374151; margin: 0 0 8px 0;">Preguntas frecuentes:</h4>
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    @foreach($comercio->quick_questions as $quickQuestion)
                    <button onclick="askQuestion('{{ $quickQuestion['question'] }}')" style="display: block; width: 100%; text-align: left; padding: 8px 12px; background: #f9fafb; border: none; border-radius: 6px; font-size: 13px; color: #374151; cursor: pointer; transition: background-color 0.2s;">
                        {{ $quickQuestion['icon'] }} {{ $quickQuestion['question'] }}
                    </button>
                    @endforeach
                </div>
            </div>
        </div>
        <button onclick="toggleQuickQuestions()" style="width: 48px; height: 48px; background: #3b82f6; border: none; border-radius: 50%; box-shadow: 0 4px 12px rgba(0,0,0,0.15); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background-color 0.2s;">
            <span style="font-size: 18px;">💡</span>
        </button>
    </div>

    <script>
        let isLoading = false;
        
        // Mostrar/ocultar preguntas frecuentes
        function toggleQuickQuestions() {
            var quickQuestions = document.getElementById('quickQuestions');
            if (quickQuestions.style.display === 'none') {
                quickQuestions.style.display = 'block';
            } else {
                quickQuestions.style.display = 'none';
            }
        }
        
        // Hacer pregunta rápida
        function askQuestion(question) {
            document.getElementById('messageInput').value = question;
            document.getElementById('quickQuestions').style.display = 'none';
            document.getElementById('chatForm').dispatchEvent(new Event('submit'));
        }
        
        // Configuración del comercio
        const comercio = {
            id: {{ $comercio->id }},
            nombre: '{{ $comercio->nombre }}',
            primaryColor: '{{ $comercio->primary_color ?? "#1e293b" }}',
            secondaryColor: '{{ $comercio->secondary_color ?? "#334155" }}',
            accentColor: '{{ $comercio->accent_color ?? "#06b6d4" }}'
        };
        
        // Manejar envío de mensajes
        document.getElementById('chatForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (message && !isLoading) {
                sendMessage(message);
                input.value = '';
                hideQuickQuestions();
            }
        });
        
        // Enviar mensaje
        async function sendMessage(message) {
            const chatMessages = document.getElementById('chatMessages');
            
            // Mostrar mensaje del usuario
            addMessage(message, 'user');
            
            // Mostrar indicador de escritura
            showTypingIndicator();
            
            try {
                const response = await fetch(`/api/chat/${comercio.id}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ message: message })
                });
                
                const data = await response.json();
                
                // Ocultar indicador de escritura
                hideTypingIndicator();
                
                // Mostrar respuesta del bot
                addMessage(data.response, 'bot');
                
            } catch (error) {
                hideTypingIndicator();
                addMessage('Lo siento, hubo un error. Inténtalo de nuevo.', 'bot');
            }
        }
        
        // Agregar mensaje al chat
        function addMessage(text, sender) {
            const chatMessages = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message-${sender} message`;
            
            const avatar = sender === 'user' ? '🧑' : '💬';
            const avatarClass = sender === 'user' ? 'bg-gray-400' : 'bg-green-500';
            
            // Formatear el texto para mejor legibilidad
            const formattedText = formatMessageText(text);
            
            messageDiv.innerHTML = `
                <div class="flex items-start space-x-3 ${sender === 'user' ? 'flex-row-reverse space-x-reverse' : ''}">
                    <div class="w-8 h-8 rounded-full ${avatarClass} flex items-center justify-center flex-shrink-0">
                        <span class="text-white text-sm">${avatar}</span>
                    </div>
                    <div class="flex-1 ${sender === 'user' ? 'flex flex-col items-end' : ''}">
                        <div class="message-bubble message-${sender}">
                            ${formattedText}
                        </div>
                    </div>
                </div>
            `;
            
            chatMessages.appendChild(messageDiv);
            
            // Scroll simple y directo
            setTimeout(() => {
                const chatContainer = document.querySelector('.chat-container');
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }, 100);
        }
        
        function formatMessageText(text) {
            // Simplificar: solo convertir saltos de línea a <br>
            return `<p>${text.replace(/\n/g, '<br>')}</p>`;
        }
        
        // Mostrar indicador de escritura
        function showTypingIndicator() {
            isLoading = true;
            const chatMessages = document.getElementById('chatMessages');
            const typingDiv = document.createElement('div');
            typingDiv.id = 'typingIndicator';
            typingDiv.className = 'message-bot message';
            
            typingDiv.innerHTML = `
                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center flex-shrink-0">
                        <span class="text-white text-sm">💬</span>
                    </div>
                    <div class="flex-1">
                        <div class="message-bubble message-bot">
                            <div class="flex items-center space-x-1">
                                <div class="typing-indicator w-2 h-2 bg-gray-400 rounded-full"></div>
                                <div class="typing-indicator w-2 h-2 bg-gray-400 rounded-full" style="animation-delay: 0.2s"></div>
                                <div class="typing-indicator w-2 h-2 bg-gray-400 rounded-full" style="animation-delay: 0.4s"></div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            chatMessages.appendChild(typingDiv);
            
            // Scroll simple y directo
            setTimeout(() => {
                const chatContainer = document.querySelector('.chat-container');
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }, 100);
        }
        
        // Ocultar indicador de escritura
        function hideTypingIndicator() {
            isLoading = false;
            const typingIndicator = document.getElementById('typingIndicator');
            if (typingIndicator) {
                typingIndicator.remove();
                
                // Scroll al final después de ocultar el indicador
                setTimeout(() => {
                    const chatContainer = document.querySelector('.chat-container');
                    chatContainer.scrollTop = chatContainer.scrollHeight;
                }, 100);
            }
        }
        
        // Scroll al final después de enviar pregunta rápida
        setTimeout(() => {
            const chatContainer = document.querySelector('.chat-container');
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }, 200);
        
        
        // Auto-focus en el input y scroll inicial
        document.getElementById('messageInput').focus();
        
        // Scroll inicial al final del chat
        setTimeout(() => {
            const chatContainer = document.querySelector('.chat-container');
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }, 500);
        
        // Ocultar header al hacer scroll
        let lastScrollTop = 0;
        const header = document.getElementById('header');
        const chatContainer = document.querySelector('.chat-container');
        
        chatContainer.addEventListener('scroll', function() {
            const scrollTop = chatContainer.scrollTop;
            
            if (scrollTop > lastScrollTop && scrollTop > 50) {
                // Scrolling down - hide header
                header.classList.add('hidden');
            } else {
                // Scrolling up - show header
                header.classList.remove('hidden');
            }
            
            lastScrollTop = scrollTop;
        });
    </script>
</body>
</html>