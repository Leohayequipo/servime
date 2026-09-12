{{-- Paginación publicada de Laravel, repintada con los tokens del panel. Venía con la
     paleta gris de fábrica de Breeze (`text-gray-700`, `bg-white`, `border-gray-300`),
     que sobre el crema se lee como un recorte de otra aplicación. Ver la skill
     `identidad`: el botón deshabilitado usa el texto secundario, no un gris cualquiera. --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex justify-between">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-text-secondary bg-secondary-dark border border-accent-gray cursor-default leading-5 rounded-md opacity-60">
                {!! __('pagination.previous') !!}
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-text-primary bg-secondary-dark border border-accent-gray leading-5 rounded-md hover:text-accent-blue focus:outline-none focus:ring ring-accent-gray focus:border-accent-blue active:bg-primary-dark transition ease-in-out duration-150">
                {!! __('pagination.previous') !!}
            </a>
        @endif

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-text-primary bg-secondary-dark border border-accent-gray leading-5 rounded-md hover:text-accent-blue focus:outline-none focus:ring ring-accent-gray focus:border-accent-blue active:bg-primary-dark transition ease-in-out duration-150">
                {!! __('pagination.next') !!}
            </a>
        @else
            <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-text-secondary bg-secondary-dark border border-accent-gray cursor-default leading-5 rounded-md opacity-60">
                {!! __('pagination.next') !!}
            </span>
        @endif
    </nav>
@endif
