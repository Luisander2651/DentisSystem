<div id="promotions-edit-modal" data-promotions-edit-modal class="fixed inset-0 z-100 hidden overflow-y-auto">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" aria-hidden="true" data-promotions-edit-cancel></div>

    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="w-full max-w-2xl rounded-[1.75rem] border border-slate-200 bg-white shadow-xl">
        <div class="border-b border-slate-200 px-6 py-5">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Promociones</p>
            <h3 class="mt-2 text-lg font-semibold text-slate-900">Editar promocion</h3>
        </div>

        <form data-promotions-edit-form class="space-y-4 px-6 py-5">
            <input type="hidden" name="id" data-promotions-edit-id />

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block text-sm font-medium text-slate-700 sm:col-span-2">
                    Nombre de la promocion
                    <input type="text" name="name" data-promotions-edit-name class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-[#E91E63] focus:outline-none focus:ring-2 focus:ring-[#F8BBD0]" required />
                </label>

                <label class="block text-sm font-medium text-slate-700 sm:col-span-2">
                    Descripcion
                    <textarea name="description" rows="3" data-promotions-edit-description class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-[#E91E63] focus:outline-none focus:ring-2 focus:ring-[#F8BBD0]" required></textarea>
                </label>

                <label class="block text-sm font-medium text-slate-700">
                    Porcentaje de descuento (%)
                    <input type="number" name="discount_percentage" step="0.01" min="0" max="100" data-promotions-edit-discount class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-[#E91E63] focus:outline-none focus:ring-2 focus:ring-[#F8BBD0]" />
                </label>

                <label class="block text-sm font-medium text-slate-700">
                    Estado
                    <select name="status" data-promotions-edit-status class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-[#E91E63] focus:outline-none focus:ring-2 focus:ring-[#F8BBD0]">
                        <option value="visible">Visible</option>
                        <option value="oculto">Oculto</option>
                    </select>
                </label>

                <label class="block text-sm font-medium text-slate-700">
                    Fecha de inicio
                    <input type="date" name="start_date" data-promotions-edit-start-date class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-[#E91E63] focus:outline-none focus:ring-2 focus:ring-[#F8BBD0]" required />
                </label>

                <label class="block text-sm font-medium text-slate-700">
                    Fecha de cierre
                    <input type="date" name="end_date" data-promotions-edit-end-date class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-[#E91E63] focus:outline-none focus:ring-2 focus:ring-[#F8BBD0]" required />
                </label>
            </div>

            <p data-promotions-edit-error class="hidden rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></p>

            <div class="flex items-center justify-end gap-2 border-t border-slate-200 pt-4">
                <button type="button" data-promotions-edit-cancel class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">Cancelar</button>
                <button type="submit" data-promotions-edit-submit class="rounded-xl bg-[#E91E63] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#d81b60] disabled:cursor-not-allowed disabled:opacity-60">Guardar cambios</button>
            </div>
        </form>
            </div>
        </div>
    </div>
</div>