{{--
    Los carteles de sesión del panel: éxito, error, atención e info.

    Único lugar donde se define cómo se ve un aviso. Antes el mismo cartel estaba
    copiado con CINCO markups distintos, y tres de ellos habían quedado con la paleta
    de la etapa oscura (`text-green-200` sobre `bg-green-900/20`): sobre el crema del
    panel, eso es texto casi blanco sobre un fondo casi blanco. El de «Comercio creado
    exitosamente» era ilegible.

    El texto va en tinta (9,36:1 sobre el crema) y el color semántico queda en el ícono
    y el borde, que es donde comunica sin costar legibilidad. Ver la skill `identidad`.

    Uso: <x-flash /> — lee lo que haya en sesión y no imprime nada si no hay nada.

    Las clases van escritas enteras y no armadas con variables porque Tailwind las
    busca como texto literal en el archivo: una clase compuesta en PHP no se compila.
--}}
@php
    $avisos = [];

    if (session('success')) {
        $avisos[] = [
            'texto' => session('success'),
            'icono' => '✓',
            'caja' => 'bg-accent-green/10 border-accent-green/40',
            'marca' => 'bg-accent-green/15 text-accent-green',
            // Sólo el éxito se va solo: un error tiene que poder leerse con calma.
            'efimero' => true,
        ];
    }

    if (session('error')) {
        $avisos[] = [
            'texto' => session('error'),
            'icono' => '✕',
            'caja' => 'bg-danger/10 border-danger/40',
            'marca' => 'bg-danger/15 text-danger',
            'efimero' => false,
        ];
    }

    if (session('warning')) {
        $avisos[] = [
            'texto' => session('warning'),
            'icono' => '!',
            'caja' => 'bg-warning/10 border-warning/40',
            'marca' => 'bg-warning/15 text-warning',
            'efimero' => false,
        ];
    }

    if (session('info')) {
        $avisos[] = [
            'texto' => session('info'),
            'icono' => 'i',
            'caja' => 'bg-accent-blue/10 border-accent-blue/40',
            'marca' => 'bg-accent-blue/15 text-accent-blue',
            'efimero' => false,
        ];
    }
@endphp

@foreach ($avisos as $aviso)
    <div class="mb-6 {{ $aviso['efimero'] ? 'js-flash' : '' }}">
        <div class="flex items-center gap-3 rounded-2xl border {{ $aviso['caja'] }} px-4 py-3">
            <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full text-sm font-bold {{ $aviso['marca'] }}">
                {{ $aviso['icono'] }}
            </span>
            <p class="text-sm font-medium text-text-primary">{{ $aviso['texto'] }}</p>
        </div>
    </div>
@endforeach

{{-- Inline y no en un @push: el layout del panel no tiene ningún @stack.
     @once por si la pantalla usa el componente más de una vez. --}}
@if ($avisos)
    @once
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.js-flash').forEach(function (el) {
                    setTimeout(function () {
                        el.style.transition = 'opacity .5s ease';
                        el.style.opacity = '0';
                        setTimeout(function () { el.remove(); }, 500);
                    }, 4000);
                });
            });
        </script>
    @endonce
@endif
