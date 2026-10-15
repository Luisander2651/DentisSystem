@props([
    'modalId' => 'create-patient-modal',
    'title' => 'Agregar nuevo paciente',
    'saveText' => 'Crear paciente',
    'cancelText' => 'Cancelar',
])

<x-ui.dialog :id="$modalId" :title="$title" data-create-patient-modal>
    <form id="{{ $modalId }}-form" data-create-patient-form class="flex flex-col gap-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.input
                variant="string"
                id="{{ $modalId }}-first-name"
                name="first_name"
                label="Nombre"
                placeholder="Escriba el nombre"
                autocomplete="off"
                data-create-patient-first-name
                required
            />

            <x-ui.input
                variant="string"
                id="{{ $modalId }}-last-name"
                name="last_name"
                label="Apellido"
                placeholder="Escriba el apellido"
                autocomplete="off"
                data-create-patient-last-name
                required
            />
        </div>

        <x-ui.input
            variant="email"
            id="{{ $modalId }}-email"
            name="email"
            label="Correo electrónico"
            placeholder="ejemplo@correo.com"
            autocomplete="off"
            data-create-patient-email
            required
        />

        <x-ui.input
            variant="password"
            id="patients-create-password"
            name="password"
            label="Contraseña temporal"
            autocomplete="new-password"
            data-create-patient-password
            required
        />
    </form>

    <x-slot:footer>
        <x-ui.button data-create-patient-cancel>{{ $cancelText }}</x-ui.button>
        <x-ui.button variant="primary" type="submit" form="{{ $modalId }}-form" data-create-patient-submit disabled>{{ $saveText }}</x-ui.button>
    </x-slot:footer>
</x-ui.dialog>
