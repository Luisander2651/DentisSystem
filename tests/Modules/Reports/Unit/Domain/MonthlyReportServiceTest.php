<?php

declare(strict_types=1);

use App\Modules\Reports\Domain\Repositories\MonthlyReportRepositoryInterface;
use App\Modules\Reports\Domain\Service\MonthlyReportService;
use App\Modules\Reports\Domain\ValueObjects\AppointmentCounts;
use App\Modules\Reports\Domain\ValueObjects\RankedItem;
use App\Modules\Reports\Domain\ValueObjects\ReportMonth;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/*
 * Spec 019, CA2, CA3, CA4 and CA7: the report of a month, worked out from what the repository
 * counts for that month and the one before.
 */

function serviceToday(): CarbonImmutable
{
    return CarbonImmutable::parse('2026-10-19 10:00:00', 'America/Mexico_City');
}

/**
 * A repository that answers from arrays, by month (AAAA-MM), and remembers which months it was
 * asked for.
 *
 * @param  array<string, AppointmentCounts>  $counts
 * @param  array<string, int>  $newPatients
 * @param  array<string, array<int, array<int, int>>>  $demand
 */
function fakeReportRepository(array $counts, array $newPatients = [], array $demand = [], int $active = 2, array $treatments = [], array $staff = []): MonthlyReportRepositoryInterface
{
    return new class($counts, $newPatients, $demand, $active, $treatments, $staff) implements MonthlyReportRepositoryInterface
    {
        /** @var list<string> */
        public array $asked = [];

        public function __construct(
            private array $counts,
            private array $newPatientsByMonth,
            private array $demand,
            private int $active,
            private array $treatments,
            private array $staff,
        ) {}

        public function appointmentCounts(ReportMonth $month, CarbonInterface $today): AppointmentCounts
        {
            $this->asked[] = $month->value();

            return $this->counts[$month->value()] ?? AppointmentCounts::empty();
        }

        public function newPatients(ReportMonth $month): int
        {
            return $this->newPatientsByMonth[$month->value()] ?? 0;
        }

        public function activePatients(): int
        {
            return $this->active;
        }

        public function treatmentRanking(ReportMonth $month): array
        {
            return $this->treatments;
        }

        public function staffLoad(ReportMonth $month): array
        {
            return $this->staff;
        }

        public function demandByWeekdayAndHour(ReportMonth $month): array
        {
            return $this->demand[$month->value()] ?? [];
        }
    };
}

function julyCounts(): AppointmentCounts
{
    return AppointmentCounts::create(total: 8, completed: 2, cancelled: 1, rescheduledPending: 0, assignedPending: 0, unclosed: 5, notCancelledPast: 7);
}

/** July 2026 of design.md: Monday 2, Tuesday 1, Wednesday 4; 9 h 2, 10 h 1, 11 h 1, 12 h 2, 16 h 1. */
function julyDemand(): array
{
    return [1 => [9 => 1, 12 => 1], 2 => [10 => 1], 3 => [9 => 1, 11 => 1, 12 => 1, 16 => 1]];
}

it('gives the rates with one decimal and compares the counts with the month before', function () {
    $repository = fakeReportRepository(['2026-07' => julyCounts()], demand: ['2026-07' => julyDemand()]);
    $report = (new MonthlyReportService($repository))->build(ReportMonth::create('2026-07', serviceToday()), serviceToday());

    expect($report->rates['attendance']['rate']->value)->toBe(28.6)
        ->and($report->rates['attendance']['rate']->numerator)->toBe(2)
        ->and($report->rates['attendance']['rate']->denominator)->toBe(7)
        ->and($report->rates['cancellation']['rate']->value)->toBe(12.5)
        ->and($report->rates['attendance']['comparison']->trend)->toBe('none')
        ->and($report->appointments['total']->difference)->toBe(8)
        ->and($report->appointments['total']->trend)->toBe('favorable')
        ->and($report->appointments['completed']->trend)->toBe('favorable')
        ->and($report->appointments['cancelled']->trend)->toBe('unfavorable')
        ->and($report->appointments['unclosed']->trend)->toBe('unfavorable')
        ->and($report->appointments['rescheduled_pending']->trend)->toBe('neutral')
        ->and($report->appointments['assigned_pending']->trend)->toBe('neutral')
        ->and($repository->asked)->toBe(['2026-07', '2026-06']);
});

