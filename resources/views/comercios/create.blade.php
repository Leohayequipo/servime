<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Crear Comercio') }}
            </h2>
            <a href="{{ route('comercios.index') }}" class="btn-secondary">
                {{ __('Volver') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('comercios.store') }}" enctype="multipart/form-data" class="space-y-8">
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
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }} ({{ $user->email }})
                                        </option>
                                    @endforeach
                                </select>
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

                <!-- Botones de Acción -->
                <div class="flex justify-center space-x-4">
                    <a href="{{ route('comercios.index') }}" class="btn-secondary">
                        {{ __('Cancelar') }}
                    </a>
                    <x-primary-button>
                        {{ __('Crear Comercio') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>