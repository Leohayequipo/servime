@props([
    'texto',             // Para el COMERCIO: qué es y qué hacer con eso, en criollo y corto.
    'admin' => null,     // Sólo super admin: cómo se calcula y qué límites tiene el número.
    'titulo' => 'Ayuda',
])

{{--
    Botón "?" de ayuda para un widget del panel.

    Abre un modal (helper showInfo del layout) y no un title nativo: en el celular no hay
    hover, así que el tooltip del navegador nunca aparecería. Va sólo en los datos que no
    se explican solos — si lo lleva todo, deja de significar que algo necesita explicación.

    Dos textos porque son dos lectores distintos. Al comerciante le sirve "cuántos volvieron
    a mirar tu menú"; que el id viva en el localStorage y que Safari lo borre a los 7 días es
    nuestro problema, y mezclarlo hacía la ayuda larga y técnica justo para quien menos la
    puede aprovechar. El bloque `admin` se agrega abajo, separado, y sólo lo ve el super admin.
--}}

@php
    // Un solo modal: SweetAlert no respeta saltos de línea en `text`, así que el bloque
    // técnico se separa con un guión largo en lugar de un párrafo aparte.
    $completo = $texto;
    if ($admin && auth()->check() && auth()->user()->isSuperAdmin()) {
        $completo .= '  —  [admin] '.$admin;
    }
@endphp

<button type="button"
        onclick="showInfo(@js($completo), @js($titulo))"
        aria-label="Qué significa {{ $titulo }}"
        {{ $attributes->merge(['class' => 'flex-none w-4 h-4 rounded-full border border-current opacity-50
                                           hover:opacity-100 text-[10px] leading-none font-bold
                                           transition-opacity cursor-pointer']) }}>?</button>
