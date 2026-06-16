<div id="certifications-delete-modal" data-certifications-delete-modal class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" aria-hidden="true" data-certifications-delete-cancel></div>

    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center">
            <form data-certifications-delete-form class="w-full max-w-md transform overflow-hidden rounded-[1.75rem] bg-white p-6 text-left align-middle shadow-xl transition-all">
                <input type="hidden" name="id" data-certifications-delete-id />
                
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <h3 class="text-lg font-semibold text-slate-900">Confirmar eliminacion</h3>
                    <button type="button" data-certifications-delete-cancel class="text-slate-400 hover:text-slate-500 transition-colors">
                        <span class="sr-only">Cerrar</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mt-4">
                    <div class="rounded-3xl border border-red-200 bg-red-50 p-4">
                        <div class="flex">
                            <div class="shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700">
                                    ¿Estás seguro de que deseas eliminar la certificacion <span class="font-bold" data-certifications-delete-name></span>? Esta accion no se puede deshacer.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <p data-certifications-delete-error class="mt-4 hidden rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></p>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" data-certifications-delete-cancel class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">Cancelar</button>
                    <button type="submit" data-certifications-delete-submit class="rounded-xl bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60">Eliminar</button>
                </div>
            </form>
        </div>
    </div>
</div>