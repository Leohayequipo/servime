<x-app-layout>
    <x-slot name="header">
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    👥 Gestión de Usuarios
                </h2>
                <div class="flex space-x-3">
                    <a href="{{ route('admin.users.create') }}" class="btn-primary">
                        ➕ Crear Usuario
                    </a>
                    <a href="{{ route('admin.dashboard') }}" class="btn-secondary">
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
                            <div class="flex-shrink-0">
                                <div class="w-2 h-2 bg-green-400 rounded-full animate-ping"></div>
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
                            <div class="flex-shrink-0">
                                <div class="w-2 h-2 bg-red-400 rounded-full animate-ping"></div>
                            </div>
                        </div>
                        
                        <div class="absolute bottom-0 left-0 h-1 bg-gradient-to-r from-red-500 to-rose-500 rounded-full animate-progress-bar"></div>
                    </div>
                </div>
            @endif

            <!-- Estadísticas Rápidas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="glass-effect rounded-2xl p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white text-xl">👥</span>
                        </div>
                        <div>
                            <p class="text-text-secondary text-sm">Total Usuarios</p>
                            <p class="text-2xl font-bold text-text-primary">{{ $users->total() }}</p>
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
                            <p class="text-2xl font-bold text-text-primary">{{ $users->where('role', 'super_admin')->count() }}</p>
                        </div>
                    </div>
                </div>

                <div class="glass-effect rounded-2xl p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white text-xl">👤</span>
                        </div>
                        <div>
                            <p class="text-text-secondary text-sm">Usuarios Comunes</p>
                            <p class="text-2xl font-bold text-text-primary">{{ $users->where('role', 'user')->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Usuarios -->
            <div class="glass-effect rounded-2xl p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-accent-blue rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white text-lg">👥</span>
                        </div>
                        <h3 class="text-xl font-bold text-text-primary">Lista de Usuarios</h3>
                    </div>
                    <a href="{{ route('admin.users.create') }}" 
                       class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-6 py-3 rounded-xl transition-all duration-300 flex items-center space-x-2 shadow-lg hover:shadow-xl">
                        <span class="text-lg">➕</span>
                        <span class="font-medium">Crear Usuario</span>
                    </a>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-accent-gray">
                                <th class="text-left py-3 px-4 text-text-primary font-medium">Usuario</th>
                                <th class="text-left py-3 px-4 text-text-primary font-medium">Email</th>
                                <th class="text-left py-3 px-4 text-text-primary font-medium">WhatsApp</th>
                                <th class="text-left py-3 px-4 text-text-primary font-medium">Rol</th>
                                <th class="text-left py-3 px-4 text-text-primary font-medium">Comercios</th>
                                <th class="text-left py-3 px-4 text-text-primary font-medium">Registrado</th>
                                <th class="text-left py-3 px-4 text-text-primary font-medium">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                <tr class="border-b border-accent-gray/50 hover:bg-secondary-dark/50 transition-colors">
                                    <td class="py-4 px-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full flex items-center justify-center">
                                                <span class="text-white font-bold">{{ substr($user->name, 0, 1) }}</span>
                                            </div>
                                            <div>
                                                <p class="font-medium text-text-primary">{{ $user->name }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <p class="text-text-secondary">{{ $user->email }}</p>
                                    </td>
                                    <td class="py-4 px-4">
                                        @if($user->whatsapp)
                                            <p class="text-text-secondary text-sm">💬 {{ $user->whatsapp }}</p>
                                        @else
                                            <p class="text-text-secondary text-sm">No especificado</p>
                                        @endif
                                    </td>
                                    <td class="py-4 px-4">
                                        <span class="px-3 py-1 text-xs rounded-full {{ $user->role === 'super_admin' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' }}">
                                            {{ $user->role === 'super_admin' ? 'Super Admin' : 'Usuario' }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4">
                                        <span class="text-text-secondary">{{ $user->comercios->count() }}</span>
                                    </td>
                                    <td class="py-4 px-4">
                                        <p class="text-text-secondary">{{ $user->created_at->format('d/m/Y') }}</p>
                                        <p class="text-xs text-text-secondary">{{ $user->created_at->diffForHumans() }}</p>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="flex items-center space-x-2">
                                            <a href="{{ route('admin.users.show', $user) }}" 
                                               class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded-lg text-sm font-medium transition-colors">
                                                Ver
                                            </a>
                                            <a href="{{ route('admin.users.edit', $user) }}" 
                                               class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded-lg text-sm font-medium transition-colors">
                                                Editar
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-text-secondary">
                                        No hay usuarios registrados
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
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
