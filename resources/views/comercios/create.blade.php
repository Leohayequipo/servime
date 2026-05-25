<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('Crear Comercio') }}
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Crear un nuevo comercio para gestionar menús digitales</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('comercios.index') }}" 
                   class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg transition-all duration-300">
                    🔄 Cancelar
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
    <form method="POST" action="{{ route('comercios.store') }}" enctype="multipart/form-data" class="space-y-8" id="comercio-form">
        @csrf
                
                <!-- 🧾 Información del Comercio -->
                <div class="glass-effect rounded-2xl p-6">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 bg-accent-blue rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white text-lg">🧾</span>
                        </div>
                        <h3 class="text-xl font-bold text-text-primary">Información del Comercio</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @if(auth()->user()->isSuperAdmin() && isset($users))
                            <!-- Selección de Usuario (Solo para Super Admin) -->
                            <div class="md:col-span-2">
                                <x-input-label for="user_id" :value="__('Asignar a Usuario')" class="text-text-primary font-medium mb-2" />
                                <select id="user_id" name="user_id" class="input-field w-full" required>
                                    <option value="">Seleccionar usuario...</option>
                                    @forelse($users as $user)
                                        <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }} ({{ $user->email }})
                                        </option>
                                    @empty
                                        <option value="" disabled>No hay usuarios disponibles (todos ya tienen comercio)</option>
                                    @endforelse
                                </select>
                                @if($users->isEmpty())
                                    <p class="mt-2 text-sm text-yellow-400">
                                        ℹ️ Todos los usuarios ya tienen un comercio asignado. Solo se muestran usuarios disponibles.
                                    </p>
                                @endif
                                <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                            </div>
                        @endif

                        <!-- Nombre del Comercio -->
                        <div class="md:col-span-2">
                            <x-input-label for="nombre" :value="__('Nombre del Comercio')" class="text-text-primary font-medium mb-2" />
                            <x-text-input id="nombre" class="input-field w-full" type="text" name="nombre" :value="old('nombre')" required autofocus />
                            <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                        </div>

                        <!-- Descripción -->
                        <div class="md:col-span-2">
                            <x-input-label for="descripcion" :value="__('Descripción')" class="text-text-primary font-medium mb-2" />
                            <textarea id="descripcion" name="descripcion" rows="3" class="input-field w-full resize-none">{{ old('descripcion') }}</textarea>
                            <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
                        </div>

                        <!-- Dirección -->
                        <div>
                            <x-input-label for="direccion" :value="__('Dirección')" class="text-text-primary font-medium mb-2" />
                            <x-text-input id="direccion" class="input-field w-full" type="text" name="direccion" :value="old('direccion')" />
                            <x-input-error :messages="$errors->get('direccion')" class="mt-2" />
                        </div>

                        <!-- Horario -->
                        <div>
                            <x-input-label for="horario" :value="__('Horario de Atención')" class="text-text-primary font-medium mb-2" />
                            <x-text-input id="horario" class="input-field w-full" type="text" name="horario" :value="old('horario')" placeholder="Ej: Lunes a Viernes 9:00 - 18:00" />
                            <x-input-error :messages="$errors->get('horario')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <!-- 🖼️ Logo -->
                <div class="glass-effect rounded-2xl p-6">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 bg-accent-green rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white text-lg">🖼️</span>
                        </div>
                        <h3 class="text-xl font-bold text-text-primary">Logo del Comercio</h3>
                    </div>
                    
                    <div>
                        <x-input-label for="logo" :value="__('Subir Logo')" class="text-text-primary font-medium mb-2" />
                        <input type="file" id="logo" name="logo" accept="image/*" class="input-field w-full file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-accent-blue file:text-white hover:file:bg-blue-600" />
                        <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                        <p class="mt-2 text-sm text-text-secondary">Formatos permitidos: JPEG, PNG, JPG, GIF. Tamaño máximo: 2MB</p>
                    </div>
                </div>

                <!-- Botón Flotante Guardar Cambios -->
                <div class="fixed bottom-6 right-6 z-50">
                    <button type="submit" 
                            class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 text-white font-semibold text-lg px-8 py-4 rounded-xl shadow-2xl hover:shadow-3xl transform hover:scale-105 transition-all duration-300 flex items-center space-x-3">
                        <span class="text-2xl">💾</span>
                        <span>Guardar Cambios</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
