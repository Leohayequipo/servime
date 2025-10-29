<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                📋 Crear Menú (Administración)
            </h2>
            <a href="{{ route('admin.menus.index') }}" class="btn-secondary">
                {{ __('Volver') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.menus.store') }}" enctype="multipart/form-data" class="space-y-8">
                @csrf
                
                <!-- 🏪 Selección de Comercio -->
                <div class="glass-effect rounded-2xl p-6">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 bg-accent-blue rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white text-lg">🏪</span>
                        </div>
                        <h3 class="text-xl font-bold text-text-primary">Selección de Comercio</h3>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="comercio_id" :value="__('Comercio')" class="text-text-primary font-medium mb-2" />
                            <select id="comercio_id" name="comercio_id" class="input-field w-full" required>
                                <option value="">Seleccionar comercio...</option>
                                @foreach($comercios as $comercio)
                                    <option value="{{ $comercio->id }}" {{ old('comercio_id') == $comercio->id ? 'selected' : '' }}>
                                        {{ $comercio->nombre }} - {{ $comercio->user->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('comercio_id')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <!-- 📋 Información del Menú -->
                <div class="glass-effect rounded-2xl p-6">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 bg-accent-green rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white text-lg">📋</span>
                        </div>
                        <h3 class="text-xl font-bold text-text-primary">Información del Menú</h3>
                    </div>
                    
                    <div class="space-y-4">
                        <!-- Nombre del Menú -->
                        <div>
                            <x-input-label for="nombre" :value="__('Nombre del Menú (Opcional)')" class="text-text-primary font-medium mb-2" />
                            <x-text-input id="nombre" class="input-field w-full" type="text" name="nombre" :value="old('nombre')" placeholder="Ej: Menú Principal, Menú de Temporada, etc." />
                            <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                        </div>

                        <!-- Archivo del Menú -->
                        <div>
                            <x-input-label for="archivo" :value="__('Archivo del Menú')" class="text-text-primary font-medium mb-2" />
                            <input type="file" id="archivo" name="archivo" accept=".pdf,.jpeg,.png,.jpg,.gif" class="input-field w-full" required>
                            <x-input-error :messages="$errors->get('archivo')" class="mt-2" />
                            <p class="text-sm text-text-secondary mt-1">Formatos permitidos: PDF, JPEG, PNG, JPG, GIF. Tamaño máximo: 10MB</p>
                        </div>
                    </div>
                </div>

                <!-- ℹ️ Información del Procesamiento -->
                <div class="glass-effect rounded-2xl p-6 bg-blue-900/20 border border-blue-500/30">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-blue-500 rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white text-lg">ℹ️</span>
                        </div>
                        <h3 class="text-xl font-bold text-blue-100">Procesamiento Automático</h3>
                    </div>
                    
                    <div class="space-y-3 text-blue-200">
                        <p class="flex items-center">
                            <span class="w-2 h-2 bg-blue-400 rounded-full mr-3"></span>
                            El sistema procesará automáticamente el menú con IA para extraer información
                        </p>
                        <p class="flex items-center">
                            <span class="w-2 h-2 bg-blue-400 rounded-full mr-3"></span>
                            Se detectará automáticamente si es PDF o imagen
                        </p>
                        <p class="flex items-center">
                            <span class="w-2 h-2 bg-blue-400 rounded-full mr-3"></span>
                            Si es el primer menú del comercio, se marcará como activo
                        </p>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="flex flex-col sm:flex-row justify-center items-center sm:justify-center gap-4 mt-6">
                    <a href="{{ route('admin.menus.index') }}" 
                       class="btn-secondary w-auto max-w-[320px] sm:max-w-none sm:w-auto px-8 py-3 text-base text-center">
                        Cancelar
                    </a>
                    <button type="submit" 
                            class="btn-primary w-auto max-w-[320px] sm:max-w-none sm:w-auto px-8 py-3 text-base text-center">
                        Crear Menú
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
