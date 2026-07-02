<x-app-layout>
    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h2 class="font-semibold text-2xl text-text-primary leading-tight">📊 Dashboard de Analytics</h2>
                    <p class="text-sm text-text-secondary mt-0.5">Resumen del sistema</p>
                </div>
                <a href="{{ route('comercios.index') }}"
                   class="bg-secondary-dark hover:bg-accent-gray text-text-primary font-medium py-2 px-4 rounded-lg border border-accent-gray">
                    ← Volver a Comercios
                </a>
            </div>

            <!-- Estadísticas Generales -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Comercios -->
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-text-secondary">Total Comercios</p>
                                <p class="text-2xl font-bold text-text-primary">{{ $stats['total_comercios'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Menús -->
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-text-secondary">Total Menús</p>
                                <p class="text-2xl font-bold text-text-primary">{{ $stats['total_menus'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Conversaciones Hoy -->
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-text-secondary">Chats Hoy</p>
                                <p class="text-2xl font-bold text-text-primary">{{ $stats['conversations_today'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Conversaciones -->
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-orange-100 dark:bg-orange-900 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-text-secondary">Total Chats</p>
                                <p class="text-2xl font-bold text-text-primary">{{ $stats['total_conversations'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Uso de OpenAI -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-text-primary mb-4">🤖 Uso de OpenAI</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-text-secondary">Respuestas con IA</span>
                                <span class="text-sm font-medium text-green-600">{{ $stats['openai_usage'] }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-text-secondary">Respuestas básicas</span>
                                <span class="text-sm font-medium text-orange-600">{{ $stats['fallback_usage'] }}</span>
                            </div>
                            <div class="w-full bg-accent-gray/40 rounded-full h-2">
                                @php
                                    $total = $stats['openai_usage'] + $stats['fallback_usage'];
                                    $openaiPercentage = $total > 0 ? round(($stats['openai_usage'] / $total) * 100) : 0;
                                @endphp
                                <div class="bg-green-600 h-2 rounded-full" style="width: {{ $openaiPercentage }}%"></div>
                            </div>
                            <p class="text-xs text-text-secondary">{{ $openaiPercentage }}% usa OpenAI</p>
                        </div>
                    </div>
                </div>

                <div class="bg-secondary-dark border border-accent-gray rounded-2xl overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-text-primary mb-4">📈 Resumen del Sistema</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-text-secondary">Comercios activos</span>
                                <span class="text-sm font-medium">{{ $stats['total_comercios'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-text-secondary">Menús procesados</span>
                                <span class="text-sm font-medium">{{ $stats['total_menus'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-text-secondary">Conversaciones totales</span>
                                <span class="text-sm font-medium">{{ $stats['total_conversations'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-text-secondary">Chats hoy</span>
                                <span class="text-sm font-medium text-green-600">{{ $stats['conversations_today'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Comercios con Métricas -->
            <div class="bg-secondary-dark border border-accent-gray rounded-2xl overflow-hidden">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-text-primary mb-6">🏪 Comercios y sus Métricas</h3>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-accent-gray/50">
                            <thead class="bg-primary-dark">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                        Comercio
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                        Menús
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                        Conversaciones
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                        Última Actividad
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-accent-gray/50">
                                @forelse($comercios as $comercio)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                @if($comercio->logo_url)
                                                    <img class="h-4 w-4 rounded-full object-cover" 
                                                         src="{{ asset('storage/' . $comercio->logo_url) }}" 
                                                         alt="{{ $comercio->nombre }}">
                                                @else
                                                    <div class="h-4 w-4 rounded-full bg-accent-gray flex items-center justify-center">
                                                        <span class="text-text-primary text-xs font-medium">{{ substr($comercio->nombre, 0, 1) }}</span>
                                                    </div>
                                                @endif
                                                <div class="ml-3">
                                                    <div class="text-sm font-medium text-text-primary">
                                                        {{ $comercio->nombre }}
                                                    </div>
                                                    <div class="text-xs text-text-secondary">
                                                        {{ $comercio->descripcion ?? 'Sin descripción' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                {{ $comercio->menus_count }} menús
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                {{ $comercio->chat_conversations_count }} chats
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                                            @if($comercio->chat_conversations_count > 0)
                                                {{ $comercio->chatConversations()->latest()->first()?->created_at?->diffForHumans() ?? 'Nunca' }}
                                            @else
                                                Nunca
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex items-center gap-2">
                                                <a href="{{ route('analytics.comercio', $comercio) }}"
                                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-accent-blue text-background rounded-lg text-xs font-bold hover:opacity-90 transition-opacity shadow shadow-accent-blue/30">
                                                    📊 Ver Analytics
                                                </a>
                                                <a href="{{ route('analytics.export', $comercio) }}"
                                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-accent-green/10 hover:bg-accent-green/20 text-accent-green border border-accent-green/30 rounded-lg text-xs font-medium transition-colors">
                                                    📥 Exportar
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-text-secondary">
                                            No hay comercios registrados
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if($iaRuns->isNotEmpty() || $iaRunsMensajes > 0)
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl overflow-hidden mt-8">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-text-primary">✨ Uso de clasificación con IA</h3>
                            <span class="text-sm text-text-secondary">{{ number_format($iaRunsMensajes) }} mensajes clasificados en total</span>
                        </div>
                        @if($iaRuns->isNotEmpty())
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-accent-gray/50">
                                    <thead class="bg-primary-dark">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase">Fecha</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase">Comercio</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase">Mensajes</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase">Modelo</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-accent-gray/50">
                                        @foreach($iaRuns as $run)
                                            <tr>
                                                <td class="px-6 py-3 text-sm text-text-secondary">{{ $run->created_at->format('d/m/Y H:i') }}</td>
                                                <td class="px-6 py-3 text-sm text-text-primary">{{ $run->comercio->nombre ?? '—' }}</td>
                                                <td class="px-6 py-3 text-sm text-text-primary">{{ $run->mensajes_procesados }}</td>
                                                <td class="px-6 py-3 text-sm text-text-secondary">{{ $run->modelo }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-sm text-text-secondary">Todavía nadie usó la reclasificación con IA.</p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

