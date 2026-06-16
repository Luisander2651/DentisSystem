<div id="day-details-modal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    {{-- Background backdrop with blur --}}
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" aria-hidden="true" data-close-modal></div>

    {{-- Modal content container --}}
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-[1.75rem] bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                        <h3 class="text-lg font-bold text-slate-900" id="modal-date-display">Citas del día</h3>
                        <button type="button" class="text-slate-400 hover:text-slate-500 transition" data-close-modal>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    
                    <div class="mt-4 max-h-[60vh] space-y-3" id="modal-appointments-list">
                        {{-- Populated by JS --}}
                    </div>
                </div>
                <div class="bg-slate-50 px-6 py-4 flex justify-end">
                    <button type="button" class="rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition" data-close-modal>
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
