<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                🏪 {{ $comercio->nombre }}
            </h2>
            <div class="flex space-x-3">
                <a href="{{ route('admin.comercios.index') }}" class="btn-secondary">
                    Volver a Comercios
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="mb-8 animate-fade-in-down">
                    <div class="relative overflow-hidden bg-gradient-to-r from-green-900/30 to-emerald-900/30 border border-green-500/30 rounded-2xl p-6 shadow-2xl backdrop-blur-sm">
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
                        </div>
                        
                        <div class="absolute bottom-0 left-0 h-1 bg-gradient-to-r from-green-500 to-emerald-500 rounded-full animate-progress-bar"></div>
                    </div>
                </div>
            @endif

            <!-- Información del Comercio -->
            <div class="glass-effect rounded-2xl p-6">
                <div class="flex items-center mb-6">
                    <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mr-6">
                        <span class="text-white text-2xl font-bold">🏪</span>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-text-primary">{{ $comercio->nombre }}</h3>
                        <p class="text-text-secondary">Propietario: {{ $comercio->user->name }}</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="text-text-secondary text-sm font-medium">Nombre del comercio</label>
                            <p class="text-text-primary font-medium">{{ $comercio->nombre }}</p>
                        </div>
                        <div>
                            <label class="text-text-secondary text-sm font-medium">Propietario</label>
                            <p class="text-text-primary font-medium">{{ $comercio->user->name }} ({{ $comercio->user->email }})</p>
                        </div>
                        @if($comercio->descripcion)
                            <div>
                                <label class="text-text-secondary text-sm font-medium">Descripción</label>
                                <p class="text-text-primary font-medium">{{ $comercio->descripcion }}</p>
                            </div>
                        @endif
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="text-text-secondary text-sm font-medium">Fecha de creación</label>
                            <p class="text-text-primary font-medium">{{ $comercio->created_at->format('d/m/Y H:i') }}</p>
                            <p class="text-xs text-text-secondary">{{ $comercio->created_at->diffForHumans() }}</p>
                        </div>
                        @if($comercio->direccion)
                            <div>
                                <label class="text-text-secondary text-sm font-medium">Dirección</label>
                                <p class="text-text-primary font-medium">{{ $comercio->direccion }}</p>
                            </div>
                        @endif
                        @if($comercio->horario)
                            <div>
                                <label class="text-text-secondary text-sm font-medium">Horario</label>
                                <p class="text-text-primary font-medium">{{ $comercio->horario }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Menús del Comercio -->
            <div class="glass-effect rounded-2xl p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-accent-green rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white text-lg">📋</span>
                        </div>
                        <h3 class="text-xl font-bold text-text-primary">Menús</h3>
                    </div>
                    <div class="flex space-x-3">
                        <span class="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 px-3 py-1 rounded-full text-sm font-medium">
                            {{ $comercio->menus->count() }} menú(s)
                        </span>
                    </div>
                </div>
                
                @if($comercio->menus->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($comercio->menus as $menu)
                            <div class="bg-secondary-dark rounded-xl p-6 hover:bg-secondary-dark/80 transition-colors">
                                <div class="flex items-center mb-4">
                                    <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-xl flex items-center justify-center mr-4">
                                        <span class="text-white text-lg">📋</span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-text-primary">{{ $menu->nombre ?: 'Menú sin nombre' }}</h4>
                                        <p class="text-sm text-text-secondary">{{ $menu->created_at->format('d/m/Y') }}</p>
                                    </div>
                                </div>
                                
                                <div class="space-y-2 mb-4">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-text-secondary">Formato:</span>
                                        <span class="px-2 py-1 text-xs rounded-full {{ $menu->formato === 'pdf' ? 'bg-red-500/20 text-red-300 border border-red-500/30' : 'bg-blue-500/20 text-blue-300 border border-blue-500/30' }}">
                                            {{ strtoupper($menu->formato) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-text-secondary">Estado:</span>
                                        <span class="px-2 py-1 text-xs rounded-full {{ $menu->procesado ? 'bg-green-500/20 text-green-300 border border-green-500/30' : 'bg-yellow-500/20 text-yellow-300 border border-yellow-500/30' }}">
                                            {{ $menu->procesado ? 'Procesado' : 'Pendiente' }}
                                        </span>
                                    </div>
                                    @if($menu->activo)
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-text-secondary">Estado:</span>
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-500/20 text-green-300 border border-green-500/30">
                                                Activo
                                            </span>
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <a href="{{ route('admin.menus.show', $menu) }}" 
                                       class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-300 shadow-lg hover:shadow-xl">
                                        Ver Detalles
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-gray-500 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-white text-2xl">📋</span>
                        </div>
                        <p class="text-text-secondary">Este comercio no tiene menús registrados</p>
                        <p class="text-text-secondary text-sm mt-2">Crea el primer menú para este comercio</p>
                    </div>
                @endif
            </div>

            <!-- Información del Propietario -->
            <div class="glass-effect rounded-2xl p-6">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-accent-purple rounded-xl flex items-center justify-center mr-4">
                        <span class="text-white text-lg">👤</span>
                    </div>
                    <h3 class="text-xl font-bold text-text-primary">Información del Propietario</h3>
                </div>
                
                <div class="bg-secondary-dark rounded-xl p-6">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-purple-600 rounded-full flex items-center justify-center">
                            <span class="text-white font-bold">{{ substr($comercio->user->name, 0, 1) }}</span>
                        </div>
                        <div>
                            <h4 class="font-bold text-text-primary">{{ $comercio->user->name }}</h4>
                            <p class="text-text-secondary">{{ $comercio->user->email }}</p>
                            @if($comercio->user->whatsapp)
                                <p class="text-text-secondary text-sm">💬 {{ $comercio->user->whatsapp }}</p>
                            @endif
                        </div>
                        <div class="ml-auto">
                            <a href="{{ route('admin.users.show', $comercio->user) }}" 
                               class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                Ver Usuario
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
