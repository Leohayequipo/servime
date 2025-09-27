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

            <!-- Quick Actions Section -->
            <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-8">
                <h2 class="text-2xl font-bold text-text-primary mb-8 text-center">Acciones Rápidas</h2>
                
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