<x-ui.dialog id="edit-appointment-modal" title="Reprogramar cita" data-edit-appointment-modal>
    <form id="edit-appointment-form" data-edit-appointment-form class="flex flex-col gap-4" novalidate>
        <p class="text-sm text-muted" data-edit-appointment-summary>Elige la nueva fecha y hora de la cita.</p>

        <div>
            <label for="edit-appointment-date" class="mb-1.5 block text-sm font-semibold text-ink">
                Fecha
                <span class="text-danger" title="Obligatorio">*</span>
            </label>
            <input type="date" id="edit-appointment-date" name="date" data-edit-appointment-date class="block h-control w-full rounded-control border border-field bg-surface px-3 text-control text-ink" required>
        </div>

        <div>
            <label for="edit-appointment-time" class="mb-1.5 block text-sm font-semibold text-ink">
                Hora
                <span class="text-danger" title="Obligatorio">*</span>
            </label>
            <input type="time" id="edit-appointment-time" name="time" step="900" data-edit-appointment-time class="block h-control w-full rounded-control border border-field bg-surface px-3 text-control text-ink" required>
        </div>

        <label for="edit-appointment-whatsapp" class="flex min-h-control items-center gap-3 text-sm text-ink">
            <input type="checkbox" id="edit-appointment-whatsapp" value="1" data-edit-appointment-whatsapp-reminder class="size-5 shrink-0 accent-ink">
            Enviar recordatorio por WhatsApp
        </label>
    </form>

    <x-slot:footer>
        <x-ui.button data-edit-appointment-cancel>Cerrar</x-ui.button>
        <x-ui.button variant="primary" type="submit" form="edit-appointment-form" data-edit-appointment-submit>Reprogramar</x-ui.button>
    </x-slot:footer>
</x-ui.dialog>
