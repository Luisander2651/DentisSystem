@props(['canEdit' => false])

<section class="w-full overflow-hidden rounded-card border border-line bg-surface shadow-sm">
    <x-ui.section-title title="Datos médicos" class="border-b border-line px-4 py-3">
        @if ($canEdit)
            <x-slot:action>
                <x-ui.button variant="primary" data-record-open-medical-form>Agregar datos médicos</x-ui.button>
            </x-slot:action>
        @endif
    </x-ui.section-title>

    <form id="record-medical-form" class="hidden space-y-4 border-b border-line bg-canvas p-4">
        <x-ui.input variant="text" id="record-medical-blood-type" name="blood_type" label="Tipo de sangre" placeholder="O+" autocomplete="off" />

        <div>
            <label for="record-medical-allergies" class="mb-1.5 block text-sm font-semibold text-ink">Alergias</label>
            <textarea id="record-medical-allergies" name="allergies" rows="2" aria-describedby="record-medical-allergies-hint" class="block w-full rounded-control border border-field bg-surface px-3 py-2.5 text-control text-ink" placeholder="Penicilina, Lactosa"></textarea>
            <p id="record-medical-allergies-hint" class="mt-1 text-min text-muted">Sepáralas con comas o en líneas distintas.</p>
        </div>

        <div>
            <label for="record-medical-medications" class="mb-1.5 block text-sm font-semibold text-ink">Medicamentos</label>
            <textarea id="record-medical-medications" name="medications" rows="2" aria-describedby="record-medical-medications-hint" class="block w-full rounded-control border border-field bg-surface px-3 py-2.5 text-control text-ink" placeholder="Ibuprofeno, Paracetamol"></textarea>
            <p id="record-medical-medications-hint" class="mt-1 text-min text-muted">Sepáralos con comas o en líneas distintas.</p>
        </div>

        <div>
            <label for="record-medical-last-visit" class="mb-1.5 block text-sm font-semibold text-ink">Última visita al dentista</label>
            <textarea id="record-medical-last-visit" name="last_dentist_visit" rows="2" aria-describedby="record-medical-last-visit-hint" class="block w-full rounded-control border border-field bg-surface px-3 py-2.5 text-control text-ink" placeholder="2026-04-01, Limpieza"></textarea>
            <p id="record-medical-last-visit-hint" class="mt-1 text-min text-muted">Fecha y motivo, separados por una coma.</p>
        </div>

        <p id="record-medical-form-error" role="alert" class="hidden rounded-box border border-danger bg-danger-soft px-3 py-2 text-sm font-semibold text-danger"></p>

        <div class="flex items-center justify-end gap-2">
            <x-ui.button data-record-cancel-medical-form>Cancelar</x-ui.button>
            <x-ui.button variant="primary" type="submit">Guardar datos médicos</x-ui.button>
        </div>
    </form>

    {{-- Por debajo de md: pares de etiqueta y valor (spec 016, CA27). --}}
    <div class="hidden px-4 pb-4 md:hidden" data-record-when="medical">
        <dl>
            <x-ui.record label="Tipo de sangre"><span data-record-field="medical.blood_type">-</span></x-ui.record>
            <x-ui.record label="Alergias"><span data-record-field="medical.allergies">-</span></x-ui.record>
            <x-ui.record label="Medicamentos"><span data-record-field="medical.medications">-</span></x-ui.record>
            <x-ui.record label="Última visita al dentista"><span data-record-field="medical.last_dentist_visit">-</span></x-ui.record>
        </dl>
        @if ($canEdit)
            <x-ui.button data-record-delete-medical class="mt-3">Eliminar datos médicos</x-ui.button>
        @endif
    </div>
    <p class="px-4 py-6 text-center text-sm text-muted md:hidden" data-record-when-empty="medical">Este paciente aún no tiene datos médicos registrados.</p>

    <div class="hidden overflow-x-auto md:block">
        <table class="min-w-full border-collapse text-left text-sm text-ink">
            <thead class="bg-canvas">
                <tr>
                    <th class="whitespace-nowrap px-4 py-3 font-semibold">Tipo de sangre</th>
                    <th class="whitespace-nowrap px-4 py-3 font-semibold">Alergias</th>
                    <th class="whitespace-nowrap px-4 py-3 font-semibold">Medicamentos</th>
                    <th class="whitespace-nowrap px-4 py-3 font-semibold">Última visita al dentista</th>
                    @if ($canEdit)
                        <th class="whitespace-nowrap px-4 py-3 font-semibold">Acciones</th>
                    @endif
                </tr>
            </thead>
            <tbody id="record-medical-data-body" class="bg-surface">
                <tr class="border-t border-line">
                    <td colspan="{{ $canEdit ? 5 : 4 }}" class="px-4 py-6 text-center text-sm text-muted">Este paciente aún no tiene datos médicos registrados.</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>
