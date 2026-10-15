@extends('layouts.admin')

@section('title', 'Gestión de contenido - Dentissa')

@section('content')
<div class="space-y-6">
    <x-ui.page-hero title="Gestión de contenido" description="Administra galería, promociones, certificaciones y testimonios desde una sola pantalla.">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" />
            </svg>
        </x-slot:icon>

        <x-slot:actions>
            <div class="flex flex-wrap gap-2" role="group" aria-label="Secciones de contenido">
                <x-ui.chip :pressed="true" data-content-tab="galeria">Galería</x-ui.chip>
                <x-ui.chip data-content-tab="promociones">Promociones</x-ui.chip>
                <x-ui.chip data-content-tab="certificaciones">Certificaciones</x-ui.chip>
                <x-ui.chip data-content-tab="testimonios">Testimonios</x-ui.chip>
            </div>
        </x-slot:actions>
    </x-ui.page-hero>

    @include('pages.contenido.galeria.index')
    @include('pages.contenido.promociones.index')
    @include('pages.contenido.certificaciones.index')
    @include('pages.contenido.testimonios.index')
</div>
@endsection

@vite('resources/js/pages/contenido/index.js')