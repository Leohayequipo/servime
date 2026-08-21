<x-app-layout>
    <div class="min-h-screen bg-background">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-10">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-accent-green/20 rounded-2xl flex items-center justify-center text-accent-green text-2xl font-bold flex-shrink-0">
                        {{ strtoupper(substr($comercio->nombre, 0, 1)) }}
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-text-primary">{{ $comercio->nombre }}</h1>
                        <p class="text-text-secondary mt-0.5">{{ $comercio->user->name }}</p>
                    </div>
                </div>
                <a href="{{ route('admin.comercios.index') }}" class="btn-secondary">
                    ← Comercios
                </a>
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

            <!-- Info del comercio -->
            <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6 mb-6">
                <p class="text-text-secondary text-xs font-medium uppercase tracking-wide mb-5">Información</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <p class="text-text-secondary text-xs mb-1">Nombre</p>
                            <p class="text-text-primary font-medium">{{ $comercio->nombre }}</p>
                        </div>
                        <div>
                            <p class="text-text-secondary text-xs mb-1">Propietario</p>
                            <p class="text-text-primary font-medium">{{ $comercio->user->name }} <span class="text-text-secondary font-normal text-sm">· {{ $comercio->user->email }}</span></p>
                        </div>
                        @if($comercio->descripcion)
                            <div>
                                <p class="text-text-secondary text-xs mb-1">Descripción</p>
                                <p class="text-text-primary font-medium">{{ $comercio->descripcion }}</p>
                            </div>
                        @endif
                    </div>
                    <div class="space-y-4">
                        <div>
                            <p class="text-text-secondary text-xs mb-1">Creado</p>
                            <p class="text-text-primary font-medium">{{ $comercio->created_at->format('d/m/Y H:i') }}</p>
                            <p class="text-text-secondary text-xs">{{ $comercio->created_at->diffForHumans() }}</p>
                        </div>
                        @if($comercio->direccion)
                            <div>
                                <p class="text-text-secondary text-xs mb-1">Dirección</p>
                                <p class="text-text-primary font-medium">{{ $comercio->direccion }}</p>
                            </div>
                        @endif
                        @if($comercio->horario)
                            <div>
                                <p class="text-text-secondary text-xs mb-1">Horario</p>
                                <p class="text-text-primary font-medium">{{ $comercio->horario }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Menús -->
            <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6 mb-6">
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 bg-accent-blue/10 rounded-lg flex items-center justify-center">
                            <span>📋</span>
                        </div>
                        <h3 class="font-bold text-text-primary">Menús</h3>
                    </div>
                    <span class="px-2.5 py-0.5 text-xs rounded-full bg-accent-gray/50 text-text-secondary font-medium">
                        {{ $comercio->menus->count() }} menú(s)
                    </span>
                </div>

                @if($comercio->menus->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($comercio->menus as $menu)
                            <div class="bg-primary-dark border border-accent-gray/50 rounded-xl p-5 hover:border-accent-blue/30 transition-colors">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="w-10 h-10 bg-accent-blue/10 rounded-xl flex items-center justify-center flex-shrink-0">
                                        <span>📋</span>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-text-primary">{{ $menu->nombre ?: 'Sin nombre' }}</h4>
                                        <p class="text-text-secondary text-xs">{{ $menu->created_at->format('d/m/Y') }}</p>
                                    </div>
                                </div>

                                <div class="space-y-2 mb-4">
                                    <div class="flex items-center justify-between">
                                        <span class="text-text-secondary text-xs">Formato</span>
                                        <span class="px-2 py-0.5 text-xs rounded-full
                                            {{ $menu->formato === 'pdf' ? 'bg-red-500/20 text-red-300 border border-red-500/30' : 'bg-accent-blue/10 text-accent-blue border border-accent-blue/20' }}">
                                            {{ strtoupper($menu->formato) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-text-secondary text-xs">Procesado</span>
                                        <span class="px-2 py-0.5 text-xs rounded-full
                                            {{ $menu->procesado ? 'bg-accent-green/20 text-accent-green border border-accent-green/30' : 'bg-warning/20 text-warning border border-warning/30' }}">
                                            {{ $menu->procesado ? 'Sí' : 'Pendiente' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-text-secondary text-xs">Activo</span>
                                        <span class="px-2 py-0.5 text-xs rounded-full
                                            {{ $menu->activo ? 'bg-accent-green/20 text-accent-green border border-accent-green/30' : 'bg-accent-gray/50 text-text-secondary border border-accent-gray' }}">
                                            {{ $menu->activo ? 'Sí' : 'No' }}
                                        </span>
                                    </div>
                                </div>

                                <a href="{{ route('admin.menus.show', $menu) }}"
                                   class="block text-center px-3 py-1.5 bg-accent-blue/10 hover:bg-accent-blue/20 text-accent-blue border border-accent-blue/20 rounded-lg text-xs font-medium transition-colors">
                                    Ver detalles
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-10">
                        <div class="w-14 h-14 bg-accent-gray/30 rounded-full flex items-center justify-center mx-auto mb-3">
                            <span class="text-2xl">📋</span>
                        </div>
                        <p class="text-text-secondary text-sm">Este comercio no tiene menús registrados.</p>
                    </div>
                @endif
            </div>

            <!-- Propietario -->
            <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-9 h-9 bg-accent-blue/10 rounded-lg flex items-center justify-center">
                        <span>👤</span>
                    </div>
                    <h3 class="font-bold text-text-primary">Propietario</h3>
                </div>

                <div class="flex items-center justify-between bg-primary-dark border border-accent-gray/40 rounded-xl px-4 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-accent-blue/20 rounded-full flex items-center justify-center text-accent-blue font-semibold flex-shrink-0">
                            {{ strtoupper(substr($comercio->user->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="font-semibold text-text-primary">{{ $comercio->user->name }}</p>
                            <p class="text-text-secondary text-sm">{{ $comercio->user->email }}</p>
                            @if($comercio->user->whatsapp)
                                <p class="text-text-secondary text-xs">💬 {{ $comercio->user->whatsapp }}</p>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('admin.users.show', $comercio->user) }}"
                       class="px-3 py-1.5 bg-accent-blue/10 hover:bg-accent-blue/20 text-accent-blue border border-accent-blue/20 rounded-lg text-xs font-medium transition-colors flex-shrink-0">
                        Ver usuario
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

