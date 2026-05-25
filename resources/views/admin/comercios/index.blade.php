<x-app-layout>
    <div class="min-h-screen bg-background">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-10">
                <div>
                    <h1 class="text-3xl font-bold text-text-primary">Gestión de Comercios</h1>
                    <p class="text-text-secondary mt-1">Todos los comercios registrados</p>
                </div>
                <a href="{{ route('dashboard') }}" class="btn-secondary">← Dashboard</a>
            </div>

            <!-- Flash -->
            @if(session('success'))
                <div class="mb-8">
                    <div class="bg-green-900/20 border border-green-500/30 rounded-2xl p-5 flex items-center gap-4">
                        <div class="w-9 h-9 bg-green-500/20 rounded-full flex items-center justify-center flex-shrink-0">✓</div>
                        <p class="text-green-200 font-medium">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            <!-- Estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="metric-card">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-accent-green/10 rounded-xl flex items-center justify-center flex-shrink-0">
                            <span class="text-xl">🏪</span>
                        </div>
                        <div>
                            <p class="text-text-secondary text-sm">Total Comercios</p>
                            <p class="text-2xl font-bold text-text-primary">{{ $comercios->total() }}</p>
                        </div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-accent-blue/10 rounded-xl flex items-center justify-center flex-shrink-0">
                            <span class="text-xl">👥</span>
                        </div>
                        <div>
                            <p class="text-text-secondary text-sm">Propietarios</p>
                            <p class="text-2xl font-bold text-text-primary">{{ $comercios->unique('user_id')->count() }}</p>
                        </div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-accent-blue/10 rounded-xl flex items-center justify-center flex-shrink-0">
                            <span class="text-xl">📋</span>
                        </div>
                        <div>
                            <p class="text-text-secondary text-sm">Total Menús</p>
                            <p class="text-2xl font-bold text-text-primary">{{ $comercios->sum('menus_count') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Comercios -->
            <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-9 h-9 bg-accent-green/10 rounded-lg flex items-center justify-center">
                        <span>🏪</span>
                    </div>
                    <h3 class="font-bold text-text-primary">Lista de Comercios</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-accent-gray">
                                <th class="text-left py-3 px-4 text-text-secondary text-xs font-medium uppercase tracking-wide">Comercio</th>
                                <th class="text-left py-3 px-4 text-text-secondary text-xs font-medium uppercase tracking-wide">Propietario</th>
                                <th class="text-left py-3 px-4 text-text-secondary text-xs font-medium uppercase tracking-wide">Menús</th>
                                <th class="text-left py-3 px-4 text-text-secondary text-xs font-medium uppercase tracking-wide">Creado</th>
                                <th class="text-left py-3 px-4 text-text-secondary text-xs font-medium uppercase tracking-wide">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($comercios as $comercio)
                                <tr class="border-b border-accent-gray/50 hover:bg-primary-dark transition-colors">
                                    <td class="py-4 px-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 bg-accent-green/20 rounded-xl flex items-center justify-center text-accent-green font-bold flex-shrink-0">
                                                {{ strtoupper(substr($comercio->nombre, 0, 1)) }}
                                            </div>
                                            <div>
                                                <p class="font-medium text-text-primary">{{ $comercio->nombre }}</p>
                                                @if($comercio->direccion)
                                                    <p class="text-xs text-text-secondary">{{ Str::limit($comercio->direccion, 30) }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <p class="font-medium text-text-primary">{{ $comercio->user->name }}</p>
                                        <p class="text-xs text-text-secondary">{{ $comercio->user->email }}</p>
                                    </td>
                                    <td class="py-4 px-4">
                                        <span class="px-2 py-0.5 text-xs rounded-full bg-accent-gray/50 text-text-secondary border border-accent-gray font-medium">
                                            {{ $comercio->menus_count }} menú(s)
                                        </span>
                                    </td>
                                    <td class="py-4 px-4">
                                        <p class="text-text-primary text-sm">{{ $comercio->created_at->format('d/m/Y') }}</p>
                                        <p class="text-xs text-text-secondary">{{ $comercio->created_at->diffForHumans() }}</p>
                                    </td>
                                    <td class="py-4 px-4">
                                        <a href="{{ route('admin.comercios.show', $comercio) }}"
                                           class="px-3 py-1.5 bg-accent-blue/10 hover:bg-accent-blue/20 text-accent-blue border border-accent-blue/20 rounded-lg text-xs font-medium transition-colors">
                                            Ver
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center">
                                        <div class="w-14 h-14 bg-accent-gray/30 rounded-full flex items-center justify-center mx-auto mb-3">
                                            <span class="text-2xl">🏪</span>
                                        </div>
                                        <p class="text-text-secondary text-sm">No hay comercios registrados.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($comercios->hasPages())
                    <div class="mt-6">
                        {{ $comercios->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>

