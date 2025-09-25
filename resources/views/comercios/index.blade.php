<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Mis Comercios') }}
            </h2>
            <a href="{{ route('comercios.create') }}" class="group relative inline-flex items-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-indigo-700 border border-transparent rounded-lg font-semibold text-sm text-white shadow-lg hover:from-indigo-700 hover:to-indigo-800 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200 transform hover:scale-105">
                <svg class="w-5 h-5 mr-2 group-hover:rotate-12 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                {{ __('Crear Comercio') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if($comercios->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($comercios as $comercio)
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <!-- Logo que ocupa todo el ancho -->
                                @if($comercio->logo_url)
                                    <div class="w-full h-32 mb-4 rounded-lg overflow-hidden shadow-lg">
                                        <img src="{{ asset('storage/' . $comercio->logo_url) }}" alt="Logo de {{ $comercio->nombre }}" class="w-full h-full object-cover">
                                    </div>
                                @endif
                                
                                <!-- Información del comercio centrada -->
                                <div class="text-center mb-6">
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ $comercio->nombre }}</h3>
                                    @if($comercio->descripcion)
                                        <p class="text-gray-600 dark:text-gray-300 mb-3">{{ $comercio->descripcion }}</p>
                                    @endif
                                    @if($comercio->direccion)
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">
                                            <span class="font-medium">Dirección:</span> {{ $comercio->direccion }}
                                        </p>
                                    @endif
                                    @if($comercio->horario)
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            <span class="font-medium">Horario:</span> {{ $comercio->horario }}
                                        </p>
                                    @endif
                                </div>
                                
                                <!-- Botón principal "Ver Menús" -->
                                <div class="flex justify-center mb-4">
                                    <a href="{{ route('menus.index', $comercio) }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-600 to-purple-700 border border-transparent rounded-lg font-semibold text-sm text-white shadow-lg hover:from-purple-700 hover:to-purple-800 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-all duration-200 transform hover:scale-105">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        {{ __('Ver Menús') }}
                                    </a>
                                </div>
                                
                                <!-- Botones secundarios -->
                                <div class="flex flex-wrap gap-2 justify-center">
                                    <a href="{{ route('comercios.customize', $comercio) }}" class="inline-flex items-center px-3 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 focus:bg-purple-700 active:bg-purple-900 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17v4a2 2 0 002 2h4M15 7l3 3"></path>
                                        </svg>
                                        {{ __('Personalizar') }}
                                    </a>
                                    <a href="{{ route('comercios.edit', $comercio) }}" class="inline-flex items-center px-3 py-2 bg-blue-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-900 focus:bg-blue-900 active:bg-blue-950 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        {{ __('Editar') }}
                                    </a>
                                    <a href="{{ route('menus.create', $comercio) }}" class="inline-flex items-center px-3 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        {{ __('Crear Menú') }}
                                    </a>
                                    <button onclick="confirmDeleteComercio({{ $comercio->id }}, '{{ $comercio->nombre }}')" class="inline-flex items-center px-3 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
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
                        <p class="text-gray-500 dark:text-gray-400 mb-4">No tienes comercios creados aún.</p>
                        <a href="{{ route('comercios.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Crear tu primer comercio') }}
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal de confirmación para eliminar comercio -->
    <div id="deleteComercioModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900">
                    <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mt-2">¿Eliminar comercio?</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        ¿Estás seguro de que quieres eliminar el comercio <strong id="comercioName"></strong>? Esta acción no se puede deshacer y también se eliminarán todos los menús asociados.
                    </p>
                </div>
                <div class="flex justify-center space-x-4 mt-4">
                    <button onclick="closeDeleteComercioModal()" class="px-4 py-2 bg-gray-300 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-400 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 transition ease-in-out duration-150">
                        Cancelar
                    </button>
                    <button onclick="deleteComercio()" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition ease-in-out duration-150">
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let comercioToDelete = null;

        function confirmDeleteComercio(id, nombre) {
            comercioToDelete = id;
            document.getElementById('comercioName').textContent = nombre;
            document.getElementById('deleteComercioModal').classList.remove('hidden');
        }

        function closeDeleteComercioModal() {
            document.getElementById('deleteComercioModal').classList.add('hidden');
            comercioToDelete = null;
        }

        function deleteComercio() {
            if (comercioToDelete) {
                // Crear un formulario oculto para eliminar el comercio
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/comercios/${comercioToDelete}`;
                
                // Agregar token CSRF
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                form.appendChild(csrfToken);
                
                // Agregar método DELETE
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