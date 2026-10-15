@props([
    'modalId' => 'edit-user-modal',
    'title' => 'Editar información del usuario',
    'saveText' => 'Guardar cambios',
    'cancelText' => 'Cancelar',
])

<x-ui.dialog :id="$modalId" :title="$title" data-edit-user-modal>
    <form id="{{ $modalId }}-form" data-edit-user-form class="flex flex-col gap-4">
        <input type="hidden" name="user_id" data-edit-user-id />

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.input
                variant="string"
                id="{{ $modalId }}-first-name"
                name="first_name"
                label="Nombre"
                placeholder="Escriba el nombre"
                autocomplete="off"
                data-edit-user-first-name
                required
            />

            <x-ui.input
                variant="string"
                id="{{ $modalId }}-last-name"
                name="last_name"
                label="Apellido"
                placeholder="Escriba el apellido"
                autocomplete="off"
                data-edit-user-last-name
                required
            />
        </div>

        <x-ui.input
            variant="password"
            id="users-edit-new-password"
            name="new_password"
            label="Nueva contraseña (opcional)"
            autocomplete="new-password"
            data-edit-user-new-password
        />

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="{{ $modalId }}-role" class="mb-1.5 block text-sm font-semibold text-ink">
                    Rol
                    <span class="text-danger" title="Obligatorio">*</span>
                </label>
                <select
                    id="{{ $modalId }}-role"
                    name="role_id"
                    data-edit-user-role
                    data-testid="edit-user-role-select"
                    class="block h-control w-full rounded-control border border-field bg-surface px-3 text-control text-ink"
                    required
                >
                    <option value="Administrador">Administrador</option>
                    <option value="Asistente">Asistente</option>
                    <option value="Doctor">Doctor</option>
                </select>
            </div>

            <div>
                <label for="{{ $modalId }}-status" class="mb-1.5 block text-sm font-semibold text-ink">
                    Estado
                    <span class="text-danger" title="Obligatorio">*</span>
                </label>
                <select
                    id="{{ $modalId }}-status"
                    name="status"
                    data-edit-user-status
                    data-testid="edit-user-status-select"
                    class="block h-control w-full rounded-control border border-field bg-surface px-3 text-control text-ink"
                    required
                >
                    <option value="active">Activo</option>
                    <option value="inactive">Inactivo</option>
                </select>
            </div>
        </div>
    </form>

    <x-slot:footer>
        <x-ui.button data-edit-user-cancel>{{ $cancelText }}</x-ui.button>
        <x-ui.button variant="primary" type="submit" form="{{ $modalId }}-form" data-edit-user-submit>{{ $saveText }}</x-ui.button>
    </x-slot:footer>
</x-ui.dialog>
