{{-- "Mi plan" del comercio. Todo sale de la base (`plans`), que se edita desde
     /admin/plans: nombres, precios, cuotas y features. Nada escrito a mano acá, o el día
     que cambie un precio esta pantalla queda mintiendo. --}}
<x-app-layout>
    <div class="py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="mb-8">
                <h2 class="font-semibold text-2xl text-text-primary leading-tight">Mi plan</h2>
                <p class="text-sm text-text-secondary mt-0.5">Qué tenés contratado y qué suma cada plan.</p>
            </div>

            <x-flash />

            {{-- Qué tiene hoy. Con varios comercios, cada uno puede estar en un plan distinto. --}}
            <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6 mb-8">
                <h3 class="text-base font-medium text-text-primary mb-4">
                    {{ $comercios->count() === 1 ? 'Tu comercio' : 'Tus comercios' }}
                </h3>

                @forelse($comercios as $comercio)
                    @php $plan = $comercio->planEfectivo(); @endphp
                    <div class="flex items-center justify-between gap-4 py-2 border-b border-accent-gray/40 last:border-0">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-text-primary truncate">{{ $comercio->nombre }}</p>
                            @if($plan)
                                <p class="text-xs text-text-secondary">
                                    {{ $plan->cupoIaLegible() }}
                                </p>
                            @endif
                        </div>
                        <span class="flex-none inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-accent-blue/15 text-accent-deep">
                            Plan {{ $plan?->nombre ?? 'sin asignar' }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-text-secondary">Todavía no tenés comercios cargados.</p>
                @endforelse
            </div>

            {{-- La escalera de planes, en el orden en que se venden. --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($planes as $plan)
                    @php
                        $loTiene = $planesEnUso->contains($plan->id);
                        // Con quién escribe y desde qué plan: el mensaje llega a un WhatsApp
                        // donde el número solo no alcanza para saber qué cuenta tocar.
                        $mensaje = 'Hola, '.auth()->user()->presentacionParaSoporte()
                            .". Quiero pasar al plan {$plan->nombre}.";
                    @endphp
                    <div class="bg-secondary-dark border rounded-2xl p-6 flex flex-col {{ $loTiene ? 'border-accent-blue' : 'border-accent-gray' }}">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <h3 class="text-lg font-semibold text-text-primary">{{ $plan->nombre }}</h3>
                            @if($loTiene)
                                <span class="flex-none text-xs font-medium text-accent-deep">Tu plan</span>
                            @endif
                        </div>

                        <p class="text-2xl font-bold text-text-primary mb-1">
                            {{ $plan->precioLegible() }}
                            @if((float) $plan->precio > 0)
                                <span class="text-sm font-normal text-text-secondary">/mes</span>
                            @endif
                        </p>

                        @if($plan->descripcion)
                            <p class="text-sm text-text-secondary mb-4">{{ $plan->descripcion }}</p>
                        @endif

                        <ul class="space-y-2 text-sm text-text-secondary mb-6">
                            {{-- "Sin asistente" va sin tilde verde: al lado de una tilde, lo que
                                 NO trae el plan se leía como algo que sí trae. --}}
                            <li class="flex items-start gap-2 {{ $plan->tieneAsistente() ? '' : 'opacity-70' }}">
                                @if($plan->tieneAsistente())
                                    <x-icono name="check" class="w-4 h-4 flex-none mt-0.5 text-accent-green" />
                                @else
                                    <span class="w-4 flex-none text-center">—</span>
                                @endif
                                {{ $plan->cupoIaLegible() }}
                            </li>
                            @if($plan->topeCartasLegible())
                                <li class="flex items-start gap-2">
                                    <x-icono name="check" class="w-4 h-4 flex-none mt-0.5 text-accent-green" />
                                    {{ $plan->topeCartasLegible() }} procesadas con IA
                                </li>
                            @endif
                            @foreach($plan->features ?? [] as $feature)
                                <li class="flex items-start gap-2">
                                    <x-icono name="check" class="w-4 h-4 flex-none mt-0.5 text-accent-green" />
                                    {{ \App\Models\Plan::FEATURES[$feature] ?? $feature }}
                                </li>
                            @endforeach
                        </ul>

                        <div class="mt-auto">
                            @if($loTiene)
                                <p class="text-sm text-text-secondary text-center py-2">Es el que estás usando.</p>
                            @elseif($soporteWhatsapp)
                                <a href="https://wa.me/{{ $soporteWhatsapp }}?text={{ rawurlencode($mensaje) }}"
                                   target="_blank" rel="noopener"
                                   class="block text-center bg-accent-blue hover:bg-accent-deep text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                                    Quiero este plan
                                </a>
                            @elseif($soporteEmail)
                                <a href="mailto:{{ $soporteEmail }}?subject={{ rawurlencode('Cambio de plan') }}&amp;body={{ rawurlencode($mensaje) }}"
                                   class="block text-center bg-accent-blue hover:bg-accent-deep text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                                    Quiero este plan
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="text-xs text-text-secondary mt-6">
                El cambio de plan lo hacemos nosotros: escribinos y te lo activamos. No hay que cargar ninguna tarjeta acá.
            </p>
        </div>
    </div>
</x-app-layout>
