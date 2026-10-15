@extends('layouts.admin')

@section('title', 'Pacientes - Dentissa')

@section('content')
<div class="space-y-6">
    {{-- Header Section --}}
    <x-ui.page-hero
        title="Pacientes"
        description="Gestiona los pacientes de la clínica."
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </x-slot:icon>

        <x-slot:actions>
            <x-ui.button variant="primary" data-create-patient-open class="w-full sm:w-auto">
                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-7-7v14"/></svg>
                Nuevo paciente
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-hero>

    {{-- Counters Section --}}
    <div class="grid grid-cols-3 gap-2 md:gap-3">
        <x-ui.stat label="Total"><span data-patients-total-count>0</span></x-ui.stat>
        <x-ui.stat label="Activos"><span data-patients-active-count>0</span></x-ui.stat>
        <x-ui.stat label="Inactivos"><span data-patients-inactive-count>0</span></x-ui.stat>
    </div>

    {{-- Search and Filter Section --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
        <div class="relative flex-1">
            <label for="patient-search" class="mb-1.5 block text-sm font-semibold text-ink">Buscar</label>
            <div class="pointer-events-none absolute bottom-0 left-0 flex h-control items-center pl-4">
                <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="h-4 w-4 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>
            <input 
                type="text" 
                id="patient-search"
                placeholder="Nombre o correo" 
                class="block h-control w-full rounded-control border border-field bg-surface pl-11 pr-4 text-control text-ink"
            >
        </div>

        <div class="flex items-center gap-2 overflow-x-auto" data-patients-status-filter>
            <x-ui.chip :pressed="true" data-status-value="">Todos</x-ui.chip>
            <x-ui.chip data-status-value="active">Activos</x-ui.chip>
            <x-ui.chip data-status-value="inactive">Inactivos</x-ui.chip>
        </div>
    </div>

    {{-- Info Card --}}
    <article class="hidden rounded-box border border-secondary bg-primary-soft p-4 md:block md:p-5">
        <div class="flex items-start gap-4">
            <div data-accent class="flex h-10 w-10 shrink-0 items-center justify-center rounded-control bg-surface text-primary">
                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-ink">Gestión de pacientes</p>
                <p class="mt-1 text-sm leading-6 text-ink">Desde aquí puedes registrar nuevos pacientes, editar sus datos generales y mantener actualizado su estado en la clínica.</p>
            </div>
        </div>
    </article>

    {{-- Feedback and List --}}
    <div id="patients-error" role="alert" class="hidden rounded-box border border-danger bg-danger-soft px-4 py-3 text-sm font-semibold text-danger"></div>
    
    <div id="patients-loading" class="hidden rounded-card border border-line bg-surface p-5 text-center text-sm text-muted shadow-sm">
        <div class="inline-block h-6 w-6 animate-spin rounded-full border-2 border-primary border-t-transparent" aria-hidden="true"></div>
        <p class="mt-2">Cargando pacientes...</p>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" id="patients-list"></div>

    <div id="patients-empty" class="hidden rounded-card border border-line bg-surface p-12 text-center shadow-sm">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-canvas text-muted" aria-hidden="true">
            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <h2 class="mt-4 text-sm font-semibold text-ink">No se encontraron pacientes</h2>
        <p class="mt-1 text-sm text-muted">Intenta ajustar tu búsqueda o registra un nuevo paciente.</p>
    </div>

    {{-- Modals --}}
    <x-ui.confirm-delete-modal
        modal-id="patients-delete-modal"
        message-prefix="¿Está segura de que desea eliminar a"
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
