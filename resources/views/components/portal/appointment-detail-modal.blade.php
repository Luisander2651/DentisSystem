{{--
    Detalle de una cita del paciente (spec 025, c3-detalle y c3-detalle-qr): el diálogo base con las
    filas del detalle del personal (tratamiento, doctor, fecha, hora y estado); en una cita abierta,
    el bloque del QR; en una completada, las recomendaciones y las recetas. Lo llena
    resources/js/pages/portal/appointment-detail.js con textContent.
--}}
<x-ui.dialog id="portal-appointment-detail" title="Detalle de la cita" data-portal-detail-modal>
    <p class="text-sm text-muted" data-portal-detail-loading>Cargando…</p>
    <p class="hidden rounded-box border border-danger bg-danger-soft px-4 py-3 text-sm font-semibold text-danger" data-portal-detail-error>No pudimos cargar el detalle de la cita. Cierra el detalle y vuelve a abrirlo.</p>

    <dl data-portal-detail-general></dl>

    <x-appointments.qr-block class="mt-4" title="Código QR de acceso" heading-id="portal-detail-qr-title" text="Muestra este código en la recepción el día de tu cita." />

    <section class="mt-4 hidden" aria-labelledby="portal-detail-recommendations-title" data-portal-detail-recommendations>
        <h3 id="portal-detail-recommendations-title" class="text-base font-bold text-ink">Recomendaciones</h3>
        <p class="mt-2 text-sm text-ink wrap-anywhere" data-portal-detail-recommendations-text></p>
    </section>

    <section class="mt-4 hidden" aria-labelledby="portal-detail-prescriptions-title" data-portal-detail-prescriptions>
        <h3 id="portal-detail-prescriptions-title" class="text-base font-bold text-ink">Recetas</h3>
        <div class="mt-2 space-y-3" data-portal-detail-prescriptions-list></div>
        <p class="mt-2 hidden rounded-box border border-danger bg-danger-soft px-4 py-3 text-sm font-semibold text-danger" data-portal-detail-prescriptions-error>No pudimos cargar las recetas. Cierra el detalle y vuelve a abrirlo.</p>
    </section>

    <x-slot:footer>
        <x-ui.button data-dialog-close>Cerrar</x-ui.button>
    </x-slot:footer>
</x-ui.dialog>
