<section data-content-panel="testimonios" class="content-panel hidden space-y-6">
    <div>
        <x-ui.section-title title="Testimonios" />
        <p class="mt-1 text-sm text-muted">Los escriben los pacientes; aquí decides cuáles se muestran en el sitio.</p>
    </div>

    <div class="grid grid-cols-3 gap-2 md:gap-3">
        <x-ui.stat label="Total"><span data-testimonials-count>0</span></x-ui.stat>
        <x-ui.stat label="Visibles"><span data-testimonials-visible-count>0</span></x-ui.stat>
        <x-ui.stat label="Ocultos"><span data-testimonials-hidden-count>0</span></x-ui.stat>
    </div>

    <div role="alert" class="hidden rounded-box border border-danger bg-danger-soft px-4 py-3 text-sm font-semibold text-danger" data-testimonials-error></div>
    <div class="hidden rounded-card border border-line bg-surface p-5 text-center text-sm text-muted shadow-sm" data-testimonials-loading>
        Cargando testimonios...
    </div>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" data-testimonials-list></div>
    <div class="hidden rounded-card border border-line bg-surface p-5 text-center text-sm text-muted shadow-sm" data-testimonials-empty>
        Aún no hay testimonios.
    </div>

    @include('pages.contenido.testimonios.edit-modal')
    @include('pages.contenido.testimonios.delete-modal')
</section>
