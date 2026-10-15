<x-ui.dialog id="certifications-delete-modal" title="Eliminar certificación" size="sm" data-certifications-delete-modal>
    <form id="certifications-delete-form" data-certifications-delete-form>
        <input type="hidden" name="id" data-certifications-delete-id />

        <p class="text-sm leading-6 text-ink">
            Vas a eliminar la certificación
            <span data-certifications-delete-name class="font-semibold wrap-anywhere">seleccionada</span>.
        </p>
        <p class="mt-2 text-sm font-semibold text-danger">Esta acción no se puede deshacer.</p>
    </form>

    <x-slot:footer>
        <x-ui.button data-certifications-delete-cancel>Cancelar</x-ui.button>
        <x-ui.button variant="danger" type="submit" form="certifications-delete-form" data-certifications-delete-submit>Eliminar</x-ui.button>
    </x-slot:footer>
</x-ui.dialog>
