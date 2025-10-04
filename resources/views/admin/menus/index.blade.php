<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                📋 Gestión de Menús
            </h2>
            <div class="flex space-x-3">
                <a href="{{ route('admin.menus.create') }}" class="btn-primary">
                    ➕ Crear Menú
                </a>
                <a href="{{ route('admin.dashboard') }}" class="btn-secondary">
                    {{ __('Volver al Dashboard') }}
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

            <!-- Estadísticas Rápidas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="glass-effect rounded-2xl p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white text-xl">📋</span>
                        </div>
                        <div>
                            <p class="text-text-secondary text-sm">Total Menús</p>
                            <p class="text-2xl font-bold text-text-primary">{{ $menus->total() }}</p>
                        </div>
                    </div>
                </div>

                <div class="glass-effect rounded-2xl p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white text-xl">🏪</span>
                        </div>
                        <div>
                            <p class="text-text-secondary text-sm">Comercios</p>
                            <p class="text-2xl font-bold text-text-primary">{{ $menus->unique('comercio_id')->count() }}</p>
                        </div>
                    </div>
                </div>

                <div class="glass-effect rounded-2xl p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white text-xl">⭐</span>
                        </div>
                        <div>
                            <p class="text-text-secondary text-sm">Menús Activos</p>
                            <p class="text-2xl font-bold text-text-primary">{{ $menus->where('activo', true)->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Menús -->
            <div class="glass-effect rounded-2xl p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-accent-green rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white text-lg">📋</span>
                        </div>
                        <h3 class="text-xl font-bold text-text-primary">Lista de Menús</h3>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-accent-gray">
                                <th class="text-left py-3 px-4 text-text-primary font-medium">Menú</th>
                                <th class="text-left py-3 px-4 text-text-primary font-medium">Comercio</th>
                                <th class="text-left py-3 px-4 text-text-primary font-medium">Propietario</th>
                                <th class="text-left py-3 px-4 text-text-primary font-medium">Estado</th>
                                <th class="text-left py-3 px-4 text-text-primary font-medium">Creado</th>
                                <th class="text-left py-3 px-4 text-text-primary font-medium">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($menus as $menu)
                                <tr class="border-b border-accent-gray/50 hover:bg-secondary-dark/50 transition-colors">
                                    <td class="py-4 px-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-green-600 rounded-full flex items-center justify-center">
                                                <span class="text-white font-bold">📋</span>
                                            </div>
                                            <div>
                                                <p class="font-medium text-text-primary">{{ $menu->nombre ?: 'Sin nombre' }}</p>
                                                <p class="text-xs text-text-secondary">{{ strtoupper($menu->formato) }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="space-y-1">
                                            <p class="font-medium text-text-primary">{{ $menu->comercio->nombre }}</p>
                                            @if($menu->comercio->direccion)
                                                <p class="text-sm text-text-secondary">{{ Str::limit($menu->comercio->direccion, 30) }}</p>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="space-y-1">
                                            <p class="font-medium text-text-primary">{{ $menu->comercio->user->name }}</p>
                                            <p class="text-sm text-text-secondary">{{ $menu->comercio->user->email }}</p>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="space-y-1">
                                            <span class="px-2 py-1 text-xs rounded-full {{ $menu->procesado ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                {{ $menu->procesado ? 'Procesado' : 'Pendiente' }}
                                            </span>
                                            @if($menu->activo)
                                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                                    Activo
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <p class="text-text-secondary">{{ $menu->created_at->format('d/m/Y') }}</p>
                                        <p class="text-xs text-text-secondary">{{ $menu->created_at->diffForHumans() }}</p>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="flex items-center space-x-2">
                                            <a href="{{ route('admin.menus.show', $menu) }}" 
                                               class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded-lg text-sm font-medium transition-colors">
                                                Ver
                                            </a>
                                            <a href="{{ route('admin.menus.edit', $menu) }}" 
                                               class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded-lg text-sm font-medium transition-colors">
                                                Editar
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-text-secondary">
                                        No hay menús registrados
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                @if($menus->hasPages())
                    <div class="mt-6">
                        {{ $menus->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
