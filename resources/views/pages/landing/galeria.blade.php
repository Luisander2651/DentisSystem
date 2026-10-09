@extends('layouts.landing')

@section('title', 'Galería de Sonrisas - Dentissa')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mx-auto mb-16 max-w-3xl space-y-4 text-center">
        <h1 class="text-base font-semibold uppercase tracking-wider text-ink underline decoration-primary decoration-2 underline-offset-8">Galería Dentissa</h1>
        <p class="text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">Nuestros Casos y Consultorios</p>
        <p class="text-muted">Un recorrido visual por nuestras instalaciones de vanguardia y los resultados reales de nuestros pacientes.</p>
    </div>

    <!-- Loading / Grid -->
    <div data-gallery-loading class="py-12 text-center text-muted">
        <span data-accent class="inline-block text-primary">
            <svg class="mx-auto h-8 w-8 animate-spin" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </span>
        <p class="mt-2 text-sm">Cargando galería...</p>
    </div>

    <div data-gallery-list class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4"></div>

    <div data-gallery-empty class="hidden rounded-card border border-secondary bg-primary-soft py-12 text-center">
        <span data-accent class="inline-block text-primary">
            <svg class="mx-auto h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="3" y="5" width="18" height="14" rx="2" />
                <path d="M8 13l2.5-2.5 2.5 2.5 1.5-1.5L18 14" />
                <circle cx="9" cy="9" r="1.25" />
            </svg>
        </span>
        <h3 class="mt-4 text-sm font-semibold text-ink">Galería sin fotos actualmente</h3>
        <p class="mt-2 text-sm text-ink">Pronto subiremos imágenes de nuestras instalaciones y tratamientos.</p>
    </div>
</div>

<!-- Visor de imagen -->
<x-ui.dialog id="gallery-lightbox" title="Imagen de la galería" size="lg" tone="dark">
    <div class="flex flex-col items-center">
        <img id="lightbox-img" class="max-h-[60dvh] max-w-full rounded-control object-contain" src="" alt="" />
        <p id="lightbox-desc" class="mt-4 max-w-xl text-center text-sm leading-relaxed"></p>
    </div>
</x-ui.dialog>
@endsection

@vite('resources/js/pages/landing/galeria.js')
