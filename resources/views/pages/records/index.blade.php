@extends('layouts.admin')

@section('title', 'Expedientes clínicos - Dentissa')

@section('content')
@php
    $isAdmin = in_array($sidebarRole, ['admin', 'administrador'], true);
    // Spec 014, CA4: the doctor sees the records read-only; the server refuses his writes anyway.
    $canEdit = in_array($sidebarRole, ['admin', 'administrador', 'asistente'], true);
    // Spec 016, CA31: below md the chosen record takes the place of the list.
    $hasSelectedPatient = filled($selectedPatientId ?? null);
@endphp
<div class="space-y-6" data-records-page data-records-can-edit="{{ $canEdit ? 'true' : 'false' }}" data-selected-patient-id="{{ (string) ($selectedPatientId ?? '') }}">
    {{-- Header Section --}}
    <x-ui.page-hero
        title="Expedientes clínicos"
        :description="$canEdit ? 'Selecciona un paciente para gestionar su historia clínica y datos de contacto.' : 'Selecciona un paciente para consultar su historia clínica y datos de contacto.'"
    />

    @if ($hasSelectedPatient)
        <a href="{{ route('records.index') }}" data-records-back class="inline-flex min-h-control items-center gap-2 text-sm font-semibold text-ink underline decoration-primary decoration-2 underline-offset-4 md:hidden">
            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5m7 7-7-7 7-7"/></svg>
            Volver a la lista de pacientes
        </a>
    @endif

    <div data-records-browser @class(['space-y-6', 'hidden md:block' => $hasSelectedPatient])>
        {{-- Counters Section --}}
        <div class="grid grid-cols-3 gap-2 md:gap-3">
            <x-ui.stat label="Total"><span data-records-total-count>0</span></x-ui.stat>
            <x-ui.stat label="Activos"><span data-records-active-count>0</span></x-ui.stat>
            <x-ui.stat label="Inactivos"><span data-records-inactive-count>0</span></x-ui.stat>
        </div>

        {{-- Info Card --}}
        <article class="hidden rounded-box border border-secondary bg-primary-soft p-4 md:block md:p-5">
            <div class="flex items-start gap-4">
                <div data-accent class="flex h-10 w-10 shrink-0 items-center justify-center rounded-control bg-surface text-primary">
                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-0.5-5Z"/><path d="M8 7h6"/><path d="M8 11h8"/><path d="M8 15h6"/></svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-ink">Consulta de expedientes</p>
                    <p class="mt-1 text-sm leading-6 text-ink">Al seleccionar un paciente, podrás {{ $canEdit ? 'ver y editar' : 'consultar' }} su información de contacto, dirección y antecedentes médicos.</p>
                </div>
            </div>
        </article>

        <div class="flex items-center gap-2 overflow-x-auto" data-records-status-filter>
            <x-ui.chip :pressed="true" data-status-value="">Todos</x-ui.chip>
            <x-ui.chip data-status-value="active">Activos</x-ui.chip>
            <x-ui.chip data-status-value="inactive">Inactivos</x-ui.chip>
        </div>

        {{-- Loading State --}}
        <div id="records-loading" class="hidden rounded-card border border-line bg-surface p-5 text-center text-sm text-muted shadow-sm">
            Cargando pacientes...
        </div>

        {{-- Patient Grid --}}
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" data-records-list id="records-table-container"></div>

        {{-- Empty State --}}
        <div id="records-empty" class="hidden rounded-card border border-line bg-surface p-5 text-center text-sm text-muted shadow-sm">
            No hay pacientes registrados para mostrar.
        </div>
    </div>

    <div id="records-error" role="alert" class="hidden rounded-box border border-danger bg-danger-soft px-4 py-3 text-sm font-semibold text-danger"></div>

    {{-- Selected Record Detail --}}
    <section id="record-detail-section" aria-labelledby="record-selected-patient-label" class="hidden space-y-6">
        <div class="rounded-card border border-line bg-surface p-4 shadow-sm md:p-6">
            <div class="flex items-center gap-4">
                <div data-accent class="flex h-12 w-12 shrink-0 items-center justify-center rounded-box bg-primary-soft text-primary" aria-hidden="true">
                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-min font-bold uppercase tracking-wide text-muted">Expediente seleccionado</p>
                    <h2 id="record-selected-patient-label" class="mt-1 wrap-anywhere text-lg font-bold text-ink">-</h2>
                </div>
            </div>
        </div>

        <div class="grid gap-6">
            <x-records.contact-info-table :can-edit="$canEdit" />
            <x-records.address-table :can-edit="$canEdit" />
            <x-records.medical-data-table :can-edit="$canEdit" />
            <x-records.appointments-history-table />
        </div>
    </section>

    {{-- Modals --}}
    <x-ui.confirm-delete-modal
        modal-id="record-delete-modal"
        title="Confirmar eliminación"
        message-prefix="¿Seguro que deseas eliminar"
        confirm-text="Eliminar"
        cancel-text="Cancelar"
    />
    <x-calendar.view-appointment-modal :is-admin="$isAdmin" />

    @if ($canEdit)
        {{-- index.js clones this to render each "Eliminar" button; without it, none is rendered. --}}
        <template data-record-delete-button-template>
            <button type="button" class="inline-flex min-h-control items-center justify-center rounded-control border border-field bg-surface px-4 text-sm font-semibold text-ink transition-colors hover:bg-danger-soft hover:text-danger">Eliminar</button>
        </template>
    @endif
</div>

@vite('resources/js/pages/records/index.js')
@vite('resources/js/pages/agenda/view-appointment.js')
@endsection
