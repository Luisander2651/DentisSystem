<section data-content-panel="galeria" class="content-panel space-y-6">
    <x-ui.section-title title="Galería">
        <x-slot:action>
            <x-ui.button variant="primary" data-gallery-create-open>
                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-7-7v14"/></svg>
                Agregar imagen
            </x-ui.button>
        </x-slot:action>
    </x-ui.section-title>

    <div class="grid grid-cols-3 gap-2 md:gap-3">
        <x-ui.stat label="Imágenes"><span data-gallery-count>0</span></x-ui.stat>
        <x-ui.stat label="Visibles"><span data-gallery-visible-count>0</span></x-ui.stat>
        <x-ui.stat label="Ocultas"><span data-gallery-hidden-count>0</span></x-ui.stat>
    </div>

    <div role="alert" class="hidden rounded-box border border-danger bg-danger-soft px-4 py-3 text-sm font-semibold text-danger" data-gallery-error></div>
    <div class="hidden rounded-card border border-line bg-surface p-5 text-center text-sm text-muted shadow-sm" data-gallery-loading>
        Cargando imágenes...
    </div>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" data-gallery-list></div>
    <div class="hidden rounded-card border border-line bg-surface p-5 text-center text-sm text-muted shadow-sm" data-gallery-empty>
        Aún no hay imágenes en la galería.
    </div>

    @include('pages.contenido.galeria.edit-modal')
    @include('pages.contenido.galeria.delete-modal')
    @include('pages.contenido.galeria.create-modal')
</section>
