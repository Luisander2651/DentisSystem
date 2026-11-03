{{-- Documento legal del sitio público: título, versión, fecha de actualización, índice y secciones (x-landing.legal-section). --}}
@props([
    'title',
    'version',
    'updatedAt',
    'sections' => [],
])

@php
    $updatedOn = \Illuminate\Support\Carbon::parse($updatedAt)->locale('es')->translatedFormat('j \d\e F \d\e Y');
@endphp

<article {{ $attributes->merge(['class' => 'mx-auto max-w-3xl space-y-10 px-4 py-12 sm:px-6 lg:px-8']) }}>
    <header class="space-y-3">
        <x-ui.h1>{{ $title }}</x-ui.h1>
        <p class="text-sm text-muted">Versión {{ $version }} · Última actualización: <time datetime="{{ $updatedAt }}">{{ $updatedOn }}</time></p>
        @isset($intro)
            <div class="space-y-3 leading-relaxed text-muted">{{ $intro }}</div>
        @endisset
    </header>

    @if ($sections !== [])
        <nav aria-labelledby="legal-index-title" class="rounded-card border border-line bg-surface p-4 sm:p-6">
            <h2 id="legal-index-title" class="text-base font-bold text-ink">Contenido</h2>
            <ol class="mt-3 list-decimal space-y-1 pl-5 text-sm">
                @foreach ($sections as $id => $sectionTitle)
                    <li><a href="#{{ $id }}" class="flex min-h-control w-full items-center font-semibold text-ink underline decoration-primary decoration-2 underline-offset-4">{{ $sectionTitle }}</a></li>
                @endforeach
            </ol>
        </nav>
    @endif

    <div class="space-y-10">
        {{ $slot }}
    </div>
</article>
