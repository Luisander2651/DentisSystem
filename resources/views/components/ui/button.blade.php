@props([
    'variant' => 'principal',
    'type' => 'button',
])

@php
    $base = 'inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60';

    $variants = [
        'principal' => 'bg-slate-800 hover:bg-slate-900 focus-visible:ring-slate-500',
        'danger' => 'bg-red-600 hover:bg-red-700 focus-visible:ring-red-500',
        'primary' => 'bg-[#E91E63] hover:bg-[#d61b5b] focus-visible:ring-[#E91E63]',
        'warning' => 'bg-amber-500 hover:bg-amber-600 focus-visible:ring-amber-500',
        'ok' => 'bg-emerald-600 hover:bg-emerald-700 focus-visible:ring-emerald-500',
    ];

    $variantClasses = $variants[$variant] ?? $variants['principal'];
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => "$base $variantClasses"]) }}
>
    {{ $slot }}
</button>
