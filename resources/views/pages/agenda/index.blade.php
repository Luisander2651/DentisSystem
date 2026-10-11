@extends('layouts.admin')

@section('title', 'Agenda - Dentissa')

@section('content')
@php $isAdmin = in_array($sidebarRole, ['admin', 'administrador'], true); @endphp
<div class="space-y-6" data-agenda-is-admin="{{ $isAdmin ? 'true' : 'false' }}">
    <x-ui.page-hero
        title="Agenda de citas"
        description="Consulta y organiza las citas de la clínica."
    />

    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 md:gap-3">
        <x-ui.stat label="Hoy"><span data-stat-today>0</span></x-ui.stat>
        <x-ui.stat label="Asignadas"><span data-stat-pending>0</span></x-ui.stat>
        <x-ui.stat label="Reprogramadas"><span data-stat-confirmed>0</span></x-ui.stat>
        <x-ui.stat label="Completadas"><span data-stat-completed>0</span></x-ui.stat>
    </div>

    <div role="group" aria-label="Filtrar citas por estado" class="flex items-center gap-2 overflow-x-auto" data-agenda-status-filter>
        <x-ui.chip :pressed="true" data-status-value="">Todas</x-ui.chip>
        <x-ui.chip data-status-value="asignada">Asignadas</x-ui.chip>
        <x-ui.chip data-status-value="reprogramada">Reprogramadas</x-ui.chip>
        <x-ui.chip data-status-value="completada">Completadas</x-ui.chip>
        <x-ui.chip data-status-value="cancelada">Canceladas</x-ui.chip>
    </div>

    <div class="grid gap-6 lg:grid-cols-12">
        <div class="min-w-0 space-y-4 lg:col-span-8">
            <x-calendar.grid />

            <section class="rounded-card border border-line bg-surface p-4 shadow-sm md:hidden" aria-labelledby="agenda-day-title" data-agenda-day>
                <h2 id="agenda-day-title" class="text-lg font-bold text-ink" data-agenda-day-title>Citas del día</h2>
                <div class="mt-3 space-y-3" data-agenda-day-list>
                    <p class="text-sm text-muted">Elige un día del calendario para ver sus citas.</p>
                </div>
            </section>
        </div>

        <div class="min-w-0 lg:col-span-4">
            <section class="rounded-card border border-secondary bg-primary-soft p-4 shadow-sm md:p-6" aria-labelledby="agenda-today-title">
                <h2 id="agenda-today-title" class="text-lg font-bold text-ink">Citas para hoy</h2>
                <p class="mt-1 text-sm text-ink">{{ ucfirst(now()->locale('es')->translatedFormat('l d \d\e F')) }}</p>

                <div id="today-appointments-container" class="mt-4 space-y-3"></div>

                <x-ui.button variant="primary" class="mt-4 w-full" data-create-appointment-open>
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    Agendar nueva cita
                </x-ui.button>
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
