{{--
    Estado de la configuración del comercio: qué está hecho y qué falta.

    Aparece en la pantalla de menús, que es donde cae el comerciante apenas crea su
    comercio. Antes el alta lo dejaba en el dashboard con un «creado exitosamente» y
    nada más: sin menú, sin portada, sin horarios, y sin forma de enterarse de que
    faltaba todo eso salvo recorriendo pantallas.

    Se muestra siempre, con dos estados:
      - Falta algo: el checklist completo, lo pendiente con el link para resolverlo y
        lo ya hecho tildado — así se ve el progreso, no sólo la deuda.
      - Está todo: se achica a una línea. Un checklist de cuatro con todo tildado,
        ocupando lo mismo en cada visita durante meses, es ruido permanente; la barra
        angosta sigue siendo visible sin pesar en la pantalla de trabajo.
--}}
@props(['comercio'])

@php
    $pasos = $comercio->pasosConfiguracion();
    $pendientes = array_values(array_filter($pasos, fn ($p) => ! $p['completo']));
@endphp

@if ($pendientes)
    <div class="mb-8 rounded-2xl border border-accent-blue/40 bg-accent-blue/5 p-5">
        {{-- Sin contador. Había un círculo con la cantidad de pasos pendientes y se leía
             como una numeración: un número dentro de un círculo, pegado arriba de una
             lista, es el patrón visual de "paso 1 de N", así que la pregunta obvia era
             dónde estaban el 2 y el 3. Encima era ambiguo al revés (¿falta 1 o hice 1?).
             Los tildes ya dicen qué está hecho y qué no. --}}
        <div class="mb-4">
            <p class="font-semibold text-text-primary">Para terminar de configurar tu menú</p>
            <p class="text-xs text-text-secondary">
                Tu menú ya funciona, pero con esto queda como tiene que estar.
            </p>
        </div>

        <ul class="space-y-2">
            @foreach ($pasos as $paso)
                <li>
                    <a href="{{ $paso['ruta'] }}"
                       class="flex items-start gap-3 rounded-xl border px-4 py-3 transition-all duration-200
                              {{ $paso['completo']
                                  ? 'border-accent-gray bg-secondary-dark/40 opacity-60 hover:opacity-100'
                                  : 'border-accent-gray bg-secondary-dark hover:border-accent-blue/40 hover:-translate-y-px' }}">
                        <span class="mt-0.5 {{ $paso['completo'] ? 'text-accent-green' : 'text-text-secondary' }}">
                            {{ $paso['completo'] ? '✓' : '○' }}
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-medium text-text-primary {{ $paso['completo'] ? 'line-through decoration-accent-gray' : '' }}">
                                {{ $paso['texto'] }}
                            </span>
                            @unless ($paso['completo'])
                                <span class="block text-xs text-text-secondary mt-0.5">{{ $paso['detalle'] }}</span>
                            @endunless
                        </span>
                        @unless ($paso['completo'])
                            <span class="text-accent-blue text-sm flex-none">→</span>
                        @endunless
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@else
    {{-- Todo listo: una línea, no el checklist de cuatro. Sigue visible —como para
         cualquier estado de este bloque— pero no pesa en la pantalla de trabajo. --}}
    <div class="mb-8 flex items-center gap-2 rounded-xl border border-accent-green/30 bg-accent-green/5 px-4 py-2.5">
        <span class="text-accent-green">✓</span>
        <p class="text-sm text-text-primary">
            <span class="font-medium">Tu menú está configurado.</span>
            <span class="text-text-secondary">Portada, horarios y logo — todo cargado.</span>
        </p>
    </div>
@endif
