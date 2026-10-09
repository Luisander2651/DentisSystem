{{-- Contador compacto (docs/design/system.md → Contador): tres por fila a 390 px. La cifra va en el slot. --}}
@props([
    'label',
])

<div {{ $attributes->merge(['class' => 'rounded-box border border-line bg-surface px-3 py-2 shadow-sm md:p-4']) }}>
    <p class="text-min font-bold uppercase tracking-wide text-muted">{{ $label }}</p>
    <p class="text-xl font-bold text-ink md:mt-1">{{ $slot }}</p>
</div>
