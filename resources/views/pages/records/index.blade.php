@extends('layouts.admin')

@section('title', 'Expedientes clinicos - Dentissa')

@section('content')
<div class="space-y-6" data-records-page data-selected-patient-id="{{ (string) ($selectedPatientId ?? '') }}">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <x-ui.h1 class="text-xl text-slate-900">Expedientes clinicos</x-ui.h1>
            <p class="mt-2 text-sm text-slate-500">Seleccione un paciente para consultar su expediente completo.</p>
        </div>
    </div>

    <div id="records-error" class="hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

    <div class="w-full overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full border-collapse text-left text-sm text-slate-700">
            <thead class="bg-[#E91E63] text-white">
                <tr>
                    <th class="whitespace-nowrap px-4 py-3 font-semibold">Nombre</th>
                    <th class="whitespace-nowrap px-4 py-3 font-semibold">Apellido</th>
                    <th class="whitespace-nowrap px-4 py-3 font-semibold">Correo</th>
                    <th class="whitespace-nowrap px-4 py-3 font-semibold">Estado</th>
                    <th class="whitespace-nowrap px-4 py-3 font-semibold">Expediente</th>
                </tr>
            </thead>
            <tbody id="records-patients-table-body" class="bg-white">
                <tr class="border-t border-slate-200">
                    <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">Cargando pacientes...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <section id="record-detail-section" class="hidden space-y-4">
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <h2 class="text-base font-semibold text-slate-900">Expediente seleccionado</h2>
            <p id="record-selected-patient-label" class="mt-1 text-sm text-slate-600">-</p>
        </div>

        <x-records.contact-info-table />
        <x-records.address-table />
        <x-records.medical-data-table />
    </section>

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
