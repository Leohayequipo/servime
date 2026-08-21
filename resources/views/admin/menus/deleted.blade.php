<x-app-layout>
    <div class="min-h-screen bg-background">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-10">
                <div>
                    <h1 class="text-3xl font-bold text-text-primary">Menús Eliminados</h1>
                    <p class="text-text-secondary mt-1">Menús con baja lógica — se pueden restaurar</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.menus.index') }}" class="btn-secondary">← Menús</a>
                </div>
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

            @if(session('error'))
                <div class="mb-8">
                    <div class="bg-red-900/20 border border-red-500/30 rounded-2xl p-5 flex items-center gap-4">
                        <div class="w-9 h-9 bg-red-500/20 rounded-full flex items-center justify-center flex-shrink-0">✗</div>
                        <p class="text-red-200 font-medium">{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            <!-- Info -->
            <div class="bg-warning/10 border border-warning/30 rounded-2xl p-5 mb-8 flex items-start gap-4">
                <div class="w-9 h-9 bg-warning/20 rounded-lg flex items-center justify-center flex-shrink-0">
                    <span>ℹ️</span>
                </div>
                <div>
                    <p class="text-text-primary font-medium mb-1">Menús con Baja Lógica</p>
                    <p class="text-text-secondary text-sm">Estos menús fueron eliminados pero sus archivos e imágenes se mantienen en el servidor. Puedes restaurarlos en cualquier momento.</p>
                </div>
            </div>

            <!-- Lista -->
            <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-9 h-9 bg-red-500/20 rounded-lg flex items-center justify-center">
                        <span>🗑️</span>
                    </div>
                    <h3 class="font-bold text-text-primary">Menús Eliminados ({{ $menus->total() }})</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-accent-gray">
                                <th class="text-left py-3 px-4 text-text-secondary text-xs font-medium uppercase tracking-wide">Menú</th>
                                <th class="text-left py-3 px-4 text-text-secondary text-xs font-medium uppercase tracking-wide">Comercio</th>
                                <th class="text-left py-3 px-4 text-text-secondary text-xs font-medium uppercase tracking-wide">Propietario</th>
                                <th class="text-left py-3 px-4 text-text-secondary text-xs font-medium uppercase tracking-wide">Eliminado</th>
                                <th class="text-left py-3 px-4 text-text-secondary text-xs font-medium uppercase tracking-wide">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($menus as $menu)
                                <tr class="border-b border-accent-gray/50 hover:bg-primary-dark transition-colors">
                                    <td class="py-4 px-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 bg-accent-gray/30 rounded-lg flex items-center justify-center flex-shrink-0 opacity-60">
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
                                        <p class="text-text-primary text-sm">{{ $menu->deleted_at->format('d/m/Y H:i') }}</p>
                                        <p class="text-xs text-text-secondary">{{ $menu->deleted_at->diffForHumans() }}</p>
                                    </td>
                                    <td class="py-4 px-4">
                                        <form method="POST" action="{{ route('admin.menus.restore', $menu->id) }}" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    onclick="return confirm('¿Restaurar este menú?')"
                                                    class="px-3 py-1.5 bg-accent-green/10 hover:bg-accent-green/20 text-accent-green border border-accent-green/30 rounded-lg text-xs font-medium transition-colors">
                                                🔄 Restaurar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center">
                                        <div class="w-14 h-14 bg-accent-gray/30 rounded-full flex items-center justify-center mx-auto mb-3">
                                            <span class="text-2xl">🗑️</span>
                                        </div>
                                        <p class="text-text-secondary text-sm">No hay menús eliminados.</p>
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

