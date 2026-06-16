<div id="certifications-edit-modal" data-certifications-edit-modal class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" aria-hidden="true" data-certifications-edit-cancel></div>

    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="w-full max-w-3xl transform overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-xl transition-all">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Certificaciones</p>
                        <h3 class="mt-2 text-lg font-semibold text-slate-900">Editar certificacion</h3>
                    </div>
                    <button type="button" data-certifications-edit-cancel class="text-slate-400 hover:text-slate-500 transition-colors">
                        <span class="sr-only">Cerrar</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form data-certifications-edit-form class="space-y-5 px-6 py-5">
                    <input type="hidden" name="id" data-certifications-edit-id />

                    <div class="grid gap-5 lg:grid-cols-[0.95fr_1.05fr]">
                        <div class="space-y-3">
                            <div class="rounded-3xl border border-dashed border-[#F5C2D6] bg-[#FFF7FA] p-4">
                                <div class="flex min-h-56 items-center justify-center overflow-hidden rounded-3xl border border-white/80 bg-white/70">
                                    <img data-certifications-edit-preview class="hidden h-full w-full object-cover" alt="Certificacion actual" />
                                    <div data-certifications-edit-preview-empty class="px-4 py-6 text-center">
                                        <div class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-[#FDF1F6] text-[#E91E63]">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M12 15l-3.5 2 1-3.8-3-2.7 3.9-.3L12 7l1.6 3.2 3.9.3-3 2.7 1 3.8z" />
                                                <path d="M8 21h8" />
                                                <path d="M12 15v6" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-700">Sin imagen cargada</p>
                                        <p class="mt-1 text-xs text-slate-500">Selecciona una nueva imagen para reemplazar la actual.</p>
                                    </div>
                                </div>
                            </div>

                            <label class="block text-sm font-medium text-slate-700">
                                Nueva imagen
                                <input type="file" name="image" accept="image/*" data-certifications-edit-image class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-[#E91E63] focus:outline-none focus:ring-2 focus:ring-[#F8BBD0]" />
                            </label>

                            <p class="text-xs text-slate-500">Opcional. Si no eliges una nueva, se conserva la actual.</p>
                        </div>

                        <div class="space-y-4">
                            <label class="block text-sm font-medium text-slate-700">
                                Nombre de la certificacion
                                <input type="text" name="name" data-certifications-edit-name class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-[#E91E63] focus:outline-none focus:ring-2 focus:ring-[#F8BBD0]" required />
                            </label>

                            <label class="block text-sm font-medium text-slate-700">
                                Descripcion
                                <textarea name="description" rows="3" data-certifications-edit-description class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-[#E91E63] focus:outline-none focus:ring-2 focus:ring-[#F8BBD0]" required></textarea>
                            </label>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <label class="block text-sm font-medium text-slate-700">
                                    Fecha
                                    <input type="date" name="date" data-certifications-edit-date class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-[#E91E63] focus:outline-none focus:ring-2 focus:ring-[#F8BBD0]" required />
                                </label>

                                <label class="block text-sm font-medium text-slate-700">
                                    Estado
                                    <select name="status" data-certifications-edit-status class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-[#E91E63] focus:outline-none focus:ring-2 focus:ring-[#F8BBD0]">
                                        <option value="visible">Visible</option>
                                        <option value="oculto">Oculto</option>
                                    </select>
                                </label>
                            </div>

                            <p data-certifications-edit-error class="hidden rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-slate-200 pt-4">
                        <button type="button" data-certifications-edit-cancel class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">Cancelar</button>
                        <button type="submit" data-certifications-edit-submit class="rounded-xl bg-[#E91E63] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#d81b60] disabled:cursor-not-allowed disabled:opacity-60">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>