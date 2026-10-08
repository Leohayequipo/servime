<x-app-layout>
    <div class="min-h-screen bg-background">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

            <div class="flex flex-wrap items-center justify-between gap-4 mb-10">
                <div>
                    <h1 class="text-4xl font-bold text-text-primary mb-2">
                        🪑 Mesas de {{ $comercio->nombre }}
                    </h1>
                    <p class="text-lg text-text-secondary">
                        Un código QR por mesa, para saber desde dónde te consultan y te piden.
                    </p>
                </div>
                <a href="{{ route('menus.index', $comercio) }}" class="btn-secondary">← Volver a los menús</a>
            </div>

            <x-flash />

            @if($errors->any())
                <div class="mb-8">
                    <div class="bg-danger/15 border border-danger/30 rounded-2xl p-5">
                        <p class="text-text-primary font-medium">{{ $errors->first() }}</p>
                    </div>
                </div>
            @endif

            {{-- Cuántas mesas hay. Es la acción principal de la pantalla: el comerciante
                 no da de alta mesa por mesa, dice cuántas tiene y el sistema las numera. --}}
            <div class="metric-card mb-10">
                <form method="POST" action="{{ route('mesas.sincronizar', $comercio) }}"
                      class="flex flex-wrap items-end gap-4"
                      onsubmit="return confirmarCantidad(event, this);">
                    @csrf
                    <div class="flex-1 min-w-[240px]">
                        <label for="cantidad" class="block text-sm font-semibold text-text-primary mb-2">
                            ¿Cuántas mesas tiene el local?
                        </label>
                        <input type="number" name="cantidad" id="cantidad" class="input-field"
                               min="0" max="{{ \App\Services\MesaService::MAX_MESAS }}"
                               value="{{ old('cantidad', $activas) }}" required>
                        <p class="text-xs text-text-secondary mt-2">
                            Se numeran de la 1 en adelante. Si bajás el número, las de más
                            quedan fuera de servicio pero no se borran: su historial se conserva
                            y volver a subirlo las reactiva con el mismo QR.
                        </p>
                    </div>
                    <button type="submit" class="btn-primary">Guardar mesas</button>
                </form>
            </div>

            {{-- Pantalla del mozo. Va acá y no en una sección propia porque no tiene
                 sentido sin mesas: es la vista de las comandas de esas mesas. --}}
            @if($comercio->tieneFeature('comandas') || auth()->user()->isSuperAdmin())
                <div class="metric-card mb-10">
                    <h2 class="text-xl font-bold text-text-primary mb-1">📋 Pantalla del mozo</h2>
                    <p class="text-text-secondary text-sm mb-4">
                        Un link para abrir en la tablet del local: muestra las mesas con lo que
                        pidieron, en vivo. Entra cualquiera del turno con un PIN, sin usuario
                        ni contraseña personal.
                    </p>

                    @if($comercio->tieneAccesoMozo())
                        <div class="rounded-xl border border-accent-gray bg-primary-dark p-4 mb-4 flex flex-wrap gap-4 items-start">
                            <div class="flex-1 min-w-[240px]">
                                <p class="text-xs text-text-secondary uppercase tracking-wide mb-1">Link de la tablet</p>
                                <p class="text-text-primary break-all font-mono text-sm">{{ route('mozo.pin', $comercio->mozo_token) }}</p>
                                <p class="text-text-secondary text-xs mt-2">
                                    Cualquiera con este link y el PIN ve las comandas del local. Si se filtra,
                                    generá uno nuevo acá abajo.
                                </p>
                            </div>
                            {{-- Escanearlo con la tablet evita tipear 24 caracteres al azar,
                                 que es donde se pierde el tiempo al configurar el local. --}}
                            <div class="text-center">
                                <img src="{{ $qrMozo ?? route('mozo.qr', $comercio) }}"
                                     alt="QR para abrir la pantalla del mozo"
                                     class="w-28 h-28 rounded-lg bg-white p-1">
                                <p class="text-text-secondary text-xs mt-1">Escanealo con la tablet</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('mozo.pin', $comercio->mozo_token) }}" target="_blank" class="btn-primary">
                                Abrir la pantalla
                            </a>

                            <form method="POST" action="{{ route('mozo.rotar', $comercio) }}"
                                  onsubmit="event.preventDefault(); const f=this; showConfirmWithLoader('Vamos a generar un link nuevo. Las tablets que estén abiertas con el actual van a tener que entrar otra vez. ¿Seguimos?', r => { if(r) f.submit(); }, 'Generando…');">
                                @csrf
                                <button type="submit" class="btn-secondary">Generar link nuevo</button>
                            </form>

                            <form method="POST" action="{{ route('mozo.desactivar', $comercio) }}"
                                  onsubmit="event.preventDefault(); const f=this; showConfirmWithLoader('Se apaga la pantalla del mozo y el botón «Pedir» del menú. Lo que ya está pedido no se borra. ¿Seguimos?', r => { if(r) f.submit(); }, 'Desactivando…');">
                                @csrf
                                <button type="submit" class="btn-secondary">Desactivar</button>
                            </form>
                        </div>

                        <form method="POST" action="{{ route('mozo.activar', $comercio) }}" class="mt-5 pt-5 border-t border-accent-gray/40">
                            @csrf
                            <label for="pin-nuevo" class="block text-sm font-semibold text-text-primary mb-2">
                                Cambiar el PIN
                            </label>
                            <div class="flex flex-wrap gap-3">
                                <input type="text" name="pin" id="pin-nuevo" inputmode="numeric"
                                       class="input-field max-w-[200px]" minlength="4" maxlength="8"
                                       placeholder="4 a 8 dígitos" required>
                                <button type="submit" class="btn-secondary">Guardar PIN</button>
                            </div>
                            <p class="text-xs text-text-secondary mt-2">
                                El PIN se guarda encriptado, así que no te lo podemos volver a mostrar:
                                si te lo olvidás, poné uno nuevo acá.
                            </p>
                        </form>
                    @else
                        <form method="POST" action="{{ route('mozo.activar', $comercio) }}">
                            @csrf
                            <label for="pin" class="block text-sm font-semibold text-text-primary mb-2">
                                Elegí un PIN para el local
                            </label>
                            <div class="flex flex-wrap gap-3">
                                <input type="text" name="pin" id="pin" inputmode="numeric"
                                       class="input-field max-w-[200px]" minlength="4" maxlength="8"
                                       placeholder="4 a 8 dígitos" required>
                                <button type="submit" class="btn-primary">Activar</button>
                            </div>
                        </form>
                    @endif
                </div>
            @endif

            @if($mesas->isEmpty())
                <div class="metric-card text-center py-16">
                    <p class="text-5xl mb-4">🪑</p>
                    <h2 class="text-2xl font-bold text-text-primary mb-2">Todavía no cargaste mesas</h2>
                    <p class="text-text-secondary">
                        Poné arriba cuántas mesas tenés y generamos un QR para cada una.
                    </p>
                </div>
            @else
                <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                    <p class="text-text-secondary">
                        <span class="text-text-primary font-semibold">{{ $activas }}</span> en servicio
                        @if($mesas->count() > $activas)
                            · {{ $mesas->count() - $activas }} fuera de servicio
                        @endif
                    </p>
                    <a href="{{ route('mesas.imprimir', $comercio) }}" target="_blank" class="btn-success">
                        🖨️ Imprimir todas
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach($mesas as $mesa)
                        <div class="metric-card {{ $mesa->activa ? '' : 'opacity-50' }}">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <p class="text-3xl font-bold text-text-primary">Mesa {{ $mesa->numero }}</p>
                                    @if($mesa->etiqueta)
                                        <p class="text-sm text-text-secondary">{{ $mesa->etiqueta }}</p>
                                    @endif
                                </div>
                                @unless($mesa->activa)
                                    <span class="text-xs font-semibold text-warning border border-warning/40 rounded-full px-3 py-1">
                                        Fuera de servicio
                                    </span>
                                @endunless
                            </div>

                            {{-- Embebido y no por URL: ver MesaController::index (la CDN). --}}
                            <img src="{{ $qrPorMesa[$mesa->id] }}"
                                 alt="Código QR de la mesa {{ $mesa->numero }}"
                                 class="w-full rounded-xl bg-white p-2 mb-4">

                            <form method="POST" action="{{ route('mesas.update', [$comercio, $mesa]) }}" class="mb-3">
                                @csrf
                                @method('PUT')
                                <div class="flex gap-2">
                                    <input type="text" name="etiqueta" maxlength="40"
                                           value="{{ $mesa->etiqueta }}" placeholder="Barra, Terraza…"
                                           class="input-field text-sm py-2">
                                    <button type="submit" class="btn-secondary px-4 py-2 text-xs">Guardar</button>
                                </div>
                            </form>

                            <div class="flex flex-wrap gap-2">
                                {{-- Entra al menú como si hubiera escaneado el QR de esta mesa.
                                     Sirve para probar sin imprimir nada, y para que el
                                     comerciante vea exactamente lo que ve un cliente sentado
                                     ahí (con el "Mesa N" y el botón de pedir). --}}
                                <a href="{{ $mesa->urlPublica() }}" target="_blank"
                                   class="btn-secondary px-4 py-2 text-xs">👁️ Abrir</a>

                                <a href="{{ $qrPorMesa[$mesa->id] }}"
                                   download="qr-{{ $comercio->slug }}-mesa-{{ $mesa->numero }}.png"
                                   class="btn-secondary px-4 py-2 text-xs">⬇️ PNG</a>

                                <form method="POST" action="{{ route('mesas.rotar', [$comercio, $mesa]) }}"
                                      onsubmit="event.preventDefault(); const f=this; showConfirmWithLoader('Le vamos a dar un código nuevo a la mesa {{ $mesa->numero }}. El cartel que está pegado ahora deja de funcionar y hay que reimprimirlo. ¿Seguimos?', r => { if(r) f.submit(); }, 'Generando…');">
                                    @csrf
                                    <button type="submit" class="btn-secondary px-4 py-2 text-xs">🔄 Código nuevo</button>
                                </form>

                                <form method="POST" action="{{ route('mesas.toggle', [$comercio, $mesa]) }}">
                                    @csrf
                                    <button type="submit" class="btn-secondary px-4 py-2 text-xs">
                                        {{ $mesa->activa ? '⏸️ Dar de baja' : '▶️ Reactivar' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <script>
        // El auto-cierre del cartel de éxito lo trae el componente de avisos.

        // Bajar la cantidad saca mesas de servicio: se avisa antes, con el número exacto.
        // Subirla o dejarla igual no necesita confirmación.
        const mesasEnServicio = {{ $activas }};

        function confirmarCantidad(event, form) {
            const nueva = parseInt(form.cantidad.value, 10);

            if (isNaN(nueva) || nueva >= mesasEnServicio) {
                return true;
            }

            event.preventDefault();
            const bajan = mesasEnServicio - nueva;
            showConfirmWithLoader(
                `Vas a dejar ${bajan} mesa(s) fuera de servicio. Sus códigos QR dejan de asignar mesa, ` +
                `pero no se borra nada: si volvés a subir el número, se reactivan con el mismo QR. ¿Seguimos?`,
                r => { if (r) form.submit(); },
                'Guardando…'
            );
            return false;
        }
    </script>
</x-app-layout>
