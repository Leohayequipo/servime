<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                🏪 Crear Comercio
            </h2>
            <div class="flex space-x-3">
                <a href="{{ route('admin.comercios.index') }}" class="btn-secondary">
                    Volver a Comercios
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Error Messages -->
            @if($errors->any())
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
                                <ul class="text-red-200 font-medium space-y-1">
                                    @foreach($errors->all() as $error)
                                        <li>• {{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        
                        <div class="absolute bottom-0 left-0 h-1 bg-gradient-to-r from-red-500 to-rose-500 rounded-full animate-progress-bar"></div>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.comercios.store') }}" enctype="multipart/form-data" onsubmit="event.preventDefault(); const form = this; showConfirm('¿Estás seguro de que quieres crear este comercio?', function(result) { if(result) form.submit(); });" class="space-y-8">
                @csrf
                
                <!-- Información del Comercio -->
                <div class="glass-effect rounded-2xl p-6">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 bg-accent-blue rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white text-lg">🏪</span>
                        </div>
                        <h3 class="text-xl font-bold text-text-primary">Información del Comercio</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="nombre" class="block text-sm font-medium text-text-secondary mb-2">
                                Nombre del comercio <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="nombre" 
                                   name="nombre" 
                                   value="{{ old('nombre') }}"
                                   required
                                   class="w-full px-4 py-3 bg-secondary-dark border border-accent-gray rounded-xl text-text-primary placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent transition-all duration-200">
                            <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                        </div>

                        <div>
                            <label for="user_id" class="block text-sm font-medium text-text-secondary mb-2">
                                Asignar a usuario <span class="text-red-500">*</span>
                            </label>
                            <select id="user_id" 
                                    name="user_id" 
                                    required
                                    class="w-full px-4 py-3 bg-secondary-dark border border-accent-gray rounded-xl text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent transition-all duration-200">
                                <option value="">Selecciona un usuario</option>
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

                        <div class="md:col-span-2">
                            <label for="descripcion" class="block text-sm font-medium text-text-secondary mb-2">
                                Descripción
                            </label>
                            <textarea id="descripcion" 
                                      name="descripcion" 
                                      rows="3"
                                      class="w-full px-4 py-3 bg-secondary-dark border border-accent-gray rounded-xl text-text-primary placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent transition-all duration-200">{{ old('descripcion') }}</textarea>
                            <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
                        </div>

                        <div>
                            <label for="direccion" class="block text-sm font-medium text-text-secondary mb-2">
                                Dirección
                            </label>
                            <input type="text" 
                                   id="direccion" 
                                   name="direccion" 
                                   value="{{ old('direccion') }}"
                                   class="w-full px-4 py-3 bg-secondary-dark border border-accent-gray rounded-xl text-text-primary placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent transition-all duration-200">
                            <x-input-error :messages="$errors->get('direccion')" class="mt-2" />
                        </div>

                        <div>
                            <label for="horario" class="block text-sm font-medium text-text-secondary mb-2">
                                Horario de atención
                            </label>
                            <input type="text" 
                                   id="horario" 
                                   name="horario" 
                                   value="{{ old('horario') }}"
                                   placeholder="Ej: Lun-Vie 9:00-18:00"
                                   class="w-full px-4 py-3 bg-secondary-dark border border-accent-gray rounded-xl text-text-primary placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent transition-all duration-200">
                            <x-input-error :messages="$errors->get('horario')" class="mt-2" />
                        </div>

                        <div class="md:col-span-2">
                            <label for="logo" class="block text-sm font-medium text-text-secondary mb-2">
                                Logo del comercio
                            </label>
                            <input type="file" 
                                   id="logo" 
                                   name="logo" 
                                   accept="image/*"
                                   class="w-full px-4 py-3 bg-secondary-dark border border-accent-gray rounded-xl text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent transition-all duration-200">
                            <p class="text-xs text-text-secondary mt-2">Formatos permitidos: JPG, PNG, GIF. Máximo 2MB.</p>
                            <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="flex justify-end space-x-4">
                    <a href="{{ route('admin.comercios.index') }}" 
                       class="btn-secondary">
                        Cancelar
                    </a>
                    <button type="submit" 
                            class="btn-primary">
                        🏪 Crear Comercio
                    </button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>