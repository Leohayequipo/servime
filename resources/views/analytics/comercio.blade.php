<x-app-layout>
    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div class="flex items-center space-x-3">
                    @if($comercio->logo_url)
                        <img src="{{ asset('storage/' . $comercio->logo_url) }}"
                             alt="{{ $comercio->nombre }}"
                             class="w-10 h-10 rounded-full object-cover">
                    @endif
                    <div>
                        <h2 class="font-semibold text-xl text-text-primary leading-tight">
                            📊 Analytics · {{ $comercio->nombre }}
                        </h2>
                        <p class="text-sm text-text-secondary">Comportamiento de los últimos 30 días</p>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('analytics.dashboard') }}"
                       class="bg-secondary-dark hover:bg-accent-gray text-text-primary font-medium py-2 px-4 rounded-lg border border-accent-gray">
                        ← Dashboard
                    </a>
                    <a href="{{ route('analytics.export', $comercio) }}"
                       class="bg-accent-green hover:opacity-90 text-gray-900 font-semibold py-2 px-4 rounded-lg">
                        📥 Exportar
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 text-green-800 dark:text-green-200 rounded-lg p-4 mb-6 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Estadísticas del Comercio -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Conversaciones -->
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl overflow-hidden">
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
                                <p class="text-sm font-medium text-text-secondary">Total Conversaciones</p>
                                <p class="text-2xl font-bold text-text-primary">{{ $stats['total_conversations'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Conversaciones Hoy -->
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl overflow-hidden">
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
                                <p class="text-sm font-medium text-text-secondary">Conversaciones Hoy</p>
                                <p class="text-2xl font-bold text-text-primary">{{ $stats['conversations_today'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Uso de OpenAI -->
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl overflow-hidden">
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
                                <p class="text-sm font-medium text-text-secondary">Con OpenAI</p>
                                <p class="text-2xl font-bold text-text-primary">{{ $stats['openai_usage'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tiempo Promedio -->
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl overflow-hidden">
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
                                <p class="text-sm font-medium text-text-secondary">Tiempo Promedio</p>
                                <p class="text-2xl font-bold text-text-primary">
                                    {{ $stats['avg_response_time'] ? round($stats['avg_response_time']) . 'ms' : 'N/A' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sugerencias accionables -->
            @if(count($suggestions) > 0)
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/40 shadow-sm sm:rounded-lg p-6 mb-8">
                    <h3 class="text-lg font-medium text-amber-900 dark:text-amber-200 mb-4">✨ Sugerencias para tu negocio</h3>
                    <ul class="space-y-2.5">
                        @foreach($suggestions as $s)
                            <li class="flex items-start gap-3 text-sm text-amber-900 dark:text-amber-100">
                                <span class="flex-shrink-0">{{ $s['icon'] }}</span>
                                <span>{{ $s['text'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Intención de las consultas -->
            <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6 mb-8">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-lg font-medium text-text-primary mb-1">🎯 ¿Para qué consultan?</h3>
                        <p class="text-xs text-text-secondary">
                            Intención en {{ $intents['total'] }} mensajes (últimos 30 días)
                            @if($intents['ia'] > 0)
                                · <span class="text-indigo-600 dark:text-indigo-400">{{ $intents['ia'] }} clasificados con IA</span>
                            @endif
                        </p>
                    </div>
                    @if(auth()->user()->isSuperAdmin() || $comercio->tieneFeature('intencion_ia'))
                        <div class="text-right">
                            <form method="POST" action="{{ route('analytics.reclassify', $comercio) }}">
                                @csrf
                                <button type="submit"
                                        class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2 px-4 rounded inline-flex items-center gap-2">
                                    ✨ Reclasificar con IA
                                </button>
                            </form>
                            @if($ultimaClasificacion)
                                <p class="text-[11px] text-text-secondary mt-1">Última: {{ $ultimaClasificacion->created_at->diffForHumans() }}</p>
                            @endif
                        </div>
                    @endif
                </div>
                @php $intMax = collect($intents['counts'])->max() ?: 1; @endphp
                @if($intents['total'] > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3">
                        @foreach($intents['counts'] as $name => $count)
                            @if($count > 0)
                                <div>
                                    <div class="flex justify-between text-sm text-text-secondary mb-1">
                                        <span>{{ $name }}</span><b>{{ $count }}</b>
                                    </div>
                                    <div class="h-2 bg-accent-gray/40 rounded">
                                        <div class="h-2 {{ $name === 'Otras' ? 'bg-gray-400' : 'bg-indigo-500' }} rounded" style="width: {{ round($count / $intMax * 100) }}%"></div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <p class="text-text-secondary text-center py-4">Sin mensajes para analizar todavía.</p>
                @endif
            </div>

            <!-- Comportamiento: IA vs Clásico + Embudo -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                @php
                    $ia = $metrics['menuChoice']['ia']; $cl = $metrics['menuChoice']['classic'];
                    $totalChoice = $ia + $cl;
                    $iaPct = $totalChoice ? round($ia / $totalChoice * 100) : 0;
                    $clPct = $totalChoice ? 100 - $iaPct : 0;
                @endphp
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                    <h3 class="text-lg font-medium text-text-primary mb-4">🤖 ¿IA o menú clásico?</h3>
                    @if($totalChoice > 0)
                        <div class="flex h-5 rounded-full overflow-hidden mb-4">
                            <div class="bg-amber-500" style="width: {{ $iaPct }}%"></div>
                            <div class="bg-gray-400 dark:bg-gray-600" style="width: {{ $clPct }}%"></div>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-text-secondary"><span class="inline-block w-3 h-3 rounded-sm bg-amber-500 mr-1"></span>IA · <b>{{ $ia }}</b> ({{ $iaPct }}%)</span>
                            <span class="text-text-secondary"><span class="inline-block w-3 h-3 rounded-sm bg-gray-400 dark:bg-gray-600 mr-1"></span>Clásico · <b>{{ $cl }}</b> ({{ $clPct }}%)</span>
                        </div>
                    @else
                        <p class="text-text-secondary text-center py-4">Sin datos de elección todavía.</p>
                    @endif
                </div>

                <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                    <h3 class="text-lg font-medium text-text-primary mb-4">🛒 Embudo de interés</h3>
                    @php
                        $fv = $metrics['funnel']['visitas']; $fe = $metrics['funnel']['eligieron']; $fi = $metrics['funnel']['interesados'];
                        $pasos = [['Visitaron el menú', $fv, 'bg-blue-500'], ['Eligieron una opción', $fe, 'bg-amber-500'], ['Mostraron interés ("Lo quiero")', $fi, 'bg-green-500']];
                    @endphp
                    @if($fv > 0)
                        <div class="space-y-3">
                            @foreach($pasos as [$label, $val, $color])
                                <div>
                                    <div class="flex justify-between text-sm text-text-secondary mb-1">
                                        <span>{{ $label }}</span><b>{{ $val }}</b>
                                    </div>
                                    <div class="h-2 bg-accent-gray/40 rounded">
                                        <div class="h-2 {{ $color }} rounded" style="width: {{ $fv ? round($val / $fv * 100) : 0 }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-text-secondary text-center py-4">Sin visitas registradas todavía.</p>
                    @endif
                </div>
            </div>

            <!-- Dispositivo · Visitantes · Tiempo de sesión -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                    <h3 class="text-sm font-medium text-text-secondary mb-3">📱 Dispositivos</h3>
                    @php $dv = $metrics['devices']; $dvTotal = array_sum($dv) ?: 1; @endphp
                    @foreach(['movil' => 'Móvil', 'desktop' => 'Escritorio', 'tablet' => 'Tablet'] as $k => $label)
                        <div class="mb-2">
                            <div class="flex justify-between text-sm text-text-secondary">
                                <span>{{ $label }}</span><b>{{ round($dv[$k] / $dvTotal * 100) }}%</b>
                            </div>
                            <div class="h-1.5 bg-accent-gray/40 rounded mt-1">
                                <div class="h-1.5 bg-teal-500 rounded" style="width: {{ round($dv[$k] / $dvTotal * 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                    <h3 class="text-sm font-medium text-text-secondary mb-3">🔁 Visitantes que vuelven</h3>
                    @php $vs = $metrics['visitors']; $vt = array_sum($vs) ?: 1; @endphp
                    <p class="text-4xl font-bold text-text-primary">{{ round($vs['recurrentes'] / $vt * 100) }}%</p>
                    <p class="text-sm text-text-secondary mt-1">
                        {{ $vs['recurrentes'] }} de {{ $metrics['totalSesiones'] }} visitantes volvieron
                    </p>
                    <p class="text-xs text-text-secondary mt-1">{{ $vs['nuevos'] }} nuevos</p>
                </div>

                <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                    <h3 class="text-sm font-medium text-text-secondary mb-3">⏱️ Tiempo promedio en el menú</h3>
                    @php $sec = $metrics['sessionAvgSeconds']; $dur = $sec >= 60 ? intdiv($sec, 60).'m '.($sec % 60).'s' : $sec.'s'; @endphp
                    <p class="text-4xl font-bold text-text-primary">{{ $dur }}</p>
                    <p class="text-sm text-text-secondary mt-1">por sesión</p>
                </div>
            </div>

            <!-- Promos y platos top -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                    <h3 class="text-lg font-medium text-text-primary mb-4">🔥 Promos más clickeadas</h3>
                    @php $promoMax = $metrics['topPromos']->max() ?: 1; @endphp
                    @forelse($metrics['topPromos'] as $promo => $count)
                        <div class="mb-3">
                            <div class="flex justify-between text-sm text-text-secondary mb-1">
                                <span class="truncate">{{ $promo }}</span><b>{{ $count }}</b>
                            </div>
                            <div class="h-2 bg-accent-gray/40 rounded">
                                <div class="h-2 bg-red-500 rounded" style="width: {{ round($count / $promoMax * 100) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-text-secondary text-center py-4">Nadie clickeó promos aún.</p>
                    @endforelse
                </div>

                <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                    <h3 class="text-lg font-medium text-text-primary mb-4">🍽️ Platos más deseados</h3>
                    @php $dishMax = $metrics['topDishes']->max() ?: 1; @endphp
                    @forelse($metrics['topDishes'] as $dish => $count)
                        <div class="mb-3">
                            <div class="flex justify-between text-sm text-text-secondary mb-1">
                                <span class="truncate">{{ $dish }}</span><b>{{ $count }}</b>
                            </div>
                            <div class="h-2 bg-accent-gray/40 rounded">
                                <div class="h-2 bg-green-500 rounded" style="width: {{ round($count / $dishMax * 100) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-text-secondary text-center py-4">Nadie tocó "Lo quiero" aún.</p>
                    @endforelse
                </div>
            </div>

            <!-- Top palabras + Heatmap -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                    <h3 class="text-lg font-medium text-text-primary mb-4">🔤 Palabras más buscadas</h3>
                    @php $wordMax = $topWords->max() ?: 1; @endphp
                    @if($topWords->count() > 0)
                        <div class="flex flex-wrap gap-2">
                            @foreach($topWords as $word => $count)
                                <span class="inline-flex items-center rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-200 px-3 py-1"
                                      style="font-size: {{ 0.75 + ($count / $wordMax) * 0.7 }}rem">
                                    {{ $word }} <span class="ml-1 text-xs opacity-70">{{ $count }}</span>
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-text-secondary text-center py-4">Sin mensajes para analizar todavía.</p>
                    @endif
                </div>

                <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                    <h3 class="text-lg font-medium text-text-primary mb-4">🕒 Actividad por hora y día</h3>
                    @php $dias = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb']; $hmMax = $metrics['heatmapMax']; @endphp
                    @if($hmMax > 0)
                        <div class="space-y-1">
                            @for($d = 0; $d < 7; $d++)
                                <div class="flex items-center gap-1">
                                    <span class="w-8 text-xs text-text-secondary flex-shrink-0">{{ $dias[$d] }}</span>
                                    @for($h = 0; $h < 24; $h++)
                                        @php $c = $metrics['heatmap'][$d][$h]; $r = $c > 0 ? max(0.15, $c / $hmMax) : 0; @endphp
                                        <div class="flex-1 h-4 rounded-sm" title="{{ $dias[$d] }} {{ $h }}:00 · {{ $c }}"
                                             style="background: {{ $c > 0 ? 'rgba(245,158,11,'.$r.')' : 'rgba(120,120,120,0.12)' }}"></div>
                                    @endfor
                                </div>
                            @endfor
                            <div class="flex justify-between text-[10px] text-text-secondary pl-9 pt-1">
                                <span>0h</span><span>6h</span><span>12h</span><span>18h</span><span>23h</span>
                            </div>
                        </div>
                    @else
                        <p class="text-text-secondary text-center py-4">Sin actividad para el heatmap todavía.</p>
                    @endif
                </div>
            </div>

            <!-- Preguntas Más Frecuentes -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-text-primary mb-4">❓ Preguntas Más Frecuentes</h3>
                        @if($frequentQuestions->count() > 0)
                            <div class="space-y-3">
                                @foreach($frequentQuestions as $question)
                                    <div class="border-l-4 border-blue-500 pl-4 py-2">
                                        <p class="text-sm text-text-primary font-medium">
                                            "{{ $question->user_message }}"
                                        </p>
                                        <p class="text-xs text-text-secondary">
                                            {{ $question->frequency }} {{ $question->frequency > 1 ? 'veces' : 'vez' }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-text-secondary text-center py-4">
                                No hay preguntas frecuentes registradas
                            </p>
                        @endif
                    </div>
                </div>

                <!-- Estadísticas Diarias -->
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-text-primary mb-4">📈 Actividad de los Últimos 7 Días</h3>
                        @if($dailyStats->count() > 0)
                            <div class="space-y-3">
                                @foreach($dailyStats as $day)
                                    <div class="flex items-center justify-between py-2 border-b border-accent-gray/50 last:border-b-0">
                                        <div>
                                            <p class="text-sm font-medium text-text-primary">
                                                {{ \Carbon\Carbon::parse($day->date)->format('d/m/Y') }}
                                            </p>
                                            <p class="text-xs text-text-secondary">
                                                {{ $day->openai_count }}/{{ $day->total }} con IA
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-bold text-text-primary">
                                                {{ $day->total }} chats
                                            </p>
                                            @if($day->avg_response_time)
                                                <p class="text-xs text-text-secondary">
                                                    {{ round($day->avg_response_time) }}ms promedio
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-text-secondary text-center py-4">
                                No hay actividad registrada en los últimos 7 días
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Historial de Conversaciones -->
            <div class="bg-secondary-dark border border-accent-gray rounded-2xl overflow-hidden">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-text-primary mb-6">💬 Historial de Conversaciones</h3>
                    
                    @if($threads->count() > 0)
                        <p class="text-xs text-text-secondary mb-4">Últimos {{ $threads->count() }} hilos (agrupados por sesión)</p>
                        <div class="space-y-3">
                            @foreach($threads as $thread)
                                <details class="border border-accent-gray/50 rounded-lg group">
                                    <summary class="flex items-center justify-between cursor-pointer px-4 py-3 select-none">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span class="text-text-secondary group-open:rotate-90 transition-transform flex-shrink-0">▶</span>
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-text-primary">
                                                    {{ $thread['inicio']->format('d/m H:i') }}
                                                    <span class="text-text-secondary font-normal">· {{ $thread['ip'] ?? 'anónimo' }}</span>
                                                </p>
                                                <p class="text-xs text-text-secondary truncate">
                                                    {{ $thread['mensajes']->first()->user_message }}
                                                </p>
                                            </div>
                                        </div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 flex-shrink-0 ml-3">
                                            {{ $thread['total'] }} msj
                                        </span>
                                    </summary>
                                    <div class="px-4 pb-4 space-y-3 border-t border-accent-gray/40 pt-3">
                                        @foreach($thread['mensajes'] as $m)
                                            <div class="flex items-start gap-2">
                                                <span class="text-xs text-text-secondary w-10 flex-shrink-0 pt-1">{{ $m->created_at->format('H:i') }}</span>
                                                <div class="flex-1 space-y-1">
                                                    <p class="text-sm text-text-primary">🧑 {{ $m->user_message }}</p>
                                                    <p class="text-sm text-text-secondary">
                                                        {{ $m->used_openai ? '🤖' : '🔧' }} {{ $m->bot_response }}
                                                        @if($m->response_time_ms)
                                                            <span class="text-xs text-text-secondary">· {{ $m->response_time_ms }}ms</span>
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-text-primary">No hay conversaciones</h3>
                            <p class="mt-1 text-sm text-text-secondary">
                                Aún no se han registrado conversaciones para este comercio.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

