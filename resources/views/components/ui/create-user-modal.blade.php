@props([
    'modalId' => 'create-user-modal',
    'title' => 'Agregar nuevo usuario',
    'saveText' => 'Crear usuario',
    'cancelText' => 'Cancelar',
])

<x-ui.dialog :id="$modalId" :title="$title" data-create-user-modal>
    <form id="{{ $modalId }}-form" data-create-user-form class="flex flex-col gap-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.input
                variant="string"
                id="{{ $modalId }}-first-name"
                name="first_name"
                label="Nombre"
                placeholder="Escriba el nombre"
                autocomplete="off"
                data-create-user-first-name
                required
            />

            <x-ui.input
                variant="string"
                id="{{ $modalId }}-last-name"
                name="last_name"
                label="Apellido"
                placeholder="Escriba el apellido"
                autocomplete="off"
                data-create-user-last-name
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
            data-create-user-email
            required
        />

        <x-ui.input
            variant="password"
            id="users-create-password"
            name="password"
            label="Contraseña"
            autocomplete="new-password"
            data-create-user-password
            required
        />

        <div>
            <label for="{{ $modalId }}-role" class="mb-1.5 block text-sm font-semibold text-ink">
                Rol del usuario
                <span class="text-danger" title="Obligatorio">*</span>
            </label>
            <select
                id="{{ $modalId }}-role"
                name="role_id"
                data-create-user-role
                data-testid="create-user-role-select"
                class="block h-control w-full rounded-control border border-field bg-surface px-3 text-control text-ink"
                required
            >
                <option value="Administrador">Administrador</option>
                <option value="Asistente">Asistente</option>
                <option value="Doctor">Doctor</option>
            </select>
        </div>
    </form>

    <x-slot:footer>
        <x-ui.button data-create-user-cancel data-testid="create-user-cancel-button">{{ $cancelText }}</x-ui.button>
        <x-ui.button variant="primary" type="submit" form="{{ $modalId }}-form" data-create-user-submit>{{ $saveText }}</x-ui.button>
    </x-slot:footer>
</x-ui.dialog>
