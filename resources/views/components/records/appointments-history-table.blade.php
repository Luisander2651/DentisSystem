<section class="w-full overflow-hidden rounded-card border border-line bg-surface shadow-sm">
    <x-ui.section-title title="Historial de citas" class="border-b border-line px-4 py-3" />

    {{-- Por debajo de md: una tarjeta por cita (spec 016, CA27). --}}
    <ul id="record-appointments-list" class="hidden space-y-3 p-4 md:hidden" data-record-when="appointments"></ul>
    <p class="px-4 py-6 text-center text-sm text-muted md:hidden" data-record-when-empty="appointments">Este paciente aún no tiene citas registradas.</p>

    <div class="hidden overflow-x-auto md:block">
        <table class="min-w-full border-collapse text-left text-sm text-ink">
            <thead class="bg-canvas">
                <tr>
                    <th class="whitespace-nowrap px-4 py-3 font-semibold">Fecha</th>
                    <th class="whitespace-nowrap px-4 py-3 font-semibold">Hora</th>
                    <th class="whitespace-nowrap px-4 py-3 font-semibold">Tratamiento</th>
                    <th class="whitespace-nowrap px-4 py-3 font-semibold">Doctor</th>
                    <th class="whitespace-nowrap px-4 py-3 font-semibold">Estado</th>
                    <th class="whitespace-nowrap px-4 py-3 font-semibold">Acciones</th>
                </tr>
            </thead>
            <tbody id="record-appointments-body" class="bg-surface">
                <tr class="border-t border-line">
                    <td colspan="6" class="px-4 py-6 text-center text-sm text-muted">Este paciente aún no tiene citas registradas.</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>