it('compares the rates in points when both months have them', function () {
    $june = AppointmentCounts::create(total: 4, completed: 1, cancelled: 0, rescheduledPending: 0, assignedPending: 0, unclosed: 3, notCancelledPast: 4);
    $report = (new MonthlyReportService(fakeReportRepository(['2026-07' => julyCounts(), '2026-06' => $june])))
        ->build(ReportMonth::create('2026-07', serviceToday()), serviceToday());

    expect($report->rates['attendance']['comparison']->previous)->toBe(25.0)
        ->and($report->rates['attendance']['comparison']->difference)->toBe(3.6)
        ->and($report->rates['attendance']['comparison']->trend)->toBe('favorable')
        ->and($report->rates['cancellation']['comparison']->difference)->toBe(12.5)
        ->and($report->rates['cancellation']['comparison']->trend)->toBe('unfavorable');
});

it('has no rates in a month without appointments', function () {
    $report = (new MonthlyReportService(fakeReportRepository([])))->build(ReportMonth::create('2026-08', serviceToday()), serviceToday());

    expect($report->rates['attendance']['rate']->value)->toBeNull()
        ->and($report->rates['cancellation']['rate']->value)->toBeNull()
        ->and($report->demand['hours'])->toBe([])
        ->and($report->demand['matrix'])->toBe([])
        ->and($report->demand['top_weekdays'])->toBe([])
        ->and($report->demand['top_hours'])->toBe([])
        ->and($report->demand['total'])->toBe(0);
});

it('lays out the week from the first to the last hour with appointments, with totals and the highest ones', function () {
    $report = (new MonthlyReportService(fakeReportRepository(['2026-07' => julyCounts()], demand: ['2026-07' => julyDemand()])))
        ->build(ReportMonth::create('2026-07', serviceToday()), serviceToday());

    expect($report->demand)->toBe([
        'hours' => [9, 10, 11, 12, 13, 14, 15, 16],
        'matrix' => [
            [1, 0, 1, 0, 0, 0, 0],
            [0, 1, 0, 0, 0, 0, 0],
            [0, 0, 1, 0, 0, 0, 0],
            [1, 0, 1, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0],
            [0, 0, 1, 0, 0, 0, 0],
        ],
        'by_weekday' => [2, 1, 4, 0, 0, 0, 0],
        'by_hour' => [2, 1, 1, 2, 0, 0, 0, 1],
        'top_weekdays' => [2],
        'top_hours' => [9, 12],
        'total' => 7,
    ]);
});

it('marks every weekday that ties for the highest', function () {
    $report = (new MonthlyReportService(fakeReportRepository([], demand: ['2026-07' => [1 => [10 => 2], 5 => [10 => 1, 11 => 1]]])))
        ->build(ReportMonth::create('2026-07', serviceToday()), serviceToday());

    expect($report->demand['top_weekdays'])->toBe([0, 4])
        ->and($report->demand['top_hours'])->toBe([10]);
});

it('compares the new patients with the month before and keeps the active ones as of today', function () {
    $report = (new MonthlyReportService(fakeReportRepository([], newPatients: ['2026-07' => 1, '2026-06' => 3], active: 12)))
        ->build(ReportMonth::create('2026-07', serviceToday()), serviceToday());

    expect($report->newPatients->current)->toBe(1)
        ->and($report->newPatients->previous)->toBe(3)
        ->and($report->newPatients->trend)->toBe('unfavorable')
        ->and($report->activePatients)->toBe(12)
        ->and($report->activeAsOf->toDateString())->toBe('2026-10-19');
});

it('passes the rankings through ordered', function () {
    $repository = fakeReportRepository([], treatments: [RankedItem::create('Resina de prueba', 2), RankedItem::create('Limpieza dental de prueba', 3)], staff: [RankedItem::create('Daniela Prueba', 5, 2)]);
    $report = (new MonthlyReportService($repository))->build(ReportMonth::create('2026-07', serviceToday()), serviceToday());

    expect($report->treatments[0]->name)->toBe('Limpieza dental de prueba')
        ->and($report->treatments[0]->isTop)->toBeTrue()
        ->and($report->staff[0]->extra)->toBe(2);
});

it('says until when the month in course has data', function () {
    $service = new MonthlyReportService(fakeReportRepository([]));

    expect($service->build(ReportMonth::create('2026-10', serviceToday()), serviceToday())->dataUntil?->toDateString())->toBe('2026-10-19')
        ->and($service->build(ReportMonth::create('2026-09', serviceToday()), serviceToday())->dataUntil)->toBeNull();
});
