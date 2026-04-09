<section class="w-full overflow-x-auto rounded-lg border border-slate-200 bg-white">
    <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
        <h3 class="text-sm font-semibold text-slate-800">Datos medicos</h3>
        <button
            type="button"
            data-record-open-medical-form
            class="rounded-md bg-[#E91E63] px-3 py-2 text-xs font-semibold text-white transition hover:bg-[#d81b60] disabled:cursor-not-allowed disabled:opacity-60"
        >
            Agregar datos medicos
        </button>
    </div>

    <form id="record-medical-form" class="hidden space-y-3 border-b border-slate-200 bg-slate-50 p-4">
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Tipo de sangre</label>
            <input type="text" name="blood_type" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700" placeholder="O+" />
        </div>

        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Alergias (separadas por coma o salto de linea)</label>
            <textarea name="allergies" rows="2" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700" placeholder="Penicilina, Lactosa"></textarea>
        </div>

        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Medicamentos (separados por coma o salto de linea)</label>
            <textarea name="medications" rows="2" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700" placeholder="Ibuprofeno, Paracetamol"></textarea>
        </div>

        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Ultima visita al dentista (separada por coma o salto de linea)</label>
            <textarea name="last_dentist_visit" rows="2" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700" placeholder="2026-04-01, Limpieza"></textarea>
        </div>

        <p id="record-medical-form-error" class="hidden rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></p>

        <div class="flex items-center justify-end gap-2">
            <button type="button" data-record-cancel-medical-form class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100">Cancelar</button>
            <button type="submit" class="rounded-md bg-[#E91E63] px-3 py-2 text-xs font-semibold text-white transition hover:bg-[#d81b60]">Guardar datos medicos</button>
        </div>
    </form>

    <table class="min-w-full border-collapse text-left text-sm text-slate-700">
        <thead class="bg-slate-100">
            <tr>
                <th class="whitespace-nowrap px-4 py-3 font-semibold">Tipo de sangre</th>
                <th class="whitespace-nowrap px-4 py-3 font-semibold">Alergias</th>
                <th class="whitespace-nowrap px-4 py-3 font-semibold">Medicamentos</th>
                <th class="whitespace-nowrap px-4 py-3 font-semibold">Ultima visita al dentista</th>
                <th class="whitespace-nowrap px-4 py-3 font-semibold">Acciones</th>
            </tr>
        </thead>
        <tbody id="record-medical-data-body" class="bg-white">
            <tr class="border-t border-slate-200">
                <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">Sin informacion medica.</td>
            </tr>
        </tbody>
    </table>
</section>
