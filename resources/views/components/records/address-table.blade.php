@props(['canEdit' => false])

<section class="w-full overflow-hidden rounded-card border border-line bg-surface shadow-sm">
    <x-ui.section-title title="Dirección" class="border-b border-line px-4 py-3">
        @if ($canEdit)
            <x-slot:action>
                <x-ui.button variant="primary" data-record-open-address-form>Agregar dirección</x-ui.button>
            </x-slot:action>
        @endif
    </x-ui.section-title>

    <form id="record-address-form" class="hidden space-y-4 border-b border-line bg-canvas p-4">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-ui.input variant="string" id="record-address-street" name="street" label="Calle" placeholder="Av. Juárez 123" autocomplete="off" />
            <x-ui.input variant="string" id="record-address-city" name="city" label="Ciudad" placeholder="Monterrey" autocomplete="off" />
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-ui.input variant="string" id="record-address-state" name="state" label="Estado" placeholder="Nuevo León" autocomplete="off" />
            <x-ui.input variant="string" id="record-address-postal-code" name="postal_code" label="Código postal" placeholder="64000" autocomplete="off" />
        </div>

        <p id="record-address-form-error" role="alert" class="hidden rounded-box border border-danger bg-danger-soft px-3 py-2 text-sm font-semibold text-danger"></p>

        <div class="flex items-center justify-end gap-2">
            <x-ui.button data-record-cancel-address-form>Cancelar</x-ui.button>
            <x-ui.button variant="primary" type="submit">Guardar dirección</x-ui.button>
        </div>
    </form>

    {{-- Por debajo de md: pares de etiqueta y valor (spec 016, CA27). --}}
    <div class="hidden px-4 pb-4 md:hidden" data-record-when="address">
        <dl>
            <x-ui.record label="Calle"><span data-record-field="address.street">-</span></x-ui.record>
            <x-ui.record label="Ciudad"><span data-record-field="address.city">-</span></x-ui.record>
            <x-ui.record label="Estado"><span data-record-field="address.state">-</span></x-ui.record>
            <x-ui.record label="Código postal"><span data-record-field="address.postal_code">-</span></x-ui.record>
        </dl>
        @if ($canEdit)
            <x-ui.button data-record-delete-address class="mt-3">Eliminar dirección</x-ui.button>
        @endif
    </div>
    <p class="px-4 py-6 text-center text-sm text-muted md:hidden" data-record-when-empty="address">Este paciente aún no tiene una dirección registrada.</p>

    <div class="hidden overflow-x-auto md:block">
        <table class="min-w-full border-collapse text-left text-sm text-ink">
            <thead class="bg-canvas">
                <tr>
                    <th class="whitespace-nowrap px-4 py-3 font-semibold">Calle</th>
                    <th class="whitespace-nowrap px-4 py-3 font-semibold">Ciudad</th>
                    <th class="whitespace-nowrap px-4 py-3 font-semibold">Estado</th>
                    <th class="whitespace-nowrap px-4 py-3 font-semibold">Código postal</th>
                    @if ($canEdit)
                        <th class="whitespace-nowrap px-4 py-3 font-semibold">Acciones</th>
                    @endif
                </tr>
            </thead>
            <tbody id="record-address-body" class="bg-surface">
                <tr class="border-t border-line">
                    <td colspan="{{ $canEdit ? 5 : 4 }}" class="px-4 py-6 text-center text-sm text-muted">Este paciente aún no tiene una dirección registrada.</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>
