@props([
    'modalId' => 'create-appointment-modal',
    'title' => 'Nueva cita',
    'saveText' => 'Guardar cita',
    'cancelText' => 'Cancelar',
    'withSelectors' => false,
    'currentPatientId' => '',
    'currentUserId' => '',
])

<x-ui.dialog
    :id="$modalId"
    :title="$title"
    data-create-appointment-modal
    data-create-appointment-with-selectors="{{ $withSelectors ? 'true' : 'false' }}"
    data-create-appointment-current-patient-id="{{ $currentPatientId }}"
    data-create-appointment-current-user-id="{{ $currentUserId }}"
>
    <form id="{{ $modalId }}-form" data-create-appointment-form class="flex flex-col gap-4" novalidate>
        @if ($withSelectors)
            <div>
                <label for="{{ $modalId }}-patient_id" class="mb-1.5 block text-sm font-semibold text-ink">
                    Paciente
                <span class="text-danger" title="Obligatorio">*</span>
                </label>
                <select id="{{ $modalId }}-patient_id" name="patient_id" data-create-appointment-patient-select class="block h-control w-full rounded-control border border-field bg-surface px-3 text-control text-ink" required>
                    <option value="">Cargando pacientes...</option>
                </select>
            </div>

            <div>
                <label for="{{ $modalId }}-user_id" class="mb-1.5 block text-sm font-semibold text-ink">
                    Doctor
                <span class="text-danger" title="Obligatorio">*</span>
                </label>
                <select id="{{ $modalId }}-user_id" name="user_id" data-create-appointment-doctor-select class="block h-control w-full rounded-control border border-field bg-surface px-3 text-control text-ink" required>
                    <option value="">Cargando doctores...</option>
                </select>
            </div>
        @else
            <input type="hidden" name="patient_id" data-create-appointment-patient-hidden value="{{ $currentPatientId }}">
            <input type="hidden" name="user_id" data-create-appointment-user-hidden value="{{ $currentUserId }}">
        @endif

            <div>
                <label for="{{ $modalId }}-treatment_id" class="mb-1.5 block text-sm font-semibold text-ink">
                    Tratamiento
                <span class="text-danger" title="Obligatorio">*</span>
                </label>
                <select id="{{ $modalId }}-treatment_id" name="treatment_id" data-create-appointment-treatment-select class="block h-control w-full rounded-control border border-field bg-surface px-3 text-control text-ink" required>
                    <option value="">Cargando tratamientos...</option>
                </select>
            </div>

        <div class="flex flex-col gap-4">
            <div>
                <label for="{{ $modalId }}-date" class="mb-1.5 block text-sm font-semibold text-ink">
                    Fecha
                <span class="text-danger" title="Obligatorio">*</span>
                </label>
                <input type="date" id="{{ $modalId }}-date" name="date" data-create-appointment-date class="block h-control w-full rounded-control border border-field bg-surface px-3 text-control text-ink" required>
            </div>

            <div>
                <label for="{{ $modalId }}-time" class="mb-1.5 block text-sm font-semibold text-ink">
                    Hora
                <span class="text-danger" title="Obligatorio">*</span>
                </label>
                <select id="{{ $modalId }}-time" name="time" data-create-appointment-time-select class="block h-control w-full rounded-control border border-field bg-surface px-3 text-control text-ink" aria-describedby="{{ $modalId }}-time-hint" required>
                    <option value="">Selecciona un tratamiento primero</option>
                </select>
            </div>
        </div>

        <p id="{{ $modalId }}-time-hint" class="text-sm text-muted">
            Las horas disponibles dependen de las citas de ese día y de la duración del tratamiento.
        </p>
    </form>

    <x-slot:footer>
        <x-ui.button data-create-appointment-cancel>{{ $cancelText }}</x-ui.button>
        <x-ui.button variant="primary" type="submit" form="{{ $modalId }}-form" data-create-appointment-submit disabled>{{ $saveText }}</x-ui.button>
    </x-slot:footer>
</x-ui.dialog>
