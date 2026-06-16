<section data-content-panel="galeria" class="content-panel space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <x-ui.h1 as="h2" class="text-xl! text-slate-900">Galeria</x-ui.h1>
            <p class="mt-2 text-sm text-slate-500">Administra las piezas visuales que alimentan la presencia digital de Dentissa.</p>
        </div>

        <button type="button" data-gallery-create-open class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#E91E63] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#d61b5b]">
            + Agregar imagen
        </button>
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Registros</p>
            <p class="mt-2 text-xl font-semibold text-slate-900" data-gallery-count>0</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Visibles</p>
            <p class="mt-2 text-xl font-semibold text-[#B5114A]" data-gallery-visible-count>0</p>
        </div>
        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-red-400">Ocultos</p>
            <p class="mt-2 text-xl font-semibold text-red-600" data-gallery-hidden-count>0</p>
        </div>
    </div>

    <article class="rounded-3xl border border-[#F5C2D6] bg-[#FFF7FA] p-5">
        <p class="text-sm font-semibold text-[#B5114A]">Acciones disponibles</p>
        <p class="mt-2 text-sm leading-6 text-slate-600">Las tarjetas incluyen botones para editar, eliminar y agregar nuevas imágenes</p>
    </article>

    <div class="hidden rounded-3xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" data-gallery-error></div>
    <div class="hidden rounded-3xl border border-slate-200 bg-white p-5 text-sm text-slate-600 shadow-sm" data-gallery-loading>
        Cargando registros de galeria...
    </div>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" data-gallery-list></div>
    <div class="hidden rounded-3xl border border-slate-200 bg-white p-5 text-sm text-slate-500 shadow-sm" data-gallery-empty>
        No hay registros de galeria para mostrar.
    </div>

    @include('pages.contenido.galeria.edit-modal')
    @include('pages.contenido.galeria.delete-modal')
    @include('pages.contenido.galeria.create-modal')
</section>