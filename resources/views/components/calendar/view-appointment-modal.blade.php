@props(['isAdmin' => false])

<div
    id="view-appointment-modal"
    data-view-appointment-modal
    data-view-appointment-is-admin="{{ $isAdmin ? 'true' : 'false' }}"
    class="fixed inset-0 z-125 hidden overflow-y-auto"
    aria-labelledby="view-appointment-title"
    role="dialog"
    aria-modal="true"
>
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" aria-hidden="true" data-view-appointment-cancel></div>

    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative w-full max-w-2xl transform overflow-hidden rounded-[1.75rem] bg-white text-left shadow-xl transition-all sm:my-8">
                <div class="bg-white px-8 pt-8 pb-6">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-6">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#B5114A]">Agenda</p>
                            <h3 id="view-appointment-title" class="mt-1 text-xl font-extrabold text-slate-900">Detalle de la cita</h3>
                        </div>
                        <button type="button" class="rounded-xl p-2 text-slate-400 transition hover:bg-slate-50 hover:text-slate-500" data-view-appointment-cancel>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="mt-6 divide-y divide-slate-50 rounded-2xl border border-slate-200 bg-slate-50 px-4" data-view-appointment-general>
                        {{-- Populated by JS --}}
                    </div>

                    <div class="mt-6 hidden" data-view-appointment-clinical-section>
                        <p class="text-xs font-bold uppercase tracking-wider text-[#B5114A]">Registro clínico</p>
                        <div class="mt-3 space-y-4" data-view-appointment-clinical-body>
                            {{-- Populated by JS --}}
                        </div>
                    </div>

                    <p data-view-appointment-error class="mt-5 hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700"></p>

                    <div class="mt-6 flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                        <button
                            type="button"
                            data-view-appointment-edit
                            class="hidden cursor-pointer rounded-2xl border border-slate-200 bg-white px-6 py-3 text-sm font-bold text-slate-600 transition hover:bg-slate-50 active:scale-95"
                        >
                            Editar cita
                        </button>
                        <button
                            type="button"
                            data-view-appointment-cancel
                            class="cursor-pointer rounded-2xl bg-[#E91E63] px-8 py-3 text-sm font-semibold text-white shadow-lg shadow-[#E91E63]/20 transition hover:bg-[#d61b5b] active:scale-95"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
