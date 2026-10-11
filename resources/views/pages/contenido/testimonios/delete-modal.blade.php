<x-ui.dialog id="testimonials-delete-modal" title="Eliminar testimonio" size="sm" data-testimonials-delete-modal>
    <form id="testimonials-delete-form" data-testimonials-delete-form>
        <input type="hidden" data-testimonials-delete-id name="id" />

        <p class="text-sm leading-6 text-ink">
            Vas a eliminar el testimonio de
            <span data-testimonials-delete-target class="font-semibold wrap-anywhere">este autor</span>.
        </p>
        <p class="mt-2 text-sm font-semibold text-danger">Esta acción no se puede deshacer.</p>
    </form>

    <x-slot:footer>
        <x-ui.button data-testimonials-delete-cancel>Cancelar</x-ui.button>
        <x-ui.button variant="danger" type="submit" form="testimonials-delete-form" data-testimonials-delete-submit>Eliminar</x-ui.button>
    </x-slot:footer>
</x-ui.dialog>
