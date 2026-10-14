<x-ui.dialog id="promotions-edit-modal" title="Editar promoción" data-promotions-edit-modal>
    <form id="promotions-edit-form" data-promotions-edit-form class="flex flex-col gap-4" novalidate>
        <input type="hidden" name="id" data-promotions-edit-id />

        <x-ui.input
            variant="text"
            id="promotions-edit-name"
            name="name"
            label="Nombre de la promoción"
            autocomplete="off"
            data-promotions-edit-name
            required
        />

        <div>
            <label for="promotions-edit-description" class="mb-1.5 block text-sm font-semibold text-ink">
                Descripción
                    <span class="text-danger" title="Obligatorio">*</span>
            </label>
            <textarea id="promotions-edit-description" name="description" rows="3" data-promotions-edit-description class="block w-full rounded-control border border-field bg-surface px-3 py-2.5 text-control text-ink" required></textarea>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.input
                variant="number"
                id="promotions-edit-discount"
                name="discount_percentage"
                label="Descuento (%)"
                hint="De 0 a 100."
                autocomplete="off"
                data-promotions-edit-discount
                required
            />

            <div>
                <label for="promotions-edit-status" class="mb-1.5 block text-sm font-semibold text-ink">Estado</label>
                <select id="promotions-edit-status" name="status" data-promotions-edit-status class="block h-control w-full rounded-control border border-field bg-surface px-3 text-control text-ink">
                    <option value="visible">Visible</option>
                    <option value="oculto">Oculto</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="promotions-edit-start-date" class="mb-1.5 block text-sm font-semibold text-ink">
                    Fecha de inicio
                    <span class="text-danger" title="Obligatorio">*</span>
                </label>
                <input type="date" id="promotions-edit-start-date" name="start_date" data-promotions-edit-start-date class="block h-control w-full rounded-control border border-field bg-surface px-3 text-control text-ink" required />
            </div>

            <div>
                <label for="promotions-edit-end-date" class="mb-1.5 block text-sm font-semibold text-ink">
                    Fecha de cierre
                    <span class="text-danger" title="Obligatorio">*</span>
                </label>
                <input type="date" id="promotions-edit-end-date" name="end_date" data-promotions-edit-end-date class="block h-control w-full rounded-control border border-field bg-surface px-3 text-control text-ink" required />
            </div>
        </div>
    </form>

    <x-slot:footer>
        <x-ui.button data-promotions-edit-cancel>Cancelar</x-ui.button>
        <x-ui.button variant="primary" type="submit" form="promotions-edit-form" data-promotions-edit-submit>Guardar cambios</x-ui.button>
    </x-slot:footer>
</x-ui.dialog>
