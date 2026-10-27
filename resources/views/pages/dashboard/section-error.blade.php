{{--
    Error de carga de una sección del inicio (spec 020, CA12, A22): el bloque de error de carga
    de docs/design/system.md, persistente hasta reintentar; solo esa sección vuelve a pedirse.
--}}
<div hidden data-dashboard-state="error" class="mt-3 flex flex-col items-start gap-3 rounded-box border border-danger bg-danger-soft px-4 py-3 text-sm text-ink">
    <strong class="font-bold text-danger">No se pudo cargar esta sección.</strong>
    <x-ui.button variant="secondary" data-dashboard-retry>Reintentar</x-ui.button>
</div>
