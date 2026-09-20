@props([
    'id',
    'titulo',
    'icono' => '',
    'resumen' => '',
    'lista' => null,        // true ✓ · false ○ · null sin indicador
    'preview' => null,      // URL que muestra el celular cuando esta sección está abierta
    'previewTitulo' => '',
    'abierta' => false,
    'local' => false,       // configuración del comercio: aplica a todos sus menús
    'sinCabecera' => false, // sólo el contenido, abierto: la pantalla de crear no tiene secciones
])

{{-- Una sección del editor del menú.

     Cerrada ocupa una línea con su resumen, así se ve de un vistazo qué está hecho sin
     bajar por todas las tarjetas. Abierta muestra adentro las mismas tarjetas de siempre:
     este componente sólo las agrupa, no cambia cómo se guarda nada.

     Se pliega con `hidden` y no sacando el contenido del HTML: el JavaScript de la pantalla
     usa esos elementos sin chequear que existan, y quitarlos rompía los scripts.

     Al abrirse le avisa al celular de la vista previa qué pantalla mostrar (data-preview). --}}
@if($sinCabecera)
    <section id="seccion-{{ $id }}" data-seccion="{{ $id }}">{{ $slot }}</section>
@else
    {{-- Cabecera y contenido en UN solo panel, con el mismo borde. Antes eran cajas sueltas
         y lo de adentro se leía como más secciones en vez de como el contenido de ésta. --}}
    <section class="editor-seccion scroll-mt-40 bg-secondary-dark border border-accent-gray rounded-2xl overflow-hidden"
             id="seccion-{{ $id }}" data-seccion="{{ $id }}"
             @if($preview) data-preview="{{ $preview }}" data-preview-titulo="{{ $previewTitulo }}" @endif>
        <button type="button"
                class="editor-seccion-cabecera w-full flex items-center gap-4 text-left px-5 sm:px-6 py-5 hover:bg-primary-dark/50 transition-colors"
                aria-expanded="{{ $abierta ? 'true' : 'false' }}"
                aria-controls="seccion-{{ $id }}-cuerpo">
            @if($icono)
                <span class="w-10 h-10 bg-accent-blue/10 rounded-xl flex items-center justify-center flex-shrink-0 text-lg">{{ $icono }}</span>
            @endif

            <span class="flex-1 min-w-0">
                <span class="flex items-center gap-2 flex-wrap">
                    <span class="text-lg font-semibold text-text-primary">{{ $titulo }}</span>
                    @if($local)
                        <span class="text-[11px] font-medium px-2 py-0.5 rounded-full bg-primary-dark text-text-secondary border border-accent-gray"
                              title="Se guarda en el comercio: si tenés otro menú, también lo usa.">
                            Aplica a todos tus menús
                        </span>
                    @endif
                </span>
                @if($resumen)
                    <span class="block text-sm text-text-secondary truncate mt-0.5">{{ $resumen }}</span>
                @endif
            </span>

            @if($lista === true)
                <span class="w-6 h-6 rounded-full bg-accent-green/15 text-accent-green flex items-center justify-center text-sm flex-shrink-0" title="Listo">✓</span>
            @elseif($lista === false)
                <span class="w-6 h-6 rounded-full border-2 border-accent-gray flex-shrink-0" title="Falta completar"></span>
            @endif

            <svg class="editor-seccion-flecha w-5 h-5 text-text-secondary flex-shrink-0 transition-transform duration-200 {{ $abierta ? 'rotate-180' : '' }}"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        {{-- Fondo apenas distinto y borde arriba: marcan dónde empieza lo que se abrió. --}}
        <div id="seccion-{{ $id }}-cuerpo"
             class="editor-seccion-cuerpo border-t border-accent-gray bg-primary-dark/40 px-5 sm:px-6 py-6 space-y-7 {{ $abierta ? '' : 'hidden' }}">
            {{ $slot }}
        </div>
    </section>
@endif
