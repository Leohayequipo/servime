{{-- Lo que ve un comercio que entra a analytics sin tenerlo en su plan.
     El nombre del plan sale de la base (Plan::primeroConFeature), nunca escrito a mano:
     los planes se editan desde /admin/plans. Y lleva el contacto, porque el cambio de
     plan lo hace el super admin — sin eso "necesitás otro plan" era un callejón. --}}
@php
    // La presentación va siempre: el pedido llega a un WhatsApp donde el número solo no
    // alcanza para saber qué cuenta hay que tocar.
    $presentacion = 'Hola, '.auth()->user()->presentacionParaSoporte($comercio).'.';

    $mensaje = $planNecesario
        ? "{$presentacion} Quiero pasar al plan {$planNecesario->nombre} para ver las analíticas."
        : "{$presentacion} Quiero activar las analíticas.";
@endphp

<x-app-layout>
    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header: el mismo del panel del comercio, así se entiende dónde está.
                 Sin botón de volver: para cambiar de comercio está el desplegable del nav. --}}
            <div class="flex items-center space-x-3 mb-8">
                @if($comercio->logo_url)
                    <img src="{{ asset('storage/' . $comercio->logo_url) }}"
                         alt="{{ $comercio->nombre }}"
                         class="w-10 h-10 rounded-full object-cover">
                @endif
                <h2 class="font-semibold text-xl text-text-primary leading-tight flex items-center gap-2">
                    <x-icono name="chart" class="w-5 h-5 flex-none opacity-60" />
                    Analytics · {{ $comercio->nombre }}
                </h2>
            </div>

            <div class="max-w-2xl bg-secondary-dark border border-accent-gray rounded-2xl p-6 sm:p-8">
                <h3 class="text-lg font-medium text-text-primary mb-2">
                    Las analíticas no están incluidas en tu plan
                </h3>
                <p class="text-text-secondary">
                    @if($planNecesario)
                        @if($planActual)
                            {{ $comercio->nombre }} está en el plan
                            <strong class="text-text-primary">{{ $planActual->nombre }}</strong>.
                        @endif
                        Para verlas necesitás el plan
                        <strong class="text-text-primary">{{ $planNecesario->nombre }}</strong>.
                    @else
                        El plan de {{ $comercio->nombre }} no las incluye. Escribinos y vemos cómo activarlas.
                    @endif
                </p>

                <p class="text-sm font-medium text-text-primary mt-6 mb-3">Con las analíticas ves:</p>
                <ul class="space-y-2 text-sm text-text-secondary">
                    @foreach([
                        'Qué le preguntan tus clientes al asistente',
                        'Lo que te piden y no está en tu carta',
                        'Los platos que más les interesan',
                        'Qué días y a qué hora miran tu menú',
                    ] as $item)
                        <li class="flex items-start gap-2">
                            <x-icono name="check" class="w-4 h-4 flex-none mt-0.5 text-accent-green" />
                            {{ $item }}
                        </li>
                    @endforeach
                </ul>

                <div class="flex flex-wrap gap-3 mt-8">
                    @if($soporteWhatsapp)
                        <a href="https://wa.me/{{ $soporteWhatsapp }}?text={{ rawurlencode($mensaje) }}"
                           target="_blank" rel="noopener"
                           class="inline-flex items-center gap-2 bg-accent-blue hover:bg-accent-deep text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                            <x-icono name="messages" class="w-4 h-4 flex-none" />
                            Pedir el cambio por WhatsApp
                        </a>
                    @endif
                    @if($soporteEmail)
                        <a href="mailto:{{ $soporteEmail }}?subject={{ rawurlencode('Cambio de plan · '.$comercio->nombre) }}&amp;body={{ rawurlencode($mensaje) }}"
                           class="{{ $soporteWhatsapp
                                ? 'bg-secondary-dark hover:bg-accent-gray text-text-primary border border-accent-gray font-medium'
                                : 'bg-accent-blue hover:bg-accent-deep text-white font-semibold' }} inline-flex items-center py-2 px-4 rounded-lg transition-colors">
                            Escribinos por mail
                        </a>
                    @endif
                </div>
                <p class="text-xs text-text-secondary mt-3">
                    El cambio de plan lo hacemos nosotros: escribinos y te lo activamos.
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
