@php
    function parseBotResponse($text) {
        $text = e($text);
        $text = nl2br($text);
        $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $text);
        $text = preg_replace('/\[IMAGEN:([^\]]+)\]/', '<img src="$1" alt="Imagen" class="w-full max-w-xs rounded-lg shadow-lg object-cover" style="max-height: 200px;" onerror="this.style.display=\'none\'">', $text);
        return $text;
    }
@endphp

<x-app-layout>
    <div class="min-h-screen bg-background">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

            <!-- Welcome -->
            <div class="mb-10">
                <h1 class="text-4xl font-bold text-text-primary mb-1">
                    Bienvenido, {{ auth()->user()->name }}
                </h1>
                <p class="text-text-secondary">
                    {{ now()->format('l d \d\e F, Y') }}
                </p>
            </div>

            <!-- Aviso de cuota de IA agotada -->
            @if(!empty($comerciosSinCuotaIA) && count($comerciosSinCuotaIA) > 0)
                <div class="mb-8 bg-yellow-900/20 border border-yellow-500/30 rounded-2xl p-5 flex items-start gap-4">
                    <div class="w-9 h-9 bg-yellow-500/20 rounded-full flex items-center justify-center flex-shrink-0">⚠️</div>
                    <div>
                        <p class="text-yellow-200 font-medium">Cuota de IA mensual alcanzada</p>
                        <p class="text-yellow-200/80 text-sm mt-1">
                            El asistente con IA dejó de responder en:
                            <strong>{{ $comerciosSinCuotaIA->pluck('nombre')->implode(', ') }}</strong>.
                            Se reactiva el mes que viene, o contactanos para ampliar tu plan.
                        </p>
                    </div>
                </div>
            @endif

            <!-- Consumo de IA del comercio (usuario final) -->
            @unless(auth()->user()->isSuperAdmin())
                @foreach($comercios as $c)
                    @php
                        $planC = $c->planEfectivo();
                        $limiteC = $planC?->mensajes_ia_mes;
                        $usadosC = $c->mensajesIaUsados();
                        $pctC = $limiteC ? min(100, (int) round($usadosC / max(1, $limiteC) * 100)) : 0;
                    @endphp
                    <div class="mb-6 bg-secondary-dark border border-accent-gray rounded-2xl p-5">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">🤖</span>
                                <p class="font-medium text-text-primary">Asistente IA · {{ $c->nombre }}</p>
                            </div>
                            <span class="px-2.5 py-0.5 text-xs rounded-full bg-accent-gray/50 text-text-secondary border border-accent-gray">
                                Plan {{ $planC?->nombre ?? '—' }}
                            </span>
                        </div>
                        @if(is_null($limiteC))
                            <p class="text-sm text-text-secondary">{{ number_format($usadosC) }} mensajes este mes · <span class="text-accent-green font-medium">Ilimitado</span></p>
                        @else
                            <p class="text-sm text-text-secondary mb-2">
                                {{ number_format($usadosC) }} / {{ number_format($limiteC) }} mensajes este mes
                                <span class="text-text-secondary/70">({{ $pctC }}%)</span>
                            </p>
                            <div class="w-full h-2 bg-accent-gray/40 rounded-full overflow-hidden">
                                <div class="h-2 rounded-full {{ $pctC >= 100 ? 'bg-red-500' : ($pctC >= 80 ? 'bg-yellow-500' : 'bg-accent-green') }}" style="width: {{ $pctC }}%"></div>
                            </div>
                            @if($pctC >= 100)
                                <p class="text-xs text-red-300 mt-2">Llegaste al límite del mes. El asistente se reactiva el mes que viene o ampliando tu plan.</p>
                            @endif
                        @endif
                    </div>
                @endforeach
            @endunless

            <!-- Flash Messages -->
            @if(session('success'))
                <div class="mb-8">
                    <div class="relative overflow-hidden bg-green-900/20 border border-green-500/30 rounded-2xl p-5">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-green-500/20 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-lg">✓</span>
                            </div>
                            <p class="text-green-200 font-medium">{{ session('success') }}</p>
                        </div>
                        <div class="absolute bottom-0 left-0 h-0.5 w-full bg-gradient-to-r from-green-500 to-emerald-500 animate-progress-bar"></div>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-8">
                    <div class="relative overflow-hidden bg-red-900/20 border border-red-500/30 rounded-2xl p-5">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-red-500/20 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-lg">⚠️</span>
                            </div>
                            <p class="text-red-200 font-medium">{{ session('error') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Métricas -->
            @if(auth()->user()->isSuperAdmin())
                @php
                    $totalConvs = $allConversaciones->count();
                    $pctIA = $totalConvs > 0 ? round(($chatsIA / $totalConvs) * 100) : 0;
                @endphp
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-10">
                    <a href="{{ route('admin.users.index') }}" class="metric-card hover:border-accent-blue/40 transition-all duration-200 group">
                        <div class="flex items-center gap-4">
                            <div class="w-11 h-11 bg-accent-blue/10 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:bg-accent-blue/20 transition-colors">
                                <span class="text-xl">👥</span>
                            </div>
                            <div>
                                <p class="text-text-secondary text-xs mb-1">Usuarios</p>
                                <p class="text-2xl font-bold text-text-primary">{{ $totalUsers }}</p>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('admin.comercios.index') }}" class="metric-card hover:border-accent-blue/40 transition-all duration-200 group">
                        <div class="flex items-center gap-4">
                            <div class="w-11 h-11 bg-accent-green/10 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:bg-accent-green/20 transition-colors">
                                <span class="text-xl">🏪</span>
                            </div>
                            <div>
                                <p class="text-text-secondary text-xs mb-1">Comercios</p>
                                <p class="text-2xl font-bold text-text-primary">{{ $comercios->count() }}</p>
                            </div>
                        </div>
                    </a>

                    <div class="metric-card">
                        <div class="flex items-center gap-4">
                            <div class="w-11 h-11 bg-yellow-500/10 rounded-xl flex items-center justify-center flex-shrink-0">
                                <span class="text-xl">💬</span>
                            </div>
                            <div>
                                <p class="text-text-secondary text-xs mb-1">Chats hoy</p>
                                <p class="text-2xl font-bold text-text-primary">{{ $chatsHoy }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="metric-card">
                        <div class="flex items-center gap-4">
                            <div class="w-11 h-11 bg-purple-500/10 rounded-xl flex items-center justify-center flex-shrink-0">
                                <span class="text-xl">🤖</span>
                            </div>
                            <div>
                                <p class="text-text-secondary text-xs mb-1">Uso IA (90d)</p>
                                <p class="text-2xl font-bold text-text-primary">{{ $pctIA }}<span class="text-base font-normal text-text-secondary">%</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-10">
                    <a href="{{ route('comercios.index') }}" class="metric-card hover:border-accent-blue/40 transition-all duration-200 group">
                        <div class="flex items-center gap-4">
                            <div class="w-11 h-11 bg-accent-blue/10 rounded-xl flex items-center justify-center flex-shrink-0">
                                <span class="text-xl">🏪</span>
                            </div>
                            <div>
                                <p class="text-text-secondary text-xs mb-1">Mis Comercios</p>
                                <p class="text-2xl font-bold text-text-primary">{{ $comercios->count() }}</p>
                            </div>
                        </div>
                    </a>
                    <div class="metric-card">
                        <div class="flex items-center gap-4">
                            <div class="w-11 h-11 bg-accent-green/10 rounded-xl flex items-center justify-center flex-shrink-0">
                                <span class="text-xl">📋</span>
                            </div>
                            <div>
                                <p class="text-text-secondary text-xs mb-1">Menús</p>
                                <p class="text-2xl font-bold text-text-primary">{{ $menus->count() }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="flex items-center gap-4">
                            <div class="w-11 h-11 bg-yellow-500/10 rounded-xl flex items-center justify-center flex-shrink-0">
                                <span class="text-xl">⚡</span>
                            </div>
                            <div>
                                <p class="text-text-secondary text-xs mb-1">Actividad (90d)</p>
                                <p class="text-2xl font-bold text-text-primary">{{ $allConversaciones->count() }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Panel Super Admin -->
            @if(auth()->user()->isSuperAdmin())
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-10">

                    <!-- Usuarios recientes -->
                    <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-5">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 bg-accent-blue/10 rounded-lg flex items-center justify-center">
                                    <span>👥</span>
                                </div>
                                <div>
                                    <h3 class="font-bold text-text-primary">Usuarios Recientes</h3>
                                    <p class="text-text-secondary text-xs">Últimas altas en el sistema</p>
                                </div>
                            </div>
                            <a href="{{ route('admin.users.index') }}" class="text-accent-blue hover:opacity-70 text-sm font-medium transition-opacity">
                                Ver todos →
                            </a>
                        </div>
                        <div class="space-y-2">
                            @forelse($recentUsers as $recentUser)
                                <a href="{{ route('admin.users.index', ['user_id' => $recentUser->id]) }}"
                                   class="flex items-center justify-between bg-primary-dark hover:bg-background rounded-xl px-4 py-3 transition-colors group">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-accent-blue/20 flex items-center justify-center text-accent-blue font-semibold text-sm flex-shrink-0">
                                            {{ strtoupper(substr($recentUser->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="text-text-primary text-sm font-medium">{{ $recentUser->name }}</p>
                                            <p class="text-text-secondary text-xs">{{ $recentUser->email }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 text-xs rounded-full {{ $recentUser->role === 'super_admin' ? 'bg-accent-blue/20 text-accent-blue' : 'bg-accent-gray/50 text-text-secondary' }}">
                                            {{ $recentUser->role === 'super_admin' ? 'Admin' : 'Usuario' }}
                                        </span>
                                        <span class="text-text-secondary text-xs">{{ $recentUser->created_at->diffForHumans() }}</span>
                                    </div>
                                </a>
                            @empty
                                <p class="text-text-secondary text-center py-6 text-sm">Sin usuarios.</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Comercios recientes -->
                    <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-5">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 bg-accent-green/10 rounded-lg flex items-center justify-center">
                                    <span>🏪</span>
                                </div>
                                <div>
                                    <h3 class="font-bold text-text-primary">Comercios Recientes</h3>
                                    <p class="text-text-secondary text-xs">Últimos comercios creados</p>
                                </div>
                            </div>
                            <a href="{{ route('admin.comercios.index') }}" class="text-accent-blue hover:opacity-70 text-sm font-medium transition-opacity">
                                Ver todos →
                            </a>
                        </div>
                        <div class="space-y-2">
                            @forelse($recentComercios as $recentComercio)
                                <a href="{{ route('menus.index', $recentComercio) }}"
                                   class="flex items-center justify-between bg-primary-dark hover:bg-background rounded-xl px-4 py-3 transition-colors group">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-accent-green/20 flex items-center justify-center text-accent-green font-semibold text-sm flex-shrink-0">
                                            {{ strtoupper(substr($recentComercio->nombre, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="text-text-primary text-sm font-medium">{{ $recentComercio->nombre }}</p>
                                            <p class="text-text-secondary text-xs">{{ $recentComercio->user->name ?? 'Sin asignar' }}</p>
                                        </div>
                                    </div>
                                    <span class="text-text-secondary text-xs">{{ $recentComercio->created_at->diffForHumans() }}</span>
                                </a>
                            @empty
                                <p class="text-text-secondary text-center py-6 text-sm">Sin comercios.</p>
                            @endforelse
                        </div>
                    </div>

                </div>
            @endif

            <!-- Sesiones de Chat -->
            @if($sesiones->count() > 0)
                <div>
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-5">
                        <div>
                            <h2 class="text-xl font-bold text-text-primary">Sesiones de Chat</h2>
                            <p class="text-text-secondary text-sm">
                                {{ $sesiones->count() }} sesiones · {{ $allConversaciones->count() }} mensajes en los últimos 90 días
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            @if(auth()->user()->isSuperAdmin())
                                <select id="filter-comercio"
                                        onchange="filterByComercio(this.value)"
                                        class="px-3 py-2 bg-secondary-dark text-text-secondary rounded-lg text-sm border border-accent-gray hover:border-accent-blue/50 transition-colors">
                                    <option value="all">Todos los comercios</option>
                                    @foreach($comerciosList as $comercio)
                                        <option value="{{ $comercio['id'] }}">{{ $comercio['nombre'] }}</option>
                                    @endforeach
                                </select>
                            @endif

                            <button onclick="filterByTime('today')" id="filter-today"
                                    class="px-3 py-2 bg-secondary-dark text-text-secondary rounded-lg text-sm border border-accent-gray hover:border-accent-blue/50 transition-colors">
                                Hoy
                            </button>
                            <button onclick="filterByTime('week')" id="filter-week"
                                    class="px-3 py-2 bg-accent-blue text-black rounded-lg text-sm font-medium active-filter">
                                Esta semana
                            </button>
                            <button onclick="filterByTime('month')" id="filter-month"
                                    class="px-3 py-2 bg-secondary-dark text-text-secondary rounded-lg text-sm border border-accent-gray hover:border-accent-blue/50 transition-colors">
                                Este mes
                            </button>
                            <button onclick="resetFilters()"
                                    class="px-3 py-2 bg-secondary-dark text-text-secondary rounded-lg text-sm border border-accent-gray hover:border-accent-blue/50 transition-colors">
                                Todo
                            </button>
                        </div>
                    </div>

                    <div class="bg-secondary-dark border border-accent-gray rounded-xl p-5">
                        <div class="space-y-3" id="chats-container">
                            @foreach($sesiones as $sesion)
                                @php
                                    $comercio = $sesion['comercio'];
                                    $ultimaConv = $sesion['ultima_conversacion'];
                                    $tieneIA = $sesion['conversaciones']->contains('used_openai', true);
                                @endphp

                                <div class="chat-card"
                                     data-session-id="{{ $sesion['session_id'] }}"
                                     data-comercio-id="{{ $comercio->id }}"
                                     data-fecha-primera="{{ $sesion['fecha_primera'] }}"
                                     data-fecha-ultima="{{ $sesion['fecha_ultima'] }}">

                                    <!-- Cabecera de sesión -->
                                    <div class="flex items-start justify-between p-4 bg-primary-dark rounded-xl border border-accent-gray hover:border-accent-blue/40 transition-colors cursor-pointer"
                                         onclick="toggleSessionExpand('{{ $sesion['session_id'] }}')">

                                        <div class="flex items-start gap-3 flex-1 min-w-0">
                                            <!-- Avatar -->
                                            <div class="relative flex-shrink-0">
                                                <div class="w-11 h-11 bg-accent-gray rounded-full flex items-center justify-center">
                                                    <span class="text-lg">💬</span>
                                                </div>
                                                <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full border-2 border-primary-dark flex items-center justify-center text-xs
                                                    {{ $tieneIA ? 'bg-purple-600' : 'bg-accent-green' }}"
                                                     title="{{ $tieneIA ? 'Sesión con IA' : 'Solo FAQ' }}">
                                                    {{ $tieneIA ? '🤖' : '⚡' }}
                                                </div>
                                            </div>

                                            <!-- Info -->
                                            <div class="flex-1 min-w-0">
                                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                                    <p class="text-text-primary font-semibold">{{ $comercio->nombre }}</p>
                                                    <span class="text-xs bg-accent-blue/10 text-accent-blue border border-accent-blue/20 px-2 py-0.5 rounded">
                                                        {{ $sesion['total_mensajes'] }} {{ $sesion['total_mensajes'] == 1 ? 'msg' : 'msgs' }}
                                                    </span>
                                                    @if($tieneIA)
                                                        <span class="text-xs bg-purple-500/20 text-purple-300 px-2 py-0.5 rounded">IA</span>
                                                    @endif
                                                </div>
                                                <p class="text-text-secondary text-sm truncate">
                                                    {{ Str::limit($ultimaConv->user_message, 80) }}
                                                </p>
                                                <p class="text-text-secondary text-xs mt-1">
                                                    {{ $sesion['primera_conversacion']->created_at->format('d/m H:i') }}
                                                    @if($sesion['primera_conversacion']->id !== $sesion['ultima_conversacion']->id)
                                                        → {{ $sesion['ultima_conversacion']->created_at->format('H:i') }}
                                                    @endif
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Timestamp + toggle -->
                                        <div class="flex flex-col items-end gap-1 ml-3 flex-shrink-0">
                                            <span class="text-text-secondary text-xs">{{ $ultimaConv->created_at->diffForHumans() }}</span>
                                            <span class="expand-icon text-text-secondary text-xs">▼</span>
                                        </div>
                                    </div>

                                    <!-- Detalle expandible -->
                                    <div class="session-expanded-content hidden mt-2 pl-4 border-l-2 border-accent-blue/40 space-y-3">
                                        @foreach($sesion['conversaciones']->sortByDesc('created_at') as $conversacion)
                                            <div class="bg-primary-dark rounded-xl p-4 border border-accent-gray/50">
                                                <!-- Mensaje usuario -->
                                                <div class="mb-3">
                                                    <div class="flex items-center gap-2 mb-1.5">
                                                        <div class="w-6 h-6 bg-accent-gray rounded-full flex items-center justify-center text-xs">👤</div>
                                                        <span class="text-text-secondary text-xs font-medium">Usuario</span>
                                                        <span class="text-text-secondary text-xs">{{ $conversacion->created_at->format('H:i') }}</span>
                                                    </div>
                                                    <p class="text-text-primary text-sm ml-8 leading-relaxed">{{ Str::limit(e($conversacion->user_message), 200) }}</p>
                                                </div>

                                                <!-- Respuesta bot -->
                                                @if($conversacion->bot_response)
                                                    <div class="border-l-2 {{ $conversacion->used_openai ? 'border-purple-500' : 'border-accent-green' }} pl-3">
                                                        <div class="flex items-center gap-2 mb-1.5">
                                                            <div class="w-6 h-6 {{ $conversacion->used_openai ? 'bg-purple-600/30' : 'bg-accent-green/20' }} rounded-full flex items-center justify-center text-xs">
                                                                {{ $conversacion->used_openai ? '🤖' : '⚡' }}
                                                            </div>
                                                            <span class="text-xs font-medium {{ $conversacion->used_openai ? 'text-purple-400' : 'text-accent-green' }}">
                                                                {{ $conversacion->used_openai ? 'IA' : 'FAQ' }}
                                                            </span>
                                                            @if($conversacion->response_time_ms)
                                                                <span class="text-text-secondary text-xs">{{ number_format($conversacion->response_time_ms) }}ms</span>
                                                            @endif
                                                        </div>
                                                        <div class="text-text-secondary text-sm ml-8 leading-relaxed">
                                                            {!! Str::limit(parseBotResponse($conversacion->bot_response), 300) !!}
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    <style>
        .session-expanded-content {
            animation: slideDown 0.3s ease-out;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .active-filter {
            background: var(--accent-blue) !important;
            color: #000 !important;
            border-color: transparent !important;
            font-weight: 600;
        }
    </style>

    <script>
        function toggleSessionExpand(sessionId) {
            const card = document.querySelector(`[data-session-id="${sessionId}"]`);
            const content = card.querySelector('.session-expanded-content');
            const icon = card.querySelector('.expand-icon');
            content.classList.toggle('hidden');
            icon.textContent = content.classList.contains('hidden') ? '▼' : '▲';
        }

        function filterByTime(period) {
            document.querySelectorAll('#filter-today, #filter-week, #filter-month').forEach(btn => {
                btn.classList.remove('active-filter', 'bg-accent-blue', 'text-black', 'font-medium');
                btn.classList.add('bg-secondary-dark', 'text-text-secondary', 'border', 'border-accent-gray');
            });
            const active = document.getElementById(`filter-${period}`);
            active.classList.add('active-filter');
            active.classList.remove('bg-secondary-dark', 'text-text-secondary', 'border', 'border-accent-gray');

            document.querySelectorAll('.chat-card').forEach(chat => {
                const fechaPrimera = chat.getAttribute('data-fecha-primera');
                if (!fechaPrimera) { chat.style.display = 'block'; return; }
                const chatDate = new Date(fechaPrimera);
                const now = new Date();
                const diffDays = Math.floor((now - chatDate) / (1000 * 60 * 60 * 24));
                const isSameDay = chatDate.toDateString() === now.toDateString();
                const isSameMonth = chatDate.getMonth() === now.getMonth() && chatDate.getFullYear() === now.getFullYear();
                let show = false;
                if (period === 'today') show = isSameDay;
                else if (period === 'week') show = diffDays <= 7;
                else if (period === 'month') show = isSameMonth;
                chat.style.display = show ? 'block' : 'none';
            });
        }

        function filterByComercio(comercioId) {
            document.querySelectorAll('.chat-card').forEach(chat => {
                chat.style.display = (comercioId === 'all' || chat.getAttribute('data-comercio-id') === comercioId) ? 'block' : 'none';
            });
        }

        function resetFilters() {
            document.querySelectorAll('#filter-today, #filter-week, #filter-month').forEach(btn => {
                btn.classList.remove('active-filter');
                btn.classList.add('bg-secondary-dark', 'text-text-secondary', 'border', 'border-accent-gray');
            });
            const comercioSelect = document.getElementById('filter-comercio');
            if (comercioSelect) comercioSelect.value = 'all';
            document.querySelectorAll('.chat-card').forEach(chat => chat.style.display = 'block');
        }

        document.addEventListener('DOMContentLoaded', () => filterByTime('week'));
    </script>
</x-app-layout>

