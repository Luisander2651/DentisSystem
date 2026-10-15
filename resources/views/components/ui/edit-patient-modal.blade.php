@props([
    'modalId' => 'edit-patient-modal',
    'title' => 'Editar información del paciente',
    'saveText' => 'Guardar cambios',
    'cancelText' => 'Cancelar',
])

<x-ui.dialog :id="$modalId" :title="$title" data-edit-patient-modal>
    <form id="{{ $modalId }}-form" data-edit-patient-form class="flex flex-col gap-4">
        <input type="hidden" name="patient_id" data-edit-patient-id />

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.input
                variant="string"
                id="{{ $modalId }}-first-name"
                name="first_name"
                label="Nombre"
                placeholder="Escriba el nombre"
                autocomplete="off"
                data-edit-patient-first-name
                required
            />

            <x-ui.input
                variant="string"
                id="{{ $modalId }}-last-name"
                name="last_name"
                label="Apellido"
                placeholder="Escriba el apellido"
                autocomplete="off"
                data-edit-patient-last-name
                required
            />
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="{{ $modalId }}-status" class="mb-1.5 block text-sm font-semibold text-ink">
                    Estado del paciente
                    <span class="text-danger" title="Obligatorio">*</span>
                </label>
                <select
                    id="{{ $modalId }}-status"
                    name="status"
                    data-edit-patient-status
                    class="block h-control w-full rounded-control border border-field bg-surface px-3 text-control text-ink"
                    required
                >
                    <option value="active">Activo</option>
                    <option value="inactive">Inactivo</option>
                </select>
            </div>

            <x-ui.input
                variant="password"
                id="patients-edit-new-password"
                name="new_password"
                label="Nueva contraseña (opcional)"
                autocomplete="new-password"
                data-edit-patient-new-password
            />
        </div>
    </form>

    <x-slot:footer>
        <x-ui.button data-edit-patient-cancel>{{ $cancelText }}</x-ui.button>
        <x-ui.button variant="primary" type="submit" form="{{ $modalId }}-form" data-edit-patient-submit>{{ $saveText }}</x-ui.button>
    </x-slot:footer>
</x-ui.dialog>
