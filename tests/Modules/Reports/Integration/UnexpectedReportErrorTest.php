<?php

declare(strict_types=1);

use App\Modules\Reports\Application\Contracts\MonthlyReportExporterInterface;
use App\Modules\Reports\Domain\Repositories\MonthlyReportRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Tests\Modules\Reports\Integration\ReportsIntegrationTestCase;

uses(ReportsIntegrationTestCase::class);

/*
 * Spec 019, P7 and the requirement that errors reveal nothing internal: an unexpected failure of
 * the report or of its file answers the generic 500; a failure writing the file also leaves
 * report.export_failed with the month and the class of the error, never its message.
 */

const REPORT_ERROR_DETAIL = 'detalle interno con datos de Zacarías Quintanilla';

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-19 10:00:00', 'America/Mexico_City'));
    $this->actingAsAdmin();
    $this->logged = new Collection;
    Event::listen(MessageLogged::class, fn (MessageLogged $entry) => $this->logged->push($entry));
});

it('answers the generic 500 when reading the report fails unexpectedly', function (string $url) {
    $this->mock(MonthlyReportRepositoryInterface::class)
        ->shouldReceive('appointmentCounts')->andThrow(new RuntimeException(REPORT_ERROR_DETAIL));

    $response = $this->getJson($url);

    $response->assertStatus(500)->assertExactJson(['error' => 'Internal server error']);
    expect($response->getContent())->not->toContain('RuntimeException')->not->toContain('Zacarías');
})->with(['/api/v1/reports/monthly?month=2026-07', '/api/v1/reports/monthly/excel?month=2026-07'])->skip('019: pendiente de T038 y T041');

it('answers the generic 500 and logs report.export_failed without the message when the file cannot be written', function () {
    $this->mock(MonthlyReportExporterInterface::class)
        ->shouldReceive('export')->andThrow(new RuntimeException(REPORT_ERROR_DETAIL));

    $response = $this->getJson($this->monthlyReportExcelUrl('2026-07'));

    $response->assertStatus(500)->assertExactJson(['error' => 'Internal server error']);

    $failures = $this->logged->filter(fn (MessageLogged $entry): bool => $entry->message === 'report.export_failed');

    expect($failures)->toHaveCount(1)
        ->and($failures->first()->level)->toBe('error')
        ->and($failures->first()->context)->toMatchArray(['month' => '2026-07', 'exception' => RuntimeException::class])
        ->and($this->logged->map(fn (MessageLogged $entry): string => $entry->message.' '.json_encode($entry->context))->implode("\n"))
        ->not->toContain('Zacarías')->not->toContain('detalle interno');
})->skip('019: pendiente de T038 y T041');
