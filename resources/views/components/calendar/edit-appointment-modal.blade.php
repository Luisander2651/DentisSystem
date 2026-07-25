<div
    id="edit-appointment-modal"
    data-edit-appointment-modal
    class="fixed inset-0 z-[120] hidden overflow-y-auto"
    aria-labelledby="edit-appointment-title"
    role="dialog"
    aria-modal="true"
>
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" aria-hidden="true" data-edit-appointment-cancel></div>

    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative w-full max-w-2xl transform overflow-hidden rounded-[1.75rem] bg-white text-left shadow-xl transition-all sm:my-8">
                <div class="bg-white px-8 pt-8 pb-6">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-6">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#B5114A]">Agenda</p>
                            <h3 id="edit-appointment-title" class="mt-1 text-xl font-extrabold text-slate-900">Editar cita</h3>
                        </div>
                        <button type="button" class="rounded-xl p-2 text-slate-400 transition hover:bg-slate-50 hover:text-slate-500" data-edit-appointment-cancel>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form data-edit-appointment-form class="mt-6 space-y-5">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-700" data-edit-appointment-summary>Actualiza la fecha, la hora o el estado de la cita.</p>
                        </div>

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-sm font-bold text-slate-700">Fecha</label>
                                <input
                                    type="date"
                                    name="date"
                                    data-edit-appointment-date
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-900 transition-all focus:border-[#B5114A] focus:outline-none focus:ring-4 focus:ring-[#B5114A]/10"
                                    required
                                >
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-slate-700">Hora</label>
                                <input
                                    type="time"
                                    name="time"
                                    data-edit-appointment-time
                                    step="900"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-900 transition-all focus:border-[#B5114A] focus:outline-none focus:ring-4 focus:ring-[#B5114A]/10"
                                    required
                                >
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-slate-700">Estado</label>
                                <select
                                    name="status"
                                    data-edit-appointment-status
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-900 transition-all focus:border-[#B5114A] focus:outline-none focus:ring-4 focus:ring-[#B5114A]/10"
                                    required
                                >
                                    <option value="asignada">Asignada</option>
                                    <option value="completada">Completada</option>
                                    <option value="cancelada">Cancelada</option>
                                    <option value="reprogramada">Reprogramada</option>
                                </select>
                            </div>
                        </div>

                        <label class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700">
                            <input type="checkbox" value="1" data-edit-appointment-whatsapp-reminder class="h-4 w-4 rounded border-slate-300 text-[#E91E63] focus:ring-[#E91E63]">
                            <span>Enviar recordatorio por WhatsApp</span>
                        </label>

                        <p data-edit-appointment-error class="hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700"></p>

                        <div class="flex items-center justify-end gap-3 pt-4">
                            <button
                                type="button"
                                data-edit-appointment-cancel
                                class="cursor-pointer rounded-2xl border border-slate-200 bg-white px-6 py-3 text-sm font-bold text-slate-600 transition hover:bg-slate-50 active:scale-95"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                data-edit-appointment-submit
                                class="cursor-pointer rounded-2xl bg-[#E91E63] px-8 py-3 text-sm font-semibold text-white shadow-lg shadow-[#E91E63]/20 transition hover:bg-[#d61b5b] active:scale-95"
                            >
                                Guardar cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
