@props([
    'badge' => 'Panel de seguridad para administradores',
    'title',
    'description' => null,
])

@php
    $heroId = 'page-hero-'.uniqid();
@endphp

<section id="{{ $heroId }}" {{ $attributes->merge(['class' => 'rounded-3xl border border-[#F5C2D6] bg-white p-6 shadow-sm shadow-[#FDF1F6] lg:p-8']) }}>
    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div class="max-w-3xl space-y-4">
            <div class="inline-flex items-center gap-2 rounded-full bg-[#FDF1F6] px-4 py-2 text-sm font-semibold text-[#B5114A]">
                @isset($icon)
                    {{ $icon }}
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" />
                    </svg>
                @endisset
                <span>{{ $badge }}</span>
            </div>

            <div>
                <x-ui.h1 as="h2" class="text-3xl! text-slate-900">{{ $title }}</x-ui.h1>
                @if (!empty($description))
                    <p class="mt-3 max-w-2xl text-base leading-7 text-slate-600">{{ $description }}</p>
                @endif
            </div>
        </div>

        @isset($actions)
            <div class="flex flex-wrap gap-3">
                {{ $actions }}
            </div>
        @endisset
    </div>
</section>