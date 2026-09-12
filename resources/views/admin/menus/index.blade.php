<x-app-layout>
    <div class="min-h-screen bg-background">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-10">
                <div>
                    <h1 class="text-3xl font-bold text-text-primary">Gestión de Menús</h1>
                    <p class="text-text-secondary mt-1">Todos los menús del sistema</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.menus.deleted') }}" class="btn-secondary flex items-center gap-2">
                        <span>🗑️</span>
                        <span>Eliminados</span>
                    </a>
                    <a href="{{ route('dashboard') }}" class="btn-secondary">← Dashboard</a>
                </div>
            </div>

            <!-- Flash -->
            <x-flash />

            <!-- Estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="metric-card">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-accent-blue/10 rounded-xl flex items-center justify-center flex-shrink-0">
                            <span class="text-xl">📋</span>
                        </div>
                        <div>
                            <p class="text-text-secondary text-sm">Total Menús</p>
                            <p class="text-2xl font-bold text-text-primary">{{ $menus->total() }}</p>
                        </div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-accent-green/10 rounded-xl flex items-center justify-center flex-shrink-0">
                            <span class="text-xl">🏪</span>
                        </div>
                        <div>
                            <p class="text-text-secondary text-sm">Comercios</p>
                            <p class="text-2xl font-bold text-text-primary">{{ $menus->unique('comercio_id')->count() }}</p>
                        </div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-accent-blue/10 rounded-xl flex items-center justify-center flex-shrink-0">
                            <span class="text-xl">⭐</span>
                        </div>
                        <div>
                            <p class="text-text-secondary text-sm">Menús Activos</p>
                            <p class="text-2xl font-bold text-text-primary">{{ $menus->where('activo', true)->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Menús -->
            <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-9 h-9 bg-accent-blue/10 rounded-lg flex items-center justify-center">
                        <span>📋</span>
                    </div>
                    <h3 class="font-bold text-text-primary">Lista de Menús</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-accent-gray">
                                <th class="text-left py-3 px-4 text-text-secondary text-xs font-medium uppercase tracking-wide">Menú</th>
                                <th class="text-left py-3 px-4 text-text-secondary text-xs font-medium uppercase tracking-wide">Comercio</th>
                                <th class="text-left py-3 px-4 text-text-secondary text-xs font-medium uppercase tracking-wide">Propietario</th>
                                <th class="text-left py-3 px-4 text-text-secondary text-xs font-medium uppercase tracking-wide">Estado</th>
                                <th class="text-left py-3 px-4 text-text-secondary text-xs font-medium uppercase tracking-wide">Creado</th>
                                <th class="text-left py-3 px-4 text-text-secondary text-xs font-medium uppercase tracking-wide">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($menus as $menu)
                                <tr class="border-b border-accent-gray/50 hover:bg-primary-dark transition-colors">
                                    <td class="py-4 px-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 bg-accent-blue/10 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <span class="text-sm">📋</span>
                                            </div>
                                            <div>
                                                <p class="font-medium text-text-primary">{{ $menu->nombre ?: 'Sin nombre' }}</p>
                                                <p class="text-xs text-text-secondary">{{ strtoupper($menu->formato) }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <p class="font-medium text-text-primary">{{ $menu->comercio->nombre }}</p>
                                        @if($menu->comercio->direccion)
                                            <p class="text-xs text-text-secondary">{{ Str::limit($menu->comercio->direccion, 30) }}</p>
                                        @endif
                                    </td>
                                    <td class="py-4 px-4">
                                        <p class="font-medium text-text-primary">{{ $menu->comercio->user->name }}</p>
                                        <p class="text-xs text-text-secondary">{{ $menu->comercio->user->email }}</p>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="flex flex-col gap-1">
                                            <span class="px-2 py-0.5 text-xs rounded-full font-medium w-fit
                                                {{ $menu->procesado ? 'bg-accent-green/20 text-accent-green border border-accent-green/30' : 'bg-warning/20 text-warning border border-warning/30' }}">
                                                {{ $menu->procesado ? 'Procesado' : 'Pendiente' }}
                                            </span>
                                            @if($menu->activo)
                                                <span class="px-2 py-0.5 text-xs rounded-full font-medium w-fit bg-accent-green/20 text-accent-green border border-accent-green/30">
                                                    Activo
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <p class="text-text-primary text-sm">{{ $menu->created_at->format('d/m/Y') }}</p>
                                        <p class="text-xs text-text-secondary">{{ $menu->created_at->diffForHumans() }}</p>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('admin.menus.show', $menu) }}"
                                               class="px-3 py-1.5 bg-accent-blue/10 hover:bg-accent-blue/20 text-accent-blue border border-accent-blue/20 rounded-lg text-xs font-medium transition-colors">
                                                Ver
                                            </a>
                                            <a href="{{ route('admin.menus.edit', $menu) }}"
                                               class="px-3 py-1.5 bg-accent-gray/30 hover:bg-accent-gray/50 text-text-primary border border-accent-gray rounded-lg text-xs font-medium transition-colors">
                                                Editar
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-12 text-center">
                                        <div class="w-14 h-14 bg-accent-gray/30 rounded-full flex items-center justify-center mx-auto mb-3">
                                            <span class="text-2xl">📋</span>
                                        </div>
                                        <p class="text-text-secondary text-sm">No hay menús registrados.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($menus->hasPages())
                    <div class="mt-6">
                        {{ $menus->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>

