<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $comercio->page_title }}</title>
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
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
            border: none;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.4);
        }
        
        .btn-modern:active {
            transform: translateY(0);
        }
        
        .btn-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-modern:hover::before {
            left: 100%;
        }
        
        /* Input moderno */
        .input-modern {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            color: var(--text-primary);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .input-modern:focus {
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .input-modern::placeholder {
            color: var(--text-secondary);
        }
        
        /* Chat container */
        .chat-container {
            height: calc(100vh - 180px);
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.9), rgba(30, 41, 59, 0.8));
        }
        
        /* Mensajes del chatbot */
        .message-bubble {
            max-width: 85%;
            padding: 12px 16px;
            border-radius: 18px;
            margin-bottom: 12px;
            position: relative;
            word-wrap: break-word;
        }
        
        .message-user {
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 6px;
        }
        
        .message-bot {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
            margin-right: auto;
            border-bottom-left-radius: 6px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
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
    <div class="relative">
        @if($comercio->logo_url)
            <!-- Logo como fondo de toda la sección -->
            <div class="h-32 bg-cover bg-center bg-no-repeat relative" style="background-image: url('{{ asset('storage/' . $comercio->logo_url) }}');">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="text-center">
                        <h1 class="text-2xl font-bold text-white mb-1 drop-shadow-lg">{{ $comercio->nombre }}</h1>
                        @if($comercio->descripcion)
                            <p class="text-sm text-white/90 drop-shadow">{{ $comercio->descripcion }}</p>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <!-- Fondo con gradiente si no hay logo -->
            <div class="h-32 bg-gradient-to-r from-accent-blue to-accent-purple relative">
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="text-center">
                        <div class="w-16 h-16 mx-auto mb-2 rounded-full bg-white/20 flex items-center justify-center">
                            <span class="text-3xl text-white">🏪</span>
                        </div>
                        <h1 class="text-2xl font-bold text-white mb-1">{{ $comercio->nombre }}</h1>
                        @if($comercio->descripcion)
                            <p class="text-sm text-white/90">{{ $comercio->descripcion }}</p>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Navegación de menús -->
    <div class="glass-effect border-b border-white/20">
        <div class="max-w-md mx-auto px-4 py-3">
            <div class="flex space-x-4">
                <button class="flex items-center space-x-2 px-4 py-2 rounded-lg bg-accent/20 text-accent border border-accent/30 hover:bg-accent/30 transition-all duration-300">
                    <span class="text-lg">🤖</span>
                    <span class="font-medium">Asistente IA</span>
                </button>
                @if($menus->count() > 0)
                    <button onclick="showMenus()" class="flex items-center space-x-2 px-4 py-2 rounded-lg text-secondary hover:text-primary hover:bg-white/10 transition-all duration-300">
                        <span class="text-lg">📋</span>
                        <span class="font-medium">Ver Menús</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Chat Container -->
    <div class="chat-container overflow-y-auto px-4 py-6">
        <div id="chatMessages" class="max-w-md mx-auto space-y-4">
            <!-- Mensaje de bienvenida -->
            <div class="message-bot message">
                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-r from-accent-blue to-accent-purple flex items-center justify-center flex-shrink-0">
                        <span class="text-white text-sm">🤖</span>
                    </div>
                    <div class="flex-1">
                        <div class="message-bubble message-bot">
                            <p>{{ $comercio->welcome_message ?? '¡Hola! Soy tu asistente virtual. Puedo ayudarte con información sobre nuestro menú. ¿En qué puedo ayudarte?' }}</p>
                        </div>
                        <div class="flex items-center space-x-2 mt-2">
                            <span class="text-xs text-secondary">{{ $comercio->chatbot_name ?? 'Asistente IA' }}</span>
                            <span class="text-xs text-secondary">•</span>
                            <span class="text-xs text-secondary">En línea</span>
                            <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Input del chat -->
    <div class="fixed bottom-0 left-0 right-0 glass-effect border-t border-white/20 p-4">
        <div class="max-w-md mx-auto">
            <form id="chatForm" class="flex space-x-3">
                <div class="flex-1 relative">
                    <input type="text" id="messageInput" placeholder="Escribe tu pregunta..." class="input-modern w-full px-4 py-3 rounded-xl focus:outline-none">
                    <button type="button" onclick="showQuickQuestions()" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-secondary hover:text-primary transition-colors">
                        <span class="text-lg">💡</span>
                    </button>
                </div>
                <button type="submit" class="btn-modern px-4 py-3 rounded-xl flex items-center justify-center hover-lift">
                    <span class="text-lg emoji-bounce">🚀</span>
                </button>
            </form>
            
            <!-- Preguntas rápidas -->
            <div id="quickQuestions" class="hidden mt-3 space-y-2">
                <div class="flex flex-wrap gap-2">
                    <button onclick="askQuestion('¿Qué tenés en el menú?')" class="px-3 py-1 bg-white/10 text-sm rounded-full hover:bg-white/20 transition-all duration-300 text-secondary hover:text-primary">
                        🍽️ ¿Qué tenés en el menú?
                    </button>
                    <button onclick="askQuestion('¿Cuáles son los precios?')" class="px-3 py-1 bg-white/10 text-sm rounded-full hover:bg-white/20 transition-all duration-300 text-secondary hover:text-primary">
                        💰 ¿Cuáles son los precios?
                    </button>
                    <button onclick="askQuestion('¿Tenés opciones vegetarianas?')" class="px-3 py-1 bg-white/10 text-sm rounded-full hover:bg-white/20 transition-all duration-300 text-secondary hover:text-primary">
                        🥗 ¿Tenés opciones vegetarianas?
                    </button>
                    <button onclick="askQuestion('¿Cuáles son los horarios?')" class="px-3 py-1 bg-white/10 text-sm rounded-full hover:bg-white/20 transition-all duration-300 text-secondary hover:text-primary">
                        🕒 ¿Cuáles son los horarios?
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de menús -->
    @if($menus->count() > 0)
        <div id="menuModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="glass-effect rounded-2xl p-6 max-w-md w-full max-h-96 overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold gradient-text">📋 Nuestros Menús</h3>
                    <button onclick="hideMenus()" class="text-secondary hover:text-primary transition-colors">
                        <span class="text-xl">✕</span>
                    </button>
                </div>
                <div class="space-y-3">
                    @foreach($menus as $menu)
                        <div class="p-4 rounded-xl bg-white/10 border border-white/20 hover:bg-white/20 transition-all duration-300 cursor-pointer hover-lift">
                            <h4 class="font-semibold text-primary">{{ $menu->nombre ?? 'Menú sin nombre' }}</h4>
                            <p class="text-sm text-secondary mt-1">Estado: {{ $menu->procesado ? 'Procesado' : 'Pendiente' }}</p>
                            <p class="text-xs text-secondary">Creado: {{ $menu->created_at->format('d/m/Y') }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <script>
        let isLoading = false;
        
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
            
            const avatar = sender === 'user' ? '👤' : '🤖';
            const avatarClass = sender === 'user' ? 'bg-gradient-to-r from-accent-purple to-accent-pink' : 'bg-gradient-to-r from-accent-blue to-accent-purple';
            
            messageDiv.innerHTML = `
                <div class="flex items-start space-x-3 ${sender === 'user' ? 'flex-row-reverse space-x-reverse' : ''}">
                    <div class="w-8 h-8 rounded-full ${avatarClass} flex items-center justify-center flex-shrink-0">
                        <span class="text-white text-sm">${avatar}</span>
                    </div>
                    <div class="flex-1 ${sender === 'user' ? 'flex flex-col items-end' : ''}">
                        <div class="message-bubble message-${sender}">
                            <p>${text}</p>
                        </div>
                        <div class="flex items-center space-x-2 mt-2 ${sender === 'user' ? 'justify-end' : ''}">
                            <span class="text-xs text-secondary">${new Date().toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'})}</span>
                            ${sender === 'bot' ? '<div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>' : ''}
                        </div>
                    </div>
                </div>
            `;
            
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
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
                    <div class="w-8 h-8 rounded-full bg-gradient-to-r from-accent-blue to-accent-purple flex items-center justify-center flex-shrink-0">
                        <span class="text-white text-sm">🤖</span>
                    </div>
                    <div class="flex-1">
                        <div class="message-bubble message-bot">
                            <div class="flex items-center space-x-1">
                                <div class="typing-indicator w-2 h-2 bg-secondary rounded-full"></div>
                                <div class="typing-indicator w-2 h-2 bg-secondary rounded-full" style="animation-delay: 0.2s"></div>
                                <div class="typing-indicator w-2 h-2 bg-secondary rounded-full" style="animation-delay: 0.4s"></div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            chatMessages.appendChild(typingDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        // Ocultar indicador de escritura
        function hideTypingIndicator() {
            isLoading = false;
            const typingIndicator = document.getElementById('typingIndicator');
            if (typingIndicator) {
                typingIndicator.remove();
            }
        }
        
        // Mostrar preguntas rápidas
        function showQuickQuestions() {
            const quickQuestions = document.getElementById('quickQuestions');
            quickQuestions.classList.toggle('hidden');
        }
        
        // Ocultar preguntas rápidas
        function hideQuickQuestions() {
            const quickQuestions = document.getElementById('quickQuestions');
            quickQuestions.classList.add('hidden');
        }
        
        // Hacer pregunta rápida
        function askQuestion(question) {
            document.getElementById('messageInput').value = question;
            hideQuickQuestions();
            sendMessage(question);
        }
        
        // Mostrar menús
        function showMenus() {
            document.getElementById('menuModal').classList.remove('hidden');
        }
        
        // Ocultar menús
        function hideMenus() {
            document.getElementById('menuModal').classList.add('hidden');
        }
        
        // Cerrar modal al hacer click fuera
        document.getElementById('menuModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                hideMenus();
            }
        });
        
        // Auto-focus en el input
        document.getElementById('messageInput').focus();
    </script>
</body>
</html>