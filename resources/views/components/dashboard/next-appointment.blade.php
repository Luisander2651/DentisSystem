{{--
    Siguiente cita del inicio (spec 020, design.md → Componentes): tarjeta `surface` con una
    franja de 6 px en `primary` a la izquierda, la hora en 40 px / 700 con su insignia, el paciente
    en 20 px / 700, el tratamiento y el doctor en `muted`, las acciones del rol y "Después: …".
    El servidor pinta la estructura; dashboard.js llena los `data-next-*` con textContent.
--}}
@props([
    'canComplete' => false,
])

<div {{ $attributes->merge(['class' => 'rounded-card border border-line border-l-[6px] border-l-primary bg-surface p-5 shadow-sm']) }} data-next-card>
    <div class="flex flex-wrap items-center gap-3">
        <span class="text-[40px] font-bold leading-none tabular-nums text-ink" data-next-time></span>
        <span data-next-status></span>
    </div>
    <p class="mt-3 text-xl font-bold text-ink" data-next-patient></p>
    <p class="mt-1 text-sm text-muted" data-next-detail></p>
    <div class="mt-4 flex flex-wrap gap-3">
        @if ($canComplete)
            <x-ui.button data-dashboard-next-complete>Completar</x-ui.button>
        @endif
        <x-ui.button data-dashboard-next-detail>Ver detalle</x-ui.button>
    </div>
    <p class="mt-4 text-sm text-muted" data-next-after hidden></p>
</div>
