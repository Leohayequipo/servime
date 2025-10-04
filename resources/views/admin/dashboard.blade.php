<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                🛡️ Panel de Super Administración
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            <!-- Estadísticas Generales -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="glass-effect rounded-2xl p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white text-xl">👥</span>
                        </div>
                        <div>
                            <p class="text-text-secondary text-sm">Total Usuarios</p>
                            <p class="text-2xl font-bold text-text-primary">{{ $stats['total_users'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="glass-effect rounded-2xl p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white text-xl">🏪</span>
                        </div>
                        <div>
                            <p class="text-text-secondary text-sm">Total Comercios</p>
                            <p class="text-2xl font-bold text-text-primary">{{ $stats['total_comercios'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="glass-effect rounded-2xl p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white text-xl">📋</span>
                        </div>
                        <div>
                            <p class="text-text-secondary text-sm">Total Menús</p>
                            <p class="text-2xl font-bold text-text-primary">{{ $stats['total_menus'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="glass-effect rounded-2xl p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white text-xl">🛡️</span>
                        </div>
                        <div>
                            <p class="text-text-secondary text-sm">Super Admins</p>
                            <p class="text-2xl font-bold text-text-primary">{{ $stats['super_admins'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gestión de Usuarios -->
            <div class="glass-effect rounded-2xl p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-accent-purple rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white text-lg">👥</span>
                        </div>
                        <h3 class="text-xl font-bold text-text-primary">Gestión de Usuarios</h3>
                    </div>
                    <a href="{{ route('admin.users.index') }}" class="text-accent-purple hover:text-purple-400 font-medium">
                        Ver todos los usuarios
                    </a>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div class="bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white px-6 py-4 rounded-xl transition-all duration-300 flex items-center justify-center space-x-2 cursor-pointer" onclick="window.location.href='{{ route('admin.users.index') }}'">
                        <span class="text-xl">👥</span>
                        <span class="font-medium">Ver Usuarios</span>
                    </div>
                    
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-6 py-4 rounded-xl transition-all duration-300 flex items-center justify-center space-x-2 cursor-pointer" onclick="window.location.href='{{ route('admin.users.index') }}'">
                        <span class="text-xl">⚙️</span>
                        <span class="font-medium">Gestionar Roles</span>
                    </div>
                </div>
                
                <!-- Usuarios recientes (máximo 3) -->
                <div class="space-y-3">
                    <h4 class="font-medium text-text-primary mb-3">Usuarios Recientes</h4>
                    @forelse($recent_users->take(3) as $user)
                        <div class="flex items-center justify-between p-3 bg-secondary-dark rounded-xl hover:bg-secondary-dark/80 transition-colors cursor-pointer" onclick="window.location.href='{{ route('admin.users.show', $user) }}'">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-gradient-to-r from-purple-500 to-purple-600 rounded-full flex items-center justify-center">
                                    <span class="text-white font-bold text-sm">{{ substr($user->name, 0, 1) }}</span>
                                </div>
                                <div>
                                    <p class="font-medium text-text-primary">{{ $user->name }}</p>
                                    <p class="text-xs text-text-secondary">{{ $user->email }}</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="px-2 py-1 text-xs rounded-full {{ $user->role === 'super_admin' ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ $user->role === 'super_admin' ? 'Admin' : 'Usuario' }}
                                </span>
                                <span class="text-xs text-text-secondary">{{ $user->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-text-secondary text-center py-4">No hay usuarios registrados</p>
                    @endforelse
                </div>
            </div>

            <!-- Acciones Rápidas -->
            <div class="glass-effect rounded-2xl p-6">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-accent-blue rounded-xl flex items-center justify-center mr-4">
                        <span class="text-white text-lg">⚡</span>
                    </div>
                    <h3 class="text-xl font-bold text-text-primary">Acciones Rápidas</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <a href="{{ route('admin.comercios.create') }}" 
                       class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-6 py-4 rounded-xl transition-all duration-300 flex items-center justify-center space-x-2">
                        <span class="text-xl">🏪</span>
                        <span class="font-medium">Crear Comercio</span>
                    </a>
                    
                    <a href="{{ route('admin.menus.create') }}" 
                       class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-6 py-4 rounded-xl transition-all duration-300 flex items-center justify-center space-x-2">
                        <span class="text-xl">📋</span>
                        <span class="font-medium">Crear Menú</span>
                    </a>
                </div>
            </div>


            <!-- Comercios Recientes -->
            <div class="glass-effect rounded-2xl p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-accent-blue rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white text-lg">🏪</span>
                        </div>
                        <h3 class="text-xl font-bold text-text-primary">Comercios Recientes</h3>
                    </div>
                    <a href="{{ route('admin.comercios.index') }}" class="text-accent-blue hover:text-blue-400 font-medium">
                        Ver todos
                    </a>
                </div>
                
                <div class="space-y-4">
                    @forelse($recent_comercios as $comercio)
                        <div class="flex items-center justify-between p-4 bg-secondary-dark rounded-xl">
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-green-600 rounded-full flex items-center justify-center">
                                    <span class="text-white font-bold">{{ substr($comercio->nombre, 0, 1) }}</span>
                                </div>
                                <div>
                                    <p class="font-medium text-text-primary">{{ $comercio->nombre }}</p>
                                    <p class="text-sm text-text-secondary">Propietario: {{ $comercio->user->name }}</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-text-secondary">{{ $comercio->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-text-secondary text-center py-8">No hay comercios registrados</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
