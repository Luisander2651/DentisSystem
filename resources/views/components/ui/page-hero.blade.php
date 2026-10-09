@props([
    'badge' => null,
    'title',
    'description' => null,
])

@php
    $heroId = 'page-hero-'.uniqid();
    $actorRole = auth()->user() instanceof \App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel
        ? mb_strtolower((string) auth()->user()->role?->name)
        : 'paciente';
    $badge ??= ['administrador' => 'Panel de administración', 'asistente' => 'Panel de asistente', 'doctor' => 'Panel clínico'][$actorRole] ?? 'Mi cuenta';
@endphp

<section id="{{ $heroId }}" {{ $attributes->merge(['class' => 'rounded-card border border-line bg-surface p-4 shadow-sm md:p-6 lg:p-8']) }}>
    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div class="max-w-3xl space-y-3">
            <div class="inline-flex items-center gap-2 rounded-full border border-secondary bg-primary-soft px-3 py-1 text-min font-bold text-ink">
                @isset($icon)
                    {{ $icon }}
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" />
                    </svg>
                @endisset
                <span data-page-badge>{{ $badge }}</span>
            </div>

            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-ink md:text-3xl">{{ $title }}</h1>
                @if (!empty($description))
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-muted md:text-base">{{ $description }}</p>
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