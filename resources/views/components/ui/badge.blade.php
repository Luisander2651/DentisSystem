{{--
    Insignia de estado (docs/design/system.md → Insignia de estado): siempre con texto, nunca
    solo color. Su gemela para lo que pinta el navegador es resources/js/ui/badge.js.
--}}
@props([
    'tone' => 'neutral',
])

@php
    $tones = [
        'success' => 'bg-success-soft text-success',
        'warning' => 'bg-warning-soft text-warning',
        'danger' => 'bg-danger-soft text-danger',
        'info' => 'bg-info-soft text-info',
        'brand' => 'border border-secondary bg-primary-soft text-ink',
        'neutral' => 'border border-line bg-canvas text-muted',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex min-h-6 items-center rounded-full px-2.5 text-min font-bold '.($tones[$tone] ?? $tones['neutral'])]) }}>
    {{ $slot }}
</span>
