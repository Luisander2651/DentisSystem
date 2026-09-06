<section class="w-full overflow-x-auto rounded-lg border border-slate-200 bg-white">
    <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
        <h3 class="text-sm font-semibold text-slate-800">Historial de citas</h3>
    </div>

    <table class="min-w-full border-collapse text-left text-sm text-slate-700">
        <thead class="bg-slate-100">
            <tr>
                <th class="whitespace-nowrap px-4 py-3 font-semibold">Fecha</th>
                <th class="whitespace-nowrap px-4 py-3 font-semibold">Hora</th>
                <th class="whitespace-nowrap px-4 py-3 font-semibold">Tratamiento</th>
                <th class="whitespace-nowrap px-4 py-3 font-semibold">Doctor</th>
                <th class="whitespace-nowrap px-4 py-3 font-semibold">Estado</th>
                <th class="whitespace-nowrap px-4 py-3 font-semibold">Acciones</th>
            </tr>
        </thead>
        <tbody id="record-appointments-body" class="bg-white">
            <tr class="border-t border-slate-200">
                <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">Sin citas registradas.</td>
            </tr>
        </tbody>
    </table>
</section>
