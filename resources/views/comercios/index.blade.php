<x-app-layout>
<div class="min-h-screen bg-background">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Comercios</h1>
            <p class="text-text-secondary text-sm mt-0.5">
                {{ $comercios->count() }} {{ $comercios->count() === 1 ? 'comercio' : 'comercios' }}
            </p>
        </div>
        @if(auth()->user()->isSuperAdmin() || $comercios->count() === 0)
            <a href="{{ route('comercios.create') }}" class="btn-primary">
                + Nuevo comercio
            </a>
        @endif
    </div>

    <!-- Filtro por usuario activo -->
    @if(isset($filterUser) && $filterUser)
        <div class="mb-6 flex items-center gap-3 bg-accent-blue/8 border border-accent-blue/20 rounded-xl px-4 py-3">
            <div class="w-8 h-8 rounded-full bg-accent-blue/20 flex items-center justify-center text-accent-blue font-semibold text-sm flex-shrink-0">
                {{ strtoupper(substr($filterUser->name, 0, 1)) }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm text-text-primary font-medium">Mostrando comercios de <span class="text-accent-blue">{{ $filterUser->name }}</span></p>
                <p class="text-xs text-text-secondary">{{ $filterUser->email }}</p>
            </div>
            <a href="{{ route('comercios.index') }}"
               class="text-xs text-text-secondary hover:text-text-primary border border-accent-gray rounded-lg px-3 py-1.5 transition-colors flex-shrink-0">
                Ver todos
            </a>
        </div>
    @endif

    <!-- Flash messages -->
    <x-flash />
    @if($comercios->count() > 0)

        <!-- Buscador (solo si hay más de un comercio que filtrar) -->
        @if($comercios->count() > 1)
        <div class="mb-6">
            <div class="relative max-w-sm">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-secondary pointer-events-none"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
                <input id="search-comercios"
                       type="text"
                       placeholder="Buscar comercio..."
                       class="w-full bg-secondary-dark border border-accent-gray rounded-xl pl-9 pr-4 py-2.5
                              text-sm text-text-primary placeholder-text-secondary
                              focus:outline-none focus:ring-2 focus:ring-accent-blue/40 focus:border-accent-blue/60
                              transition-colors">
            </div>
        </div>
        @endif

        <!-- Grid de cards -->
        <div id="comercios-grid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            @foreach($comercios as $comercio)
            <div class="comercio-card bg-secondary-dark border border-accent-gray rounded-2xl overflow-hidden
                        hover:border-accent-blue/30 transition-colors duration-200 flex flex-col"
                 data-name="{{ strtolower($comercio->nombre) }}"
                 data-owner="{{ strtolower($comercio->user->name ?? '') }}">

                <!-- Imagen -->
                <div class="relative h-40 bg-primary-dark flex-shrink-0 overflow-hidden">
                    @if($comercio->logo_url)
                        <img src="{{ asset('storage/' . $comercio->logo_url) }}"
                             alt="{{ $comercio->nombre }}"
                             class="w-full h-full object-cover hover:scale-105 transition-transform duration-500">
                    @else
                        <img src="{{ asset('images/logo_empresa_default.png') }}"
                             alt="{{ $comercio->nombre }}"
                             class="w-full h-full object-cover opacity-40">
                    @endif

                    <!-- Chip dueño (solo super admin) -->
                    @if(auth()->user()->isSuperAdmin() && $comercio->user)
                        <div class="absolute bottom-2 left-3">
                            <span class="inline-flex items-center gap-1.5 bg-black/60 backdrop-blur-sm
                                         text-text-secondary text-xs px-2.5 py-1 rounded-full">
                                <span class="w-4 h-4 rounded-full bg-accent-blue/20 text-accent-blue
                                             flex items-center justify-center text-[9px] font-bold flex-shrink-0">
                                    {{ strtoupper(substr($comercio->user->name, 0, 1)) }}
                                </span>
                                {{ $comercio->user->name }}
                            </span>
                        </div>
                    @endif
                </div>

                <!-- Contenido -->
                <div class="p-5 flex flex-col flex-1">
                    <!-- Nombre y meta -->
                    <div class="mb-4">
                        <h3 class="text-base font-semibold text-text-primary mb-1 truncate">
                            {{ $comercio->nombre }}
                        </h3>
                        <div class="flex items-center gap-3 text-xs text-text-secondary">
                            <span class="flex items-center gap-1">
                                <span class="text-accent-blue font-semibold">{{ $comercio->menus->count() }}</span>
                                menú(s)
                            </span>
                            <span class="text-accent-gray">·</span>
                            <span class="flex items-center gap-1">
                                <span class="text-accent-green font-semibold">{{ $comercio->chatConversations->count() }}</span>
                                conv.
                            </span>
                            @if($comercio->direccion)
                                <span class="text-accent-gray">·</span>
                                <span class="truncate max-w-[100px]">{{ $comercio->direccion }}</span>
                            @endif
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="mt-auto flex gap-2">
                        @php($hasMenus = $comercio->menus->count() > 0)
                        <a href="{{ route('menus.index', $comercio) }}"
                           class="btn-primary flex-1 text-center text-sm py-2">
                            {{ $hasMenus ? '📋 Ver menús' : '➕ Crear menú' }}
                        </a>
                        <a href="{{ route('comercios.edit', $comercio) }}"
                           class="btn-secondary px-4 py-2 text-sm"
                           title="Editar comercio">
                            ✏️
                        </a>
                        @if(auth()->user()->isSuperAdmin())
                            <form method="POST" action="{{ route('admin.comercios.destroy', $comercio) }}"
                                  onsubmit="event.preventDefault(); const f=this; showConfirmWithLoader('¿Eliminar {{ addslashes($comercio->nombre) }}?', r=>{ if(r) f.submit(); }, 'Eliminando...');">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="px-3 py-2 rounded-xl text-sm border border-accent-gray
                                               text-text-secondary hover:text-danger hover:border-danger/40
                                               hover:bg-danger/10 transition-all"
                                        title="Eliminar">🗑️</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('comercios.destroy', $comercio) }}"
                                  onsubmit="event.preventDefault(); const f=this; showConfirmWithLoader('¿Eliminar {{ addslashes($comercio->nombre) }}?', r=>{ if(r) f.submit(); }, 'Eliminando...');">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="px-3 py-2 rounded-xl text-sm border border-accent-gray
                                               text-text-secondary hover:text-danger hover:border-danger/40
                                               hover:bg-danger/10 transition-all"
                                        title="Eliminar">🗑️</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Sin resultados de búsqueda -->
        <div id="no-results" class="hidden text-center py-16">
            <p class="text-text-secondary text-sm">No se encontraron comercios que coincidan con la búsqueda.</p>
        </div>

    @else
        <!-- Estado vacío -->
        <div class="text-center py-20">
            <div class="w-16 h-16 mx-auto mb-4 bg-accent-gray/20 rounded-full flex items-center justify-center text-3xl">🏪</div>
            <h3 class="text-lg font-semibold text-text-primary mb-2">Sin comercios aún</h3>
            <p class="text-text-secondary text-sm mb-6">Creá tu primer comercio para gestionar menús digitales.</p>
            <a href="{{ route('comercios.create') }}" class="btn-primary">+ Crear comercio</a>
        </div>
    @endif

</div>
</div>

<script>
(function () {
    const input = document.getElementById('search-comercios');
    if (!input) return;

    input.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        const cards = document.querySelectorAll('.comercio-card');
        let visible = 0;

        cards.forEach(card => {
            const name  = card.dataset.name  || '';
            const owner = card.dataset.owner || '';
            const match = !q || name.includes(q) || owner.includes(q);
            card.style.display = match ? '' : 'none';
            if (match) visible++;
        });

        document.getElementById('no-results').classList.toggle('hidden', visible > 0);
    });
})();
</script>
</x-app-layout>
