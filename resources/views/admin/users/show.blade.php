<x-app-layout>
    <div class="min-h-screen bg-background">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-10">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-accent-blue/20 rounded-2xl flex items-center justify-center text-accent-blue text-2xl font-bold flex-shrink-0">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-text-primary">{{ $user->name }}</h1>
                        <p class="text-text-secondary mt-0.5">{{ $user->email }}</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.users.edit', $user) }}" class="btn-primary">
                        Editar
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="btn-secondary">
                        ← Usuarios
                    </a>
                </div>
            </div>

            <!-- Info del usuario -->
            <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6 mb-6">
                <div class="flex items-center gap-2 mb-5">
                    <span class="text-text-secondary text-sm font-medium uppercase tracking-wide">Información</span>
                    <span class="px-2.5 py-0.5 text-xs rounded-full font-medium
                        {{ $user->role === 'super_admin' ? 'bg-accent-blue/20 text-accent-blue' : 'bg-accent-gray/50 text-text-secondary' }}">
                        {{ $user->role === 'super_admin' ? 'Super Admin' : 'Usuario' }}
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <p class="text-text-secondary text-xs mb-1">Nombre</p>
                            <p class="text-text-primary font-medium">{{ $user->name }}</p>
                        </div>
                        <div>
                            <p class="text-text-secondary text-xs mb-1">Email</p>
                            <p class="text-text-primary font-medium">{{ $user->email }}</p>
                        </div>
                        <div>
                            <p class="text-text-secondary text-xs mb-1">WhatsApp</p>
                            <p class="text-text-primary font-medium">{{ $user->whatsapp ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-text-secondary text-xs mb-1">Email verificado</p>
                            @if($user->email_verified_at)
                                <p class="text-accent-green font-medium">✓ Verificado <span class="text-text-secondary text-xs font-normal">· {{ $user->email_verified_at->format('d/m/Y H:i') }}</span></p>
                            @else
                                <p class="text-red-400 font-medium">✗ No verificado</p>
                            @endif
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <p class="text-text-secondary text-xs mb-1">Rol</p>
                            <p class="text-text-primary font-medium">{{ $user->role === 'super_admin' ? 'Super Administrador' : 'Usuario Común' }}</p>
                        </div>
                        <div>
                            <p class="text-text-secondary text-xs mb-1">Registrado</p>
                            <p class="text-text-primary font-medium">{{ $user->created_at->format('d/m/Y H:i') }}</p>
                            <p class="text-text-secondary text-xs">{{ $user->created_at->diffForHumans() }}</p>
                        </div>
                        <div>
                            <p class="text-text-secondary text-xs mb-1">Última actualización</p>
                            <p class="text-text-primary font-medium">{{ $user->updated_at->format('d/m/Y H:i') }}</p>
                            <p class="text-text-secondary text-xs">{{ $user->updated_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Comercios -->
            <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6 mb-6">
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 bg-accent-green/10 rounded-lg flex items-center justify-center">
                            <span>🏪</span>
                        </div>
                        <h3 class="font-bold text-text-primary">Comercios</h3>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="px-2.5 py-0.5 text-xs rounded-full bg-accent-gray/50 text-text-secondary font-medium">
                            {{ $user->comercios->count() }} comercio(s)
                        </span>
                        <a href="{{ route('comercios.create', ['for_user' => $user->id]) }}"
                           class="px-3 py-1.5 bg-accent-green/10 hover:bg-accent-green/20 text-accent-green border border-accent-green/20 rounded-lg text-xs font-medium transition-colors">
                            + Crear comercio
                        </a>
                    </div>
                </div>

                @if($user->comercios->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($user->comercios as $comercio)
                            <div class="bg-primary-dark border border-accent-gray/50 rounded-xl p-5 hover:border-accent-blue/30 transition-colors">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 bg-accent-green/20 rounded-xl flex items-center justify-center text-accent-green font-bold flex-shrink-0">
                                        {{ strtoupper(substr($comercio->nombre, 0, 1)) }}
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-text-primary">{{ $comercio->nombre }}</h4>
                                        <p class="text-text-secondary text-xs">{{ $comercio->created_at->format('d/m/Y') }}</p>
                                    </div>
                                </div>
                                @if($comercio->descripcion)
                                    <p class="text-text-secondary text-sm mb-3">{{ Str::limit($comercio->descripcion, 80) }}</p>
                                @endif
                                <div class="flex items-center justify-between pt-3 border-t border-accent-gray/30">
                                    <span class="text-text-secondary text-xs">{{ $comercio->menus->count() }} menú(s)</span>
                                    <a href="{{ route('admin.comercios.show', $comercio) }}"
                                       class="px-3 py-1.5 bg-accent-blue/10 hover:bg-accent-blue/20 text-accent-blue border border-accent-blue/20 rounded-lg text-xs font-medium transition-colors">
                                        Ver detalles
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-10">
                        <div class="w-14 h-14 bg-accent-gray/30 rounded-full flex items-center justify-center mx-auto mb-3">
                            <span class="text-2xl">🏪</span>
                        </div>
                        <p class="text-text-secondary text-sm">Este usuario no tiene comercios registrados.</p>
                    </div>
                @endif
            </div>

            <!-- Menús -->
            @php
                $totalMenus = $user->comercios->sum(fn($c) => $c->menus->count());
            @endphp

            @if($totalMenus > 0)
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-5">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-accent-blue/10 rounded-lg flex items-center justify-center">
                                <span>📋</span>
                            </div>
                            <h3 class="font-bold text-text-primary">Menús</h3>
                        </div>
                        <span class="px-2.5 py-0.5 text-xs rounded-full bg-accent-gray/50 text-text-secondary font-medium">
                            {{ $totalMenus }} en total
                        </span>
                    </div>

                    <div class="space-y-4">
                        @foreach($user->comercios as $comercio)
                            @if($comercio->menus->count() > 0)
                                <div class="bg-primary-dark border border-accent-gray/40 rounded-xl p-4">
                                    <h4 class="font-semibold text-text-primary mb-3 text-sm">{{ $comercio->nombre }}</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                        @foreach($comercio->menus as $menu)
                                            <div class="bg-background border border-accent-gray/30 rounded-lg p-3">
                                                <div class="flex items-center justify-between mb-1.5">
                                                    <h5 class="font-medium text-text-primary text-sm">{{ $menu->nombre ?: 'Sin nombre' }}</h5>
                                                    @if($menu->activo)
                                                        <span class="px-1.5 py-0.5 text-xs rounded bg-accent-green/20 text-accent-green">Activo</span>
                                                    @endif
                                                </div>
                                                <p class="text-text-secondary text-xs mb-2">{{ $menu->created_at->format('d/m/Y') }} · {{ $menu->formato }} · {{ $menu->procesado ? 'Procesado' : 'Pendiente' }}</p>
                                                <a href="{{ route('admin.menus.show', $menu) }}"
                                                   class="inline-block px-2.5 py-1 bg-accent-blue/10 hover:bg-accent-blue/20 text-accent-blue border border-accent-blue/20 rounded text-xs font-medium transition-colors">
                                                    Ver
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>

