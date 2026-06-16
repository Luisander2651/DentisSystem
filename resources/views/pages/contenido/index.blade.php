@extends('layouts.admin')

@section('title', 'Gestion de contenido')

@section('content')
<div class="space-y-8">
    <section class="rounded-3xl border border-[#F5C2D6] bg-white p-6 shadow-sm shadow-[#FDF1F6] lg:p-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl space-y-4">
                <div class="inline-flex items-center gap-2 rounded-full bg-[#FDF1F6] px-4 py-2 text-sm font-semibold text-[#B5114A]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" />
                    </svg>
                    Panel de seguridad para administradores
                </div>

                <div>
                    <x-ui.h1 as="h2" class="text-3xl! text-slate-900">Gestion de contenido</x-ui.h1>
                    <p class="mt-3 max-w-2xl text-base leading-7 text-slate-600">
                        Administra galería, promociones, certificaciones y testimonios desde una sola pantalla.
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
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
            </div>
        </div>
    </section>

    @include('pages.contenido.galeria.index')
    @include('pages.contenido.promociones.index')
    @include('pages.contenido.certificaciones.index')
    @include('pages.contenido.testimonios.index')
</div>
@endsection

@vite('resources/js/pages/contenido/index.js')