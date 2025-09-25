<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Menús de') }} {{ $comercio->nombre }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('menus.create', $comercio) }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Crear Menú') }}
                </a>
                <a href="{{ route('comercios.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-300 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-400 dark:hover:bg-gray-600 focus:bg-gray-400 dark:focus:bg-gray-600 active:bg-gray-500 dark:active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Volver a Comercios') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Información del Comercio -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex items-center space-x-4">
                        @if($comercio->logo_url)
                            <img src="{{ asset('storage/' . $comercio->logo_url) }}" alt="Logo de {{ $comercio->nombre }}" class="w-16 h-16 object-cover rounded-lg">
                        @endif
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $comercio->nombre }}</h3>
                            @if($comercio->descripcion)
                                <p class="text-gray-600 dark:text-gray-300">{{ $comercio->descripcion }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Menús -->
            @if($menus->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($menus as $menu)
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <!-- Logo del comercio -->
                                @if($comercio->logo_url)
                                    <div class="w-full h-32 mb-4 rounded-lg overflow-hidden shadow-lg">
                                        <img src="{{ asset('storage/' . $comercio->logo_url) }}" alt="Logo de {{ $comercio->nombre }}" class="w-full h-full object-cover">
                                    </div>
                                @elseif($menu->archivo_original && $menu->formato !== 'pdf')
                                    <div class="w-full h-32 mb-4 rounded-lg overflow-hidden shadow-lg">
                                        <img src="{{ asset('storage/' . $menu->archivo_original) }}" alt="Menú de {{ $comercio->nombre }}" class="w-full h-full object-cover">
                                    </div>
                                @endif

                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center space-x-2">
                                        @if($menu->formato === 'pdf')
                                            <svg class="w-8 h-8 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                                            </svg>
                                        @else
                                            <svg class="w-8 h-8 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>
                                            </svg>
                                        @endif
                                        <span class="text-sm font-medium text-gray-900 dark:text-white uppercase">{{ $menu->formato }}</span>
                                    </div>
                                    <div class="flex items-center space-x-1">
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
                                    </div>
                                </div>

                                <div class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                    <p>Subido: {{ $menu->created_at->format('d/m/Y H:i') }}</p>
                                    @if($menu->procesado)
                                        <p>Procesado: {{ $menu->updated_at->format('d/m/Y H:i') }}</p>
                                    @endif
                                </div>

                                <div class="flex space-x-2">
                                    <a href="{{ route('menus.show', [$comercio, $menu]) }}" class="inline-flex items-center px-3 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        {{ __('Ver') }}
                                    </a>
                                    <a href="{{ route('menus.edit', [$comercio, $menu]) }}" class="inline-flex items-center px-3 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        {{ __('Editar') }}
                                    </a>
                                    <button onclick="confirmDeleteMenu({{ $menu->id }}, '{{ $menu->formato }}')" class="inline-flex items-center px-3 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        {{ __('Eliminar') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No hay menús</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Comienza subiendo el primer menú para este comercio.</p>
                        <div class="mt-6">
                            <a href="{{ route('menus.create', $comercio) }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('Crear primer menú') }}
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal de confirmación para eliminar menú -->
    <div id="deleteMenuModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900">
                    <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mt-2">¿Eliminar menú?</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        ¿Estás seguro de que quieres eliminar este menú? Esta acción no se puede deshacer.
                    </p>
                </div>
                <div class="flex justify-center space-x-4 mt-4">
                    <button onclick="closeDeleteMenuModal()" class="px-4 py-2 bg-gray-300 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-400 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 transition ease-in-out duration-150">
                        Cancelar
                    </button>
                    <button onclick="deleteMenu()" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition ease-in-out duration-150">
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let menuToDelete = null;

        function confirmDeleteMenu(id, formato) {
            menuToDelete = id;
            document.getElementById('deleteMenuModal').classList.remove('hidden');
        }

        function closeDeleteMenuModal() {
            document.getElementById('deleteMenuModal').classList.add('hidden');
            menuToDelete = null;
        }

        function deleteMenu() {
            if (menuToDelete) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/comercios/{{ $comercio->id }}/menus/${menuToDelete}`;
                
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                form.appendChild(csrfToken);
                
                const methodField = document.createElement('input');
                methodField.type = 'hidden';
                methodField.name = '_method';
                methodField.value = 'DELETE';
                form.appendChild(methodField);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</x-app-layout>
