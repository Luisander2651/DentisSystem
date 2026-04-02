@props([
    'modalId' => 'create-user-modal',
    'title' => 'Agregar nuevo usuario',
    'saveText' => 'Crear usuario',
    'cancelText' => 'Cancelar',
])

<div
    id="{{ $modalId }}"
    data-create-user-modal
    class="fixed inset-0 z-100 hidden items-center justify-center bg-slate-900/50 p-4"
>
    <div class="w-full max-w-lg rounded-xl border border-slate-200 bg-white shadow-xl">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="text-lg font-semibold text-slate-900">{{ $title }}</h3>
        </div>

        <form data-create-user-form class="space-y-4 px-5 py-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.input
                    variant="string"
                    name="first_name"
                    label="Nombre"
                    placeholder="Escriba el nombre"
                    data-create-user-first-name
                    required
                />

                <x-ui.input
                    variant="string"
                    name="last_name"
                    label="Apellido"
                    placeholder="Escriba el apellido"
                    data-create-user-last-name
                    required
                />
            </div>

            <x-ui.input
                variant="email"
                name="email"
                label="Correo"
                placeholder="Escriba el correo"
                data-create-user-email
                required
            />

            <x-ui.input
                variant="password"
                id="users-create-password"
                name="password"
                label="Contrasena"
                placeholder="Escriba la contrasena"
                data-create-user-password
                required
            />

            <label class="block text-sm font-medium text-slate-700">
                Rol
                <select
                    name="role_id"
                    data-create-user-role
                    class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-[#E91E63] focus:outline-none focus:ring-2 focus:ring-[#F8BBD0]"
                    required
                >
                    <option value="admin">Administrador</option>
                    <option value="asistent">Asistente</option>
                </select>
            </label>

            <p data-create-user-error class="hidden rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></p>

            <div class="flex items-center justify-end gap-2 border-t border-slate-200 pt-4">
                <button
                    type="button"
                    data-create-user-cancel
                    class="cursor-pointer rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100"
                >
                    {{ $cancelText }}
                </button>
                <button
                    type="submit"
                    data-create-user-submit
                    class="cursor-pointer rounded-md bg-[#E91E63] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#d81b60] disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {{ $saveText }}
                </button>
            </div>
        </form>
    </div>
</div>
