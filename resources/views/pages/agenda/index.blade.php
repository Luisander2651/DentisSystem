@extends('layouts.admin')

@section('title', 'Agenda - Dentissa')

@section('content')
@php $isAdmin = in_array($sidebarRole, ['admin', 'administrador'], true); @endphp
<div class="space-y-6" data-agenda-is-admin="{{ $isAdmin ? 'true' : 'false' }}">
    {{-- Header Section --}}
    <x-ui.page-hero
        title="Agenda de Citas"
        description="Gestiona los horarios y citas medicas de la clinica."
    />

    {{-- Stats/Counters --}}
    <div class="grid gap-3 sm:grid-cols-4">
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Hoy</p>
            <p class="mt-2 text-xl font-semibold text-slate-900" data-stat-today>0</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Pendientes</p>
            <p class="mt-2 text-xl font-semibold text-amber-600" data-stat-pending>0</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Confirmadas</p>
            <p class="mt-2 text-xl font-semibold text-[#B5114A]" data-stat-confirmed>0</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Completadas</p>
            <p class="mt-2 text-xl font-semibold text-emerald-600" data-stat-completed>0</p>
        </div>
    </div>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
        <div class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-white p-1 shadow-sm" data-agenda-status-filter>
            <button type="button" class="rounded-xl px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50" data-status-value="">Todos</button>
            <button type="button" class="rounded-xl px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50" data-status-value="asignada">Asignadas</button>
            <button type="button" class="rounded-xl px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50" data-status-value="reprogramada">Reprogramadas</button>
            <button type="button" class="rounded-xl px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50" data-status-value="completada">Completadas</button>
            <button type="button" class="rounded-xl px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50" data-status-value="cancelada">Canceladas</button>
        </div>
    </div>

    {{-- Main Layout: Calendar + Side Info --}}
    <div class="grid gap-6 lg:grid-cols-12">
        {{-- Left: Calendar View --}}
        <div class="lg:col-span-8 space-y-4">
            <x-calendar.grid />
        </div>

        {{-- Right: Appointments List / Details --}}
        <div class="lg:col-span-4 flex h-full flex-col">
            <section class="flex min-h-136 flex-1 flex-col overflow-hidden rounded-3xl border border-[#F5C2D6] bg-[#FFF7FA] p-6 shadow-sm">
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-[#B5114A]">Citas para Hoy</h3>
                    <p class="mt-1 text-xs text-slate-500">{{ now()->translatedFormat('l d \d\e F') }}</p>
                </div>

                <div class="mt-6 flex min-h-0 flex-1 flex-col gap-4">
                    {{-- Appointment Card Item --}}
                    <div id="today-appointments-container" class="min-h-0 flex-1 space-y-3 pr-1">
                        {{-- Populated by JS --}}
                    </div>

                    <button type="button" data-create-appointment-open class="sticky bottom-0 w-full rounded-2xl bg-[#E91E63] p-4 text-xs font-semibold text-white shadow-sm transition hover:bg-[#d61b5b]">
                        + Agendar nueva cita
                    </button>
                </div>
            </section>
        </div>
    </div>
</div>

{{-- Modals --}}
<x-calendar.day-details-modal />
<x-calendar.create-appointment-modal :is-admin="$isAdmin" />
<x-calendar.edit-appointment-modal />
<x-calendar.view-appointment-modal :is-admin="$isAdmin" />
@if ($isAdmin)
    <x-calendar.complete-appointment-modal />
@endif

@vite('resources/js/pages/agenda/index.js')
@vite('resources/js/pages/agenda/create-appointment.js')
@vite('resources/js/pages/agenda/view-appointment.js')
@if ($isAdmin)
    @vite('resources/js/pages/agenda/complete-appointment.js')
@endif
@endsection
