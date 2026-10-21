<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Testing\TestResponse;
use Tests\Modules\Reports\Integration\ReportsIntegrationTestCase;

uses(ReportsIntegrationTestCase::class);

/*
 * Spec 019, CA1, CA9 and TM7: the Reportes screen of the administrator. The months to choose and
 * the one chosen come from the server (A10); a month that is not one of them is ignored and not
 * written back into the page.
 */

beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(CarbonImmutable::parse('2026-10-19 10:00:00', 'America/Mexico_City'));
    $this->actingAsAdmin();
});

/**
 * The options of the "Mes" select: value => [label, selected].
 *
 * @return array<string, array{0: string, 1: bool}>
 */
function reportMonthOptions(TestResponse $response): array
{
    preg_match('/<select[^>]*data-reports-month[^>]*>(.*?)<\/select>/s', (string) $response->getContent(), $select);
    preg_match_all('/<option\s+value="([^"]*)"([^>]*)>\s*([^<]*?)\s*<\/option>/s', $select[1] ?? '', $options, PREG_SET_ORDER);

    $months = [];

    foreach ($options as [, $value, $attributes, $label]) {
        $months[$value] = [$label, str_contains($attributes, 'selected')];
    }

    return $months;
}

function chosenReportMonth(TestResponse $response): ?string
{
    return array_key_first(array_filter(reportMonthOptions($response), fn (array $option): bool => $option[1]));
}

it('opens the screen with the months from the one in course back to January 2020, and none to come', function () {
    $response = $this->get($this->reportsScreenUrl());
    $months = reportMonthOptions($response->assertOk());

    expect(array_key_first($months))->toBe('2026-10')
        ->and($months['2026-10'][0])->toBe('Octubre de 2026')
        ->and(array_key_last($months))->toBe('2020-01')
        ->and($months)->toHaveCount(82)
        ->and($months)->not->toHaveKey('2026-11');
})->skip('019: pendiente de T044');

it('chooses the last complete month by default', function () {
    expect(chosenReportMonth($this->get($this->reportsScreenUrl())))->toBe('2026-09');
})->skip('019: pendiente de T044');

it('chooses the month of the address when it is one of the list', function () {
    expect(chosenReportMonth($this->get($this->reportsScreenUrl('2026-07'))))->toBe('2026-07');
})->skip('019: pendiente de T044');

it('ignores a month of the address that is not one of the list', function (string $month) {
    expect(chosenReportMonth($this->get($this->reportsScreenUrl($month))))->toBe('2026-09');
})->with(['month 13' => '2026-13', 'a month to come' => '2030-01', 'a word' => 'julio'])->skip('019: pendiente de T044');

it('does not write an injected month back into the page (abuse)', function () {
    $response = $this->get('/reportes?mes='.rawurlencode('<script>alert(1)</script>'));

    $response->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('alert(1)', false);
})->skip('019: pendiente de T044');

it('offers the download and has the status region of the frame', function () {
    $this->get($this->reportsScreenUrl())
        ->assertOk()
        ->assertSee('data-reports-download', false)
        ->assertSee('Descargar Excel')
        ->assertSee('data-ui-status-region', false);
})->skip('019: pendiente de T044');

it('loads its own script, registered in Vite', function () {
    $root = dirname(__DIR__, 4);

    expect(file_get_contents($root.'/resources/views/pages/reportes/index.blade.php'))->toContain("@vite('resources/js/pages/reportes/index.js')")
        ->and(file_get_contents($root.'/vite.config.js'))->toContain("'resources/js/pages/reportes/index.js'");
})->skip('019: pendiente de T045');
