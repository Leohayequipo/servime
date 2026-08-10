@props([
    'texto',            // qué explica: CÓMO se mide el dato, no qué dice el título
    'titulo' => 'Ayuda', // encabezado del modal
])

{{--
    Botón "?" de ayuda para un widget del panel.

    Abre un modal (helper showInfo del layout) y no un title nativo: en el celular no hay
    hover, así que el tooltip del navegador nunca aparecería. Va sólo en los datos que no
    se explican solos — si lo lleva todo, deja de significar que algo necesita explicación.
--}}

<button type="button"
        onclick="showInfo(@js($texto), @js($titulo))"
        aria-label="Qué significa {{ $titulo }}"
        {{ $attributes->merge(['class' => 'flex-none w-4 h-4 rounded-full border border-current opacity-50
                                           hover:opacity-100 text-[10px] leading-none font-bold
                                           transition-opacity cursor-pointer']) }}>?</button>
