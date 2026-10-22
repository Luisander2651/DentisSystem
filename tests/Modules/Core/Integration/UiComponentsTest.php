<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

uses(TestCase::class);

/*
 * Spec 019 (design.md → Componentes; A4, A11, A19): the select, the comparison stat and the change
 * indicator of the design system, as Blade renders them.
 */

function normalizedHtml(string $html): string
{
    return trim((string) preg_replace('/\s+/', ' ', $html));
}

it('renders the select with its label, its options and the chosen one', function () {
    $html = Blade::render('<x-ui.select name="mes" id="report-month" label="Mes" :options="$options" value="2026-07" data-reports-month />', [
        'options' => ['2026-10' => 'Octubre de 2026', '2026-07' => 'Julio de 2026'],
    ]);

    expect($html)->toContain('<label for="report-month"')
        ->toContain('>Mes</label>')
        ->toContain('id="report-month"')
        ->toContain('name="mes"')
        ->toContain('data-reports-month')
        ->toContain('h-control')->toContain('rounded-control')->toContain('border-field')->toContain('text-control')
        ->and(preg_match('/<option value="2026-07"[^>]*selected[^>]*>\s*Julio de 2026\s*<\/option>/', $html))->toBe(1)
        ->and(preg_match('/<option value="2026-10"(?![^>]*selected)[^>]*>\s*Octubre de 2026\s*<\/option>/', $html))->toBe(1);
});

it('links the help and the error of the select to it', function () {
    $html = Blade::render('<x-ui.select name="mes" id="report-month" label="Mes" :options="[\'2026-07\' => \'Julio de 2026\']" hint="Del mes en curso a enero de 2020." error-text="Elige un mes de la lista." />');

    expect($html)->toMatch('/<select[^>]*aria-describedby="report-month-hint report-month-error"/')
        ->toMatch('/<select[^>]*aria-invalid="true"/')
        ->toContain('id="report-month-hint"')
        ->toContain('Del mes en curso a enero de 2020.')
        ->toContain('id="report-month-error"')
        ->toContain('Elige un mes de la lista.')
        ->toContain('border-danger');
});

it('renders the comparison stat as a pair of term and description, wide when asked', function () {
    $wide = trim(Blade::render('<x-ui.stat variant="comparison" label="Citas del mes" wide>8<x-slot:trend><span data-trend>+8 vs junio</span></x-slot:trend></x-ui.stat>'));
    $narrow = trim(Blade::render('<x-ui.stat variant="comparison" label="Completadas">2</x-ui.stat>'));

    expect($wide)->toMatch('/^<div class="[^"]*\bcol-span-2\b[^"]*">\s*<dt class="[^"]*">Citas del mes<\/dt>\s*<dd class="[^"]*">\s*<span class="[^"]*">8<\/span>\s*<span data-trend>\+8 vs junio<\/span>\s*<\/dd>\s*<\/div>$/')
        ->and($narrow)->toMatch('/^<div class="[^"]*">\s*<dt class="[^"]*">Completadas<\/dt>\s*<dd class="[^"]*">\s*<span class="[^"]*">2<\/span>\s*<\/dd>\s*<\/div>$/')
        ->and($narrow)->not->toContain('col-span-2');
});

it('keeps the compact stat as it was', function () {
    expect(normalizedHtml(Blade::render('<x-ui.stat label="Citas del mes">8</x-ui.stat>')))
        ->toBe(normalizedHtml('<div class="rounded-box border border-line bg-surface px-3 py-2 shadow-sm md:p-4"> <p class="text-min font-bold uppercase tracking-wide text-muted">Citas del mes</p> <p class="text-xl font-bold text-ink md:mt-1">8</p> </div>'));
});

it('writes the change indicator as the design says, with the arrow hidden from screen readers', function (array $props, string $visible, string $spoken, array $classes, ?string $arrow) {
    $html = Blade::render('<x-ui.trend :trend="$trend" :difference="$difference" :unit="$unit" :current="$current" :previous="$previous" previous-short-label="junio" />', $props);
    $text = normalizedHtml(strip_tags((string) preg_replace('/<span[^>]*aria-hidden="true"[^>]*>.*?<\/span>/s', '', (string) preg_replace('/<span class="sr-only">.*?<\/span>/s', '', $html))));

    expect($text)->toBe($visible);

    if ($spoken === '') {
        expect($html)->not->toContain('sr-only');
    } else {
        expect($html)->toContain('<span class="sr-only">'.$spoken.'</span>');
    }

    foreach ($classes as $class) {
        expect($html)->toContain($class);
    }

    if ($arrow === null) {
        expect($html)->not->toContain('aria-hidden="true"');
    } else {
        expect($html)->toMatch('/<span aria-hidden="true">'.preg_quote($arrow, '/').'<\/span>/');
    }
})->with([
    'favorable, going up' => [['trend' => 'favorable', 'difference' => 8, 'unit' => 'count', 'current' => 8, 'previous' => 0], '+8 vs junio', ' (favorable)', ['rounded-full', 'text-success', 'bg-success-soft'], '▲'],
    'favorable, going down' => [['trend' => 'favorable', 'difference' => -2, 'unit' => 'count', 'current' => 0, 'previous' => 2], '−2 vs junio', ' (favorable)', ['text-success', 'bg-success-soft'], '▼'],
    'unfavorable' => [['trend' => 'unfavorable', 'difference' => 1, 'unit' => 'count', 'current' => 1, 'previous' => 0], '+1 vs junio', ' (desfavorable)', ['text-warning', 'bg-warning-soft'], '▲'],
    'neutral indicator that changed' => [['trend' => 'neutral', 'difference' => 1, 'unit' => 'count', 'current' => 3, 'previous' => 2], '+1 vs junio', '', ['text-muted', 'bg-surface', 'border-line'], '▲'],
    'no change' => [['trend' => 'neutral', 'difference' => 0, 'unit' => 'count', 'current' => 4, 'previous' => 4], 'sin cambio', ' (sin cambio)', ['text-muted', 'bg-surface', 'border-line'], '='],
    'rate in points' => [['trend' => 'unfavorable', 'difference' => 3.2, 'unit' => 'points', 'current' => 12.5, 'previous' => 9.3], '+3.2 pts vs junio', ' (desfavorable)', ['text-warning'], '▲'],
    'last month without data' => [['trend' => 'none', 'difference' => null, 'unit' => 'points', 'current' => 28.6, 'previous' => null], 'junio: Sin datos', ' (sin comparación)', ['rounded-full', 'text-muted', 'border-line'], null],
    'this month without data' => [['trend' => 'none', 'difference' => null, 'unit' => 'points', 'current' => null, 'previous' => 28.6], 'junio: 28.6 %', '', ['text-muted'], null],
]);

it('writes no pill when this month has no data and last month has', function () {
    $html = Blade::render('<x-ui.trend trend="none" :difference="null" unit="points" :current="null" :previous="28.6" previous-short-label="julio" />');

    expect($html)->not->toContain('rounded-full')->toContain('julio: 28.6 %');
});
