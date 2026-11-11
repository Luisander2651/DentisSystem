{{--
    Mis citas (spec 025, design.md → c3): the history of the patient, from the most recent
    appointment to the oldest. The header is x-ui.page-hero, as on every inner screen; the list
    comes from the API.
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
</div>
@endsection
