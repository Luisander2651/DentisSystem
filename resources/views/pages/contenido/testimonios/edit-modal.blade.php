<x-ui.dialog id="testimonials-edit-modal" title="Cambiar visibilidad del testimonio" data-testimonials-edit-modal>
    <form id="testimonials-edit-form" data-testimonials-edit-form class="flex flex-col gap-4">
        <input type="hidden" name="id" data-testimonials-edit-id />

        <dl>
            <x-ui.record label="Autor"><span data-testimonials-edit-author-label></span></x-ui.record>
            <x-ui.record label="Testimonio"><span data-testimonials-edit-description-label></span></x-ui.record>
        </dl>

        <div>
            <label for="testimonials-edit-status" class="mb-1.5 block text-sm font-semibold text-ink">Estado</label>
            <select id="testimonials-edit-status" name="status" data-testimonials-edit-status class="block h-control w-full rounded-control border border-field bg-surface px-3 text-control text-ink">
                <option value="visible">Visible</option>
                <option value="oculto">Oculto</option>
            </select>
        </div>
    </form>

    <x-slot:footer>
        <x-ui.button data-testimonials-edit-cancel>Cancelar</x-ui.button>
        <x-ui.button variant="primary" type="submit" form="testimonials-edit-form" data-testimonials-edit-submit>Guardar cambios</x-ui.button>
    </x-slot:footer>
</x-ui.dialog>
