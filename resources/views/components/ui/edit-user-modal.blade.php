@props([
    'modalId' => 'edit-user-modal',
    'title' => 'Editar información del usuario',
    'saveText' => 'Guardar cambios',
    'cancelText' => 'Cancelar',
])

<div
    id="{{ $modalId }}"
    data-edit-user-modal
    class="fixed inset-0 z-[100] hidden overflow-y-auto"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
>
    {{-- Background backdrop with blur --}}
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" aria-hidden="true" data-edit-user-cancel></div>

    {{-- Modal content container --}}
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative w-full max-w-lg transform overflow-hidden rounded-[1.75rem] bg-white text-left shadow-xl transition-all sm:my-8">
                <div class="bg-white px-8 pt-8 pb-6">
                    <div class="flex items-center justify-between pb-6 border-b border-slate-100">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#B5114A]">Personal</p>
                            <h3 class="mt-1 text-xl font-extrabold text-slate-900">{{ $title }}</h3>
                        </div>
                        <button type="button" class="rounded-xl p-2 text-slate-400 hover:bg-slate-50 hover:text-slate-500 transition" data-edit-user-cancel>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form data-edit-user-form class="mt-6 space-y-5">
                        <input type="hidden" name="user_id" data-edit-user-id />

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <x-ui.input
                                variant="string"
                                name="first_name"
                                label="Nombre"
                                placeholder="Escriba el nombre"
                                data-edit-user-first-name
                                required
                            />

                            <x-ui.input
                                variant="string"
                                name="last_name"
                                label="Apellido"
                                placeholder="Escriba el apellido"
                                data-edit-user-last-name
                                required
                            />
                        </div>

                        <x-ui.input
                            variant="password"
                            id="users-edit-new-password"
                            name="new_password"
                            label="Nueva contraseña (opcional)"
                            placeholder="••••••••"
                            data-edit-user-new-password
                        />

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Rol</label>
                                <select
                                    name="role_id"
                                    data-edit-user-role
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-900 focus:border-[#B5114A] focus:outline-none focus:ring-4 focus:ring-[#B5114A]/10 transition-all"
                                    required
                                >
                                    <option value="admin">Administrador</option>
                                    <option value="asistent">Asistente</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Estado</label>
                                <select
                                    name="status"
                                    data-edit-user-status
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-900 focus:border-[#B5114A] focus:outline-none focus:ring-4 focus:ring-[#B5114A]/10 transition-all"
                                    required
                                >
                                    <option value="active">Activo</option>
                                    <option value="inactive">Inactivo</option>
                                </select>
                            </div>
                        </div>

                        <p data-edit-user-error class="hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700"></p>

                        <div class="flex items-center justify-end gap-3 pt-4">
                            <button
                                type="button"
                                data-edit-user-cancel
                                class="cursor-pointer rounded-2xl border border-slate-200 bg-white px-6 py-3 text-sm font-bold text-slate-600 transition hover:bg-slate-50 active:scale-95"
                            >
                                {{ $cancelText }}
                            </button>
                            <button
                                type="submit"
                                data-edit-user-submit
                                class="cursor-pointer rounded-2xl bg-[#B5114A] px-8 py-3 text-sm font-bold text-white shadow-lg shadow-[#B5114A]/20 transition hover:bg-[#9a0e3f] active:scale-95"
                            >
                                {{ $saveText }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
