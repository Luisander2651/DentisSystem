{{--
    Marca (docs/design/system.md → Marca). En las barras, el icono junto a "Dentissa" (variante
    `icon`, con una línea opcional debajo en el slot); donde hay espacio, el logo completo
    (variante `logo`). Siempre con texto alternativo.
--}}
@props([
    'variant' => 'icon',
])

@if ($variant === 'logo')
    <img
        src="{{ asset('images/brand/logo.png') }}"
        alt="Melissa López N., odontología integral"
        width="520"
        height="156"
        {{ $attributes->merge(['class' => 'h-auto w-64 max-w-full']) }}
    >
@else
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-3']) }}>
        <img src="{{ asset('images/brand/icon-64.png') }}" alt="Logo de Dentissa" width="40" height="40" class="size-10 shrink-0 rounded-control">
        <span class="leading-tight">
            <span class="block text-base font-bold tracking-tight text-ink">Dentissa</span>
            @if ($slot->isNotEmpty())
                <span class="block text-min font-semibold text-muted">{{ $slot }}</span>
            @endif
        </span>
    </span>
@endif
