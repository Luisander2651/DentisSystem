{{--
    Mis citas (spec 025, design.md → c3): the history of the patient, from the most recent
    appointment to the oldest. The header is x-ui.page-hero, as on every inner screen; the list
    comes from the API (portal/appointments.js) into the history table of the clinical record,
    which stays hidden while it loads or if it fails (CA3, CA4, CA9).
--}}
@extends('layouts.patient')

@section('title', 'Mis citas - Dentissa')

@push('head')
    <meta name="robots" content="noindex">
@endpush

@section('content')
<div class="space-y-6" data-portal-appointments>
    <x-ui.page-hero
        title="Mis citas"
        description="Consulta tus citas, de la más reciente a la más antigua."
    />

    <section class="rounded-card border border-line bg-surface p-4 shadow-sm md:p-6" aria-label="Historial de citas" data-portal-history-status>
        <p class="text-sm text-muted" data-dashboard-state="loading">Cargando…</p>
        @include('pages.dashboard.section-error')
    </section>

    <div data-dashboard-content hidden>
        <x-records.appointments-history-table empty-text="Aún no tienes citas registradas" />
    </div>
</div>

@vite('resources/js/pages/portal/appointments.js')
@endsection
