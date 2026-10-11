<section data-content-panel="promociones" class="content-panel hidden space-y-6">
    <x-ui.section-title title="Promociones">
        <x-slot:action>
            <x-ui.button variant="primary" data-promotions-create-open>
                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-7-7v14"/></svg>
                Nueva promoción
            </x-ui.button>
        </x-slot:action>
    </x-ui.section-title>

    <div class="grid grid-cols-3 gap-2 md:gap-3">
        <x-ui.stat label="Total"><span data-promotions-count>0</span></x-ui.stat>
        <x-ui.stat label="Visibles"><span data-promotions-visible-count>0</span></x-ui.stat>
        <x-ui.stat label="Ocultas"><span data-promotions-hidden-count>0</span></x-ui.stat>
    </div>

    <div role="alert" class="hidden rounded-box border border-danger bg-danger-soft px-4 py-3 text-sm font-semibold text-danger" data-promotions-error></div>
    <div class="hidden rounded-card border border-line bg-surface p-5 text-center text-sm text-muted shadow-sm" data-promotions-loading>
        Cargando promociones...
    </div>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" data-promotions-list></div>
    <div class="hidden rounded-card border border-line bg-surface p-5 text-center text-sm text-muted shadow-sm" data-promotions-empty>
        Aún no hay promociones registradas.
    </div>

    @include('pages.contenido.promociones.edit-modal')
    @include('pages.contenido.promociones.delete-modal')
    @include('pages.contenido.promociones.create-modal')
</section>
