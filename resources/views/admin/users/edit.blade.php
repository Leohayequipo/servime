<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                ✏️ Editar Usuario: {{ $user->name }}
            </h2>
            <div class="flex space-x-3">
                <a href="{{ route('admin.users.show', $user) }}" class="btn-secondary">
                    Ver Usuario
                </a>
                <a href="{{ route('admin.users.index') }}" class="btn-secondary">
                    Volver a Usuarios
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
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
                        </div>
                        
                        <div class="absolute bottom-0 left-0 h-1 bg-gradient-to-r from-green-500 to-emerald-500 rounded-full animate-progress-bar"></div>
                    </div>
                </div>
            @endif

            <!-- Formulario de Edición -->
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-8">
                @csrf
                @method('PUT')
                
                <!-- Información Personal -->
                <div class="glass-effect rounded-2xl p-6">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 bg-accent-blue rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white text-lg">👤</span>
                        </div>
                        <h3 class="text-xl font-bold text-text-primary">Información Personal</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Nombre -->
                        <div class="md:col-span-2">
                            <x-input-label for="name" :value="__('Nombre Completo')" class="text-text-primary font-medium mb-2" />
                            <x-text-input id="name" class="input-field w-full" type="text" name="name" value="{{ old('name', $user->name) }}" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <!-- Email -->
                        <div>
                            <x-input-label for="email" :value="__('Email')" class="text-text-primary font-medium mb-2" />
                            <x-text-input id="email" class="input-field w-full" type="email" name="email" value="{{ old('email', $user->email) }}" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <!-- WhatsApp -->
                        <div>
                            <x-input-label for="whatsapp" :value="__('WhatsApp')" class="text-text-primary font-medium mb-2" />
                            <x-text-input id="whatsapp" class="input-field w-full" type="text" name="whatsapp" value="{{ old('whatsapp', $user->whatsapp) }}" placeholder="Ej: +54 9 11 1234-5678" />
                            <x-input-error :messages="$errors->get('whatsapp')" class="mt-2" />
                            <p class="text-xs text-text-secondary mt-1">Número de WhatsApp para contacto</p>
                        </div>
                    </div>
                </div>

                <!-- Rol y Permisos -->
                <div class="glass-effect rounded-2xl p-6">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 bg-accent-orange rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white text-lg">🛡️</span>
                        </div>
                        <h3 class="text-xl font-bold text-text-primary">Rol y Permisos</h3>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="role" :value="__('Rol del Usuario')" class="text-text-primary font-medium mb-2" />
                            <select id="role" name="role" class="input-field w-full" required>
                                <option value="user" {{ old('role', $user->role) === 'user' ? 'selected' : '' }}>
                                    Usuario Común
                                </option>
                                <option value="super_admin" {{ old('role', $user->role) === 'super_admin' ? 'selected' : '' }}>
                                    Super Administrador
                                </option>
                            </select>
                            <x-input-error :messages="$errors->get('role')" class="mt-2" />
                        </div>

                        <!-- Información sobre roles -->
                        <div class="bg-blue-900/20 border border-blue-500/30 rounded-xl p-4">
                            <h4 class="font-bold text-blue-100 mb-2">Información sobre los roles:</h4>
                            <div class="space-y-2 text-blue-200 text-sm">
                                <div class="flex items-start">
                                    <span class="w-2 h-2 bg-blue-400 rounded-full mr-3 mt-2"></span>
                                    <div>
                                        <strong>Usuario Común:</strong> Puede crear un comercio y gestionar sus menús
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <span class="w-2 h-2 bg-blue-400 rounded-full mr-3 mt-2"></span>
                                    <div>
                                        <strong>Super Administrador:</strong> Acceso total al sistema, puede gestionar todos los usuarios, comercios y menús
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información de la Cuenta -->
                <div class="glass-effect rounded-2xl p-6">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 bg-accent-gray rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white text-lg">ℹ️</span>
                        </div>
                        <h3 class="text-xl font-bold text-text-primary">Información de la Cuenta</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="text-text-secondary text-sm font-medium">Fecha de registro</label>
                            <p class="text-text-primary font-medium">{{ $user->created_at->format('d/m/Y H:i:s') }}</p>
                            <p class="text-xs text-text-secondary">{{ $user->created_at->diffForHumans() }}</p>
                        </div>
                        
                        <div>
                            <label class="text-text-secondary text-sm font-medium">Última actualización</label>
                            <p class="text-text-primary font-medium">{{ $user->updated_at->format('d/m/Y H:i:s') }}</p>
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
                        
                        <div>
                            <label class="text-text-secondary text-sm font-medium">Comercios</label>
                            <p class="text-text-primary font-medium">{{ $user->comercios->count() }} comercio(s)</p>
                            @if($user->comercios->count() > 0)
                                <p class="text-xs text-text-secondary">
                                    {{ $user->comercios->sum(function($c) { return $c->menus->count(); }) }} menú(s) total
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="flex justify-end space-x-4">
                    <a href="{{ route('admin.users.show', $user) }}" class="btn-secondary">
                        Cancelar
                    </a>
                    <button type="submit" class="btn-primary">
                        Actualizar Usuario
                    </button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
