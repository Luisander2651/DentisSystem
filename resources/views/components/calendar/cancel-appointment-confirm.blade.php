{{--
    Confirmación de cancelar una cita (spec 017, design.md → Composición). Componente propio de la
    agenda hecho con x-ui.dialog y x-ui.button: no reutiliza x-ui.confirm-delete-modal porque ese
    fija sus textos. El foco empieza en "No, mantenerla"; Escape, la X y el fondo equivalen a no
    cancelar. Lo maneja resources/js/pages/agenda/cancel-appointment.js.
--}}
<x-ui.dialog id="cancel-appointment-confirm" title="Cancelar cita" size="sm" data-cancel-appointment-confirm>
    <p class="text-sm leading-6 text-ink">
        ¿Seguro que quieres cancelar la cita de
        <strong class="font-semibold wrap-anywhere" data-cancel-appointment-patient></strong>
        el <span data-cancel-appointment-date></span> a las <span data-cancel-appointment-time></span>?
    </p>
    <p class="mt-2 text-sm font-semibold text-danger">Esta acción no se puede deshacer.</p>

    <x-slot:footer>
        <x-ui.button data-cancel-appointment-keep>No, mantenerla</x-ui.button>
        <x-ui.button variant="danger" data-cancel-appointment-accept>Sí, cancelar la cita</x-ui.button>
    </x-slot:footer>
</x-ui.dialog>
