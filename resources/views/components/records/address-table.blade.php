<section class="w-full overflow-x-auto rounded-lg border border-slate-200 bg-white">
    <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
        <h3 class="text-sm font-semibold text-slate-800">Direccion</h3>
        <button
            type="button"
            data-record-open-address-form
            class="rounded-md bg-[#E91E63] px-3 py-2 text-xs font-semibold text-white transition hover:bg-[#d81b60] disabled:cursor-not-allowed disabled:opacity-60"
        >
            Agregar direccion
        </button>
    </div>

    <form id="record-address-form" class="hidden space-y-3 border-b border-slate-200 bg-slate-50 p-4">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Calle</label>
                <input type="text" name="street" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700" placeholder="Av. Juarez 123" />
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Ciudad</label>
                <input type="text" name="city" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700" placeholder="Monterrey" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Estado</label>
                <input type="text" name="state" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700" placeholder="Nuevo Leon" />
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Codigo postal</label>
                <input type="text" name="postal_code" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700" placeholder="64000" />
            </div>
        </div>

        <p id="record-address-form-error" class="hidden rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></p>

        <div class="flex items-center justify-end gap-2">
            <button type="button" data-record-cancel-address-form class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100">Cancelar</button>
            <button type="submit" class="rounded-md bg-[#E91E63] px-3 py-2 text-xs font-semibold text-white transition hover:bg-[#d81b60]">Guardar direccion</button>
        </div>
    </form>

    <table class="min-w-full border-collapse text-left text-sm text-slate-700">
        <thead class="bg-slate-100">
            <tr>
                <th class="whitespace-nowrap px-4 py-3 font-semibold">Calle</th>
                <th class="whitespace-nowrap px-4 py-3 font-semibold">Ciudad</th>
                <th class="whitespace-nowrap px-4 py-3 font-semibold">Estado</th>
                <th class="whitespace-nowrap px-4 py-3 font-semibold">Codigo postal</th>
                <th class="whitespace-nowrap px-4 py-3 font-semibold">Acciones</th>
            </tr>
        </thead>
        <tbody id="record-address-body" class="bg-white">
            <tr class="border-t border-slate-200">
                <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">Sin informacion de direccion.</td>
            </tr>
        </tbody>
    </table>
</section>
