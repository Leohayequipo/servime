<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                📋 {{ $menu->nombre ?: 'Menú sin nombre' }}
            </h2>
            <div class="flex space-x-3">
                <a href="{{ route('admin.menus.index') }}" class="btn-secondary">
                    Volver a Menús
                </a>
                <a href="{{ route('admin.menus.edit', $menu) }}" class="btn-primary">
                    Editar Menú
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

            <!-- Información del Menú -->
            <div class="glass-effect rounded-2xl p-6">
                <div class="flex items-center mb-6">
                    <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-green-600 rounded-2xl flex items-center justify-center mr-6">
                        <span class="text-white text-2xl font-bold">📋</span>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-text-primary">{{ $menu->nombre ?: 'Menú sin nombre' }}</h3>
                        <p class="text-text-secondary">Comercio: {{ $menu->comercio->nombre }}</p>
                        <p class="text-text-secondary">Propietario: {{ $menu->comercio->user->name }}</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="text-text-secondary text-sm font-medium">Nombre del menú</label>
                            <p class="text-text-primary font-medium">{{ $menu->nombre ?: 'Sin nombre' }}</p>
                        </div>
                        <div>
                            <label class="text-text-secondary text-sm font-medium">Comercio</label>
                            <p class="text-text-primary font-medium">{{ $menu->comercio->nombre }}</p>
                        </div>
                        <div>
                            <label class="text-text-secondary text-sm font-medium">Propietario</label>
                            <p class="text-text-primary font-medium">{{ $menu->comercio->user->name }} ({{ $menu->comercio->user->email }})</p>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="text-text-secondary text-sm font-medium">Formato</label>
                            <span class="px-3 py-1 text-sm rounded-full {{ $menu->formato === 'pdf' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ strtoupper($menu->formato) }}
                            </span>
                        </div>
                        <div>
                            <label class="text-text-secondary text-sm font-medium">Estado</label>
                            <span class="px-3 py-1 text-sm rounded-full {{ $menu->procesado ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ $menu->procesado ? 'Procesado' : 'Pendiente' }}
                            </span>
                        </div>
                        @if($menu->activo)
                            <div>
                                <label class="text-text-secondary text-sm font-medium">Estado del menú</label>
                                <span class="px-3 py-1 text-sm rounded-full bg-green-100 text-green-800">
                                    Activo
                                </span>
                            </div>
                        @endif
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="text-text-secondary text-sm font-medium">Fecha de creación</label>
                            <p class="text-text-primary font-medium">{{ $menu->created_at->format('d/m/Y H:i') }}</p>
                            <p class="text-xs text-text-secondary">{{ $menu->created_at->diffForHumans() }}</p>
                        </div>
                        <div>
                            <label class="text-text-secondary text-sm font-medium">Última actualización</label>
                            <p class="text-text-primary font-medium">{{ $menu->updated_at->format('d/m/Y H:i') }}</p>
                            <p class="text-xs text-text-secondary">{{ $menu->updated_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Archivo del Menú -->
            @if($menu->archivo_original)
                <div class="glass-effect rounded-2xl p-6">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 bg-accent-blue rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white text-lg">📄</span>
                        </div>
                        <h3 class="text-xl font-bold text-text-primary">Archivo del Menú</h3>
                    </div>
                    
                    <div class="bg-secondary-dark rounded-xl p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl flex items-center justify-center">
                                    <span class="text-white font-bold text-lg">
                                        {{ $menu->formato === 'pdf' ? '📄' : '🖼️' }}
                                    </span>
                                </div>
                                <div>
                                    <p class="font-medium text-text-primary">{{ basename($menu->archivo_original) }}</p>
                                    <p class="text-sm text-text-secondary">Formato: {{ strtoupper($menu->formato) }}</p>
                                </div>
                            </div>
                            <div class="flex space-x-3">
                                @if($menu->formato === 'pdf')
                                    <a href="{{ asset('storage/' . $menu->archivo_original) }}" 
                                       target="_blank"
                                       class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                        Ver PDF
                                    </a>
                                @else
                                    <a href="{{ asset('storage/' . $menu->archivo_original) }}" 
                                       target="_blank"
                                       class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                        Ver Imagen
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Contenido Procesado -->
            @if($menu->contexto_gpt)
                <div class="glass-effect rounded-2xl p-6">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 bg-accent-green rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white text-lg">🤖</span>
                        </div>
                        <h3 class="text-xl font-bold text-text-primary">Contenido Procesado por IA</h3>
                    </div>
                    
                    <div class="bg-secondary-dark rounded-xl p-6">
                        <pre class="text-text-primary text-sm whitespace-pre-wrap">{{ $menu->contexto_gpt }}</pre>
                    </div>
                </div>
            @endif

            <!-- Acciones -->
            <div class="glass-effect rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-text-primary">Acciones</h3>
                    <div class="flex space-x-3">
                        <a href="{{ route('admin.menus.edit', $menu) }}" 
                           class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                            Editar Menú
                        </a>
                        <a href="{{ route('admin.menus.index') }}" 
                           class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                            Volver a Lista
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
