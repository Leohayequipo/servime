<x-app-layout>
    {{-- Mientras la IA lee el archivo, esta pantalla es TODO lo que se ve del menú.

         No es sólo prolijidad: el editor guarda con un update sobre el mismo menú que el
         job está escribiendo, así que alguien que tocara "Actualizar" en el medio podía
         pisar el contenido recién extraído. Además no hay nada útil que hacer todavía
         —los platos no existen— y el progreso quedaba al fondo de una pantalla larguísima,
         donde había que ir a buscarlo.

         Cuando el job termina, el polling recarga y `MenuController@edit` ya deja pasar al
         editor de siempre. --}}
    <div class="min-h-screen">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

            <div class="flex items-center justify-between mb-10">
                <div>
                    <h1 class="text-3xl font-bold text-text-primary mb-2">
                        {{ $menu->nombre ?? 'Menú nuevo' }}
                    </h1>
                    <p class="text-text-secondary mt-0.5">{{ $comercio->nombre }}</p>
                </div>
                <a href="{{ route('menus.index', $comercio) }}"
                   class="bg-secondary-dark hover:bg-accent-gray text-text-primary font-medium py-3 px-6 rounded-xl transition-all duration-300 border border-accent-gray">
                    ← Volver
                </a>
            </div>

            <div class="metric-card" id="menuProceso" data-estado="{{ $menu->estado }}">
                <div class="text-center py-12 px-6">
                    <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-accent-blue mx-auto mb-6"></div>

                    <h2 class="text-2xl font-medium text-text-primary mb-3">Estamos leyendo tu menú…</h2>

                    <p class="text-text-secondary max-w-md mx-auto">
                        La IA está sacando los platos, las categorías y los precios del archivo.
                        Suele tardar entre medio minuto y un par de minutos.
                    </p>

                    <p class="text-text-secondary text-sm mt-4" id="menuProcesoReloj" aria-live="polite"></p>

                    {{-- Recién a los dos minutos: antes de eso la demora es normal y avisar sólo
                         asusta. Pasado ese rato, casi siempre es el worker de la cola caído. --}}
                    <p class="text-text-secondary text-sm mt-2 hidden" id="menuProcesoDemora">
                        Está tardando más de lo habitual. Si sigue así, escribinos.
                    </p>

                    <div class="mt-8 pt-6 border-t border-accent-gray/60 max-w-md mx-auto">
                        <p class="text-text-secondary text-sm">
                            No hace falta que esperes acá: cuando termine te llega un mail, y el
                            menú te espera listo para editar.
                        </p>
                    </div>
                </div>
            </div>

            @if($menu->archivo_original)
                <p class="text-center text-text-secondary text-xs mt-6">
                    @if($menu->tieneVariasFotos())
                        {{ count($menu->archivosEnOrden()) }} fotos, leídas juntas como una sola carta
                    @else
                        Archivo: {{ basename($menu->archivo_original) }}
                    @endif
                </p>
            @endif

        </div>
    </div>

    <script>
        (function () {
            const URL_ESTADO = @json(route('menus.estado', [$comercio, $menu]));

            const reloj = document.getElementById('menuProcesoReloj');
            const demora = document.getElementById('menuProcesoDemora');

            const desde = Date.now();
            let timer = null;

            function tick() {
                const seg = Math.floor((Date.now() - desde) / 1000);
                reloj.textContent = seg < 60
                    ? `Van ${seg} segundos…`
                    : `Van ${Math.floor(seg / 60)} min ${String(seg % 60).padStart(2, '0')} s…`;
                if (seg > 120) demora.classList.remove('hidden');
            }

            async function consultar() {
                try {
                    const r = await fetch(URL_ESTADO, { headers: { 'Accept': 'application/json' } });
                    if (!r.ok) return;
                    const data = await r.json();

                    // Listo o con error, las dos salidas están del otro lado de una recarga:
                    // el editor muestra los platos, o la caja de error con "Volver a intentar".
                    if (data.listo || data.error) {
                        clearInterval(timer);
                        window.location.reload();
                    }
                } catch (e) {
                    // Sin conexión: se reintenta en el próximo ciclo.
                }
            }

            tick();
            timer = setInterval(function () { tick(); consultar(); }, 2000);
        })();
    </script>
</x-app-layout>
