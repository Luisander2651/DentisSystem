<x-ui.dialog id="promotions-delete-modal" title="Eliminar promoción" size="sm" data-promotions-delete-modal>
    <form id="promotions-delete-form" data-promotions-delete-form>
        <input type="hidden" data-promotions-delete-id name="id" />

        <p class="text-sm leading-6 text-ink">
            Vas a eliminar la promoción
            <span data-promotions-delete-target class="font-semibold wrap-anywhere">seleccionada</span>.
        </p>
        <p class="mt-2 text-sm font-semibold text-danger">Esta acción no se puede deshacer.</p>
    </form>

    <x-slot:footer>
        <x-ui.button data-promotions-delete-cancel>Cancelar</x-ui.button>
        <x-ui.button variant="danger" type="submit" form="promotions-delete-form" data-promotions-delete-submit>Eliminar</x-ui.button>
    </x-slot:footer>
</x-ui.dialog>
