<?php

declare(strict_types=1);

use App\Modules\Reports\Domain\Exceptions\ReportMonthException;
use App\Modules\Reports\Domain\Repositories\MonthlyReportRepositoryInterface;
use App\Modules\Reports\Domain\ValueObjects\AppointmentCounts;
use App\Modules\Reports\Domain\ValueObjects\ReportMonth;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Tests\Modules\Reports\Integration\ReportsIntegrationTestCase;

uses(ReportsIntegrationTestCase::class);

/*
 * Spec 019, CA11 and TM6: a month that is not one of the range gets the same message in Spanish
 * on both endpoints, with no file and no internal detail.
 */

const REPORT_MONTH_MESSAGE = 'Elige un mes entre enero de 2020 y el mes en curso.';

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-19 10:00:00', 'America/Mexico_City'));
    $this->actingAsAdmin();
});

dataset('invalid report months', [
    'month 13' => '2026-13',
    'month without leading zero' => '2026-7',
    'a word' => 'julio',
    'before January 2020' => '2019-12',
    'the next month' => '2026-11',
    'empty' => '',
    'an injection' => "2026-07' OR 1=1",
]);

it('refuses an invalid month in the report', function (string $month) {
    $this->getJson('/api/v1/reports/monthly?month='.rawurlencode($month))
        ->assertUnprocessable()
        ->assertExactJson(['message' => REPORT_MONTH_MESSAGE, 'errors' => ['month' => [REPORT_MONTH_MESSAGE]]]);
})->with('invalid report months');

it('refuses an invalid month in the download, without a file', function (string $month) {
    $response = $this->getJson('/api/v1/reports/monthly/excel?month='.rawurlencode($month));

    $response->assertUnprocessable()
        ->assertExactJson(['message' => REPORT_MONTH_MESSAGE, 'errors' => ['month' => [REPORT_MONTH_MESSAGE]]]);
    expect($response->headers->get('Content-Disposition'))->toBeNull();
})->with('invalid report months');

it('refuses a request without the month', function (string $url) {
    $this->getJson($url)
        ->assertUnprocessable()
        ->assertJsonPath('errors.month', [REPORT_MONTH_MESSAGE]);
})->with(['/api/v1/reports/monthly', '/api/v1/reports/monthly/excel']);

it('answers the same refusal when the month stops being valid after the request was checked', function (string $url) {
    $this->app->bind(MonthlyReportRepositoryInterface::class, fn () => new class implements MonthlyReportRepositoryInterface
    {
        public function appointmentCounts(ReportMonth $month, CarbonInterface $today): AppointmentCounts
        {
            throw ReportMonthException::outOfRange();
        }

        public function newPatients(ReportMonth $month): int
        {
            throw ReportMonthException::outOfRange();
        }

        public function activePatients(): int
        {
            throw ReportMonthException::outOfRange();
        }

        public function treatmentRanking(ReportMonth $month): array
        {
            throw ReportMonthException::outOfRange();
        }

        public function staffLoad(ReportMonth $month): array
        {
            throw ReportMonthException::outOfRange();
        }

        public function demandByWeekdayAndHour(ReportMonth $month): array
        {
            throw ReportMonthException::outOfRange();
        }
    });

    $this->getJson($url)
        ->assertUnprocessable()
        ->assertExactJson(['message' => REPORT_MONTH_MESSAGE, 'errors' => ['month' => [REPORT_MONTH_MESSAGE]]]);
})->with(['/api/v1/reports/monthly?month=2026-10', '/api/v1/reports/monthly/excel?month=2026-10']);
