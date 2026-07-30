@props([
    'label',
    'value',
    'nota' => null,      // texto chico debajo del número
    'delta' => null,     // variación % contra el período anterior (int, puede ser negativo)
    'serie' => null,     // array<int> para el sparkline
    'tono' => 'neutro',  // neutro | alerta | ok
])

{{--
    Tarjeta de métrica.

    Antes cada una tenía un círculo pastel de color distinto (azul, verde, púrpura,
    naranja) con un icono adentro: es el look de plantilla de admin y los cuatro colores
    no significaban nada. Acá el color aparece SOLO cuando comunica algo (tono alerta),
    y el dato gana contexto con la variación contra el período anterior y un sparkline.

    Un número solo ("159") no se puede interpretar; "159, +18%" sí.
--}}

@php
    $serie = is_array($serie) ? array_values($serie) : null;
    $max = $serie ? max($serie) : 0;

    // Sparkline como polyline en un viewBox de 100x28. Si todos los valores son iguales
    // se dibuja plano al medio en lugar de pegado al piso.
    $puntos = null;
    if ($serie && count($serie) > 1) {
        $n = count($serie) - 1;
        $puntos = collect($serie)->map(function ($v, $i) use ($n, $max) {
            $x = round($i / $n * 100, 2);
            $y = $max > 0 ? round(26 - ($v / $max) * 24, 2) : 14;

            return $x.','.$y;
        })->implode(' ');
    }

    $colorValor = $tono === 'alerta' ? 'text-amber-500' : 'text-text-primary';
@endphp

<div class="bg-secondary-dark border border-accent-gray rounded-2xl p-5 flex flex-col">
    <div class="flex items-start justify-between gap-2">
        <p class="text-xs font-medium uppercase tracking-wide text-text-secondary">{{ $label }}</p>
        @if($delta !== null)
            {{-- La variación se colorea por dirección, no por gusto: es el único color
                 que aporta información en la tarjeta. --}}
            <span class="flex-none inline-flex items-center gap-0.5 text-xs font-semibold
                         {{ $delta > 0 ? 'text-emerald-500' : ($delta < 0 ? 'text-rose-400' : 'text-text-secondary') }}">
                @if($delta > 0)▲@elseif($delta < 0)▼@endif{{ abs($delta) }}%
            </span>
        @endif
    </div>

    <p class="mt-2 text-3xl font-semibold tracking-tight {{ $colorValor }}" style="font-variant-numeric: tabular-nums;">
        {{ $value }}
    </p>

    @if($nota)
        <p class="mt-0.5 text-xs text-text-secondary">{{ $nota }}</p>
    @endif

    @if($puntos)
        <svg class="mt-auto pt-3 w-full h-7 {{ $tono === 'alerta' ? 'text-amber-500' : 'text-text-secondary' }}"
             viewBox="0 0 100 28" preserveAspectRatio="none" fill="none" aria-hidden="true">
            <polyline points="{{ $puntos }}" stroke="currentColor" stroke-width="1.5"
                      stroke-linecap="round" stroke-linejoin="round" opacity="0.9" vector-effect="non-scaling-stroke"/>
        </svg>
    @endif
</div>
