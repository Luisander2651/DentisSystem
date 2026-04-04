@props([
    'modalId' => 'edit-patient-modal',
    'title' => 'Editar paciente',
    'saveText' => 'Guardar cambios',
    'cancelText' => 'Cancelar',
])

<div
    id="{{ $modalId }}"
    data-edit-patient-modal
    class="fixed inset-0 z-100 hidden items-center justify-center bg-slate-900/50 p-4"
>
    <div class="w-full max-w-xl rounded-xl border border-slate-200 bg-white shadow-xl">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="text-lg font-semibold text-slate-900">{{ $title }}</h3>
        </div>

        <form data-edit-patient-form class="space-y-4 px-5 py-4">
            <input type="hidden" name="patient_id" data-edit-patient-id />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <label class="block text-sm font-medium text-slate-700">
                    Nombre
                    <input
                        type="text"
                        name="first_name"
                        data-edit-patient-first-name
                        class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-[#E91E63] focus:outline-none focus:ring-2 focus:ring-[#F8BBD0]"
                        required
                    />
                </label>

                <label class="block text-sm font-medium text-slate-700">
                    Apellido
                    <input
                        type="text"
                        name="last_name"
                        data-edit-patient-last-name
                        class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-[#E91E63] focus:outline-none focus:ring-2 focus:ring-[#F8BBD0]"
                        required
                    />
                </label>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <label class="block text-sm font-medium text-slate-700">
                    Estado
                    <select
                        name="status"
                        data-edit-patient-status
                        class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-[#E91E63] focus:outline-none focus:ring-2 focus:ring-[#F8BBD0]"
                        required
                    >
                        <option value="active">Activo</option>
                        <option value="inactive">Inactivo</option>
                    </select>
                </label>

                <x-ui.input
                    variant="password"
                    id="patients-edit-new-password"
                    name="new_password"
                    label="Nueva contraseña"
                    placeholder="Escriba la nueva contraseña"
                    data-edit-patient-new-password
                />
            </div>

            <p data-edit-patient-error class="hidden rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></p>

            <div class="flex items-center justify-end gap-2 border-t border-slate-200 pt-4">
                <button
                    type="button"
                    data-edit-patient-cancel
                    class="cursor-pointer rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100"
                >
                    {{ $cancelText }}
                </button>
                <button
                    type="submit"
                    data-edit-patient-submit
                    class="cursor-pointer rounded-md bg-[#E91E63] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#d81b60] disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {{ $saveText }}
                </button>
            </div>
        </form>
    </div>
</div>