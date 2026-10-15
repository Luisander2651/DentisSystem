<x-ui.dialog id="gallery-create-modal" title="Agregar imagen" data-gallery-create-modal>
    <form id="gallery-create-form" data-gallery-create-form class="flex flex-col gap-4" novalidate>
        <div class="flex h-40 items-center justify-center overflow-hidden rounded-box border border-line bg-canvas">
            <img data-gallery-create-preview class="hidden h-full w-full object-cover" alt="Vista previa de la imagen elegida" />
            <p data-gallery-create-preview-empty class="px-4 text-center text-sm text-muted">Elige una imagen para verla antes de guardar.</p>
        </div>

        <div>
            <label for="gallery-create-image" class="mb-1.5 block text-sm font-semibold text-ink">
                Imagen
                <span class="text-danger" title="Obligatorio">*</span>
            </label>
            <input type="file" id="gallery-create-image" name="image" accept="image/*" data-gallery-create-image class="block w-full text-min text-ink file:mr-2 file:min-h-control file:rounded-control file:border file:border-solid file:border-field file:bg-surface file:px-2 file:text-sm file:font-semibold file:text-ink" required />
        </div>

        <div>
            <label for="gallery-create-description" class="mb-1.5 block text-sm font-semibold text-ink">
                Descripción
                <span class="text-danger" title="Obligatorio">*</span>
            </label>
            <textarea id="gallery-create-description" name="description" rows="4" aria-describedby="gallery-create-description-hint" data-gallery-create-description class="block w-full rounded-control border border-field bg-surface px-3 py-2.5 text-control text-ink" placeholder="Describe la imagen en pocas palabras" required></textarea>
            <p id="gallery-create-description-hint" class="mt-1 text-min text-muted">La imagen se publica como visible; puedes ocultarla después al editarla.</p>
        </div>
    </form>

    <x-slot:footer>
        <x-ui.button data-gallery-create-cancel>Cancelar</x-ui.button>
        <x-ui.button variant="primary" type="submit" form="gallery-create-form" data-gallery-create-submit>Guardar imagen</x-ui.button>
    </x-slot:footer>
</x-ui.dialog>
