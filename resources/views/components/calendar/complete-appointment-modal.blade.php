<div
    id="complete-appointment-modal"
    data-complete-appointment-modal
    class="fixed inset-0 z-130 hidden overflow-y-auto"
    aria-labelledby="complete-appointment-title"
    role="dialog"
    aria-modal="true"
>
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" aria-hidden="true" data-complete-appointment-cancel></div>

    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative w-full max-w-2xl transform overflow-hidden rounded-[1.75rem] bg-white text-left shadow-xl transition-all sm:my-8">
                <div class="bg-white px-8 pt-8 pb-6">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-6">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#B5114A]">Agenda</p>
                            <h3 id="complete-appointment-title" class="mt-1 text-xl font-extrabold text-slate-900">Completar cita</h3>
                            <p class="mt-1 text-xs font-bold text-slate-400" data-complete-appointment-step-indicator>Paso 1 de 2 · Datos clínicos</p>
                        </div>
                        <button type="button" class="rounded-xl p-2 text-slate-400 transition hover:bg-slate-50 hover:text-slate-500" data-complete-appointment-cancel>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-700" data-complete-appointment-summary>Paciente • Tratamiento</p>
                    </div>

                    <form data-complete-appointment-form class="mt-6" novalidate>
                        {{-- Step 1: general clinical data --}}
                        <div data-complete-appointment-step="1" class="space-y-5">
                            <div>
                                <label class="mb-1 block text-sm font-bold text-slate-700">Motivo de la consulta</label>
                                <textarea
                                    data-complete-appointment-reason
                                    rows="2"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-900 transition-all focus:border-[#B5114A] focus:outline-none focus:ring-4 focus:ring-[#B5114A]/10"
                                    placeholder="Ej. Dolor en molar inferior derecho"
                                ></textarea>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-slate-700">
                                    Síntomas <span class="font-normal text-slate-400">(separados por coma, opcional)</span>
                                </label>
                                <textarea
                                    data-complete-appointment-symptoms
                                    rows="2"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-900 transition-all focus:border-[#B5114A] focus:outline-none focus:ring-4 focus:ring-[#B5114A]/10"
                                    placeholder="Ej. Sensibilidad, inflamación"
                                ></textarea>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-slate-700">Diagnóstico</label>
                                <textarea
                                    data-complete-appointment-diagnosis
                                    rows="2"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-900 transition-all focus:border-[#B5114A] focus:outline-none focus:ring-4 focus:ring-[#B5114A]/10"
                                    placeholder="Ej. Caries profunda en pieza 46"
                                ></textarea>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-slate-700">Procedimiento realizado</label>
                                <textarea
                                    data-complete-appointment-procedure
                                    rows="2"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-900 transition-all focus:border-[#B5114A] focus:outline-none focus:ring-4 focus:ring-[#B5114A]/10"
                                    placeholder="Ej. Obturación con resina compuesta"
                                ></textarea>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-slate-700">
                                    Observaciones <span class="font-normal text-slate-400">(opcional)</span>
                                </label>
                                <textarea
                                    data-complete-appointment-observations
                                    rows="2"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-900 transition-all focus:border-[#B5114A] focus:outline-none focus:ring-4 focus:ring-[#B5114A]/10"
                                ></textarea>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-slate-700">
                                    Recomendaciones <span class="font-normal text-slate-400">(opcional)</span>
                                </label>
                                <textarea
                                    data-complete-appointment-recommendations
                                    rows="2"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-900 transition-all focus:border-[#B5114A] focus:outline-none focus:ring-4 focus:ring-[#B5114A]/10"
                                ></textarea>
                            </div>
                        </div>

                        {{-- Step 2: prescriptions --}}
                        <div data-complete-appointment-step="2" class="hidden space-y-4">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-bold text-slate-700">Prescripciones</p>
                                <button
                                    type="button"
                                    data-complete-appointment-add-prescription
                                    class="cursor-pointer rounded-xl border border-[#F5C2D6] bg-[#FDF1F6] px-3 py-2 text-xs font-bold text-[#B5114A] transition hover:bg-[#F9DCE7] active:scale-95"
                                >
                                    + Agregar prescripción
                                </button>
                            </div>

                            <div data-complete-appointment-prescriptions-empty class="rounded-2xl border border-dashed border-slate-200 py-8 text-center text-xs font-semibold text-slate-400">
                                Sin prescripciones. Puedes completar la cita sin agregar ninguna.
                            </div>

                            <div data-complete-appointment-prescriptions-list class="space-y-3"></div>
                        </div>

                        <p data-complete-appointment-error class="mt-5 hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700"></p>

                        <div class="mt-6 flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                            <button
                                type="button"
                                data-complete-appointment-cancel
                                class="cursor-pointer rounded-2xl border border-slate-200 bg-white px-6 py-3 text-sm font-bold text-slate-600 transition hover:bg-slate-50 active:scale-95"
                            >
                                Cancelar
                            </button>
                            <button
                                type="button"
                                data-complete-appointment-back
                                class="hidden cursor-pointer rounded-2xl border border-slate-200 bg-white px-6 py-3 text-sm font-bold text-slate-600 transition hover:bg-slate-50 active:scale-95"
                            >
                                Atrás
                            </button>
                            <button
                                type="button"
                                data-complete-appointment-next
                                class="cursor-pointer rounded-2xl bg-[#E91E63] px-8 py-3 text-sm font-semibold text-white shadow-lg shadow-[#E91E63]/20 transition hover:bg-[#d61b5b] active:scale-95"
                            >
                                Siguiente
                            </button>
                            <button
                                type="button"
                                data-complete-appointment-submit
                                class="hidden cursor-pointer rounded-2xl bg-[#E91E63] px-8 py-3 text-sm font-semibold text-white shadow-lg shadow-[#E91E63]/20 transition hover:bg-[#d61b5b] active:scale-95"
                            >
                                Completar cita
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Nested confirmation sub-dialog (discard changes / confirm completion) --}}
    <div data-complete-appointment-confirm class="fixed inset-0 z-20 hidden overflow-y-auto">
        <div class="fixed inset-0 bg-slate-900/50" aria-hidden="true" data-complete-appointment-confirm-cancel></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative w-full max-w-sm transform overflow-hidden rounded-[1.75rem] bg-white text-left shadow-xl transition-all sm:my-8">
                    <div class="bg-white px-8 pt-8 pb-6 text-center">
                        <h3 class="text-lg font-extrabold text-slate-900" data-complete-appointment-confirm-title>¿Estás seguro?</h3>
                        <p class="mt-3 text-sm font-medium leading-relaxed text-slate-500" data-complete-appointment-confirm-message></p>
                        <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                            <button
                                type="button"
                                data-complete-appointment-confirm-cancel
                                class="w-full cursor-pointer rounded-2xl border border-slate-200 bg-white px-6 py-3 text-sm font-bold text-slate-600 transition hover:bg-slate-50 active:scale-95 sm:w-auto"
                            >
                                <span data-complete-appointment-confirm-cancel-text>Seguir editando</span>
                            </button>
                            <button
                                type="button"
                                data-complete-appointment-confirm-accept
                                class="w-full cursor-pointer rounded-2xl bg-[#E91E63] px-6 py-3 text-sm font-bold text-white shadow-lg shadow-[#E91E63]/20 transition hover:bg-[#d61b5b] active:scale-95 sm:w-auto"
                            >
                                <span data-complete-appointment-confirm-accept-text>Confirmar</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
