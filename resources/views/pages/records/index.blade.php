@extends('layouts.admin')

@section('title', 'Expedientes clinicos - Dentissa')

@section('content')
<div class="space-y-6" data-records-page data-selected-patient-id="{{ (string) ($selectedPatientId ?? '') }}">
    {{-- Header Section --}}
    <x-ui.page-hero
        title="Expedientes clinicos"
        description="Selecciona un paciente para gestionar su historia clinica y datos de contacto."
    />

    {{-- Counters Section --}}
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Total Pacientes</p>
            <p class="mt-2 text-xl font-semibold text-slate-900" data-records-total-count>0</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Activos</p>
            <p class="mt-2 text-xl font-semibold text-[#B5114A]" data-records-active-count>0</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Inactivos</p>
            <p class="mt-2 text-xl font-semibold text-slate-400" data-records-inactive-count>0</p>
        </div>
    </div>

    {{-- Info Card --}}
    <article class="rounded-3xl border border-[#F5C2D6] bg-[#FFF7FA] p-5">
        <div class="flex items-start gap-4">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-[#B5114A] shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-0.5-5Z"/><path d="M8 7h6"/><path d="M8 11h8"/><path d="M8 15h6"/></svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-[#B5114A]">Consulta de expedientes</p>
                <p class="mt-1 text-sm leading-6 text-slate-600">Al seleccionar un paciente, podras ver y editar su informacion de contacto, direccion y antecedentes medicos.</p>
            </div>
        </div>
    </article>

    <div id="records-error" class="hidden rounded-3xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

    {{-- Loading State --}}
    <div id="records-loading" class="hidden rounded-3xl border border-slate-200 bg-white p-5 text-sm text-slate-600 shadow-sm">
        Cargando pacientes...
    </div>

    {{-- Patient Grid --}}
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" data-records-list id="records-table-container"></div>

    {{-- Empty State --}}
    <div id="records-empty" class="hidden rounded-3xl border border-slate-200 bg-white p-5 text-sm text-slate-500 shadow-sm">
        No hay pacientes registrados para mostrar.
    </div>

    {{-- Selected Record Detail --}}
    <section id="record-detail-section" class="hidden space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
        <div class="rounded-3xl border border-[#F5C2D6] bg-white p-6 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#FDF1F6] text-[#B5114A]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Expediente Seleccionado</p>
                    <h3 id="record-selected-patient-label" class="mt-1 text-lg font-bold text-slate-900">-</h3>
                </div>
            </div>
        </div>

        <div class="grid gap-6">
            <x-records.contact-info-table />
            <x-records.address-table />
            <x-records.medical-data-table />
        </div>
    </section>

    {{-- Modals --}}
    <x-ui.confirm-delete-modal
        modal-id="record-delete-modal"
        title="Confirmar eliminacion"
        message-prefix="Esta segura que desea eliminar"
        confirm-text="Eliminar"
        cancel-text="Cancelar"
    />
</div>

@vite('resources/js/pages/records/index.js')
@endsection
