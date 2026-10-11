<x-ui.dialog id="promotions-create-modal" title="Nueva promoción" data-promotions-create-modal>
    <form id="promotions-create-form" data-promotions-create-form class="flex flex-col gap-4" novalidate>
        <x-ui.input
            variant="string"
            id="promotions-create-name"
            name="name"
            label="Nombre de la promoción"
            placeholder="Ej. Limpieza de temporada"
            autocomplete="off"
            data-promotions-create-name
            required
        />

        <div>
            <label for="promotions-create-description" class="mb-1.5 block text-sm font-semibold text-ink">
                Descripción
                    <span class="text-danger" title="Obligatorio">*</span>
            </label>
            <textarea id="promotions-create-description" name="description" rows="3" data-promotions-create-description class="block w-full rounded-control border border-field bg-surface px-3 py-2.5 text-control text-ink" required></textarea>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.input
                variant="number"
                id="promotions-create-discount"
                name="discount_percentage"
                label="Descuento (%)"
                hint="De 0 a 100."
                autocomplete="off"
                data-promotions-create-discount
                required
            />
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="promotions-create-start-date" class="mb-1.5 block text-sm font-semibold text-ink">
                    Fecha de inicio
                    <span class="text-danger" title="Obligatorio">*</span>
                </label>
                <input type="date" id="promotions-create-start-date" name="start_date" data-promotions-create-start-date class="block h-control w-full rounded-control border border-field bg-surface px-3 text-control text-ink" required />
            </div>

            <div>
                <label for="promotions-create-end-date" class="mb-1.5 block text-sm font-semibold text-ink">
                    Fecha de cierre
                    <span class="text-danger" title="Obligatorio">*</span>
                </label>
                <input type="date" id="promotions-create-end-date" name="end_date" data-promotions-create-end-date class="block h-control w-full rounded-control border border-field bg-surface px-3 text-control text-ink" required />
            </div>
        </div>
    </form>

    <x-slot:footer>
        <x-ui.button data-promotions-create-cancel>Cancelar</x-ui.button>
        <x-ui.button variant="primary" type="submit" form="promotions-create-form" data-promotions-create-submit>Guardar promoción</x-ui.button>
    </x-slot:footer>
</x-ui.dialog>
