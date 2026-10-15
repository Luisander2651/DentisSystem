{{-- Subtítulo con acción (docs/design/system.md → Subtítulo con acción): un h2 y, a su derecha, la acción del slot `action`. --}}
@props([
    'title',
])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center justify-between gap-3']) }}>
    <h2 class="text-lg font-bold text-ink md:text-xl">{{ $title }}</h2>
    @isset($action)
        {{ $action }}
    @endisset
</div>
