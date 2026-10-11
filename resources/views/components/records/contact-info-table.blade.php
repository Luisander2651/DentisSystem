@props(['canEdit' => false])

<section class="w-full overflow-hidden rounded-card border border-line bg-surface shadow-sm">
    <x-ui.section-title title="Contacto" class="border-b border-line px-4 py-3">
        @if ($canEdit)
            <x-slot:action>
                <x-ui.button variant="primary" data-record-open-contact-form>Agregar contacto</x-ui.button>
            </x-slot:action>
        @endif
    </x-ui.section-title>

    <form id="record-contact-form" class="hidden space-y-4 border-b border-line bg-canvas p-4">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-ui.input variant="string" id="record-contact-phone" name="phone_number" label="Teléfono" placeholder="+52 81 1234 5678" autocomplete="off" />
            <x-ui.input variant="email" id="record-contact-email" name="contact_email" label="Correo de contacto" placeholder="contacto@correo.com" autocomplete="off" />
        </div>

        <x-ui.input variant="string" id="record-contact-emergency" name="emergency_contact" label="Contacto de emergencia" placeholder="Nombre de contacto" autocomplete="off" />

        <p id="record-contact-form-error" role="alert" class="hidden rounded-box border border-danger bg-danger-soft px-3 py-2 text-sm font-semibold text-danger"></p>

        <div class="flex items-center justify-end gap-2">
            <x-ui.button data-record-cancel-contact-form>Cancelar</x-ui.button>
            <x-ui.button variant="primary" type="submit">Guardar contacto</x-ui.button>
        </div>
    </form>

    {{-- Por debajo de md: pares de etiqueta y valor (spec 016, CA27). --}}
    <div class="hidden px-4 pb-4 md:hidden" data-record-when="contact">
        <dl>
            <x-ui.record label="Teléfono"><span data-record-field="contact.phone_number">-</span></x-ui.record>
            <x-ui.record label="Correo"><span data-record-field="contact.contact_email">-</span></x-ui.record>
            <x-ui.record label="Contacto de emergencia"><span data-record-field="contact.emergency_contact">-</span></x-ui.record>
        </dl>
        @if ($canEdit)
            <x-ui.button data-record-delete-contact class="mt-3">Eliminar contacto</x-ui.button>
        @endif
    </div>
    <p class="px-4 py-6 text-center text-sm text-muted md:hidden" data-record-when-empty="contact">Este paciente aún no tiene datos de contacto.</p>

    <div class="hidden overflow-x-auto md:block">
        <table class="min-w-full border-collapse text-left text-sm text-ink">
            <thead class="bg-canvas">
                <tr>
                    <th class="whitespace-nowrap px-4 py-3 font-semibold">Teléfono</th>
                    <th class="whitespace-nowrap px-4 py-3 font-semibold">Correo</th>
                    <th class="whitespace-nowrap px-4 py-3 font-semibold">Contacto de emergencia</th>
                    @if ($canEdit)
                        <th class="whitespace-nowrap px-4 py-3 font-semibold">Acciones</th>
                    @endif
                </tr>
            </thead>
            <tbody id="record-contact-info-body" class="bg-surface">
                <tr class="border-t border-line">
                    <td colspan="{{ $canEdit ? 4 : 3 }}" class="px-4 py-6 text-center text-sm text-muted">Este paciente aún no tiene datos de contacto.</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>
