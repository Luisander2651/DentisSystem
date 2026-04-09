<section class="w-full overflow-x-auto rounded-lg border border-slate-200 bg-white">
    <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
        <h3 class="text-sm font-semibold text-slate-800">Contacto</h3>
        <button
            type="button"
            data-record-open-contact-form
            class="rounded-md bg-[#E91E63] px-3 py-2 text-xs font-semibold text-white transition hover:bg-[#d81b60] disabled:cursor-not-allowed disabled:opacity-60"
        >
            Agregar contacto
        </button>
    </div>

    <form id="record-contact-form" class="hidden space-y-3 border-b border-slate-200 bg-slate-50 p-4">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Telefono</label>
                <input type="text" name="phone_number" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700" placeholder="+52 81 1234 5678" />
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Correo de contacto</label>
                <input type="email" name="contact_email" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700" placeholder="contacto@correo.com" />
            </div>
        </div>

        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Contacto de emergencia</label>
            <input type="text" name="emergency_contact" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700" placeholder="Nombre de contacto" />
        </div>

        <p id="record-contact-form-error" class="hidden rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></p>

        <div class="flex items-center justify-end gap-2">
            <button type="button" data-record-cancel-contact-form class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100">Cancelar</button>
            <button type="submit" class="rounded-md bg-[#E91E63] px-3 py-2 text-xs font-semibold text-white transition hover:bg-[#d81b60]">Guardar contacto</button>
        </div>
    </form>

    <table class="min-w-full border-collapse text-left text-sm text-slate-700">
        <thead class="bg-slate-100">
            <tr>
                <th class="whitespace-nowrap px-4 py-3 font-semibold">Telefono</th>
                <th class="whitespace-nowrap px-4 py-3 font-semibold">Correo</th>
                <th class="whitespace-nowrap px-4 py-3 font-semibold">Contacto de emergencia</th>
                <th class="whitespace-nowrap px-4 py-3 font-semibold">Acciones</th>
            </tr>
        </thead>
        <tbody id="record-contact-info-body" class="bg-white">
            <tr class="border-t border-slate-200">
                <td colspan="4" class="px-4 py-6 text-center text-sm text-slate-500">Sin informacion de contacto.</td>
            </tr>
        </tbody>
    </table>
</section>
