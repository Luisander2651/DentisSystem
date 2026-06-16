@extends('layouts.admin')

@section('title', 'Pacientes - Dentissa')

@section('content')
<div class="space-y-6">
    {{-- Header Section --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <x-ui.h1 as="h2" class="text-xl! text-slate-900">Pacientes</x-ui.h1>
            <p class="mt-2 text-sm text-slate-500">Gestiona la base de datos de pacientes de la clinica.</p>
        </div>

        <button type="button" data-create-patient-open class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#E91E63] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#d61b5b]">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-7-7v14"/></svg>
            Nuevo paciente
        </button>
    </div>

    {{-- Counters Section --}}
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Total Pacientes</p>
            <p class="mt-2 text-xl font-semibold text-slate-900" data-patients-total-count>0</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Activos</p>
            <p class="mt-2 text-xl font-semibold text-[#B5114A]" data-patients-active-count>0</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Inactivos</p>
            <p class="mt-2 text-xl font-semibold text-slate-400" data-patients-inactive-count>0</p>
        </div>
    </div>

    {{-- Search and Filter Section --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
        <div class="relative flex-1">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>
            <input 
                type="text" 
                id="patient-search"
                placeholder="Buscar por nombre o correo..." 
                class="block w-full rounded-2xl border-slate-200 bg-white py-3.5 pl-11 pr-4 text-sm placeholder:text-slate-400 focus:border-[#F5C2D6] focus:ring-4 focus:ring-[#F5C2D6]/10 transition-all shadow-sm"
            >
        </div>
    </div>

    {{-- Info Card --}}
    <article class="rounded-3xl border border-[#F5C2D6] bg-[#FFF7FA] p-5">
        <div class="flex items-start gap-4">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-[#B5114A] shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-[#B5114A]">Gestion de Pacientes</p>
                <p class="mt-1 text-sm leading-6 text-slate-600">Desde aqui puedes registrar nuevos pacientes, editar sus datos generales y mantener actualizado su estado en la clinica.</p>
            </div>
        </div>
    </article>

    {{-- Feedback and List --}}
    <div id="patients-error" class="hidden rounded-3xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>
    
    <div id="patients-loading" class="hidden rounded-3xl border border-slate-200 bg-white p-5 text-sm text-slate-600 shadow-sm text-center">
        <div class="inline-block h-6 w-6 animate-spin rounded-full border-2 border-[#B5114A] border-t-transparent"></div>
        <p class="mt-2">Cargando pacientes...</p>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" id="patients-list"></div>

    <div id="patients-empty" class="hidden rounded-3xl border border-slate-200 bg-white p-12 text-center shadow-sm">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-50 text-slate-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <h3 class="mt-4 text-sm font-semibold text-slate-900">No se encontraron pacientes</h3>
        <p class="mt-1 text-sm text-slate-500">Intenta ajustar tu busqueda o registra un nuevo paciente.</p>
    </div>

    {{-- Modals --}}
    <x-ui.confirm-delete-modal
        modal-id="patients-delete-modal"
        message-prefix="Esta segura que desea eliminar"
    />

    <x-ui.edit-patient-modal
        modal-id="patients-edit-modal"
    />

    <x-ui.create-patient-modal
        modal-id="patients-create-modal"
    />
</div>

@vite('resources/js/pages/patients/index.js')
@vite('resources/js/pages/patients/create-patient.js')
@vite('resources/js/pages/patients/edit-patient.js')
@vite('resources/js/pages/patients/delete-patient.js')
@endsection
