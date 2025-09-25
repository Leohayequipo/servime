<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Menús de') }} {{ $comercio->nombre }}
            </h2>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('menus.create', $comercio) }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('Crear Menú') }}
                            </a>
                            <a href="{{ route('menu.public', $comercio) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                                Ver Página Pública
                            </a>
                            <button onclick="generateQR()" class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 focus:bg-purple-700 active:bg-purple-900 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                                </svg>
                                Generar QR
                            </button>
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
                                <!-- Imagen del menú -->
                                @if($menu->archivo_original && $menu->formato !== 'pdf')
                                    <div class="w-full h-32 mb-4 rounded-lg overflow-hidden shadow-lg">
                                        <img src="{{ asset('storage/' . $menu->archivo_original) }}" alt="Menú de {{ $comercio->nombre }}" class="w-full h-full object-cover">
                                    </div>
                                @elseif($comercio->logo_url)
                                    <div class="w-full h-32 mb-4 rounded-lg overflow-hidden shadow-lg">
                                        <img src="{{ asset('storage/' . $comercio->logo_url) }}" alt="Logo de {{ $comercio->nombre }}" class="w-full h-full object-cover">
                                    </div>
                                @endif


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
                    <div class="p-12 text-center">
                        <!-- Icono proporcionado y elegante -->
                        <div class="mx-auto w-16 h-16 bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900 dark:to-purple-900 rounded-full flex items-center justify-center mb-6">
                            <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        
                        <!-- Título más prominente -->
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">No hay menús aún</h3>
                        <p class="text-lg text-gray-600 dark:text-gray-400 mb-8 max-w-md mx-auto">
                            Comienza creando el primer menú para <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ $comercio->nombre }}</span>
                        </p>
                        
                        <!-- Botón más atractivo -->
                        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                            <a href="{{ route('menus.create', $comercio) }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 border border-transparent rounded-lg font-semibold text-sm text-white uppercase tracking-widest hover:from-indigo-700 hover:to-purple-700 focus:from-indigo-700 focus:to-purple-700 active:from-indigo-800 active:to-purple-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                {{ __('Crear primer menú') }}
                            </a>
                            
                            <a href="{{ route('comercios.index') }}" class="inline-flex items-center px-6 py-3 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg font-semibold text-sm text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-200 dark:hover:bg-gray-600 focus:bg-gray-200 dark:focus:bg-gray-600 active:bg-gray-300 dark:active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                {{ __('Volver a comercios') }}
                            </a>
                        </div>
                        
                        <!-- Información adicional -->
                        <div class="mt-8 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg max-w-2xl mx-auto">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-semibold">💡 Tip:</span> Puedes subir menús en formato PDF o imagen. Nuestro sistema de IA procesará automáticamente el contenido para extraer la información de platos y precios.
                            </p>
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

        function generateQR() {
            const publicUrl = '{{ route("menu.public", $comercio) }}';
            const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(publicUrl)}`;
            
            // Crear modal para mostrar QR
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-lg p-6 max-w-sm mx-auto">
                    <div class="text-center">
                        <h3 class="text-lg font-semibold mb-4">Código QR para {{ $comercio->nombre }}</h3>
                        <img src="${qrUrl}" alt="QR Code" class="mx-auto mb-4">
                        <p class="text-sm text-gray-600 mb-4">Los clientes pueden escanear este código para acceder al menú digital</p>
                        <div class="flex space-x-2">
                            <button onclick="downloadQR('${qrUrl}')" class="flex-1 bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">
                                Descargar
                            </button>
                            <button onclick="closeQRModal()" class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
        }

        function downloadQR(qrUrl) {
            const link = document.createElement('a');
            link.href = qrUrl;
            link.download = 'qr-{{ $comercio->nombre }}-menu.png';
            link.click();
        }

        function closeQRModal() {
            const modal = document.querySelector('.fixed.inset-0');
            if (modal) {
                modal.remove();
            }
        }
    </script>
</x-app-layout>
