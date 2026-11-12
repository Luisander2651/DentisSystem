{{--
    Bloque del QR de una cita (spec 024, extraído en la spec 025; design.md → Componentes): marco
    `rounded-box border-field` con la imagen de 240 × 240 px, el aviso de error y "Descargar QR".
    Lo usan el detalle de la cita del personal (con título) y el portal del paciente (`wide`: el
    texto y los botones junto a la imagen desde 768 px, con acciones propias en el slot `actions`).
    El JS lo encuentra por sus atributos `data-view-appointment-qr*` (resources/js/ui/appointment-qr.js).
--}}
@props([
    'title' => null,
    'text' => '',
    'headingId' => 'view-appointment-qr-title',
    'wide' => false,
])

<section
    {{ $attributes->class(['hidden']) }}
    @if ($title) aria-labelledby="{{ $headingId }}" @endif
    data-view-appointment-qr
>
    @if ($wide)
        <div class="flex flex-col items-start gap-3 md:flex-row md:items-center">
            <div class="shrink-0 rounded-box border border-field bg-surface p-2">
                <img class="block size-60" alt="" data-view-appointment-qr-image>
            </div>
            <div class="flex flex-col items-start gap-3">
                <p class="text-sm text-muted">{{ $text }}</p>
                <p class="hidden rounded-box border border-danger bg-danger-soft px-4 py-3 text-sm font-semibold text-danger" data-view-appointment-qr-error>No pudimos cargar el código QR. Vuelve a cargar la página.</p>
                <div class="flex flex-wrap gap-3">
                    <x-ui.button disabled data-view-appointment-qr-download>Descargar QR</x-ui.button>
                    {{ $actions ?? '' }}
                </div>
            </div>
        </div>
    @else
        @if ($title)
            <h3 id="{{ $headingId }}" class="text-base font-bold text-ink">{{ $title }}</h3>
        @endif
        <p class="mt-1 text-sm text-muted">{{ $text }}</p>
        <div class="mt-2 flex flex-col items-start gap-3">
            <div class="shrink-0 rounded-box border border-field bg-surface p-2">
                <img class="block size-60" alt="" data-view-appointment-qr-image>
            </div>
            <p class="hidden rounded-box border border-danger bg-danger-soft px-4 py-3 text-sm font-semibold text-danger" data-view-appointment-qr-error>No pudimos cargar el código QR. Cierra el detalle y vuelve a abrirlo.</p>
            <x-ui.button disabled data-view-appointment-qr-download>Descargar QR</x-ui.button>
        </div>
    @endif
</section>
