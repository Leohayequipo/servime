<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $comercio->nombre }} - Menú Digital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .chat-container {
            height: calc(100vh - 200px);
        }
        .message {
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header del comercio -->
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-md mx-auto px-4 py-6">
            <div class="flex items-center space-x-4">
                @if($comercio->logo_url)
                    <div class="w-12 h-12 rounded-full overflow-hidden shadow-md">
                        <img src="{{ asset('storage/' . $comercio->logo_url) }}" alt="Logo de {{ $comercio->nombre }}" class="w-full h-full object-cover">
                    </div>
                @endif
                <div>
                    <h1 class="text-xl font-bold text-gray-900">{{ $comercio->nombre }}</h1>
                    @if($comercio->descripcion)
                        <p class="text-sm text-gray-600">{{ $comercio->descripcion }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Menús disponibles -->
    <div class="max-w-md mx-auto px-4 py-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Nuestros Menús</h2>
        
        @if($menus->count() > 0)
            <div class="space-y-4">
                @foreach($menus as $menu)
                    <div class="bg-white rounded-lg shadow-sm border p-4">
                        @if($menu->archivo_original && $menu->formato !== 'pdf')
                            <div class="w-full h-32 mb-3 rounded-lg overflow-hidden">
                                <img src="{{ asset('storage/' . $menu->archivo_original) }}" alt="Menú de {{ $comercio->nombre }}" class="w-full h-full object-cover">
                            </div>
                        @endif
                        
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="font-medium text-gray-900">Menú {{ strtoupper($menu->formato) }}</h3>
                                <p class="text-sm text-gray-500">Actualizado: {{ $menu->updated_at->format('d/m/Y') }}</p>
                            </div>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>
                                Procesado
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8">
                <i class="fas fa-utensils text-4xl text-gray-400 mb-4"></i>
                <p class="text-gray-500">No hay menús disponibles en este momento.</p>
            </div>
        @endif
    </div>

    <!-- Chatbot -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t shadow-lg">
        <!-- Chat header -->
        <div class="max-w-md mx-auto px-4 py-3 border-b">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-robot text-indigo-600 text-sm"></i>
                    </div>
                    <span class="font-medium text-gray-900">Asistente IA</span>
                </div>
                <button onclick="toggleChat()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-chevron-up" id="chatToggle"></i>
                </button>
            </div>
        </div>

        <!-- Chat messages -->
        <div class="max-w-md mx-auto px-4 py-4 chat-container hidden" id="chatContainer">
            <div class="space-y-4 mb-4" id="chatMessages">
                <div class="flex items-start space-x-2">
                    <div class="w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-robot text-indigo-600 text-xs"></i>
                    </div>
                    <div class="bg-gray-100 rounded-lg p-3 max-w-xs">
                        <p class="text-sm text-gray-800">¡Hola! Soy tu asistente de IA. Puedo ayudarte con información sobre nuestro menú. ¿En qué puedo ayudarte?</p>
                    </div>
                </div>
            </div>

            <!-- Chat input -->
            <div class="flex space-x-2">
                <input type="text" id="chatInput" placeholder="Escribe tu pregunta..." 
                       class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                       onkeypress="handleKeyPress(event)">
                <button onclick="sendMessage()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
        let chatOpen = false;
        const comercioId = {{ $comercio->id }};

        function toggleChat() {
            const container = document.getElementById('chatContainer');
            const toggle = document.getElementById('chatToggle');
            
            chatOpen = !chatOpen;
            
            if (chatOpen) {
                container.classList.remove('hidden');
                toggle.classList.remove('fa-chevron-up');
                toggle.classList.add('fa-chevron-down');
            } else {
                container.classList.add('hidden');
                toggle.classList.remove('fa-chevron-down');
                toggle.classList.add('fa-chevron-up');
            }
        }

        function handleKeyPress(event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        }

        function sendMessage() {
            const input = document.getElementById('chatInput');
            const message = input.value.trim();
            
            if (!message) return;
            
            // Agregar mensaje del usuario
            addMessage(message, 'user');
            input.value = '';
            
            // Obtener CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            // Enviar al servidor
            fetch(`/api/chat/${comercioId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ message: message })
            })
            .then(response => response.json())
            .then(data => {
                addMessage(data.response, 'bot');
            })
            .catch(error => {
                addMessage('Lo siento, hubo un error. Inténtalo de nuevo.', 'bot');
            });
        }

        function addMessage(message, sender) {
            const messagesContainer = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `flex items-start space-x-2 message ${sender === 'user' ? 'flex-row-reverse space-x-reverse' : ''}`;
            
            if (sender === 'user') {
                messageDiv.innerHTML = `
                    <div class="bg-indigo-600 text-white rounded-lg p-3 max-w-xs">
                        <p class="text-sm">${message}</p>
                    </div>
                `;
            } else {
                messageDiv.innerHTML = `
                    <div class="w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-robot text-indigo-600 text-xs"></i>
                    </div>
                    <div class="bg-gray-100 rounded-lg p-3 max-w-xs">
                        <p class="text-sm text-gray-800">${message}</p>
                    </div>
                `;
            }
            
            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    </script>
</body>
</html>
