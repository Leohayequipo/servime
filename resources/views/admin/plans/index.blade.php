<x-app-layout>
    <div class="min-h-screen bg-background">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-10">
                <div>
                    <h1 class="text-3xl font-bold text-text-primary">Planes</h1>
                    <p class="text-text-secondary mt-1">Editá los planes y asignáselos a cada comercio</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.plans.create') }}" class="btn-primary">+ Nuevo plan</a>
                    <a href="{{ route('dashboard') }}" class="btn-secondary">← Dashboard</a>
                </div>
            </div>

            <!-- Flash -->
            @if(session('success'))
                <div class="mb-6 bg-green-900/20 border border-green-500/30 rounded-2xl p-5 flex items-center gap-4">
                    <div class="w-9 h-9 bg-green-500/20 rounded-full flex items-center justify-center flex-shrink-0">✓</div>
                    <p class="text-green-200 font-medium">{{ session('success') }}</p>
                </div>
            @endif
            @if(session('error'))
                <div class="mb-6 bg-red-900/20 border border-red-500/30 rounded-2xl p-5 flex items-center gap-4">
                    <div class="w-9 h-9 bg-red-500/20 rounded-full flex items-center justify-center flex-shrink-0">!</div>
                    <p class="text-red-200 font-medium">{{ session('error') }}</p>
                </div>
            @endif

            <!-- Planes -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                @foreach($plans as $plan)
                    <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6 flex flex-col {{ $plan->activo ? '' : 'opacity-50' }}">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-xl font-bold text-text-primary">{{ $plan->nombre }}</h3>
                            <span class="px-2 py-0.5 text-xs rounded-full bg-accent-gray/50 text-text-secondary border border-accent-gray font-mono">{{ $plan->key }}</span>
                        </div>
                        @if($plan->descripcion)
                            <p class="text-text-secondary text-sm mb-4">{{ $plan->descripcion }}</p>
                        @endif
                        <ul class="space-y-2 text-sm text-text-primary mb-5">
                            <li class="flex justify-between"><span class="text-text-secondary">Precio</span><span>{{ is_null($plan->precio) ? '—' : '$'.number_format($plan->precio, 2) }}</span></li>
                            <li class="flex justify-between"><span class="text-text-secondary">Mensajes IA / mes</span><span>{{ is_null($plan->mensajes_ia_mes) ? 'Ilimitado' : number_format($plan->mensajes_ia_mes) }}</span></li>
                            <li class="flex justify-between"><span class="text-text-secondary">Modelo IA</span><span class="font-mono text-xs">{{ $plan->modelo_ia }}</span></li>
                            <li class="flex justify-between"><span class="text-text-secondary">Max tokens</span><span>{{ $plan->max_tokens_chat }}</span></li>
                            <li class="flex justify-between gap-2"><span class="text-text-secondary">Features</span><span class="text-right">{{ empty($plan->features) ? '—' : implode(', ', $plan->features) }}</span></li>
                        </ul>
                        <div class="mt-auto flex items-center gap-2">
                            <a href="{{ route('admin.plans.edit', $plan) }}" class="flex-1 text-center px-3 py-1.5 bg-accent-blue/10 hover:bg-accent-blue/20 text-accent-blue border border-accent-blue/20 rounded-lg text-xs font-medium transition-colors">Editar</a>
                            <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}" onsubmit="event.preventDefault(); const f=this; showConfirmWithLoader('¿Eliminar el plan {{ addslashes($plan->nombre) }}? Esta acción no se puede deshacer.', r => { if(r) f.submit(); }, 'Eliminando...');">
                                @csrf @method('DELETE')
                                <button type="submit" class="px-3 py-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-300 border border-red-500/20 rounded-lg text-xs font-medium transition-colors">Eliminar</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Asignación por comercio -->
            <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-9 h-9 bg-accent-green/10 rounded-lg flex items-center justify-center"><span>🏪</span></div>
                    <h3 class="font-bold text-text-primary">Plan por comercio</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-accent-gray">
                                <th class="text-left py-3 px-4 text-text-secondary text-xs font-medium uppercase tracking-wide">Comercio</th>
                                <th class="text-left py-3 px-4 text-text-secondary text-xs font-medium uppercase tracking-wide">Plan</th>
                                <th class="text-left py-3 px-4 text-text-secondary text-xs font-medium uppercase tracking-wide">Uso IA (mes actual)</th>
                                <th class="text-left py-3 px-4 text-text-secondary text-xs font-medium uppercase tracking-wide">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($comercios as $comercio)
                                @php
                                    $planActual = $comercio->planEfectivo();
                                    $usados = $comercio->mensajesIaUsados();
                                    $limite = $planActual?->mensajes_ia_mes;
                                    $pct = $limite ? min(100, round($usados / max(1, $limite) * 100)) : 0;
                                @endphp
                                <tr class="border-b border-accent-gray/50 hover:bg-primary-dark transition-colors">
                                    <td class="py-4 px-4">
                                        <p class="font-medium text-text-primary">{{ $comercio->nombre }}</p>
                                        <p class="text-xs text-text-secondary">{{ $comercio->user->name ?? '—' }}</p>
                                    </td>
                                    <td class="py-4 px-4">
                                        <form method="POST" action="{{ route('admin.comercios.assign-plan', $comercio) }}" class="flex items-center gap-2">
                                            @csrf @method('PUT')
                                            <select name="plan_id" class="bg-primary-dark border border-accent-gray rounded-lg text-sm text-text-primary px-2 py-1.5">
                                                @foreach($plans as $plan)
                                                    <option value="{{ $plan->id }}" @selected($planActual && $planActual->id === $plan->id)>{{ $plan->nombre }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="px-3 py-1.5 bg-accent-blue/10 hover:bg-accent-blue/20 text-accent-blue border border-accent-blue/20 rounded-lg text-xs font-medium transition-colors">Guardar</button>
                                        </form>
                                    </td>
                                    <td class="py-4 px-4">
                                        @if(is_null($limite))
                                            <span class="text-text-secondary text-sm">{{ number_format($usados) }} · ilimitado</span>
                                        @else
                                            <p class="text-text-primary text-sm mb-1">{{ number_format($usados) }} / {{ number_format($limite) }}</p>
                                            <div class="w-32 h-1.5 bg-accent-gray/40 rounded-full overflow-hidden">
                                                <div class="h-full {{ $pct >= 100 ? 'bg-red-500' : ($pct >= 80 ? 'bg-yellow-500' : 'bg-accent-green') }}" style="width: {{ $pct }}%"></div>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="py-4 px-4">
                                        <form method="POST" action="{{ route('admin.comercios.reset-ia', $comercio) }}" onsubmit="event.preventDefault(); const f=this; showConfirm('¿Resetear el uso de IA de {{ addslashes($comercio->nombre) }}? Esto se cobra aparte.', r => { if(r) f.submit(); });">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 bg-yellow-500/10 hover:bg-yellow-500/20 text-yellow-300 border border-yellow-500/20 rounded-lg text-xs font-medium transition-colors">Resetear uso IA</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-12 text-center text-text-secondary text-sm">No hay comercios registrados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
