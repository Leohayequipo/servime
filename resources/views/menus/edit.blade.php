<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Editar Menú') }} - {{ $comercio->nombre }}
            </h2>
            <a href="{{ route('menus.index', $comercio) }}" class="inline-flex items-center px-4 py-2 bg-gray-300 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-400 dark:hover:bg-gray-600 focus:bg-gray-400 dark:focus:bg-gray-600 active:bg-gray-500 dark:active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('Volver') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route('menus.update', [$comercio, $menu]) }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        @method('PUT')
                        
                        <!-- Información del Comercio -->
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg mb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Comercio: {{ $comercio->nombre }}</h3>
                            @if($comercio->descripcion)
                                <p class="text-sm text-gray-600 dark:text-gray-300">{{ $comercio->descripcion }}</p>
                            @endif
                        </div>

                        <!-- Archivo actual -->
                        @if($menu->archivo_original)
                            <div class="mb-6">
                                <x-input-label :value="__('Archivo Actual')" />
                                <div class="mt-2 flex items-center space-x-4">
                                    @if($menu->formato === 'pdf')
                                        <svg class="w-12 h-12 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                                        </svg>
                                    @else
                                        <img src="{{ asset('storage/' . $menu->archivo_original) }}" alt="Archivo actual" class="w-12 h-12 object-cover rounded">
                                    @endif
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ strtoupper($menu->formato) }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Subido: {{ $menu->created_at->format('d/m/Y H:i') }}</p>
                                        <a href="{{ asset('storage/' . $menu->archivo_original) }}" target="_blank" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300">Ver archivo</a>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Nuevo archivo -->
                        <div>
                            <x-input-label for="archivo" :value="__('Nuevo Archivo (opcional)')" />
                            <input type="file" id="archivo" name="archivo" accept=".pdf,.jpeg,.jpg,.png,.gif" class="block mt-1 w-full text-sm text-gray-500 dark:text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-900 file:text-indigo-700 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-800" />
                            <x-input-error :messages="$errors->get('archivo')" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Deja vacío para mantener el archivo actual. Formatos permitidos: PDF, JPEG, PNG, JPG, GIF. Tamaño máximo: 10MB
                            </p>
                        </div>

                        <!-- Tipo de Archivo -->
                        <div>
                            <x-input-label for="formato" :value="__('Tipo de Archivo')" />
                            <select id="formato" name="formato" required class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                                <option value="">Selecciona el tipo de archivo</option>
                                <option value="pdf" {{ old('formato', $menu->formato) == 'pdf' ? 'selected' : '' }}>PDF</option>
                                <option value="image" {{ old('formato', $menu->formato) == 'image' ? 'selected' : '' }}>Imagen (JPEG, PNG, JPG, GIF)</option>
                            </select>
                            <x-input-error :messages="$errors->get('formato')" class="mt-2" />
                        </div>

                        <!-- Estado del procesamiento -->
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Estado del Procesamiento</h4>
                            <div class="flex items-center space-x-2">
                                @if($menu->procesado)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        Procesado
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                        </svg>
                                        Procesando
                                    </span>
                                @endif
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    Última actualización: {{ $menu->updated_at->format('d/m/Y H:i') }}
                                </span>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="flex items-center justify-end mt-4 space-x-4">
                            <a href="{{ route('menus.index', $comercio) }}" class="inline-flex items-center px-4 py-2 bg-gray-300 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-400 dark:hover:bg-gray-600 focus:bg-gray-400 dark:focus:bg-gray-600 active:bg-gray-500 dark:active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('Cancelar') }}
                            </a>
                            <x-primary-button>
                                {{ __('Actualizar Menú') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
