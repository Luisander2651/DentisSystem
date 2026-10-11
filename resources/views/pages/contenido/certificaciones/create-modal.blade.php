<x-ui.dialog id="certifications-create-modal" title="Nueva certificación" data-certifications-create-modal>
    <form id="certifications-create-form" data-certifications-create-form class="flex flex-col gap-4" novalidate>
        <div class="flex h-40 items-center justify-center overflow-hidden rounded-box border border-line bg-canvas">
            <img data-certifications-create-preview class="hidden h-full w-full object-contain" alt="Vista previa de la imagen elegida" />
            <p data-certifications-create-preview-empty class="px-4 text-center text-sm text-muted">Elige una imagen para verla antes de guardar.</p>
        </div>

        <div>
            <label for="certifications-create-image" class="mb-1.5 block text-sm font-semibold text-ink">
                Imagen o sello
                <span class="text-danger" title="Obligatorio">*</span>
            </label>
            <input type="file" id="certifications-create-image" name="image" accept="image/*" data-certifications-create-image class="block w-full text-min text-ink file:mr-2 file:min-h-control file:rounded-control file:border file:border-solid file:border-field file:bg-surface file:px-2 file:text-sm file:font-semibold file:text-ink" required />
        </div>

        <x-ui.input
            variant="string"
            id="certifications-create-name"
            name="name"
            label="Nombre de la certificación"
            autocomplete="off"
            data-certifications-create-name
            required
        />

        <div>
            <label for="certifications-create-description" class="mb-1.5 block text-sm font-semibold text-ink">
                Descripción
                <span class="text-danger" title="Obligatorio">*</span>
            </label>
            <textarea id="certifications-create-description" name="description" rows="3" data-certifications-create-description class="block w-full rounded-control border border-field bg-surface px-3 py-2.5 text-control text-ink" required></textarea>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="certifications-create-date" class="mb-1.5 block text-sm font-semibold text-ink">
                    Fecha del documento
                <span class="text-danger" title="Obligatorio">*</span>
                </label>
                <input type="date" id="certifications-create-date" name="date" data-certifications-create-date class="block h-control w-full rounded-control border border-field bg-surface px-3 text-control text-ink" required />
            </div>
        </div>
    </form>

    <x-slot:footer>
        <x-ui.button data-certifications-create-cancel>Cancelar</x-ui.button>
        <x-ui.button variant="primary" type="submit" form="certifications-create-form" data-certifications-create-submit>Guardar certificación</x-ui.button>
    </x-slot:footer>
</x-ui.dialog>
