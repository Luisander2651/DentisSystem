<div id="testimonials-delete-modal" data-testimonials-delete-modal class="fixed inset-0 z-100 hidden overflow-y-auto">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" aria-hidden="true" data-testimonials-delete-cancel></div>

    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="w-full max-w-md rounded-[1.75rem] border border-slate-200 bg-white shadow-xl">
        <div class="border-b border-slate-200 px-6 py-5">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Testimonios</p>
            <h3 class="mt-2 text-lg font-semibold text-slate-900">Eliminar testimonio</h3>
        </div>

        <form data-testimonials-delete-form class="space-y-4 px-6 py-5">
            <input type="hidden" data-testimonials-delete-id name="id" />

            <div class="rounded-2xl border border-red-200 bg-red-50 p-4">
                <p class="text-sm leading-6 text-red-800">
                    Esta accion eliminara el testimonio de
                    <span data-testimonials-delete-target class="font-semibold text-red-900">el autor</span>
                    de forma permanente.
                </p>
            </div>

            <p data-testimonials-delete-error class="hidden rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></p>

            <div class="flex items-center justify-end gap-2 border-t border-slate-200 pt-4">
                <button type="button" data-testimonials-delete-cancel class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">Cancelar</button>
                <button type="submit" data-testimonials-delete-submit class="rounded-xl bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60">Eliminar</button>
            </div>
        </form>
            </div>
        </div>
    </div>
</div>