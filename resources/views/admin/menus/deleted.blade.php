<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                🗑️ Menús Eliminados
            </h2>
            <div class="flex space-x-3">
                <a href="{{ route('admin.menus.index') }}" class="btn-secondary">
                    ← Volver a Menús
                </a>
                <a href="{{ route('dashboard') }}" class="btn-secondary">
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

            @if(session('error'))
                <div class="mb-8 animate-fade-in-down">
                    <div class="relative overflow-hidden bg-gradient-to-r from-red-900/30 to-rose-900/30 border border-red-500/30 rounded-2xl p-6 shadow-2xl backdrop-blur-sm">
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
                        </div>
                    </div>
                </div>
            @endif

            <!-- Información -->
            <div class="glass-effect rounded-2xl p-6 border border-yellow-500/30">
                <div class="flex items-start">
                    <div class="w-12 h-12 bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-xl flex items-center justify-center mr-4 flex-shrink-0">
                        <span class="text-white text-xl">ℹ️</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-text-primary mb-2">Menús con Baja Lógica</h3>
                        <p class="text-text-secondary">
                            Estos menús fueron eliminados pero sus archivos e imágenes se mantienen en el servidor. 
                            Puedes restaurarlos en cualquier momento. Los menús restaurados volverán a aparecer en la lista principal.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Lista de Menús Eliminados -->
            <div class="glass-effect rounded-2xl p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-r from-red-500 to-red-600 rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white text-lg">🗑️</span>
                        </div>
                        <h3 class="text-xl font-bold text-text-primary">Menús Eliminados ({{ $menus->total() }})</h3>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-accent-gray">
                                <th class="text-left py-3 px-4 text-text-primary font-medium">Menú</th>
                                <th class="text-left py-3 px-4 text-text-primary font-medium">Comercio</th>
                                <th class="text-left py-3 px-4 text-text-primary font-medium">Propietario</th>
                                <th class="text-left py-3 px-4 text-text-primary font-medium">Eliminado</th>
                                <th class="text-left py-3 px-4 text-text-primary font-medium">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($menus as $menu)
                                <tr class="border-b border-accent-gray/50 hover:bg-secondary-dark/50 transition-colors">
                                    <td class="py-4 px-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-10 h-10 bg-gradient-to-r from-gray-500 to-gray-600 rounded-full flex items-center justify-center opacity-60">
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
                                            <p class="text-text-secondary">{{ $menu->deleted_at->format('d/m/Y H:i') }}</p>
                                            <p class="text-xs text-text-secondary">{{ $menu->deleted_at->diffForHumans() }}</p>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <form method="POST" action="{{ route('admin.menus.restore', $menu->id) }}" class="inline">
                                            @csrf
                                            <button type="submit" 
                                                    onclick="return confirm('¿Estás seguro de que quieres restaurar este menú?')"
                                                    class="bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-300 shadow-lg hover:shadow-xl">
                                                🔄 Restaurar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-text-secondary">
                                        <div class="flex flex-col items-center">
                                            <span class="text-4xl mb-4">🗑️</span>
                                            <p class="text-lg">No hay menús eliminados</p>
                                            <p class="text-sm mt-2">Todos los menús están activos</p>
                                        </div>
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

