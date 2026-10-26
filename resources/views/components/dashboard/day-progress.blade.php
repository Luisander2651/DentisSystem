{{--
    Cómo va el día (spec 020, design.md → Componentes): barra apilada de 12 px, decorativa, con
    completadas en `success`, por atender en `warning` y canceladas en `danger`, y debajo una `dl`
    de cuatro columnas con la cifra (24 px / 700), su muestra de color y la etiqueta en `muted`.
    Los colores de estado son relleno no textual: la cifra y la etiqueta van siempre al lado.
    dashboard.js pone las cifras y el ancho de cada tramo.
--}}
@php
    $counters = [
        'total' => ['Citas', null],
        'completed' => ['Completadas', 'bg-success'],
        'pending' => ['Por atender', 'bg-warning'],
        'cancelled' => ['Canceladas', 'bg-danger'],
    ];
@endphp

<div {{ $attributes->merge(['class' => 'space-y-4']) }}>
    <div class="flex h-3 overflow-hidden rounded-full bg-canvas" aria-hidden="true" data-day-bar>
        <span class="h-full bg-success" style="width: 0%" data-day-segment="completed"></span>
        <span class="h-full bg-warning" style="width: 0%" data-day-segment="pending"></span>
        <span class="h-full bg-danger" style="width: 0%" data-day-segment="cancelled"></span>
    </div>
    <dl class="grid grid-cols-4 divide-x divide-line">
        @foreach ($counters as $key => [$label, $swatch])
            <div class="flex flex-col gap-1 px-3 first:pl-0">
                <dt class="text-min text-muted">{{ $label }}</dt>
                <dd class="flex items-center gap-2 text-2xl font-bold tabular-nums text-ink">
                    @if ($swatch)
                        <span class="size-2.5 shrink-0 rounded-full {{ $swatch }}" aria-hidden="true"></span>
                    @endif
                    <span data-day-count="{{ $key }}">0</span>
                </dd>
            </div>
        @endforeach
    </dl>
</div>
