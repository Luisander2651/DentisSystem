<x-ui.dialog id="day-details-modal" title="Citas del día">
    <p id="modal-date-display" class="text-sm font-semibold text-ink"></p>
    <div class="mt-3 space-y-3" id="modal-appointments-list"></div>

    <x-slot:footer>
        <x-ui.button data-dialog-close>Cerrar</x-ui.button>
    </x-slot:footer>
</x-ui.dialog>
