<section data-content-panel="testimonios" class="content-panel hidden space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <x-ui.h1 as="h2" class="text-xl! text-slate-900">Testimonios</x-ui.h1>
            <p class="mt-2 text-sm text-slate-500">Administra las opiniones y experiencias compartidas por los pacientes.</p>
        </div>

        <div class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#B5114A]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M7 8h10" />
                <path d="M7 12h6" />
                <path d="M7 16h4" />
                <path d="M5 4h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H9l-4 3v-3H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z" />
            </svg>
            Gestion exclusiva de visibilidad
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Total</p>
            <p class="mt-2 text-xl font-semibold text-slate-900" data-testimonials-count>0</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Visibles</p>
            <p class="mt-2 text-xl font-semibold text-[#B5114A]" data-testimonials-visible-count>0</p>
        </div>
        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-red-400">Ocultos</p>
            <p class="mt-2 text-xl font-semibold text-red-600" data-testimonials-hidden-count>0</p>
        </div>
    </div>

    <article class="rounded-3xl border border-[#F5C2D6] bg-[#FFF7FA] p-5">
        <p class="text-sm font-semibold text-[#B5114A]">Gestion de testimonios</p>
        <p class="mt-2 text-sm leading-6 text-slate-600">Puedes moderar los testimonios de los pacientes o eliminarlos según sea necesario.</p>
    </article>

    <div class="hidden rounded-3xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" data-testimonials-error></div>
    <div class="hidden rounded-3xl border border-slate-200 bg-white p-5 text-sm text-slate-600 shadow-sm" data-testimonials-loading>
        Cargando testimonios...
    </div>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" data-testimonials-list></div>
    <div class="hidden rounded-3xl border border-slate-200 bg-white p-5 text-sm text-slate-500 shadow-sm" data-testimonials-empty>
        No hay testimonios registrados para mostrar.
    </div>

    @include('pages.contenido.testimonios.edit-modal')
    @include('pages.contenido.testimonios.delete-modal')
</section>