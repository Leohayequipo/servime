<x-app-layout>
    @php $isNew = !$plan->exists; @endphp
    <div class="min-h-screen bg-background">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

            <div class="flex items-center justify-between gap-4 mb-10">
                <h1 class="text-3xl font-bold text-text-primary">{{ $isNew ? 'Nuevo plan' : 'Editar plan' }}</h1>
                <a href="{{ route('admin.plans.index') }}" class="btn-secondary">← Planes</a>
            </div>

            @if($errors->any())
                <div class="mb-6 bg-danger/10 border border-danger/30 rounded-2xl p-5">
                    <ul class="list-disc list-inside text-danger text-sm space-y-1">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ $isNew ? route('admin.plans.store') : route('admin.plans.update', $plan) }}"
                  class="bg-secondary-dark border border-accent-gray rounded-2xl p-6 space-y-5">
                @csrf
                @unless($isNew) @method('PUT') @endunless

                <div>
                    <label class="block text-text-secondary text-xs font-medium uppercase tracking-wide mb-2">Nombre (marketinero)</label>
                    <input type="text" name="nombre" value="{{ old('nombre', $plan->nombre) }}" class="input-field" required>
                </div>

                @if($isNew)
                    <div>
                        <label class="block text-text-secondary text-xs font-medium uppercase tracking-wide mb-2">Key interna (opcional)</label>
                        <input type="text" name="key" value="{{ old('key') }}" class="input-field" placeholder="se genera del nombre si lo dejás vacío">
                        <p class="text-text-secondary text-xs mt-1">Identificador estable, no se puede cambiar luego.</p>
                    </div>
                @else
                    <div>
                        <label class="block text-text-secondary text-xs font-medium uppercase tracking-wide mb-2">Key interna</label>
                        <input type="text" value="{{ $plan->key }}" class="input-field opacity-60" disabled>
                    </div>
                @endif

                <div>
                    <label class="block text-text-secondary text-xs font-medium uppercase tracking-wide mb-2">Descripción</label>
                    <input type="text" name="descripcion" value="{{ old('descripcion', $plan->descripcion) }}" class="input-field" maxlength="255">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-text-secondary text-xs font-medium uppercase tracking-wide mb-2">Precio (informativo)</label>
                        <input type="number" step="0.01" min="0" name="precio" value="{{ old('precio', $plan->precio) }}" class="input-field" placeholder="—">
                    </div>
                    <div>
                        <label class="block text-text-secondary text-xs font-medium uppercase tracking-wide mb-2">Orden</label>
                        <input type="number" min="0" name="orden" value="{{ old('orden', $plan->orden ?? 0) }}" class="input-field">
                    </div>
                </div>

                <div>
                    <label class="block text-text-secondary text-xs font-medium uppercase tracking-wide mb-2">Mensajes IA por mes</label>
                    <input type="number" min="0" name="mensajes_ia_mes" value="{{ old('mensajes_ia_mes', $plan->mensajes_ia_mes) }}" class="input-field" placeholder="vacío = ilimitado">
                    <p class="text-text-secondary text-xs mt-1">Dejalo vacío para cuota ilimitada. En <strong>0</strong>, el plan no tiene asistente: el menú no ofrece el chat y entra directo a la carta.</p>
                </div>

                {{-- Cuántas cartas puede procesar con IA. Son dos números y no uno porque al
                     principio el comercio sube, corrige y vuelve a subir; después la carta
                     cambia poco. Cuenta cada carta nueva que sube el comercio;
                     reintentar una carta que falló no cuenta. --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-text-secondary text-xs font-medium uppercase tracking-wide mb-2">Cartas los primeros 30 días</label>
                        <input type="number" min="0" name="cartas_primer_mes" value="{{ old('cartas_primer_mes', $plan->cartas_primer_mes) }}" class="input-field" placeholder="vacío = sin límite">
                    </div>
                    <div>
                        <label class="block text-text-secondary text-xs font-medium uppercase tracking-wide mb-2">Cartas por mes, después</label>
                        <input type="number" min="0" name="cartas_por_mes" value="{{ old('cartas_por_mes', $plan->cartas_por_mes) }}" class="input-field" placeholder="vacío = sin límite">
                    </div>
                </div>
                <p class="text-text-secondary text-xs -mt-3">Cartas procesadas con IA. Dejalo vacío para no poner límite.</p>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-text-secondary text-xs font-medium uppercase tracking-wide mb-2">Modelo IA</label>
                        {{-- La lista sale del modelo e incluye SIEMPRE el que el plan ya tiene,
                             aunque se haya cargado por SQL y no esté entre los ofrecidos: si no,
                             guardar cualquier otro campo del plan lo cambiaba en silencio al
                             primero de la lista. --}}
                        <select name="modelo_ia" class="input-field">
                            @foreach($plan->modelosParaElegir() as $modelo)
                                <option value="{{ $modelo }}" @selected(old('modelo_ia', $plan->modelo_ia) === $modelo)>{{ $modelo }}</option>
                            @endforeach
                        </select>
                        <p class="text-text-secondary text-xs mt-1">
                            <span class="text-accent-blue">gpt-5.6-luna</span> es el recomendado:
                            salió más barato que el mini y es el único que acierta las consultas
                            de dietas (sin TACC, vegano).
                        </p>
                    </div>
                    <div>
                        <label class="block text-text-secondary text-xs font-medium uppercase tracking-wide mb-2">Max tokens chat</label>
                        <input type="number" min="50" max="4000" name="max_tokens_chat" value="{{ old('max_tokens_chat', $plan->max_tokens_chat ?? 900) }}" class="input-field" required>
                    </div>
                </div>

                <div>
                    <label class="block text-text-secondary text-xs font-medium uppercase tracking-wide mb-3">Features incluidas</label>
                    @php $featuresActuales = old('features', $plan->features ?? []); @endphp
                    {{-- Se recorren desde el modelo: cuando los checkboxes estaban escritos
                         a mano acá y la lista válida en el `Rule::in` del controlador, sumar
                         una feature a esta vista sin tocar la otra hacía que el panel la
                         ofreciera y guardar fallara. Ahora agregar una es una línea en
                         `Plan::FEATURES` y aparece en los dos lados. --}}
                    {{-- Las que dependen de otra cuelgan debajo de ella y se tildan juntas
                         (ver `Plan::REQUISITOS` y el script de abajo). --}}
                    <div class="space-y-2">
                        @foreach(\App\Models\Plan::featuresParaElPanel() as $clave => $padre)
                            @php $requisitos = \App\Models\Plan::REQUISITOS[$clave] ?? []; @endphp
                            <div class="{{ $padre ? 'ml-2 pl-5 border-l border-accent-gray' : '' }}">
                                <label class="flex items-center gap-3 text-text-primary text-sm">
                                    <input type="checkbox" name="features[]" value="{{ $clave }}"
                                           @checked(in_array($clave, $featuresActuales))
                                           @if($requisitos) data-requiere="{{ implode(',', $requisitos) }}" @endif
                                           class="rounded border-accent-gray bg-primary-dark text-accent-blue">
                                    {{ \App\Models\Plan::FEATURES[$clave] }}
                                </label>
                                @if(in_array('asistente', $requisitos, true))
                                    <p class="text-text-secondary text-xs ml-7 mt-0.5" data-aviso-de="{{ $clave }}" hidden>
                                        Necesita asistente: con 0 mensajes IA no hay chat.
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <label class="flex items-center gap-3 text-text-primary text-sm">
                    <input type="checkbox" name="activo" value="1" @checked(old('activo', $plan->activo ?? true)) class="rounded border-accent-gray bg-primary-dark text-accent-blue">
                    Plan activo
                </label>

                <div class="pt-2">
                    <button type="submit" class="btn-primary w-full">{{ $isNew ? 'Crear plan' : 'Guardar cambios' }}</button>
                </div>
            </form>

        </div>
    </div>

    <script>
        // Las casillas relacionadas se mueven juntas, en vez de trabar el guardado:
        // - tildar una trae lo que necesita (Comandas tilda Mesas);
        // - destildar una se lleva las que dependen de ella, y si vuelve, vuelven: un
        //   click de más no tiene que hacer perder lo que estaba marcado;
        // - el asistente no es una casilla y su número no se puede inventar: con 0
        //   mensajes, lo que lo necesita queda apagado y dice por qué.
        // Lo mismo hace `Plan::conRequisitos()` al guardar.
        (function () {
            const mensajes = document.querySelector('input[name="mensajes_ia_mes"]');
            const casillas = [...document.querySelectorAll('input[name="features[]"]')];
            const casilla = clave => casillas.find(cb => cb.value === clave);
            const requisitos = cb => (cb.dataset.requiere || '').split(',').filter(Boolean);

            // Vacío es ilimitado: sólo el 0 es "sin asistente".
            const hayAsistente = () => mensajes.value.trim() === '' || Number(mensajes.value) !== 0;

            const traerRequisitos = cb => requisitos(cb).forEach(r => { if (casilla(r)) casilla(r).checked = true; });

            function actualizar(tocada) {
                if (tocada?.checked) {
                    traerRequisitos(tocada);
                }

                // En el orden del panel, que pone cada una debajo de la que necesita.
                casillas.filter(cb => cb.dataset.requiere).forEach(cb => {
                    const ok = requisitos(cb).every(r => r === 'asistente' ? hayAsistente() : casilla(r)?.checked);

                    if (! ok && cb.checked) {
                        cb.checked = false;
                        cb.dataset.apagadaSola = '1';
                    } else if (ok && cb.dataset.apagadaSola) {
                        cb.checked = true;
                    }
                    if (ok || cb === tocada) {
                        delete cb.dataset.apagadaSola;
                    }

                    const sinAsistente = requisitos(cb).includes('asistente') && ! hayAsistente();
                    cb.disabled = sinAsistente;
                    cb.closest('label').classList.toggle('opacity-50', sinAsistente);
                    const aviso = document.querySelector(`[data-aviso-de="${cb.value}"]`);
                    if (aviso) aviso.hidden = ! sinAsistente;
                });
            }

            mensajes.addEventListener('input', () => actualizar());
            casillas.forEach(cb => cb.addEventListener('change', () => actualizar(cb)));

            // Un plan guardado antes de esto puede tener Comandas sin Mesas: se completa,
            // igual que al guardar, en vez de sacarle lo que tenía.
            casillas.filter(cb => cb.checked).forEach(traerRequisitos);
            actualizar();
        })();
    </script>
</x-app-layout>
