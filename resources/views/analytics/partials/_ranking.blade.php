{{--
    Ranking de magnitud, una sola serie => un solo color (accent-blue).
    Ordenado de mayor a menor. Sin leyenda: el título ya nombra la serie.

    @param string $titulo
    @param string $icono
    @param array  $items    [['label' => string, 'total' => int, 'code' => ?string], ...]
    @param int    $visitas  total del período, para calcular el %
    @param string $vacio    mensaje cuando no hay datos
    @param array  $nombres  (opcional) mapa slug => nombre lindo
--}}
@php
    $max = collect($items)->max('total') ?: 1;
    $nombres = $nombres ?? [];
@endphp

<div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
    <h4 class="text-base font-medium text-text-primary mb-5">{{ $icono }} {{ $titulo }}</h4>

    @if (empty($items))
        <p class="text-text-secondary text-sm py-2">{{ $vacio }}</p>
    @else
        <div class="space-y-3">
            @foreach ($items as $item)
                @php
                    $label = $nombres[$item['label']] ?? ucfirst($item['label']);
                    $pct = $visitas > 0 ? round($item['total'] / $visitas * 100) : 0;
                    $ancho = round($item['total'] / $max * 100, 1);   // relativo al mayor
                @endphp
                <div>
                    <div class="flex items-baseline justify-between gap-3 mb-1.5">
                        <span class="text-sm text-text-primary truncate">
                            @isset($item['code'])
                                <span class="text-text-secondary mr-1">{{ $item['code'] }}</span>
                            @endisset
                            {{ $label }}
                        </span>
                        {{-- El valor va en tinta de texto, no del color de la serie --}}
                        <span class="text-sm text-text-secondary whitespace-nowrap tabular-nums">
                            {{ number_format($item['total']) }}
                            <span class="text-text-secondary/60">· {{ $pct }}%</span>
                        </span>
                    </div>
                    {{-- Track recesivo + barra fina con extremo redondeado, anclada a la izquierda --}}
                    <div class="h-1.5 w-full rounded-full bg-white/5 overflow-hidden">
                        <div class="h-full rounded-full bg-accent-blue" style="width: {{ $ancho }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
