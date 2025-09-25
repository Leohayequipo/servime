<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                @if($comercio->logo_url)
                    <img src="{{ asset('storage/' . $comercio->logo_url) }}" 
                         alt="{{ $comercio->nombre }}" 
                         class="w-8 h-8 rounded-full object-cover">
                @endif
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        📊 Analytics - {{ $comercio->nombre }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Análisis detallado del chatbot</p>
                </div>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('analytics.dashboard') }}" 
                   class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    ← Dashboard General
                </a>
                <a href="{{ route('analytics.export', $comercio->id) }}" 
                   class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    📥 Exportar Datos
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Estadísticas del Comercio -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Conversaciones -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Conversaciones</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_conversations'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Conversaciones Hoy -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Conversaciones Hoy</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['conversations_today'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Uso de OpenAI -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Con OpenAI</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['openai_usage'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tiempo Promedio -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-orange-100 dark:bg-orange-900 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tiempo Promedio</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ $stats['avg_response_time'] ? round($stats['avg_response_time']) . 'ms' : 'N/A' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preguntas Más Frecuentes -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">❓ Preguntas Más Frecuentes</h3>
                        @if($frequentQuestions->count() > 0)
                            <div class="space-y-3">
                                @foreach($frequentQuestions as $question)
                                    <div class="border-l-4 border-blue-500 pl-4 py-2">
                                        <p class="text-sm text-gray-800 dark:text-gray-200 font-medium">
                                            "{{ $question->user_message }}"
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $question->frequency }} vez{{ $question->frequency > 1 ? 'es' : '' }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 dark:text-gray-400 text-center py-4">
                                No hay preguntas frecuentes registradas
                            </p>
                        @endif
                    </div>
                </div>

                <!-- Estadísticas Diarias -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">📈 Actividad de los Últimos 7 Días</h3>
                        @if($dailyStats->count() > 0)
                            <div class="space-y-3">
                                @foreach($dailyStats as $day)
                                    <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ \Carbon\Carbon::parse($day->date)->format('d/m/Y') }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $day->openai_count }}/{{ $day->total }} con IA
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-bold text-gray-900 dark:text-white">
                                                {{ $day->total }} chats
                                            </p>
                                            @if($day->avg_response_time)
                                                <p class="text-xs text-gray-500">
                                                    {{ round($day->avg_response_time) }}ms promedio
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 dark:text-gray-400 text-center py-4">
                                No hay actividad registrada en los últimos 7 días
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Historial de Conversaciones -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">💬 Historial de Conversaciones</h3>
                    
                    @if($conversations->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Fecha
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Usuario
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Mensaje
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Respuesta
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            IA
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Tiempo
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($conversations as $conversation)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ $conversation->created_at->format('d/m H:i') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ $conversation->user_ip ?? 'Desconocido' }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white max-w-xs truncate">
                                                {{ $conversation->user_message }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white max-w-xs truncate">
                                                {{ $conversation->bot_response }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($conversation->used_openai)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                        🤖 OpenAI
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                                        🔧 Básico
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ $conversation->response_time_ms ? $conversation->response_time_ms . 'ms' : 'N/A' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <div class="mt-6">
                            {{ $conversations->links() }}
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No hay conversaciones</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Aún no se han registrado conversaciones para este comercio.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
