<x-ui.dialog id="gallery-edit-modal" title="Editar imagen" data-gallery-edit-modal>
    <form id="gallery-edit-form" data-gallery-edit-form class="flex flex-col gap-4" novalidate>
        <input type="hidden" data-gallery-edit-id name="id" />

        <div class="flex h-40 items-center justify-center overflow-hidden rounded-box border border-line bg-canvas">
            <img data-gallery-edit-preview class="hidden h-full w-full object-cover" alt="Imagen actual" />
            <p data-gallery-edit-preview-empty class="px-4 text-center text-sm text-muted">Esta entrada no tiene imagen.</p>
        </div>

        <div>
            <label for="gallery-edit-image" class="mb-1.5 block text-sm font-semibold text-ink">Imagen nueva (opcional)</label>
            <input type="file" id="gallery-edit-image" name="image" accept="image/*" aria-describedby="gallery-edit-image-hint" data-gallery-edit-image class="block w-full text-min text-ink file:mr-2 file:min-h-control file:rounded-control file:border file:border-solid file:border-field file:bg-surface file:px-2 file:text-sm file:font-semibold file:text-ink" />
            <p id="gallery-edit-image-hint" class="mt-1 text-min text-muted">Si no eliges una nueva, se conserva la actual.</p>
        </div>

        <div>
            <label for="gallery-edit-description" class="mb-1.5 block text-sm font-semibold text-ink">Descripción</label>
            <textarea id="gallery-edit-description" name="description" rows="4" data-gallery-edit-description class="block w-full rounded-control border border-field bg-surface px-3 py-2.5 text-control text-ink"></textarea>
        </div>

        <div>
            <label for="gallery-edit-status" class="mb-1.5 block text-sm font-semibold text-ink">Estado</label>
            <select id="gallery-edit-status" name="status" data-gallery-edit-status class="block h-control w-full rounded-control border border-field bg-surface px-3 text-control text-ink">
                <option value="visible">Visible</option>
                <option value="hidden">Oculto</option>
            </select>
        </div>
    </form>

    <x-slot:footer>
        <x-ui.button data-gallery-edit-cancel>Cancelar</x-ui.button>
        <x-ui.button variant="primary" type="submit" form="gallery-edit-form" data-gallery-edit-submit>Guardar cambios</x-ui.button>
    </x-slot:footer>
</x-ui.dialog>
