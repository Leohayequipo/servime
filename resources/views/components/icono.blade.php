@props(['name', 'class' => 'w-4 h-4'])

{{--
    Iconos de la interfaz, en SVG inline y de trazo (estilo outline).

    Reemplazan los emoji que había en los títulos del panel: los emoji se ven distintos
    en cada sistema operativo, no se pueden alinear ni teñir, y leen informal. Estos
    heredan el color del texto con currentColor y el tamaño por la clase.

    Uso:  <x-icono name="funnel" class="w-4 h-4 text-text-secondary" />
--}}

@php
    $paths = [
        // Análisis
        'chart' => '<path d="M3 20h18"/><path d="M6 16l4-5 3.5 3L20 7"/>',
        'funnel' => '<path d="M3 4h18l-7 8v8l-4-2v-6L3 4Z"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'target' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.4"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-4.3-4.3"/>',

        // Estados y avisos
        'idea' => '<path d="M9 18h6"/><path d="M10 21.5h4"/><path d="M12 2.5a6.5 6.5 0 0 0-3.8 11.8V17h7.6v-2.7A6.5 6.5 0 0 0 12 2.5Z"/>',
        'check' => '<circle cx="12" cy="12" r="9"/><path d="M8.5 12.3l2.4 2.4 4.6-4.9"/>',
        'alert' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><path d="M12 16.3v.4"/>',

        // Comportamiento
        'phone' => '<rect x="7" y="2.5" width="10" height="19" rx="2.5"/><path d="M11 18.5h2"/>',
        'return' => '<path d="M3.5 12a8.5 8.5 0 0 1 14.2-6.3L20.5 8"/><path d="M20.5 4v4h-4"/><path d="M20.5 12a8.5 8.5 0 0 1-14.2 6.3L3.5 16"/><path d="M3.5 20v-4h4"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7.5V12l3 1.8"/>',
        'split' => '<path d="M12 3v5.5"/><path d="M12 8.5 7 13.5V21"/><path d="M12 8.5l5 5V21"/>',

        // Menú
        'flame' => '<path d="M12 21.5c3.3 0 6-2.6 6-5.8 0-4.2-6-13.2-6-13.2S6 11.5 6 15.7c0 3.2 2.7 5.8 6 5.8Z"/><path d="M12 17.8a2.3 2.3 0 0 0 2.3-2.3c0-1.6-2.3-4-2.3-4s-2.3 2.4-2.3 4A2.3 2.3 0 0 0 12 17.8Z"/>',
        'heart' => '<path d="M12 20.3s-7.3-4.5-7.3-9.4A4.2 4.2 0 0 1 12 8.2a4.2 4.2 0 0 1 7.3 2.7c0 4.9-7.3 9.4-7.3 9.4Z"/>',

        // Acciones
        'chevron' => '<path d="M9 5l7 7-7 7"/>',
        'download' => '<path d="M12 3.5v11"/><path d="M8 10.5l4 4 4-4"/><path d="M4 19.5h16"/>',

        // Conversación
        'help' => '<path d="M20.5 14.5a3 3 0 0 1-3 3H8.5l-5 3.5V6.5a3 3 0 0 1 3-3h11a3 3 0 0 1 3 3v8Z"/><path d="M10.2 9.2A2 2 0 0 1 14 10c0 1.2-1.5 1.5-1.5 2.6"/><path d="M12.5 15v.4"/>',
        'messages' => '<path d="M8.5 13.5H5.5a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/><path d="M20.5 20.5l-3-3h-6a2 2 0 0 1-2-2v-4.5a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v9.5Z"/>',
    ];
@endphp

<svg {{ $attributes->merge(['class' => $class]) }}
     viewBox="0 0 24 24" fill="none" stroke="currentColor"
     stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"
     aria-hidden="true" focusable="false">
    {!! $paths[$name] ?? $paths['alert'] !!}
</svg>
