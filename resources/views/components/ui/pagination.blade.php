{{-- Paginación (docs/design/system.md → Paginación; spec 028): el lugar del control bajo una rejilla de
     tarjetas (`cards`) o en el pie de una tabla (`table`). Empieza oculto y vacío: lo pinta
     resources/js/ui/pagination.js desde el `meta` de la lista y no aparece con una sola página. --}}
@props([
    'label',
    'variant' => 'cards',
])

<nav data-pagination aria-label="{{ $label }}" hidden {{ $attributes->merge(['class' => 'flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between' . ($variant === 'table' ? ' border-t border-line p-4' : ' pt-6')]) }}></nav>
