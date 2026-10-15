{{--
    Botón del sistema de diseño (docs/design/system.md → Botón): un solo tamaño y cuatro variantes.
    - primary: la acción principal de la pantalla (una sola).
    - secondary: cualquier otra acción.
    - danger: eliminar.
    - icon: botón de 44 × 44 px que solo muestra un icono; exige `label`, que es su nombre accesible.
    Con `href` se pinta como enlace con el mismo aspecto.
--}}
@props([
    'variant' => 'secondary',
    'type' => 'button',
    'href' => null,
    'label' => null,
])

@php
    $base = 'inline-flex min-h-control items-center justify-center gap-2 rounded-control border text-sm font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-60';

    $variants = [
        'primary' => 'border-transparent bg-primary px-4 text-ink hover:bg-primary-hover',
        'secondary' => 'border-field bg-surface px-4 text-ink hover:bg-canvas',
        'danger' => 'border-transparent bg-danger px-4 text-on-dark hover:bg-danger/90',
        'icon' => 'size-control shrink-0 border-field bg-surface text-ink hover:bg-canvas',
    ];

    $variantClasses = $variants[$variant] ?? $variants['secondary'];
    $name = $label ?? $attributes->get('aria-label');

    if ($variant === 'icon' && blank($name)) {
        throw new InvalidArgumentException('x-ui.button: la variante icon necesita `label`, su nombre accesible.');
    }
@endphp

@if ($href)
    <a href="{{ $href }}" @if ($name) aria-label="{{ $name }}" @endif {{ $attributes->except('aria-label')->merge(['class' => "$base $variantClasses"]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" @if ($name) aria-label="{{ $name }}" @endif {{ $attributes->except('aria-label')->merge(['class' => "$base $variantClasses"]) }}>
        {{ $slot }}
    </button>
@endif
