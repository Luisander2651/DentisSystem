@extends('layouts.landing')

@section('title', 'Galería de Sonrisas - Dentissa')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="text-center max-w-3xl mx-auto space-y-4 mb-16">
        <h1 class="text-base font-semibold uppercase tracking-wider text-[#B5114A]">Galería Dentissa</h1>
        <p class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Nuestros Casos y Consultorios</p>
        <p class="text-slate-500">Un recorrido visual por nuestras instalaciones de vanguardia y los resultados reales de nuestros pacientes.</p>
    </div>

    <!-- Loading / Errors / Grid -->
    <div data-gallery-loading class="py-12 text-center text-slate-400">
        <svg class="animate-spin mx-auto h-8 w-8 text-[#B5114A]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="mt-2 text-sm">Cargando galería...</p>
    </div>

    <div data-gallery-error class="hidden rounded-2xl border border-red-100 bg-red-50 p-4 text-sm text-red-600 text-center"></div>

    <div data-gallery-list class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4"></div>

    <div data-gallery-empty class="hidden py-12 text-center bg-[#FFF7FA] rounded-3xl border border-[#F5C2D6]">
        <svg class="mx-auto h-10 w-10 text-[#B5114A]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="5" width="18" height="14" rx="2" />
            <path d="M8 13l2.5-2.5 2.5 2.5 1.5-1.5L18 14" />
            <circle cx="9" cy="9" r="1.25" />
        </svg>
        <h3 class="mt-4 text-sm font-semibold text-slate-900">Galería sin fotos actualmente</h3>
        <p class="mt-2 text-xs text-slate-500">Pronto subiremos imágenes de nuestras instalaciones y tratamientos.</p>
    </div>
</div>

<!-- Lightbox Modal -->
<div id="gallery-lightbox" class="fixed inset-0 z-100 hidden bg-slate-950/90 backdrop-blur-md flex items-center justify-center p-4">
    <!-- Close Button -->
    <button type="button" id="lightbox-close" class="absolute top-6 right-6 text-white/80 hover:text-white hover:bg-white/10 rounded-xl p-2.5 transition-colors focus:outline-none" aria-label="Cerrar">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>
    
    <!-- Image Box -->
    <div class="max-w-4xl max-h-[80vh] w-full flex flex-col items-center">
        <img id="lightbox-img" class="max-w-full max-h-[70vh] object-contain rounded-xl shadow-2xl border border-white/5" src="" alt="Ampliar imagen" />
        <p id="lightbox-desc" class="mt-4 text-sm text-white/80 max-w-xl text-center leading-relaxed"></p>
    </div>
</div>
@endsection

@vite('resources/js/pages/landing/galeria.js')
