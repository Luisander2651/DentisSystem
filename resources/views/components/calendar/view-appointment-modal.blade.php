@props(['isAdmin' => false])

<x-ui.dialog
    id="view-appointment-modal"
    title="Detalle de la cita"
    data-view-appointment-modal
    data-view-appointment-is-admin="{{ $isAdmin ? 'true' : 'false' }}"
>
    <dl data-view-appointment-general></dl>

    <section class="mt-4 hidden" aria-labelledby="view-appointment-clinical-title" data-view-appointment-clinical-section>
        <h3 id="view-appointment-clinical-title" class="text-base font-bold text-ink">Registro clínico</h3>
        <div class="mt-2" data-view-appointment-clinical-body></div>
    </section>

    <x-slot:footer>
        <x-ui.button hidden data-view-appointment-cancel-appointment>Cancelar cita</x-ui.button>
        <x-ui.button hidden data-view-appointment-edit>Reprogramar</x-ui.button>
        <x-ui.button data-view-appointment-cancel>Cerrar</x-ui.button>
    </x-slot:footer>
</x-ui.dialog>
