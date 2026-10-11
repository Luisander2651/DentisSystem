<x-ui.dialog id="gallery-delete-modal" title="Eliminar imagen" size="sm" data-gallery-delete-modal>
    <form id="gallery-delete-form" data-gallery-delete-form>
        <input type="hidden" data-gallery-delete-id name="id" />

        <p class="text-sm leading-6 text-ink">
            Vas a eliminar la imagen
            <span data-gallery-delete-target class="font-semibold wrap-anywhere">seleccionada</span>.
        </p>
        <p class="mt-2 text-sm font-semibold text-danger">Esta acción no se puede deshacer.</p>
    </form>

    <x-slot:footer>
        <x-ui.button data-gallery-delete-cancel>Cancelar</x-ui.button>
        <x-ui.button variant="danger" type="submit" form="gallery-delete-form" data-gallery-delete-submit>Eliminar</x-ui.button>
    </x-slot:footer>
</x-ui.dialog>
