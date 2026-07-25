@extends('layouts.admin')

@section('title', 'Gestion de contenido')

@section('content')
<div class="space-y-8">
    <x-ui.page-hero title="Gestion de contenido" description="Administra galería, promociones, certificaciones y testimonios desde una sola pantalla.">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" />
            </svg>
        </x-slot:icon>

        <x-slot:actions>
            <button type="button" data-content-tab="galeria" class="rounded-2xl border border-[#F5C2D6] bg-[#FDF1F6] px-5 py-3 text-sm font-semibold text-[#B5114A] transition hover:bg-white">
                Galeria
            </button>
            <button type="button" data-content-tab="promociones" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 transition hover:border-[#F5C2D6] hover:text-[#B5114A]">
                Promociones
            </button>
            <button type="button" data-content-tab="certificaciones" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 transition hover:border-[#F5C2D6] hover:text-[#B5114A]">
                Certificaciones
            </button>
            <button type="button" data-content-tab="testimonios" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 transition hover:border-[#F5C2D6] hover:text-[#B5114A]">
                Testimonios
            </button>
        </x-slot:actions>
    </x-ui.page-hero>

    @include('pages.contenido.galeria.index')
    @include('pages.contenido.promociones.index')
    @include('pages.contenido.certificaciones.index')
    @include('pages.contenido.testimonios.index')
</div>
@endsection

@vite('resources/js/pages/contenido/index.js')