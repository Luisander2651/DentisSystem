{{--
    Filtro (docs/design/system.md → Filtro): botón de 44 px con forma de píldora. El elegido
    lleva `aria-pressed="true"`, que es también lo que lo pinta.
--}}
@props([
    'pressed' => false,
])

<button
    type="button"
    aria-pressed="{{ $pressed ? 'true' : 'false' }}"
    {{ $attributes->merge(['class' => 'inline-flex min-h-control shrink-0 items-center whitespace-nowrap rounded-full border border-field bg-surface px-4 text-sm font-semibold text-ink transition-colors hover:bg-canvas aria-pressed:border-primary aria-pressed:bg-primary-soft']) }}
>
    {{ $slot }}
</button>
