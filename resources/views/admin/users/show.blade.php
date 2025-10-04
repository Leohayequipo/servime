<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                👤 Usuario: {{ $user->name }}
            </h2>
            <div class="flex space-x-3">
                <a href="{{ route('admin.users.edit', $user) }}" class="btn-primary">
                    Editar Usuario
                </a>
                <a href="{{ route('admin.users.index') }}" class="btn-secondary">
                    Volver a Usuarios
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            <!-- Información del Usuario -->
            <div class="glass-effect rounded-2xl p-6">
                <div class="flex items-center mb-6">
                    <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mr-6">
                        <span class="text-white text-2xl font-bold">{{ substr($user->name, 0, 1) }}</span>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-text-primary">{{ $user->name }}</h3>
                        <p class="text-text-secondary">{{ $user->email }}</p>
                        <span class="inline-block mt-2 px-3 py-1 text-sm rounded-full {{ $user->role === 'super_admin' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' }}">
                            {{ $user->role === 'super_admin' ? 'Super Administrador' : 'Usuario Común' }}
                        </span>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="text-text-secondary text-sm font-medium">Nombre completo</label>
                            <p class="text-text-primary font-medium">{{ $user->name }}</p>
                        </div>
                        <div>
                            <label class="text-text-secondary text-sm font-medium">Email</label>
                            <p class="text-text-primary font-medium">{{ $user->email }}</p>
                        </div>
                        <div>
                            <label class="text-text-secondary text-sm font-medium">WhatsApp</label>
                            <p class="text-text-primary font-medium">{{ $user->whatsapp ?: 'No especificado' }}</p>
                        </div>
                        <div>
                            <label class="text-text-secondary text-sm font-medium">Rol</label>
                            <p class="text-text-primary font-medium">
                                {{ $user->role === 'super_admin' ? 'Super Administrador' : 'Usuario Común' }}
                            </p>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="text-text-secondary text-sm font-medium">Fecha de registro</label>
                            <p class="text-text-primary font-medium">{{ $user->created_at->format('d/m/Y H:i') }}</p>
                            <p class="text-xs text-text-secondary">{{ $user->created_at->diffForHumans() }}</p>
                        </div>
                        <div>
                            <label class="text-text-secondary text-sm font-medium">Última actualización</label>
                            <p class="text-text-primary font-medium">{{ $user->updated_at->format('d/m/Y H:i') }}</p>
                            <p class="text-xs text-text-secondary">{{ $user->updated_at->diffForHumans() }}</p>
                        </div>
                        <div>
                            <label class="text-text-secondary text-sm font-medium">Email verificado</label>
                            <p class="text-text-primary font-medium">
                                @if($user->email_verified_at)
                                    <span class="text-green-400">✓ Verificado</span>
                                    <br>
                                    <span class="text-xs text-text-secondary">{{ $user->email_verified_at->format('d/m/Y H:i') }}</span>
                                @else
                                    <span class="text-red-400">✗ No verificado</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Comercios del Usuario -->
            <div class="glass-effect rounded-2xl p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-accent-green rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white text-lg">🏪</span>
                        </div>
                        <h3 class="text-xl font-bold text-text-primary">Comercios</h3>
                    </div>
                    <span class="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 px-3 py-1 rounded-full text-sm font-medium">
                        {{ $user->comercios->count() }} comercio(s)
                    </span>
                </div>
                
                @if($user->comercios->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($user->comercios as $comercio)
                            <div class="bg-secondary-dark rounded-xl p-6 hover:bg-secondary-dark/80 transition-colors">
                                <div class="flex items-center mb-4">
                                    <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-xl flex items-center justify-center mr-4">
                                        <span class="text-white text-lg">🏪</span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-text-primary">{{ $comercio->nombre }}</h4>
                                        <p class="text-sm text-text-secondary">{{ $comercio->created_at->format('d/m/Y') }}</p>
                                    </div>
                                </div>
                                
                                @if($comercio->descripcion)
                                    <p class="text-text-secondary text-sm mb-4">{{ Str::limit($comercio->descripcion, 100) }}</p>
                                @endif
                                
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-text-secondary">
                                        {{ $comercio->menus->count() }} menú(s)
                                    </span>
                                    <a href="{{ route('admin.comercios.show', $comercio) }}" 
                                       class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded-lg text-sm font-medium transition-colors">
                                        Ver Detalles
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-gray-500 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-white text-2xl">🏪</span>
                        </div>
                        <p class="text-text-secondary">Este usuario no tiene comercios registrados</p>
                        @if($user->role === 'user')
                            <p class="text-text-secondary text-sm mt-2">Como Super Admin, puedes crear un comercio para este usuario</p>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Menús del Usuario (a través de sus comercios) -->
            @php
                $totalMenus = $user->comercios->sum(function($comercio) {
                    return $comercio->menus->count();
                });
            @endphp
            
            @if($totalMenus > 0)
                <div class="glass-effect rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-accent-purple rounded-xl flex items-center justify-center mr-4">
                                <span class="text-white text-lg">📋</span>
                            </div>
                            <h3 class="text-xl font-bold text-text-primary">Menús</h3>
                        </div>
                        <span class="bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200 px-3 py-1 rounded-full text-sm font-medium">
                            {{ $totalMenus }} menú(s) total
                        </span>
                    </div>
                    
                    <div class="space-y-4">
                        @foreach($user->comercios as $comercio)
                            @if($comercio->menus->count() > 0)
                                <div class="bg-secondary-dark rounded-xl p-4">
                                    <h4 class="font-bold text-text-primary mb-3">{{ $comercio->nombre }}</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        @foreach($comercio->menus as $menu)
                                            <div class="bg-accent-gray/30 rounded-lg p-4">
                                                <div class="flex items-center justify-between mb-2">
                                                    <h5 class="font-medium text-text-primary">{{ $menu->nombre ?: 'Menú sin nombre' }}</h5>
                                                    @if($menu->activo)
                                                        <span class="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 px-2 py-1 rounded-full text-xs">
                                                            Activo
                                                        </span>
                                                    @endif
                                                </div>
                                                <p class="text-xs text-text-secondary mb-2">{{ $menu->created_at->format('d/m/Y') }}</p>
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs text-text-secondary">
                                                        {{ $menu->formato }} - {{ $menu->procesado ? 'Procesado' : 'Pendiente' }}
                                                    </span>
                                                    <a href="{{ route('admin.menus.show', $menu) }}" 
                                                       class="bg-purple-500 hover:bg-purple-600 text-white px-2 py-1 rounded text-xs font-medium transition-colors">
                                                        Ver
                                                    </a>
                                                </div>
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
