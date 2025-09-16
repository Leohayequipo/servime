<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Mis Comercios') }}
            </h2>
            <a href="{{ route('comercios.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
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
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ $comercio->nombre }}</h3>
                                @if($comercio->descripcion)
                                    <p class="text-gray-600 dark:text-gray-300 mb-3">{{ $comercio->descripcion }}</p>
                                @endif
                                @if($comercio->direccion)
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                                        <span class="font-medium">Dirección:</span> {{ $comercio->direccion }}
                                    </p>
                                @endif
                                @if($comercio->horario)
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                        <span class="font-medium">Horario:</span> {{ $comercio->horario }}
                                    </p>
                                @endif
                                <div class="flex space-x-2">
                                    <a href="{{ route('comercios.edit', $comercio) }}" class="inline-flex items-center px-3 py-2 bg-blue-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-900 focus:bg-blue-900 active:bg-blue-950 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        {{ __('Editar') }}
                                    </a>
                                    <a href="#" class="inline-flex items-center px-3 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        {{ __('Crear Menú') }}
                                    </a>
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
</x-app-layout>