<x-ui.dialog id="certifications-edit-modal" title="Editar certificación" data-certifications-edit-modal>
    <form id="certifications-edit-form" data-certifications-edit-form class="flex flex-col gap-4" novalidate>
        <input type="hidden" name="id" data-certifications-edit-id />

        <div class="flex h-40 items-center justify-center overflow-hidden rounded-box border border-line bg-canvas">
            <img data-certifications-edit-preview class="hidden h-full w-full object-contain" alt="Imagen actual" />
            <p data-certifications-edit-preview-empty class="px-4 text-center text-sm text-muted">Esta certificación no tiene imagen.</p>
        </div>

        <div>
            <label for="certifications-edit-image" class="mb-1.5 block text-sm font-semibold text-ink">
                Imagen nueva (opcional)
            </label>
            <input type="file" id="certifications-edit-image" name="image" accept="image/*" data-certifications-edit-image class="block w-full text-min text-ink file:mr-2 file:min-h-control file:rounded-control file:border file:border-solid file:border-field file:bg-surface file:px-2 file:text-sm file:font-semibold file:text-ink" aria-describedby="certifications-edit-image-hint" />
            <p id="certifications-edit-image-hint" class="mt-1 text-min text-muted">Si no eliges una nueva, se conserva la actual.</p>
        </div>

        <x-ui.input
            variant="text"
            id="certifications-edit-name"
            name="name"
            label="Nombre de la certificación"
            autocomplete="off"
            data-certifications-edit-name
            required
        />

        <div>
            <label for="certifications-edit-description" class="mb-1.5 block text-sm font-semibold text-ink">
                Descripción
                <span class="text-danger" title="Obligatorio">*</span>
            </label>
            <textarea id="certifications-edit-description" name="description" rows="3" data-certifications-edit-description class="block w-full rounded-control border border-field bg-surface px-3 py-2.5 text-control text-ink" required></textarea>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="certifications-edit-date" class="mb-1.5 block text-sm font-semibold text-ink">
                    Fecha del documento
                <span class="text-danger" title="Obligatorio">*</span>
                </label>
                <input type="date" id="certifications-edit-date" name="date" data-certifications-edit-date class="block h-control w-full rounded-control border border-field bg-surface px-3 text-control text-ink" required />
            </div>

            <div>
                <label for="certifications-edit-status" class="mb-1.5 block text-sm font-semibold text-ink">Estado</label>
                <select id="certifications-edit-status" name="status" data-certifications-edit-status class="block h-control w-full rounded-control border border-field bg-surface px-3 text-control text-ink">
                    <option value="visible">Visible</option>
                    <option value="oculto">Oculto</option>
                </select>
            </div>
        </div>
    </form>

    <x-slot:footer>
        <x-ui.button data-certifications-edit-cancel>Cancelar</x-ui.button>
        <x-ui.button variant="primary" type="submit" form="certifications-edit-form" data-certifications-edit-submit>Guardar cambios</x-ui.button>
    </x-slot:footer>
</x-ui.dialog>
