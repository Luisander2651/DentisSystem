<section data-content-panel="certificaciones" class="content-panel hidden space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <x-ui.h1 as="h2" class="text-xl! text-slate-900">Certificaciones</x-ui.h1>
            <p class="mt-2 text-sm text-slate-500">Administra los reconocimientos, sellos y certificaciones institucionales.</p>
        </div>

        <button type="button" data-certifications-create-open class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#E91E63] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#d61b5b]">
            + Agregar certificacion
        </button>
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Total</p>
            <p class="mt-2 text-xl font-semibold text-slate-900" data-certifications-count>0</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Visibles</p>
            <p class="mt-2 text-xl font-semibold text-[#B5114A]" data-certifications-visible-count>0</p>
        </div>
        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-red-400">Ocultos</p>
            <p class="mt-2 text-xl font-semibold text-red-600" data-certifications-hidden-count>0</p>
        </div>
    </div>

    <article class="rounded-3xl border border-[#F5C2D6] bg-[#FFF7FA] p-5">
        <p class="text-sm font-semibold text-[#B5114A]">Gestion de certificaciones</p>
        <p class="mt-2 text-sm leading-6 text-slate-600">Puedes subir nuevos sellos, editar la informacion de los existentes o eliminarlos.</p>
    </article>

    <div class="hidden rounded-3xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" data-certifications-error></div>
    <div class="hidden rounded-3xl border border-slate-200 bg-white p-5 text-sm text-slate-600 shadow-sm" data-certifications-loading>
        Cargando certificaciones...
    </div>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" data-certifications-list></div>
    <div class="hidden rounded-3xl border border-slate-200 bg-white p-5 text-sm text-slate-500 shadow-sm" data-certifications-empty>
        No hay certificaciones registradas para mostrar.
    </div>

    @include('pages.contenido.certificaciones.edit-modal')
    @include('pages.contenido.certificaciones.delete-modal')
    @include('pages.contenido.certificaciones.create-modal')
</section>