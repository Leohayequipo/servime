{{--
    Avisos del comercio (notificaciones de Laravel guardadas en base).

    Sólo entra lo accionable: cupo del asistente por agotarse o agotado, menú listo o
    con error, y cambio de plan. Nada por consulta del chat ni por "Lo quiero": en un
    local lleno son cientos por día y la campanita se vuelve ruido de fondo.

    Los datos de cada aviso tienen siempre la misma forma, la arman las clases de
    App\Notifications: tipo, titulo, detalle, url.
--}}
@php
    $user = auth()->user();
    $avisos = $user->unreadNotifications()->take(8)->get();
    $sinLeer = $user->unreadNotifications()->count();

    // El ícono y su color salen del tipo. `warning` y `danger` son semánticos: un aviso
    // de cupo tiene que leerse como aviso aunque cambie la identidad.
    $iconos = [
        'cuota' => ['alert', 'text-warning'],
        'menu_error' => ['alert', 'text-danger'],
        'menu_revisar' => ['alert', 'text-warning'],
        'menu_listo' => ['check', 'text-accent-green'],
        'plan' => ['check', 'text-accent-green'],
    ];
@endphp

<div class="relative" id="campanita">
    <button type="button"
            onclick="document.getElementById('panelAvisos').classList.toggle('hidden')"
            class="relative p-2 rounded-lg text-text-secondary hover:text-accent-deep hover:bg-primary-dark transition-colors"
            aria-label="Avisos{{ $sinLeer > 0 ? ' ('.$sinLeer.' sin leer)' : '' }}">
        <x-icono name="campana" class="w-6 h-6" />
        @if($sinLeer > 0)
            <span class="absolute top-0.5 right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-accent-blue text-white text-[11px] font-bold flex items-center justify-center">
                {{ $sinLeer > 9 ? '9+' : $sinLeer }}
            </span>
        @endif
    </button>

    <div id="panelAvisos"
         class="hidden absolute right-0 mt-2 w-80 sm:w-96 max-w-[calc(100vw-2rem)] bg-secondary-dark border border-accent-gray rounded-xl shadow-lg z-50 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-accent-gray">
            <p class="text-sm font-medium text-text-primary">Avisos</p>
            @if($sinLeer > 0)
                <form method="POST" action="{{ route('avisos.leidos') }}">
                    @csrf
                    <button type="submit" class="text-xs text-text-secondary hover:text-accent-deep transition-colors">
                        Marcar todo como leído
                    </button>
                </form>
            @endif
        </div>

        <div class="max-h-[60vh] overflow-y-auto">
            @forelse($avisos as $aviso)
                @php [$icono, $color] = $iconos[$aviso->data['tipo'] ?? ''] ?? ['alert', 'text-text-secondary']; @endphp
                <a href="{{ route('avisos.abrir', $aviso->id) }}"
                   class="flex gap-3 px-4 py-3 border-b border-accent-gray/40 last:border-0 hover:bg-primary-dark transition-colors">
                    <x-icono :name="$icono" class="w-5 h-5 flex-none mt-0.5 {{ $color }}" />
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-text-primary">{{ $aviso->data['titulo'] ?? 'Aviso' }}</p>
                        <p class="text-sm text-text-secondary">{{ $aviso->data['detalle'] ?? '' }}</p>
                        <p class="text-xs text-text-secondary/70 mt-1">{{ $aviso->created_at->diffForHumans() }}</p>
                    </div>
                </a>
            @empty
                <p class="px-4 py-6 text-sm text-text-secondary text-center">
                    No tenés avisos nuevos.
                </p>
            @endforelse
        </div>
    </div>
</div>

<script>
    // Cerrar al tocar afuera. Sin esto, en mobile el panel tapa media pantalla y la única
    // forma de sacarlo es volver a dar justo en la campanita.
    document.addEventListener('click', function (e) {
        const caja = document.getElementById('campanita');
        const panel = document.getElementById('panelAvisos');
        if (caja && panel && !caja.contains(e.target)) {
            panel.classList.add('hidden');
        }
    });
</script>
