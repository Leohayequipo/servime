<x-app-layout>
    <div class="min-h-screen bg-background">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-text-primary">Analytics de la Web</h1>
                    <p class="text-text-secondary mt-1">De dónde entran los visitantes de la landing</p>
                </div>
                <a href="{{ route('dashboard') }}" class="btn-secondary">← Dashboard</a>
            </div>

            <!-- Filtro de período (una sola fila, arriba de todo) -->
            <div class="flex flex-wrap items-center gap-2 mb-8">
                @foreach ([7 => '7 días', 30 => '30 días', 90 => '90 días', 365 => '1 año'] as $d => $txt)
                    <a href="{{ route('admin.web-analytics.index', ['dias' => $d]) }}"
                       class="px-4 py-2 rounded-xl text-sm transition-all
                              {{ $dias === $d
                                 ? 'bg-accent-blue text-gray-900 font-semibold'
                                 : 'text-text-secondary border border-white/10 hover:border-accent-blue/40' }}">
                        {{ $txt }}
                    </a>
                @endforeach
            </div>

            <!-- KPIs: números sueltos, no necesitan gráfico -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="metric-card">
                    <p class="text-text-secondary text-sm">Visitas</p>
                    <p class="text-3xl font-bold text-text-primary mt-1 tabular-nums">{{ number_format($visitas) }}</p>
                    <p class="text-text-secondary/60 text-xs mt-1">últimos {{ $dias }} días</p>
                </div>
                <div class="metric-card">
                    <p class="text-text-secondary text-sm">Visitantes únicos</p>
                    <p class="text-3xl font-bold text-text-primary mt-1 tabular-nums">{{ number_format($visitantes) }}</p>
                    <p class="text-text-secondary/60 text-xs mt-1">personas distintas</p>
                </div>
                <div class="metric-card">
                    <p class="text-text-secondary text-sm">Canal principal</p>
                    <p class="text-3xl font-bold text-text-primary mt-1 capitalize">{{ $canalTop }}</p>
                    <p class="text-text-secondary/60 text-xs mt-1">de donde más llegan</p>
                </div>
            </div>

            <!-- Rankings -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                @include('admin.web-analytics._ranking', [
                    'titulo'  => 'Canal de entrada',
                    'icono'   => '🔗',
                    'items'   => $porCanal,
                    'visitas' => $visitas,
                    'vacio'   => 'Todavía no hay visitas en este período.',
                    'nombres' => [
                        'directo'      => 'Directo (escribió la URL)',
                        'instagram'    => 'Instagram',
                        'facebook'     => 'Facebook',
                        'whatsapp'     => 'WhatsApp',
                        'google'       => 'Google',
                        'tiktok'       => 'TikTok',
                        'youtube'      => 'YouTube',
                        'linkedin'     => 'LinkedIn',
                        'x'            => 'X (Twitter)',
                        'bing'         => 'Bing',
                        'mercadolibre' => 'Mercado Libre',
                        'otro'         => 'Otros sitios',
                    ],
                ])

                @include('admin.web-analytics._ranking', [
                    'titulo'  => 'Dispositivo',
                    'icono'   => '📱',
                    'items'   => $porDispositivo,
                    'visitas' => $visitas,
                    'vacio'   => 'Sin datos de dispositivo todavía.',
                    'nombres' => [
                        'mobile'      => 'Celular',
                        'desktop'     => 'Computadora',
                        'tablet'      => 'Tablet',
                        'desconocido' => 'Desconocido',
                    ],
                ])

                @include('admin.web-analytics._ranking', [
                    'titulo'  => 'País',
                    'icono'   => '🌎',
                    'items'   => $porPais,
                    'visitas' => $visitas,
                    'vacio'   => 'Todavía no se geolocalizó ninguna visita.',
                ])

                @include('admin.web-analytics._ranking', [
                    'titulo'  => 'Ciudad',
                    'icono'   => '📍',
                    'items'   => $porCiudad,
                    'visitas' => $visitas,
                    'vacio'   => 'Todavía no se geolocalizó ninguna visita.',
                ])
            </div>

            @if ($sinGeo > 0)
                <p class="text-text-secondary/60 text-xs mt-6">
                    {{ number_format($sinGeo) }} {{ $sinGeo === 1 ? 'visita sin ubicar' : 'visitas sin ubicar' }}
                    (geolocalización en cola, IP local, o el proveedor no la reconoció).
                    Los porcentajes de país y ciudad se calculan sobre el total de visitas.
                </p>
            @endif

        </div>
    </div>
</x-app-layout>
