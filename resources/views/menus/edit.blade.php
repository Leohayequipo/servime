<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-text-primary leading-tight">
                {{ __('Editar Menú') }} - {{ $comercio->nombre }}
            </h2>
            <a href="{{ route('menus.index', $comercio) }}" class="inline-flex items-center px-4 py-2 bg-accent-gray border border-transparent rounded-md font-semibold text-xs text-text-primary uppercase tracking-widest hover:bg-accent-gray focus:bg-accent-gray active:bg-accent-gray focus:outline-none focus:ring-2 focus:ring-accent-blue focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('Volver') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-text-primary">
                    <form method="POST" action="{{ route('menus.update', [$comercio, $menu]) }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        @method('PUT')
                        
                        <!-- Información del Comercio -->
                        <div class="bg-secondary-dark p-4 rounded-lg mb-6">
                            <h3 class="text-lg font-medium text-text-primary mb-2">Comercio: {{ $comercio->nombre }}</h3>
                            @if($comercio->descripcion)
                                <p class="text-sm text-text-secondary">{{ $comercio->descripcion }}</p>
                            @endif
                        </div>

                        <!-- Archivo actual -->
                        @if($menu->archivo_original)
                            <div class="mb-6">
                                <x-input-label :value="__('Archivo Actual')" />
                                <div class="mt-2 flex items-center space-x-4">
                                    @if($menu->formato === 'pdf')
                                        <svg class="w-12 h-12 text-danger" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                                        </svg>
                                    @else
                                        <img src="{{ asset('storage/' . $menu->archivo_original) }}" alt="Archivo actual" class="w-12 h-12 object-cover rounded">
                                    @endif
                                    <div>
                                        <p class="text-sm font-medium text-text-primary">{{ strtoupper($menu->formato) }}</p>
                                        <p class="text-sm text-text-secondary">Subido: {{ $menu->created_at->format('d/m/Y H:i') }}</p>
                                        <a href="{{ asset('storage/' . $menu->archivo_original) }}" target="_blank" class="text-sm text-accent-blue hover:text-accent-blue">Ver archivo</a>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Nuevo archivo -->
                        <div>
                            <x-input-label for="archivo" :value="__('Nuevo Archivo (opcional)')" />
                            <input type="file" id="archivo" name="archivo" accept=".pdf,.jpeg,.jpg,.png,.gif" class="block mt-1 w-full text-sm text-text-secondary file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-accent-blue/10 file:text-accent-blue hover:file:bg-accent-blue/15" />
                            <x-input-error :messages="$errors->get('archivo')" class="mt-2" />
                            <p class="mt-1 text-sm text-text-secondary">
                                Deja vacío para mantener el archivo actual. Formatos permitidos: PDF, JPEG, PNG, JPG, GIF. Tamaño máximo: 10MB
                            </p>
                        </div>

                        <!-- Tipo de Archivo -->
                        <div>
                            <x-input-label for="formato" :value="__('Tipo de Archivo')" />
                            <select id="formato" name="formato" required class="border-accent-gray bg-secondary-dark text-text-primary focus:border-accent-blue focus:ring-accent-blue rounded-md shadow-sm block mt-1 w-full">
                                <option value="">Selecciona el tipo de archivo</option>
                                <option value="pdf" {{ old('formato', $menu->formato) == 'pdf' ? 'selected' : '' }}>PDF</option>
                                <option value="image" {{ old('formato', $menu->formato) == 'image' ? 'selected' : '' }}>Imagen (JPEG, PNG, JPG, GIF)</option>
                            </select>
                            <x-input-error :messages="$errors->get('formato')" class="mt-2" />
                        </div>

                        <!-- Estado del procesamiento -->
                        <div class="bg-secondary-dark p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-text-primary mb-2">Estado del Procesamiento</h4>
                            <div class="flex items-center space-x-2">
                                @if($menu->procesado)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-accent-green/15 text-accent-green">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        Procesado
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-warning/15 text-warning">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                        </svg>
                                        Procesando
                                    </span>
                                @endif
                                <span class="text-sm text-text-secondary">
                                    Última actualización: {{ $menu->updated_at->format('d/m/Y H:i') }}
                                </span>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="flex items-center justify-end mt-4 space-x-4">
                            <a href="{{ route('menus.index', $comercio) }}" class="inline-flex items-center px-4 py-2 bg-accent-gray border border-transparent rounded-md font-semibold text-xs text-text-primary uppercase tracking-widest hover:bg-accent-gray focus:bg-accent-gray active:bg-accent-gray focus:outline-none focus:ring-2 focus:ring-accent-blue focus:ring-offset-2 transition ease-in-out duration-150">
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

