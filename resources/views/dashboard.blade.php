<x-app-layout>
    <div class="min-h-screen bg-background">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            
            <!-- Welcome Section -->
            <div class="text-center mb-12">
                <h1 class="text-5xl font-bold text-text-primary mb-4">
                    ¡Bienvenido, {{ auth()->user()->name }}!
                </h1>
                <p class="text-xl text-text-secondary">
                    Gestiona tus comercios y menús desde aquí.
                </p>
            </div>

            <!-- Success Messages -->
            @if(session('success'))
                <div class="mb-8 animate-fade-in-down">
                    <div class="relative overflow-hidden bg-gradient-to-r from-green-900/30 to-emerald-900/30 border border-green-500/30 rounded-2xl p-6 shadow-2xl backdrop-blur-sm">
                        <!-- Animated background -->
                        <div class="absolute inset-0 bg-gradient-to-r from-green-500/10 to-emerald-500/10 animate-pulse"></div>
                        
                        <div class="relative flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-emerald-500 rounded-full flex items-center justify-center shadow-lg animate-bounce">
                                    <span class="text-white text-xl">✨</span>
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <h3 class="text-lg font-bold text-green-100 mb-1">¡Éxito!</h3>
                                <p class="text-green-200 font-medium">{{ session('success') }}</p>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="w-2 h-2 bg-green-400 rounded-full animate-ping"></div>
                            </div>
                        </div>
                        
                        <!-- Progress bar -->
                        <div class="absolute bottom-0 left-0 h-1 bg-gradient-to-r from-green-500 to-emerald-500 rounded-full animate-progress-bar"></div>
                    </div>
                </div>
            @endif

            <!-- Error Messages -->
            @if(session('error'))
                <div class="mb-8 animate-fade-in-down">
                    <div class="relative overflow-hidden bg-gradient-to-r from-red-900/30 to-rose-900/30 border border-red-500/30 rounded-2xl p-6 shadow-2xl backdrop-blur-sm">
                        <!-- Animated background -->
                        <div class="absolute inset-0 bg-gradient-to-r from-red-500/10 to-rose-500/10 animate-pulse"></div>
                        
                        <div class="relative flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-gradient-to-r from-red-500 to-rose-500 rounded-full flex items-center justify-center shadow-lg animate-shake">
                                    <span class="text-white text-xl">⚠️</span>
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <h3 class="text-lg font-bold text-red-100 mb-1">Error</h3>
                                <p class="text-red-200 font-medium">{{ session('error') }}</p>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="w-2 h-2 bg-red-400 rounded-full animate-ping"></div>
                            </div>
                        </div>
                        
                        <!-- Progress bar -->
                        <div class="absolute bottom-0 left-0 h-1 bg-gradient-to-r from-red-500 to-rose-500 rounded-full animate-progress-bar"></div>
                    </div>
                </div>
            @endif

            <!-- Metrics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                <!-- Total Comercios -->
                <a href="{{ route('comercios.index') }}" class="metric-card hover-lift cursor-pointer transition-all duration-300 hover:scale-105">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mr-4 neon-glow">
                            <span class="text-2xl">🏪</span>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-text-primary mb-1">Total Comercios</h3>
                            <p class="text-3xl font-bold text-accent-blue">{{ $comercios->count() }}</p>
                        </div>
                    </div>
                </a>

                <!-- Total Menús -->
                <div class="metric-card">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-accent-green to-green-500 rounded-xl flex items-center justify-center mr-4 neon-glow-green">
                            <span class="text-2xl">📋</span>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-text-primary mb-1">Total Menús</h3>
                            <p class="text-3xl font-bold text-accent-green">{{ $menus->count() }}</p>
                        </div>
                    </div>
                </div>

                <!-- Actividad Reciente -->
                <div class="metric-card">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-yellow-500 to-orange-500 rounded-xl flex items-center justify-center mr-4 neon-glow">
                            <span class="text-2xl">⚡</span>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-text-primary mb-1">Actividad Reciente</h3>
                            <p class="text-3xl font-bold text-yellow-400">{{ $allConversaciones->count() ?? 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>

            @if(auth()->user()->isSuperAdmin())
                <!-- Panel de Administración -->
                <div class="bg-gradient-to-r from-orange-900/30 to-red-900/30 border border-orange-500/30 rounded-2xl p-8 mb-12">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-gradient-to-r from-orange-500 to-red-500 rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white text-2xl">🛡️</span>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-orange-100">Panel de Super Administración</h2>
                            <p class="text-orange-200">Gestiona usuarios, comercios y menús del sistema</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Gestión de Usuarios -->
                        <div class="bg-secondary-dark/50 rounded-xl p-6 hover:bg-secondary-dark/70 transition-all duration-300 cursor-pointer" onclick="window.location.href='{{ route('admin.users.index') }}'">
                            <div class="flex items-center mb-4">
                                <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg flex items-center justify-center mr-3">
                                    <span class="text-white text-lg">👥</span>
                                </div>
                                <h3 class="text-lg font-bold text-text-primary">Usuarios</h3>
                            </div>
                            <p class="text-text-secondary text-sm mb-4">Gestiona usuarios y roles del sistema</p>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-text-secondary">Ver, editar, cambiar roles</span>
                                <span class="text-accent-blue">→</span>
                            </div>
                        </div>

                        <!-- Gestionar -->
                        <div class="bg-secondary-dark/50 rounded-xl p-6 hover:bg-secondary-dark/70 transition-all duration-300 cursor-pointer" onclick="window.location.href='{{ route('comercios.index') }}'">
                            <div class="flex items-center mb-4">
                                <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg flex items-center justify-center mr-3">
                                    <span class="text-white text-lg">⚙️</span>
                                </div>
                                <h3 class="text-lg font-bold text-text-primary">Gestionar</h3>
                            </div>
                            <p class="text-text-secondary text-sm mb-4">Comercios y Menús</p>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-text-secondary">Ver, crear, gestionar</span>
                                <span class="text-accent-blue">→</span>
                            </div>
                        </div>
                    </div>

                </div>
            @endif

            <!-- Quick Actions Section -->
            <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-8">
                <h2 class="text-2xl font-bold text-text-primary mb-8 text-center">Acciones Rápidas</h2>
                
                @if(auth()->user()->isSuperAdmin())
                    <!-- Acciones para Super Administrador -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Crear Comercio (Admin) -->
                        <a href="{{ route('admin.comercios.create') }}" 
                           class="group bg-gradient-to-r from-accent-blue to-blue-600 hover:from-blue-500 hover:to-accent-blue rounded-xl p-6 transition-all duration-300 hover-lift neon-glow">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mr-4 group-hover:scale-110 transition-transform duration-300">
                                    <span class="text-2xl">🏪</span>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-text-primary mb-2">Crear Comercio</h3>
                                    <p class="text-text-secondary">Asignar a Usuario</p>
                                </div>
                            </div>
                        </a>

                        <!-- Crear Menú (Admin) -->
                        <a href="{{ route('admin.menus.create') }}" 
                           class="group bg-gradient-to-r from-accent-green to-green-600 hover:from-green-500 hover:to-accent-green rounded-xl p-6 transition-all duration-300 hover-lift neon-glow-green">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mr-4 group-hover:scale-110 transition-transform duration-300">
                                    <span class="text-2xl">📋</span>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-text-primary mb-2">Crear Menú</h3>
                                    <p class="text-text-secondary">En cualquier Comercio</p>
                                </div>
                            </div>
                        </a>
                    </div>
                @else
                    <!-- Acciones para Usuario Común -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @if(auth()->user()->comercios()->count() === 0)
                            <!-- Crear Nuevo Comercio (solo si no tiene comercios) -->
                            <a href="{{ route('comercios.create') }}" 
                               class="group bg-gradient-to-r from-accent-blue to-blue-600 hover:from-blue-500 hover:to-accent-blue rounded-xl p-6 transition-all duration-300 hover-lift neon-glow">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mr-4 group-hover:scale-110 transition-transform duration-300">
                                        <span class="text-2xl">➕</span>
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-semibold text-text-primary mb-2">Crear Nuevo Comercio</h3>
                                        <p class="text-text-secondary">Agrega un nuevo comercio a tu cuenta</p>
                                    </div>
                                </div>
                            </a>
                        @else
                            <!-- Comercio ya creado - Botón deshabilitado -->
                            <div class="group bg-gradient-to-r from-gray-500 to-gray-600 rounded-xl p-6 opacity-60 cursor-not-allowed relative">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mr-4">
                                        <span class="text-2xl">✅</span>
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-semibold text-gray-300 mb-2">Comercio Creado</h3>
                                        <p class="text-gray-400">Ya tienes un comercio registrado</p>
                                    </div>
                                </div>
                                <!-- Badge de "Completado" -->
                                <div class="absolute top-4 right-4">
                                    <span class="bg-green-500 text-white px-3 py-1 rounded-full text-xs font-medium flex items-center">
                                        <span class="w-2 h-2 bg-white rounded-full mr-2"></span>
                                        Completado
                                    </span>
                                </div>
                            </div>
                        @endif

                        @if(auth()->user()->comercios()->count() > 0)
                            <!-- Gestionar Menús (solo si tiene comercio) -->
                            <a href="{{ route('menus.index', auth()->user()->comercios->first()) }}" 
                               class="group bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-500 hover:to-purple-700 rounded-xl p-6 transition-all duration-300 hover-lift">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mr-4 group-hover:scale-110 transition-transform duration-300">
                                        <span class="text-2xl">📋</span>
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-semibold text-text-primary mb-2">Gestionar Menús</h3>
                                        <p class="text-text-secondary">Crea y administra los menús de tu comercio</p>
                                    </div>
                                </div>
                            </a>
                        @endif
                    </div>
                @endif
            </div>


            <!-- Recent Activity - Improved UX (Agrupado por Sesión) -->
            @if($sesiones->count() > 0)
                <div class="mt-12">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-text-primary">Sesiones de Chat Recientes</h2>
                            <p class="text-text-secondary text-sm mt-1">
                                {{ $sesiones->count() }} sesiones • {{ $allConversaciones->count() }} mensajes totales
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <!-- Filtros rápidos -->
                            <button onclick="filterChats('all')" id="filter-all" class="px-4 py-2 bg-accent-blue text-white rounded-lg text-sm font-medium hover:bg-blue-600 transition-colors active-filter">
                                Todas
                            </button>
                            <button onclick="filterChats('ai')" id="filter-ai" class="px-4 py-2 bg-secondary-dark text-text-secondary rounded-lg text-sm font-medium hover:bg-primary-dark transition-colors border border-accent-gray">
                                Con IA
                            </button>
                            <button onclick="filterChats('faq')" id="filter-faq" class="px-4 py-2 bg-secondary-dark text-text-secondary rounded-lg text-sm font-medium hover:bg-primary-dark transition-colors border border-accent-gray">
                                Solo FAQ
                            </button>
                        </div>
                    </div>
                    
                    <div class="bg-secondary-dark border border-accent-gray rounded-xl p-6">
                        <div class="space-y-4" id="chats-container">
                            @foreach($sesiones as $sesion)
                                @php
                                    $comercio = $sesion['comercio'];
                                    $ultimaConv = $sesion['ultima_conversacion'];
                                    $tieneIA = $sesion['conversaciones']->contains('used_openai', true);
                                    $chatType = $tieneIA ? 'ai' : 'faq';
                                @endphp
                                
                                <div class="chat-card group transition-all duration-200" 
                                     data-chat-type="{{ $chatType }}"
                                     data-session-id="{{ $sesion['session_id'] }}">
                                    <!-- Header de sesión -->
                                    <div class="flex items-start justify-between p-4 bg-primary-dark rounded-xl border border-accent-gray hover:border-accent-blue/50 transition-all duration-200 cursor-pointer"
                                         onclick="toggleSessionExpand('{{ $sesion['session_id'] }}')">
                                        <div class="flex items-start flex-1 min-w-0">
                                            <!-- Avatar e indicadores -->
                                            <div class="relative flex-shrink-0 mr-3">
                                                <div class="w-12 h-12 bg-gradient-to-br from-accent-blue to-blue-600 rounded-full flex items-center justify-center shadow-md">
                                                    <span class="text-xl">💬</span>
                                                </div>
                                                <!-- Badge de tipo -->
                                                @if($tieneIA)
                                                    <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-gradient-to-br from-purple-500 to-purple-600 rounded-full border-2 border-primary-dark flex items-center justify-center" title="Sesión con IA">
                                                        <span class="text-xs">🤖</span>
                                                    </div>
                                                @else
                                                    <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-gradient-to-br from-green-500 to-green-600 rounded-full border-2 border-primary-dark flex items-center justify-center" title="Solo FAQ">
                                                        <span class="text-xs">⚡</span>
                                                    </div>
                                                @endif
                                            </div>
                                            
                                            <!-- Contenido principal de la sesión -->
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <p class="text-text-primary font-semibold text-base">
                                                        {{ $comercio->nombre }}
                                                    </p>
                                                    <span class="text-xs text-text-secondary bg-secondary-dark px-2 py-0.5 rounded">
                                                        {{ $sesion['total_mensajes'] }} {{ $sesion['total_mensajes'] == 1 ? 'mensaje' : 'mensajes' }}
                                                    </span>
                                                    @if($tieneIA)
                                                        <span class="text-xs text-purple-400 bg-purple-500/20 px-2 py-0.5 rounded">
                                                            Con IA
                                                        </span>
                                                    @endif
                                                </div>
                                                
                                                <!-- Preview del último mensaje -->
                                                <div class="mb-2">
                                                    <div class="flex items-start gap-2">
                                                        <span class="text-text-secondary text-xs font-medium mt-0.5">👤</span>
                                                        <p class="text-text-primary text-sm leading-relaxed flex-1">
                                                            {{ Str::limit($ultimaConv->user_message, 80) }}
                                                        </p>
                                                    </div>
                                                </div>
                                                
                                                <!-- Rango de tiempo de la sesión -->
                                                <div class="text-text-secondary text-xs">
                                                    <span>{{ $sesion['primera_conversacion']->created_at->format('d/m H:i') }}</span>
                                                    @if($sesion['primera_conversacion']->id !== $sesion['ultima_conversacion']->id)
                                                        <span> → </span>
                                                        <span>{{ $sesion['ultima_conversacion']->created_at->format('H:i') }}</span>
                                                        <span class="ml-2">({{ $sesion['ultima_conversacion']->created_at->diffForHumans() }})</span>
                                                    @else
                                                        <span class="ml-2">({{ $ultimaConv->created_at->diffForHumans() }})</span>
                                                    @endif
                                                </div>
                                            </div>
                                            
                                            <!-- Timestamp y acciones -->
                                            <div class="flex flex-col items-end gap-2 ml-3 flex-shrink-0">
                                                <span class="text-text-secondary text-xs whitespace-nowrap">
                                                    Última: {{ $ultimaConv->created_at->diffForHumans() }}
                                                </span>
                                                <!-- Botón ver chat completo -->
                                                <a href="{{ route('menu.public', $comercio) }}" 
                                                   target="_blank"
                                                   onclick="event.stopPropagation();"
                                                   class="opacity-0 group-hover:opacity-100 transition-opacity text-accent-blue hover:text-blue-400 text-xs font-medium flex items-center gap-1">
                                                    Ver chat →
                                                </a>
                                        </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Expandible: Todos los mensajes de la sesión -->
                                    <div class="session-expanded-content hidden mt-3 pl-4 border-l-2 border-accent-gray/30 space-y-3">
                                        @foreach($sesion['conversaciones']->sortByDesc('created_at') as $conversacion)
                                            <div class="bg-background rounded-lg p-3 border border-accent-gray/30">
                                                <!-- Mensaje del usuario -->
                                                <div class="mb-3">
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <div class="w-6 h-6 bg-gradient-to-br from-accent-blue to-blue-600 rounded-full flex items-center justify-center">
                                                            <span class="text-xs">👤</span>
                                                        </div>
                                                        <span class="text-text-secondary text-xs font-medium">Usuario</span>
                                                        <span class="text-text-secondary text-xs">•</span>
                                                        <span class="text-text-secondary text-xs">{{ $conversacion->created_at->format('H:i') }}</span>
                                                    </div>
                                                    <p class="text-text-primary text-sm ml-8">{{ Str::limit($conversacion->user_message, 150) }}</p>
                                                </div>
                                                
                                                <!-- Respuesta del bot -->
                                                @if($conversacion->bot_response)
                                                    <div>
                                                        <div class="flex items-center gap-2 mb-1">
                                                            <div class="w-6 h-6 bg-gradient-to-br {{ $conversacion->used_openai ? 'from-purple-500 to-purple-600' : 'from-green-500 to-green-600' }} rounded-full flex items-center justify-center">
                                                                <span class="text-xs">{{ $conversacion->used_openai ? '🤖' : '⚡' }}</span>
                                                            </div>
                                                            <span class="text-text-secondary text-xs font-medium">
                                                                {{ $conversacion->used_openai ? 'IA' : 'FAQ' }}
                                                            </span>
                                                            @if($conversacion->response_time_ms)
                                                                <span class="text-text-secondary text-xs">•</span>
                                                                <span class="text-text-secondary text-xs">{{ number_format($conversacion->response_time_ms) }}ms</span>
                                                            @endif
                                                        </div>
                                                        <p class="text-text-secondary text-sm ml-8 line-clamp-2">{{ Str::limit(strip_tags($conversacion->bot_response), 150) }}</p>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <!-- Ver más -->
                        @if($allConversaciones->count() > 10)
                            <div class="mt-6 text-center">
                                <button class="px-6 py-3 bg-accent-blue hover:bg-blue-600 text-white rounded-lg font-medium transition-colors">
                                    Ver todas las sesiones ({{ $allConversaciones->count() }} mensajes)
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
            
            <style>
                .session-expanded-content {
                    animation: slideDown 0.2s ease-out;
                }
                
                @keyframes slideDown {
                    from {
                        opacity: 0;
                        transform: translateY(-10px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                .line-clamp-2 {
                    display: -webkit-box;
                    -webkit-line-clamp: 2;
                    -webkit-box-orient: vertical;
                    overflow: hidden;
                }
                .active-filter {
                    opacity: 1 !important;
                    border: 2px solid var(--accent-blue) !important;
                }
            </style>
            
            <script>
                function toggleSessionExpand(sessionId) {
                    const card = document.querySelector(`[data-session-id="${sessionId}"]`);
                    const expandedContent = card.querySelector('.session-expanded-content');
                    expandedContent.classList.toggle('hidden');
                }
                
                function filterChats(type) {
                    // Remover active de todos los filtros
                    document.querySelectorAll('[id^="filter-"]').forEach(btn => {
                        btn.classList.remove('active-filter');
                        btn.classList.add('bg-secondary-dark', 'text-text-secondary');
                        btn.classList.remove('bg-accent-blue', 'text-white');
                    });
                    
                    // Activar el filtro seleccionado
                    const activeBtn = document.getElementById(`filter-${type}`);
                    activeBtn.classList.add('active-filter', 'bg-accent-blue', 'text-white');
                    activeBtn.classList.remove('bg-secondary-dark', 'text-text-secondary');
                    
                    // Filtrar chats
                    const chats = document.querySelectorAll('.chat-card');
                    chats.forEach(chat => {
                        if (type === 'all') {
                            chat.style.display = 'block';
                        } else {
                            const chatType = chat.getAttribute('data-chat-type');
                            chat.style.display = (chatType === type) ? 'block' : 'none';
                        }
                    });
                }
            </script>
        </div>
    </div>
</x-app-layout>