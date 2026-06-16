<div id="testimonials-edit-modal" data-testimonials-edit-modal class="fixed inset-0 z-100 hidden overflow-y-auto">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" aria-hidden="true" data-testimonials-edit-cancel></div>

    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="w-full max-w-2xl rounded-[1.75rem] border border-slate-200 bg-white shadow-xl">
        <div class="border-b border-slate-200 px-6 py-5">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Testimonios</p>
            <h3 class="mt-2 text-lg font-semibold text-slate-900">Editar testimonio</h3>
        </div>

        <form data-testimonials-edit-form class="space-y-4 px-6 py-5">
            <input type="hidden" name="id" data-testimonials-edit-id />

            <div class="rounded-2xl bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Paciente</p>
                <p class="mt-1 text-sm font-medium text-slate-900" data-testimonials-edit-author-label></p>
                
                <p class="mt-3 text-xs font-semibold uppercase tracking-widest text-slate-400">Testimonio</p>
                <p class="mt-1 text-sm leading-6 text-slate-600" data-testimonials-edit-description-label></p>
            </div>

            <label class="block text-sm font-medium text-slate-700">
                Visibilidad del testimonio
                <select name="status" data-testimonials-edit-status class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-[#E91E63] focus:outline-none focus:ring-2 focus:ring-[#F8BBD0]">
                    <option value="visible">Visible</option>
                    <option value="oculto">Oculto</option>
                </select>
            </label>

            <p data-testimonials-edit-error class="hidden rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></p>

            <div class="flex items-center justify-end gap-2 border-t border-slate-200 pt-4">
                <button type="button" data-testimonials-edit-cancel class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">Cancelar</button>
                <button type="submit" data-testimonials-edit-submit class="rounded-xl bg-[#E91E63] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#d81b60] disabled:cursor-not-allowed disabled:opacity-60">Guardar cambios</button>
            </div>
        </form>
            </div>
        </div>
    </div>
</div>