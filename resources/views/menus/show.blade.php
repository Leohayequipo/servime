<x-app-layout>
    <x-slot name="head">
        <style>
        .logo-redondo {
            border-radius: 50% !important;
            overflow: hidden !important;
        }
        .logo-redondo img {
            border-radius: 50% !important;
            display: block !important;
            width: 100% !important;
            height: 100% !important;
            object-fit: contain !important;
        }
        </style>
    </x-slot>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-text-primary leading-tight">
                {{ __('Detalles del Menú') }} - {{ $comercio->nombre }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('menus.edit', [$comercio, $menu]) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Editar') }}
                </a>
                <a href="{{ route('menus.index', $comercio) }}" class="inline-flex items-center px-4 py-2 bg-accent-gray border border-transparent rounded-md font-semibold text-xs text-text-primary uppercase tracking-widest hover:bg-accent-gray focus:bg-accent-gray active:bg-accent-gray focus:outline-none focus:ring-2 focus:ring-accent-blue focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Volver') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Información del Menú -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-4">
                            @if($comercio->logo_url)
                                <div class="w-9 h-9 shadow-md border border-accent-blue/25" style="border-radius: 50% !important; overflow: hidden !important; width: 36px !important; height: 36px !important;">
                                    <img src="{{ asset('storage/' . $comercio->logo_url) }}" alt="Logo de {{ $comercio->nombre }}" class="w-full h-full object-cover" style="border-radius: 50% !important; width: 100% !important; height: 100% !important;">
                                </div>
                            @endif
                            <div>
                                <h3 class="text-lg font-semibold text-text-primary">{{ $comercio->nombre }}</h3>
                                <p class="text-sm text-text-secondary">Menú {{ strtoupper($menu->formato) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Estado Procesado abajo -->
                    <div class="flex justify-end mb-4">
                        @if($menu->procesado)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                Procesado
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-warning/15 text-warning">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                </svg>
                                Procesando
                            </span>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Información del archivo -->
                        <div>
                            <h4 class="text-md font-medium text-text-primary mb-3">Información del Archivo</h4>
                            <dl class="space-y-2">
                                <div>
                                    <dt class="text-sm font-medium text-text-secondary">Tipo de archivo:</dt>
                                    <dd class="text-sm text-text-primary">{{ strtoupper($menu->formato) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-text-secondary">Subido:</dt>
                                    <dd class="text-sm text-text-primary">{{ $menu->created_at->format('d/m/Y H:i') }}</dd>
                                </div>
                                @if($menu->procesado)
                                    <div>
                                        <dt class="text-sm font-medium text-text-secondary">Procesado:</dt>
                                        <dd class="text-sm text-text-primary">{{ $menu->updated_at->format('d/m/Y H:i') }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>

                        <!-- Acciones -->
                        <div>
                            <h4 class="text-md font-medium text-text-primary mb-3">Acciones</h4>
                            <div class="space-y-2">
                                <button onclick="toggleMenuImage()" class="inline-flex items-center px-4 py-2 bg-accent-blue border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-accent-blue focus:bg-accent-blue active:bg-accent-blue/20 focus:outline-none focus:ring-2 focus:ring-accent-blue focus:ring-offset-2 transition ease-in-out duration-150 w-full justify-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    <span id="buttonText">Ver Imagen del Menú</span>
                                </button>
                                <a href="{{ route('menus.edit', [$comercio, $menu]) }}" class="inline-flex items-center px-4 py-2 bg-accent-gray border border-transparent rounded-md font-semibold text-xs text-text-primary uppercase tracking-widest hover:bg-accent-gray focus:bg-accent-gray active:bg-accent-gray focus:outline-none focus:ring-2 focus:ring-accent-blue focus:ring-offset-2 transition ease-in-out duration-150 w-full justify-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Editar Menú
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenido procesado (si está disponible) -->
            @if($menu->procesado && ($menu->texto_extraido || $menu->contexto_gpt))
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="text-lg font-medium text-text-primary mb-4">Contenido Extraído</h4>
                        
                        @php
                            // Obtener el contenido JSON desde contexto_gpt o texto_extraido
                            $rawContent = $menu->contexto_gpt ?? $menu->texto_extraido;
                            
                            // Limpiar el contenido: remover ```json y ``` si están presentes
                            $jsonContent = $rawContent;
                            if (strpos($jsonContent, '```json') !== false) {
                                $jsonContent = preg_replace('/```json\s*/', '', $jsonContent);
                                $jsonContent = preg_replace('/```\s*$/', '', $jsonContent);
                            }
                            
                            // Decodificar el JSON
                            $contenido = json_decode(trim($jsonContent), true);
                        @endphp
                        
                        @if($contenido && is_array($contenido))
                            <div class="space-y-6">
                                <!-- Nombre del restaurante -->
                                @if(isset($contenido['nombre_del_restaurante']))
                                    <div class="text-center">
                                        <h3 class="text-2xl font-bold text-text-primary mb-2">
                                            {{ $contenido['nombre_del_restaurante'] }}
                                        </h3>
                                        <div class="w-24 h-1 bg-accent-blue mx-auto rounded"></div>
                                    </div>
                                @endif
                                
                                <!-- Categorías y platos -->
                                @if(isset($contenido['categorías_de_platos']) && is_array($contenido['categorías_de_platos']))
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                        @foreach($contenido['categorías_de_platos'] as $categoria => $platos)
                                            <div class="bg-secondary-dark rounded-lg p-4">
                                                <h4 class="text-lg font-semibold text-text-primary mb-3 border-b border-accent-gray pb-2">
                                                    {{ $categoria }}
                                                </h4>
                                                <div class="space-y-2">
                                                    @if(is_array($platos))
                                                        @foreach($platos as $plato)
                                                            <div class="flex justify-between items-center py-2 border-b border-accent-gray last:border-b-0">
                                                                <span class="text-text-primary font-medium">
                                                                    {{ $plato['nombre'] ?? 'Sin nombre' }}
                                                                </span>
                                                                <span class="text-accent-blue font-bold">
                                                                    {{ $plato['precio'] ?? 'Sin precio' }}
                                                                </span>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @else
                            <!-- Fallback: mostrar JSON si no se puede parsear -->
                            <div class="prose max-w-none">
                                <pre class="whitespace-pre-wrap text-sm text-text-primary bg-secondary-dark p-4 rounded-lg">{{ $rawContent }}</pre>
                            </div>
                        @endif
                    </div>
                </div>
            @elseif($menu->procesado)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="text-lg font-medium text-text-primary mb-4">Contenido Procesado</h4>
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-text-primary">No hay contenido procesado</h3>
                            <p class="mt-1 text-sm text-text-secondary">El menú fue procesado pero no se extrajo contenido.</p>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="text-lg font-medium text-text-primary mb-4">Procesamiento</h4>
                        <div class="text-center py-8">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-accent-blue mx-auto"></div>
                            <h3 class="mt-4 text-sm font-medium text-text-primary">Procesando menú...</h3>
                            <p class="mt-1 text-sm text-text-secondary">El menú se está procesando automáticamente. Vuelve más tarde para ver el contenido extraído.</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Modal para mostrar la imagen del menú -->
            @if($menu->archivo_original && $menu->formato !== 'pdf')
                <div id="menuImageModal" class="fixed inset-0 bg-black bg-opacity-75 overflow-y-auto h-full w-full hidden z-50">
                    <div class="relative top-4 mx-auto p-5 border w-11/12 max-w-6xl shadow-lg rounded-md bg-white">
                        <!-- Header del modal -->
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-text-primary">Menú de {{ $comercio->nombre }}</h3>
                            <button onclick="closeMenuImageModal()" class="text-text-secondary hover:text-text-secondary">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <!-- Imagen del menú -->
                        <div class="max-h-screen overflow-auto">
                            <img src="{{ asset('storage/' . $menu->archivo_original) }}" alt="Menú de {{ $comercio->nombre }}" class="w-full h-auto object-contain">
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        function toggleMenuImage() {
            const modal = document.getElementById('menuImageModal');
            if (modal) {
                modal.classList.remove('hidden');
                // Prevenir scroll del body cuando el modal está abierto
                document.body.style.overflow = 'hidden';
            }
        }

        function closeMenuImageModal() {
            const modal = document.getElementById('menuImageModal');
            if (modal) {
                modal.classList.add('hidden');
                // Restaurar scroll del body
                document.body.style.overflow = 'auto';
            }
        }

        // Cerrar modal con la tecla Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeMenuImageModal();
            }
        });

        // Cerrar modal haciendo clic fuera del contenido
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('menuImageModal');
            if (modal && event.target === modal) {
                closeMenuImageModal();
            }
        });
    </script>
</x-app-layout>

