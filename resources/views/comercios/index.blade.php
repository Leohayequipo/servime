<x-app-layout>
    <div class="min-h-screen bg-background">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            
            <!-- Header Section -->
            <div class="text-center mb-12">
                <h1 class="text-5xl font-bold text-text-primary mb-4">
                    🏪 Mis Comercios
                </h1>
                <p class="text-xl text-text-secondary">
                    Gestiona tus negocios y menús digitales
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

            <!-- Create Button (Solo para Super Admin o usuarios sin comercio) -->
            @if(auth()->user()->isSuperAdmin() || $comercios->count() === 0)
                <div class="text-center mb-12">
                    <a href="{{ route('comercios.create') }}" 
                       class="btn-primary inline-flex items-center text-lg px-8 py-4 rounded-xl">
                        <span class="mr-2">➕</span>
                        Crear Comercio
                    </a>
                </div>
            @endif
        
            @if($comercios->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
                    @foreach($comercios as $comercio)
                        <div class="metric-card group">
                            <!-- Logo/Imagen del comercio -->
                            <div class="relative h-48 overflow-hidden rounded-t-xl mb-6 bg-gradient-to-br from-primary-dark to-secondary-dark">
                                @if($comercio->logo_url)
                                    <img src="{{ asset('storage/' . $comercio->logo_url) }}" 
                                         alt="Logo de {{ $comercio->nombre }}" 
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                         style="display: block !important; visibility: visible !important; opacity: 1 !important; height: 100% !important; width: 100% !important; object-fit: cover !important;">
                                @else
                                    <!-- Logo por defecto cuando no hay logo -->
                                    <img src="{{ asset('images/logo_empresa_default.png') }}" 
                                         alt="Logo por defecto de {{ $comercio->nombre }}" 
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                         style="display: block !important; visibility: visible !important; opacity: 1 !important; height: 100% !important; width: 100% !important; object-fit: cover !important;">
                                @endif
                                <div class="edit-button-container" style="position: absolute; top: 1rem; right: 1rem;">
                                    <a href="{{ route('comercios.edit', $comercio) }}" 
                                       class="edit-button w-8 h-8 bg-gray-800/60 backdrop-blur-sm rounded-full flex items-center justify-center hover:bg-accent-blue hover:scale-110 transition-all duration-300 group/edit"
                                       style="background: rgba(31, 41, 55, 0.6) !important; border: none !important; border-radius: 50% !important;">
                                        <span class="text-accent-blue text-sm group-hover/edit:text-white">✏️</span>
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Contenido de la tarjeta -->
                            <div class="p-6">
                                <!-- Información principal -->
                                <div class="mb-6 text-center">
                                    <h3 class="text-2xl font-bold text-text-primary mb-2 group-hover:text-accent-blue transition-colors duration-300">
                                        {{ $comercio->nombre }}
                                    </h3>
                                    
                                @if($comercio->descripcion)
                                        <p class="text-text-secondary mb-4 line-clamp-2">{{ $comercio->descripcion }}</p>
                                @endif
                                    
                                    <!-- Información de contacto -->
                                    <div class="space-y-2 text-sm text-text-secondary text-center">
                                @if($comercio->direccion)
                                            <div class="flex items-center justify-center">
                                                <span class="w-4 h-4 mr-2 text-accent-blue">📍</span>
                                                <span class="truncate">{{ $comercio->direccion }}</span>
                                            </div>
                                        @endif
                                        @if($comercio->telefono)
                                            <div class="flex items-center justify-center">
                                                <span class="w-4 h-4 mr-2 text-accent-blue">📞</span>
                                                <span>{{ $comercio->telefono }}</span>
                                            </div>
                                @endif
                                @if($comercio->horario)
                                            <div class="flex items-center justify-center">
                                                <span class="w-4 h-4 mr-2 text-accent-blue">🕒</span>
                                                <span>{{ $comercio->horario }}</span>
                                            </div>
                                @endif
                                    </div>
                                </div>
                                
                                <!-- Estadísticas -->
                                <div class="grid grid-cols-2 gap-4 mb-6">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-accent-blue">{{ $comercio->menus->count() }}</div>
                                        <div class="text-xs text-text-secondary">Menús</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-accent-green">{{ $comercio->chatConversations->count() }}</div>
                                        <div class="text-xs text-text-secondary">Conversaciones</div>
                                    </div>
                                </div>
                                
                                <!-- Botones de acción -->
                                <div class="space-y-3">
                                    @php($hasMenus = optional($comercio->menus)->count() > 0)
                                    <a href="{{ route('menus.index', $comercio) }}" 
                                       class="w-full btn-primary text-center block">
                                        <span class="mr-2">📋</span>
                                        {{ $hasMenus ? 'Ver Menús' : 'Crear Menú' }}
                                        <span class="ml-2 group-hover:translate-x-1 transition-transform duration-300">→</span>
                                    </a>
                                    
                                    <!-- Botón Editar -->
                                    <div class="grid grid-cols-1 gap-2">
                                        <a href="{{ route('comercios.edit', $comercio) }}" 
                                           class="bg-accent-blue hover:bg-blue-600 text-white font-medium py-2 px-3 rounded-lg transition-all duration-300 flex items-center justify-center text-sm">
                                            <span class="mr-1">✏️</span>
                                            Editar Comercio
                                        </a>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 gap-2">
                                        @if(auth()->user()->isSuperAdmin())
                                            <form method="POST" action="{{ route('admin.comercios.destroy', $comercio) }}" 
                                                  onsubmit="event.preventDefault(); const form = this; showConfirmWithLoader('¿Estás seguro de que quieres eliminar este comercio?', function(result) { if(result) { form.submit(); } }, 'Eliminando comercio...');"
                                                  class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="w-full bg-secondary-dark hover:bg-red-500/20 hover:text-red-400 text-text-primary font-medium py-2 px-3 rounded-lg transition-all duration-300 flex items-center justify-center text-sm border border-accent-gray">
                                                    <span class="mr-1">🗑️</span>
                                                    Eliminar
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('comercios.destroy', $comercio) }}" 
                                                  onsubmit="event.preventDefault(); const form = this; showConfirmWithLoader('¿Estás seguro de que quieres eliminar este comercio?', function(result) { if(result) { form.submit(); } }, 'Eliminando comercio...');"
                                                  class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="w-full bg-secondary-dark hover:bg-red-500/20 hover:text-red-400 text-text-primary font-medium py-2 px-3 rounded-lg transition-all duration-300 flex items-center justify-center text-sm border border-accent-gray">
                                                    <span class="mr-1">🗑️</span>
                                                    Eliminar
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <!-- Estado vacío -->
                <div class="text-center py-16">
                    <div class="w-32 h-32 mx-auto mb-8 bg-gradient-to-r from-accent-blue to-accent-green rounded-full flex items-center justify-center neon-glow">
                        <span class="text-6xl">🏪</span>
                    </div>
                    <h3 class="text-3xl font-bold text-text-primary mb-4">No tienes comercios aún</h3>
                    <p class="text-xl text-text-secondary mb-8">Crea tu primer comercio para comenzar a gestionar menús digitales</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>