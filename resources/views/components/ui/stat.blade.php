{{-- Contador compacto (docs/design/system.md → Contador): tres por fila a 390 px. La cifra va en el slot.
     Variante `comparison` (spec 019): par dt/dd dentro de un dl de dos columnas, con el indicador
     de cambio en el slot `trend`; `wide` ocupa las dos columnas. --}}
@props([
    'label',
    'variant' => 'compact',
    'wide' => false,
])

@if ($variant === 'comparison')
<div {{ $attributes->merge(['class' => 'rounded-box border border-line bg-surface px-3 py-2 shadow-sm md:p-4' . ($wide ? ' col-span-2' : '')]) }}>
    <dt class="text-min font-bold uppercase tracking-wide text-muted">{{ $label }}</dt>
    <dd class="mt-1 flex flex-col items-start gap-1">
        <span class="text-xl font-bold text-ink">{{ $slot }}</span>
        {{ $trend ?? '' }}
    </dd>
</div>
@else
<div {{ $attributes->merge(['class' => 'rounded-box border border-line bg-surface px-3 py-2 shadow-sm md:p-4']) }}>
    <p class="text-min font-bold uppercase tracking-wide text-muted">{{ $label }}</p>
    <p class="text-xl font-bold text-ink md:mt-1">{{ $slot }}</p>
</div>
@endif
