{{--
    Indicador de cambio (docs/design/system.md → Indicador de cambio · spec 019). El color dice si
    el cambio es bueno o malo para la clínica (`trend`: favorable, unfavorable, neutral, none), no si
    sube o baja; siempre con flecha y texto. resources/js/ui/trend.js pinta el mismo marcado.
    `unit`: count (citas) o points (puntos de un porcentaje). `current` y `previous` son la cifra del
    mes y la del mes anterior; `previousShortLabel`, el mes anterior sin el año ("junio").
--}}
@props([
    'trend' => 'none',
    'difference' => null,
    'unit' => 'count',
    'current' => null,
    'previous' => null,
    'previousShortLabel' => '',
])

@php
    $pill = 'inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-min font-bold';
    $colors = [
        'favorable' => 'border-transparent bg-success-soft text-success',
        'unfavorable' => 'border-transparent bg-warning-soft text-warning',
    ][$trend] ?? 'border-line bg-surface text-muted';
    $spoken = ['favorable' => ' (favorable)', 'unfavorable' => ' (desfavorable)'][$trend] ?? '';
    $number = fn (int|float $value): string => $unit === 'points' ? number_format(abs($value), 1, '.', '') : (string) abs($value);
@endphp

@if ($current === null && $previous !== null)
    <span class="text-min text-muted">{{ $previousShortLabel }}: {{ number_format($previous, 1, '.', '') }} %</span>
@elseif ($difference === null)
    <span class="{{ $pill }} border-line bg-surface text-muted">{{ $previousShortLabel }}: Sin datos<span class="sr-only"> (sin comparación)</span></span>
@elseif ($difference == 0)
    <span class="{{ $pill }} border-line bg-surface text-muted"><span aria-hidden="true">=</span> sin cambio<span class="sr-only"> (sin cambio)</span></span>
@else
    <span class="{{ $pill }} {{ $colors }}"><span aria-hidden="true">{{ $difference > 0 ? '▲' : '▼' }}</span> {{ $difference > 0 ? '+' : '−' }}{{ $number($difference) }}{{ $unit === 'points' ? ' pts' : '' }} vs {{ $previousShortLabel }}@if ($spoken !== '')<span class="sr-only">{{ $spoken }}</span>@endif</span>
@endif
