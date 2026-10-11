<section data-content-panel="certificaciones" class="content-panel hidden space-y-6">
    <x-ui.section-title title="Certificaciones">
        <x-slot:action>
            <x-ui.button variant="primary" data-certifications-create-open>
                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-7-7v14"/></svg>
                Nueva certificación
            </x-ui.button>
        </x-slot:action>
    </x-ui.section-title>

    <div class="grid grid-cols-3 gap-2 md:gap-3">
        <x-ui.stat label="Total"><span data-certifications-count>0</span></x-ui.stat>
        <x-ui.stat label="Visibles"><span data-certifications-visible-count>0</span></x-ui.stat>
        <x-ui.stat label="Ocultas"><span data-certifications-hidden-count>0</span></x-ui.stat>
    </div>

    <div role="alert" class="hidden rounded-box border border-danger bg-danger-soft px-4 py-3 text-sm font-semibold text-danger" data-certifications-error></div>
    <div class="hidden rounded-card border border-line bg-surface p-5 text-center text-sm text-muted shadow-sm" data-certifications-loading>
        Cargando certificaciones...
    </div>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" data-certifications-list></div>
    <div class="hidden rounded-card border border-line bg-surface p-5 text-center text-sm text-muted shadow-sm" data-certifications-empty>
        Aún no hay certificaciones registradas.
    </div>

    @include('pages.contenido.certificaciones.edit-modal')
    @include('pages.contenido.certificaciones.delete-modal')
    @include('pages.contenido.certificaciones.create-modal')
</section>
