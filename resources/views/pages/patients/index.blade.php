@extends('layouts.admin')

@section('title', 'Pacientes - Dentissa')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <x-ui.h1 class="text-xl text-slate-900">Pacientes</x-ui.h1>
            <p class="mt-2 text-sm text-slate-500">Listado de pacientes registrados en el sistema.</p>
        </div>

        <x-ui.button variant="primary" type="button" class="cursor-pointer" data-create-patient-open>
            Agregar un nuevo paciente
        </x-ui.button>
    </div>

    <div id="patients-error" class="hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

    <x-ui.table
        table-id="patients-table"
        :headers="[
            'first_name' => 'Nombre',
            'last_name' => 'Apellido',
            'email' => 'Correo',
            'status' => 'Estado',
        ]"
        :rows="[]"
        empty-message="Sin datos"
        :actions="true"
        action-prefix="patient"
    />

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
