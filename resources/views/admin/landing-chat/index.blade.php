<x-app-layout>
    <div class="min-h-screen bg-background">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-10">
                <div>
                    <h1 class="text-3xl font-bold text-text-primary">Chat del Landing</h1>
                    <p class="text-text-secondary mt-1">Conversaciones del asistente de ventas de la landing page</p>
                </div>
                <a href="{{ route('dashboard') }}" class="btn-secondary">← Dashboard</a>
            </div>

            <!-- Métricas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="metric-card">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-accent-blue/10 rounded-xl flex items-center justify-center flex-shrink-0">
                            <span class="text-xl">💬</span>
                        </div>
                        <div>
                            <p class="text-text-secondary text-sm">Total Mensajes</p>
                            <p class="text-2xl font-bold text-text-primary">{{ number_format($totalMensajes) }}</p>
                        </div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-accent-green/10 rounded-xl flex items-center justify-center flex-shrink-0">
                            <span class="text-xl">👤</span>
                        </div>
                        <div>
                            <p class="text-text-secondary text-sm">Sesiones únicas</p>
                            <p class="text-2xl font-bold text-text-primary">{{ number_format($totalSesiones) }}</p>
                        </div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-yellow-500/10 rounded-xl flex items-center justify-center flex-shrink-0">
                            <span class="text-xl">📅</span>
                        </div>
                        <div>
                            <p class="text-text-secondary text-sm">Hoy</p>
                            <p class="text-2xl font-bold text-text-primary">{{ $hoy }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <form method="GET" class="bg-secondary-dark border border-accent-gray rounded-2xl p-5 mb-6">
                <div class="flex flex-col sm:flex-row gap-3">
                    <input
                        type="text"
                        name="session_id"
                        value="{{ request('session_id') }}"
                        placeholder="Filtrar por session ID..."
                        class="flex-1 bg-primary-dark border border-accent-gray rounded-xl px-4 py-2.5 text-sm text-text-primary placeholder-text-secondary focus:outline-none focus:border-accent-blue"
                    >
                    <button type="submit" class="btn-primary px-6">Filtrar</button>
                    @if(request('session_id'))
                        <a href="{{ route('admin.landing-chat.index') }}" class="btn-secondary px-6 text-center">Limpiar</a>
                    @endif
                </div>
            </form>

            <!-- Tabla de mensajes -->
            <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-9 h-9 bg-accent-blue/10 rounded-lg flex items-center justify-center">
                        <span>🤖</span>
                    </div>
                    <h3 class="font-bold text-text-primary">Historial de conversaciones</h3>
                    <span class="ml-auto text-text-secondary text-sm">{{ $mensajes->total() }} mensajes</span>
                </div>

                @forelse($mensajes as $mensaje)
                    <div class="border border-accent-gray/50 rounded-xl p-4 mb-4 last:mb-0">
                        <div class="flex flex-wrap items-center gap-3 mb-3 text-xs text-text-secondary">
                            <span class="font-mono bg-primary-dark px-2 py-0.5 rounded">{{ $mensaje->session_id }}</span>
                            <span>{{ $mensaje->user_ip }}</span>
                            <span class="ml-auto">{{ $mensaje->created_at->format('d/m/Y H:i') }}</span>
                        </div>

                        <div class="space-y-2">
                            <div class="flex gap-3">
                                <span class="text-xs font-medium text-accent-blue w-16 flex-shrink-0 pt-0.5">Usuario</span>
                                <p class="text-sm text-text-primary bg-primary-dark rounded-lg px-3 py-2 flex-1">{{ $mensaje->user_message }}</p>
                            </div>
                            <div class="flex gap-3">
                                <span class="text-xs font-medium text-accent-green w-16 flex-shrink-0 pt-0.5">Bot</span>
                                <p class="text-sm text-text-secondary bg-primary-dark rounded-lg px-3 py-2 flex-1 whitespace-pre-line">{{ $mensaje->bot_response }}</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center">
                        <div class="w-14 h-14 bg-accent-gray/30 rounded-full flex items-center justify-center mx-auto mb-3">
                            <span class="text-2xl">💬</span>
                        </div>
                        <p class="text-text-secondary text-sm">No hay mensajes registrados.</p>
                    </div>
                @endforelse

                @if($mensajes->hasPages())
                    <div class="mt-6">
                        {{ $mensajes->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
