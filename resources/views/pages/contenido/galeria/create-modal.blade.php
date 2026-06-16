<div id="gallery-create-modal" data-gallery-create-modal class="fixed inset-0 z-100 hidden overflow-y-auto">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" aria-hidden="true" data-gallery-create-cancel></div>

    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="w-full max-w-3xl rounded-[1.75rem] border border-slate-200 bg-white shadow-xl">
        <div class="border-b border-slate-200 px-6 py-5">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Galeria</p>
            <h3 class="mt-2 text-lg font-semibold text-slate-900">Agregar imagen</h3>
        </div>

        <form data-gallery-create-form class="space-y-5 px-6 py-5">
            <div class="grid gap-5 lg:grid-cols-[0.95fr_1.05fr]">
                <div class="space-y-3">
                    <div class="rounded-3xl border border-dashed border-[#F5C2D6] bg-[#FFF7FA] p-4">
                        <div class="flex min-h-56 items-center justify-center overflow-hidden rounded-2xl border border-white/80 bg-white/70">
                            <img data-gallery-create-preview class="hidden h-full w-full object-cover" alt="Nueva imagen" />
                            <div data-gallery-create-preview-empty class="px-4 py-6 text-center">
                                <div class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-[#FDF1F6] text-[#E91E63]">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                                        <circle cx="8.5" cy="8.5" r="1.5" />
                                        <path d="m21 15-5-5L5 21" />
                                    </svg>
                                </div>
                                <p class="text-sm font-semibold text-slate-700">Previsualizacion</p>
                                <p class="mt-1 text-xs text-slate-500">Selecciona una imagen para verla antes de guardar.</p>
                            </div>
                        </div>
                    </div>

                    <label class="block text-sm font-medium text-slate-700">
                        Imagen
                        <input type="file" name="image" accept="image/*" data-gallery-create-image class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-[#E91E63] focus:outline-none focus:ring-2 focus:ring-[#F8BBD0]" required />
                    </label>

                    <p class="text-xs text-slate-500">La imagen es obligatoria para crear un registro nuevo.</p>
                </div>

                <div class="space-y-4">
                    <label class="block text-sm font-medium text-slate-700">
                        Descripcion
                        <textarea name="description" rows="8" data-gallery-create-description class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-[#E91E63] focus:outline-none focus:ring-2 focus:ring-[#F8BBD0]" placeholder="Escribe una descripcion breve" required></textarea>
                    </label>

                    <article class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Estado inicial</p>
                        <p class="mt-2 text-sm text-slate-600">El backend asigna <span class="font-semibold text-slate-900">visible</span> por defecto al crear el registro.</p>
                    </article>

                    <p data-gallery-create-error class="hidden rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-slate-200 pt-4">
                <button type="button" data-gallery-create-cancel class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">Cancelar</button>
                <button type="submit" data-gallery-create-submit class="rounded-xl bg-[#E91E63] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#d81b60] disabled:cursor-not-allowed disabled:opacity-60">Guardar imagen</button>
            </div>
        </form>
            </div>
        </div>
    </div>
</div>