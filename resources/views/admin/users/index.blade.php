<x-app-layout>
    <div class="min-h-screen bg-background">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-10">
                <div>
                    <h1 class="text-3xl font-bold text-text-primary">Gestión de Usuarios</h1>
                    <p class="text-text-secondary mt-1">Administrá usuarios y roles del sistema</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.users.create') }}" class="btn-primary">
                        + Crear Usuario
                    </a>
                    <a href="{{ route('dashboard') }}" class="btn-secondary">
                        ← Dashboard
                    </a>
                </div>
            </div>

            <!-- Flash Messages -->
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
                        <div class="w-9 h-9 bg-red-500/20 rounded-full flex items-center justify-center flex-shrink-0">⚠️</div>
                        <p class="text-red-200 font-medium">{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            <!-- Métricas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
                <div class="metric-card">
                    <div class="flex items-center gap-4">
                        <div class="w-11 h-11 bg-accent-blue/10 rounded-xl flex items-center justify-center flex-shrink-0">
                            <span class="text-xl">👥</span>
                        </div>
                        <div>
                            <p class="text-text-secondary text-xs mb-1">Total Usuarios</p>
                            <p class="text-2xl font-bold text-text-primary">{{ $users->total() }}</p>
                        </div>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="flex items-center gap-4">
                        <div class="w-11 h-11 bg-accent-blue/10 rounded-xl flex items-center justify-center flex-shrink-0">
                            <span class="text-xl">🛡️</span>
                        </div>
                        <div>
                            <p class="text-text-secondary text-xs mb-1">Super Admins</p>
                            <p class="text-2xl font-bold text-text-primary">{{ $users->where('role', 'super_admin')->count() }}</p>
                        </div>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="flex items-center gap-4">
                        <div class="w-11 h-11 bg-accent-green/10 rounded-xl flex items-center justify-center flex-shrink-0">
                            <span class="text-xl">👤</span>
                        </div>
                        <div>
                            <p class="text-text-secondary text-xs mb-1">Usuarios Comunes</p>
                            <p class="text-2xl font-bold text-text-primary">{{ $users->where('role', 'user')->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtro activo -->
            @if(isset($filterUser) && $filterUser)
                <div class="mb-5 flex items-center gap-3 bg-accent-blue/8 border border-accent-blue/20 rounded-xl px-4 py-3">
                    <div class="w-8 h-8 rounded-full bg-accent-blue/20 flex items-center justify-center text-accent-blue font-semibold text-sm flex-shrink-0">
                        {{ strtoupper(substr($filterUser->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-text-primary font-medium">Mostrando: <span class="text-accent-blue">{{ $filterUser->name }}</span></p>
                        <p class="text-xs text-text-secondary">{{ $filterUser->email }}</p>
                    </div>
                    <a href="{{ route('admin.users.index') }}"
                       class="text-xs text-text-secondary hover:text-text-primary border border-accent-gray rounded-lg px-3 py-1.5 transition-colors flex-shrink-0">
                        Ver todos
                    </a>
                </div>
            @endif

            <!-- Buscador -->
            <div class="mb-5">
                <div class="relative max-w-sm">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-secondary pointer-events-none"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                    </svg>
                    <input id="search-users" type="text" placeholder="Buscar por nombre o email..."
                           class="w-full bg-secondary-dark border border-accent-gray rounded-xl pl-9 pr-4 py-2.5
                                  text-sm text-text-primary placeholder-text-secondary
                                  focus:outline-none focus:ring-2 focus:ring-accent-blue/40 focus:border-accent-blue/60
                                  transition-colors">
                </div>
            </div>

            <!-- Tabla -->
            <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">

                <!-- Vista Desktop -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-accent-gray">
                                <th class="text-left py-3 px-4 text-text-secondary text-sm font-medium">Usuario</th>
                                <th class="text-left py-3 px-4 text-text-secondary text-sm font-medium">Email</th>
                                <th class="text-left py-3 px-4 text-text-secondary text-sm font-medium hidden sm:table-cell">WhatsApp</th>
                                <th class="text-left py-3 px-4 text-text-secondary text-sm font-medium">Rol</th>
                                <th class="text-left py-3 px-4 text-text-secondary text-sm font-medium hidden md:table-cell">Comercios</th>
                                <th class="text-left py-3 px-4 text-text-secondary text-sm font-medium hidden lg:table-cell">Registrado</th>
                                <th class="text-left py-3 px-4 text-text-secondary text-sm font-medium">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                <tr class="user-row border-b border-accent-gray/40 hover:bg-primary-dark transition-colors"
                                    data-name="{{ strtolower($user->name) }}"
                                    data-email="{{ strtolower($user->email) }}">
                                    <td class="py-4 px-4">
                                        <a href="{{ route('comercios.index') }}?user_id={{ $user->id }}"
                                           class="flex items-center gap-3 group/name">
                                            <div class="w-9 h-9 bg-accent-blue/20 rounded-full flex items-center justify-center text-accent-blue font-semibold text-sm flex-shrink-0">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </div>
                                            <p class="font-medium text-text-primary group-hover/name:text-accent-blue transition-colors">{{ $user->name }}</p>
                                        </a>
                                    </td>
                                    <td class="py-4 px-4">
                                        <p class="text-text-secondary text-sm break-words">{{ $user->email }}</p>
                                    </td>
                                    <td class="py-4 px-4 hidden sm:table-cell">
                                        <p class="text-text-secondary text-sm">{{ $user->whatsapp ?? '—' }}</p>
                                    </td>
                                    <td class="py-4 px-4">
                                        <span class="px-2.5 py-1 text-xs rounded-full font-medium
                                            {{ $user->role === 'super_admin'
                                                ? 'bg-accent-blue/20 text-accent-blue'
                                                : 'bg-accent-gray/50 text-text-secondary' }}">
                                            {{ $user->role === 'super_admin' ? 'Super Admin' : 'Usuario' }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 hidden md:table-cell">
                                        <a href="{{ route('comercios.index') }}?user_id={{ $user->id }}"
                                           class="text-text-secondary text-sm hover:text-accent-blue transition-colors">
                                            {{ $user->comercios->count() }} comercio(s)
                                        </a>
                                    </td>
                                    <td class="py-4 px-4 hidden lg:table-cell">
                                        <p class="text-text-secondary text-sm">{{ $user->created_at->format('d/m/Y') }}</p>
                                        <p class="text-text-secondary text-xs">{{ $user->created_at->diffForHumans() }}</p>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('admin.users.edit', $user) }}"
                                               class="px-3 py-1.5 bg-accent-blue/10 hover:bg-accent-blue/20 text-accent-blue border border-accent-blue/20 rounded-lg text-xs font-medium transition-colors">
                                                Editar
                                            </a>
                                            @if($user->id !== auth()->id())
                                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                                      onsubmit="event.preventDefault(); const form = this; showConfirmWithLoader('¿Eliminar a {{ addslashes($user->name) }}? Esta acción no se puede deshacer.', function(result) { if(result) { form.submit(); } }, 'Eliminando usuario...');"
                                                      class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="px-3 py-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20 rounded-lg text-xs font-medium transition-colors">
                                                        Eliminar
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-10 text-center text-text-secondary">
                                        No hay usuarios registrados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Vista Móvil -->
                <div class="md:hidden space-y-3">
                    @forelse($users as $user)
                        <div class="user-row bg-primary-dark rounded-xl p-4 border border-accent-gray/40 hover:border-accent-blue/30 transition-colors"
                             data-name="{{ strtolower($user->name) }}"
                             data-email="{{ strtolower($user->email) }}">
                            <a href="{{ route('comercios.index') }}?user_id={{ $user->id }}"
                               class="flex items-center gap-3 mb-3 group/name">
                                <div class="w-10 h-10 bg-accent-blue/20 rounded-full flex items-center justify-center text-accent-blue font-semibold flex-shrink-0">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-text-primary truncate group-hover/name:text-accent-blue transition-colors">{{ $user->name }}</p>
                                    <span class="inline-block mt-0.5 px-2 py-0.5 text-xs rounded-full font-medium
                                        {{ $user->role === 'super_admin'
                                            ? 'bg-accent-blue/20 text-accent-blue'
                                            : 'bg-accent-gray/50 text-text-secondary' }}">
                                        {{ $user->role === 'super_admin' ? 'Super Admin' : 'Usuario' }}
                                    </span>
                                </div>
                            </a>
                            <div class="space-y-1.5 mb-4 text-sm text-text-secondary">
                                <p>📧 {{ $user->email }}</p>
                                @if($user->whatsapp)<p>💬 {{ $user->whatsapp }}</p>@endif
                                <p>🏪 {{ $user->comercios->count() }} comercio(s)</p>
                                <p>📅 {{ $user->created_at->format('d/m/Y') }}</p>
                            </div>
                            <div class="flex gap-2 pt-3 border-t border-accent-gray/30">
                                <a href="{{ route('comercios.index') }}?user_id={{ $user->id }}"
                                   class="flex-1 py-2 text-center bg-accent-blue/10 hover:bg-accent-blue/20 text-accent-blue border border-accent-blue/20 rounded-lg text-sm font-medium transition-colors">
                                    Ver comercios
                                </a>
                                <a href="{{ route('admin.users.edit', $user) }}"
                                   class="flex-1 py-2 text-center bg-accent-gray/20 hover:bg-accent-gray/40 text-text-secondary border border-accent-gray/30 rounded-lg text-sm font-medium transition-colors">
                                    Editar
                                </a>
                                @if($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                          onsubmit="event.preventDefault(); const form = this; showConfirmWithLoader('¿Eliminar a {{ addslashes($user->name) }}?', function(result) { if(result) { form.submit(); } }, 'Eliminando usuario...');"
                                          class="flex-1">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="w-full py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20 rounded-lg text-sm font-medium transition-colors">
                                            Eliminar
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-text-secondary py-8">No hay usuarios registrados.</p>
                    @endforelse
                </div>

                <!-- Paginación -->
                @if($users->hasPages())
                    <div class="mt-6">
                        {{ $users->links() }}
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>

<script>
(function () {
    const input = document.getElementById('search-users');
    if (!input) return;
    input.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        document.querySelectorAll('.user-row').forEach(row => {
            const match = !q ||
                (row.dataset.name  || '').includes(q) ||
                (row.dataset.email || '').includes(q);
            row.style.display = match ? '' : 'none';
        });
    });
})();
</script>

