@props([
    // Spec 018: whose clinical record the detail shows: 'all', 'own' (the actor's appointments) or 'none'.
    'trackingScope' => 'none',
    'actorId' => '',
])

<x-ui.dialog
    id="view-appointment-modal"
    title="Detalle de la cita"
    data-view-appointment-modal
    data-view-appointment-tracking-scope="{{ $trackingScope }}"
    data-view-appointment-actor-id="{{ $actorId }}"
>
    <dl data-view-appointment-general></dl>

    {{-- Spec 024: the QR code of an open appointment, for reception to show or send to the patient. --}}
    <section class="mt-4 hidden" aria-labelledby="view-appointment-qr-title" data-view-appointment-qr>
        <h3 id="view-appointment-qr-title" class="text-base font-bold text-ink">Código QR de acceso</h3>
        <p class="mt-1 text-sm text-muted">Muéstralo o mándalo al paciente para que entre el día de su cita.</p>
        <div class="mt-2 flex flex-col items-start gap-3">
            <div class="shrink-0 rounded-box border border-field bg-surface p-2">
                <img class="block size-60" alt="" data-view-appointment-qr-image>
            </div>
            <p class="hidden rounded-box border border-danger bg-danger-soft px-4 py-3 text-sm font-semibold text-danger" data-view-appointment-qr-error>No pudimos cargar el código QR. Cierra el detalle y vuelve a abrirlo.</p>
            <x-ui.button disabled data-view-appointment-qr-download>Descargar QR</x-ui.button>
        </div>
    </section>

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
