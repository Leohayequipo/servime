<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Detalles del Menú') }} - {{ $comercio->nombre }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('menus.edit', [$comercio, $menu]) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Editar') }}
                </a>
                <a href="{{ route('menus.index', $comercio) }}" class="inline-flex items-center px-4 py-2 bg-gray-300 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-400 dark:hover:bg-gray-600 focus:bg-gray-400 dark:focus:bg-gray-600 active:bg-gray-500 dark:active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Volver') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Información del Menú -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            @if($menu->formato === 'pdf')
                                <svg class="w-10 h-10 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                                </svg>
                            @else
                                <svg class="w-10 h-10 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>
                                </svg>
                            @endif
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Menú {{ strtoupper($menu->formato) }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Archivo original</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            @if($menu->procesado)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    Procesado
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                    </svg>
                                    Procesando
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Información del archivo -->
                        <div>
                            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Información del Archivo</h4>
                            <dl class="space-y-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tipo de archivo:</dt>
                                    <dd class="text-sm text-gray-900 dark:text-white">{{ strtoupper($menu->formato) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Subido:</dt>
                                    <dd class="text-sm text-gray-900 dark:text-white">{{ $menu->created_at->format('d/m/Y H:i') }}</dd>
                                </div>
                                @if($menu->procesado)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Procesado:</dt>
                                        <dd class="text-sm text-gray-900 dark:text-white">{{ $menu->updated_at->format('d/m/Y H:i') }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>

                        <!-- Acciones -->
                        <div>
                            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Acciones</h4>
                            <div class="space-y-2">
                                <a href="{{ asset('storage/' . $menu->archivo_original) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 w-full justify-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    Ver Archivo Original
                                </a>
                                <a href="{{ route('menus.edit', [$comercio, $menu]) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150 w-full justify-center">
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
            @if($menu->procesado && $menu->texto_extraido)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Contenido Extraído</h4>
                        <div class="prose dark:prose-invert max-w-none">
                            <pre class="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">{{ $menu->texto_extraido }}</pre>
                        </div>
                    </div>
                </div>
            @elseif($menu->procesado)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Contenido Procesado</h4>
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No hay contenido procesado</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">El menú fue procesado pero no se extrajo contenido.</p>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Procesamiento</h4>
                        <div class="text-center py-8">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto"></div>
                            <h3 class="mt-4 text-sm font-medium text-gray-900 dark:text-white">Procesando menú...</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">El menú se está procesando automáticamente. Vuelve más tarde para ver el contenido extraído.</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
