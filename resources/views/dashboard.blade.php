<x-app-layout>
    <div class="min-h-screen bg-background">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            
            <!-- Welcome Section -->
            <div class="text-center mb-12">
                <h1 class="text-5xl font-bold text-text-primary mb-4">
                    ¡Bienvenido, {{ auth()->user()->name }}!
                </h1>
                <p class="text-xl text-text-secondary">
                    Gestiona tus comercios y menús desde aquí.
                </p>
            </div>

            <!-- Success Messages -->
            @if(session('success'))
                <div class="mb-8 animate-fade-in-down">
                    <div class="relative overflow-hidden bg-gradient-to-r from-green-900/30 to-emerald-900/30 border border-green-500/30 rounded-2xl p-6 shadow-2xl backdrop-blur-sm">
                        <!-- Animated background -->
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
                        
                        <!-- Progress bar -->
                        <div class="absolute bottom-0 left-0 h-1 bg-gradient-to-r from-green-500 to-emerald-500 rounded-full animate-progress-bar"></div>
                    </div>
                </div>
            @endif

            <!-- Error Messages -->
            @if(session('error'))
                <div class="mb-8 animate-fade-in-down">
                    <div class="relative overflow-hidden bg-gradient-to-r from-red-900/30 to-rose-900/30 border border-red-500/30 rounded-2xl p-6 shadow-2xl backdrop-blur-sm">
                        <!-- Animated background -->
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
                        
                        <!-- Progress bar -->
                        <div class="absolute bottom-0 left-0 h-1 bg-gradient-to-r from-red-500 to-rose-500 rounded-full animate-progress-bar"></div>
                    </div>
                </div>
            @endif

            <!-- Metrics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                <!-- Total Comercios -->
                <a href="{{ route('comercios.index') }}" class="metric-card hover-lift cursor-pointer transition-all duration-300 hover:scale-105">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mr-4 neon-glow">
                            <span class="text-2xl">🏪</span>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-text-primary mb-1">Total Comercios</h3>
                            <p class="text-3xl font-bold text-accent-blue">{{ $comercios->count() }}</p>
                        </div>
                    </div>
                </a>

                <!-- Total Menús -->
                <div class="metric-card">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-accent-green to-green-500 rounded-xl flex items-center justify-center mr-4 neon-glow-green">
                            <span class="text-2xl">📋</span>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-text-primary mb-1">Total Menús</h3>
                            <p class="text-3xl font-bold text-accent-green">{{ $menus->count() }}</p>
                        </div>
                    </div>
                </div>

                <!-- Actividad Reciente -->
                <div class="metric-card">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-yellow-500 to-orange-500 rounded-xl flex items-center justify-center mr-4 neon-glow">
                            <span class="text-2xl">⚡</span>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-text-primary mb-1">Actividad Reciente</h3>
                            <p class="text-3xl font-bold text-yellow-400">{{ $conversaciones->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            @if(auth()->user()->isSuperAdmin())
                <!-- Panel de Administración -->
                <div class="bg-gradient-to-r from-orange-900/30 to-red-900/30 border border-orange-500/30 rounded-2xl p-8 mb-12">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-gradient-to-r from-orange-500 to-red-500 rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white text-2xl">🛡️</span>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-orange-100">Panel de Super Administración</h2>
                            <p class="text-orange-200">Gestiona usuarios, comercios y menús del sistema</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Gestión de Usuarios -->
                        <div class="bg-secondary-dark/50 rounded-xl p-6 hover:bg-secondary-dark/70 transition-all duration-300 cursor-pointer" onclick="window.location.href='{{ route('admin.users.index') }}'">
                            <div class="flex items-center mb-4">
                                <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg flex items-center justify-center mr-3">
                                    <span class="text-white text-lg">👥</span>
                                </div>
                                <h3 class="text-lg font-bold text-text-primary">Usuarios</h3>
                            </div>
                            <p class="text-text-secondary text-sm mb-4">Gestiona usuarios y roles del sistema</p>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-text-secondary">Ver, editar, cambiar roles</span>
                                <span class="text-accent-blue">→</span>
                            </div>
                        </div>

                        <!-- Gestionar -->
                        <div class="bg-secondary-dark/50 rounded-xl p-6 hover:bg-secondary-dark/70 transition-all duration-300 cursor-pointer" onclick="window.location.href='{{ route('comercios.index') }}'">
                            <div class="flex items-center mb-4">
                                <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg flex items-center justify-center mr-3">
                                    <span class="text-white text-lg">⚙️</span>
                                </div>
                                <h3 class="text-lg font-bold text-text-primary">Gestionar</h3>
                            </div>
                            <p class="text-text-secondary text-sm mb-4">Comercios y Menús</p>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-text-secondary">Ver, crear, gestionar</span>
                                <span class="text-accent-blue">→</span>
                            </div>
                        </div>
                    </div>

                </div>
            @endif

            <!-- Quick Actions Section -->
            <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-8">
                <h2 class="text-2xl font-bold text-text-primary mb-8 text-center">Acciones Rápidas</h2>
                
                @if(auth()->user()->isSuperAdmin())
                    <!-- Acciones para Super Administrador -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Crear Comercio (Admin) -->
                        <a href="{{ route('admin.comercios.create') }}" 
                           class="group bg-gradient-to-r from-accent-blue to-blue-600 hover:from-blue-500 hover:to-accent-blue rounded-xl p-6 transition-all duration-300 hover-lift neon-glow">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mr-4 group-hover:scale-110 transition-transform duration-300">
                                    <span class="text-2xl">🏪</span>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-text-primary mb-2">Crear Comercio</h3>
                                    <p class="text-text-secondary">Asignar a Usuario</p>
                                </div>
                            </div>
                        </a>

                        <!-- Crear Menú (Admin) -->
                        <a href="{{ route('admin.menus.create') }}" 
                           class="group bg-gradient-to-r from-accent-green to-green-600 hover:from-green-500 hover:to-accent-green rounded-xl p-6 transition-all duration-300 hover-lift neon-glow-green">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mr-4 group-hover:scale-110 transition-transform duration-300">
                                    <span class="text-2xl">📋</span>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-text-primary mb-2">Crear Menú</h3>
                                    <p class="text-text-secondary">En cualquier Comercio</p>
                                </div>
                            </div>
                        </a>
                    </div>
                @else
                    <!-- Acciones para Usuario Común -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Crear Nuevo Comercio -->
                        <a href="{{ route('comercios.create') }}" 
                           class="group bg-gradient-to-r from-accent-blue to-blue-600 hover:from-blue-500 hover:to-accent-blue rounded-xl p-6 transition-all duration-300 hover-lift neon-glow">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mr-4 group-hover:scale-110 transition-transform duration-300">
                                    <span class="text-2xl">➕</span>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-text-primary mb-2">Crear Nuevo Comercio</h3>
                                    <p class="text-text-secondary">Agrega un nuevo comercio a tu cuenta</p>
                                </div>
                            </div>
                        </a>

                        <!-- Gestionar Comercios -->
                        <a href="{{ route('comercios.index') }}" 
                           class="group bg-gradient-to-r from-accent-green to-green-600 hover:from-green-500 hover:to-accent-green rounded-xl p-6 transition-all duration-300 hover-lift neon-glow-green">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mr-4 group-hover:scale-110 transition-transform duration-300">
                                    <span class="text-2xl">📊</span>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-text-primary mb-2">Gestionar Comercios</h3>
                                    <p class="text-text-secondary">Ve y edita tus comercios existentes</p>
                                </div>
                            </div>
                        </a>
                    </div>
                @endif
            </div>

            <!-- Recent Activity -->
            @if($conversaciones->count() > 0)
                <div class="mt-12">
                    <h2 class="text-2xl font-bold text-text-primary mb-6">Actividad Reciente</h2>
                    <div class="bg-secondary-dark border border-accent-gray rounded-xl p-6">
                        <div class="space-y-4">
                            @foreach($conversaciones->take(5) as $conversacion)
                                <div class="flex items-center justify-between p-4 bg-primary-dark rounded-lg border border-accent-gray">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-accent-blue rounded-full flex items-center justify-center mr-3">
                                            <span class="text-sm">💬</span>
                                        </div>
                                        <div>
                                            <p class="text-text-primary font-medium">{{ $conversacion->comercio->nombre }}</p>
                                            <p class="text-text-secondary text-sm">{{ Str::limit($conversacion->user_message, 50) }}</p>
                                        </div>
                                    </div>
                                    <div class="text-text-secondary text-sm">
                                        {{ $conversacion->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>