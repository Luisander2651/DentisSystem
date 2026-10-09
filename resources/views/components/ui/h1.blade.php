{{-- Título principal de una pantalla: uno por pantalla (docs/design/system.md → Tipografía). --}}
@props([
    'as' => 'h1',
])

@php
    $base = 'text-3xl sm:text-4xl font-semibold tracking-tight text-ink';
@endphp

<{{ $as }} {{ $attributes->merge(['class' => $base]) }}>
    {{ $slot }}
</{{ $as }}>
