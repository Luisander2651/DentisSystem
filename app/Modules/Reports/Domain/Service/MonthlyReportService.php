<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\Service;

use App\Modules\Reports\Domain\Repositories\MonthlyReportRepositoryInterface;
use App\Modules\Reports\Domain\ValueObjects\AppointmentCounts;
use App\Modules\Reports\Domain\ValueObjects\Comparison;
use App\Modules\Reports\Domain\ValueObjects\MonthlyReport;
use App\Modules\Reports\Domain\ValueObjects\Rate;
use App\Modules\Reports\Domain\ValueObjects\ReportMonth;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Spec 019 (CA2–CA7, CA9; P13): works out the report of a month from what the repository counts
 * for it and for the month before: rates, differences, whether each change is good or bad for the
 * clinic, and the week from the first to the last hour with appointments.
 */
final readonly class MonthlyReportService
{
    public function __construct(
        private MonthlyReportRepositoryInterface $repository,
    ) {}

    public function build(ReportMonth $month, CarbonInterface $today): MonthlyReport
    {
        $current = $this->repository->appointmentCounts($month, $today);
        $previous = $this->repository->appointmentCounts($month->previous(), $today);
        $today = CarbonImmutable::instance($today);

        return MonthlyReport::create(
            month: $month,
            dataUntil: $month->isCurrent($today) ? $today->startOfDay() : null,
            appointments: [
                'total' => Comparison::between($current->total, $previous->total, Comparison::UP_IS_GOOD),
                'completed' => Comparison::between($current->completed, $previous->completed, Comparison::UP_IS_GOOD),
                'cancelled' => Comparison::between($current->cancelled, $previous->cancelled, Comparison::UP_IS_BAD),
                'unclosed' => Comparison::between($current->unclosed, $previous->unclosed, Comparison::UP_IS_BAD),
                'rescheduled_pending' => Comparison::between($current->rescheduledPending, $previous->rescheduledPending, Comparison::NEUTRAL),
                'assigned_pending' => Comparison::between($current->assignedPending, $previous->assignedPending, Comparison::NEUTRAL),
            ],
            rates: [
                'attendance' => $this->rate(self::attendance($current), self::attendance($previous), Comparison::UP_IS_GOOD),
                'cancellation' => $this->rate(self::cancellation($current), self::cancellation($previous), Comparison::UP_IS_BAD),
            ],
            newPatients: Comparison::between($this->repository->newPatients($month), $this->repository->newPatients($month->previous()), Comparison::UP_IS_GOOD),
            activePatients: $this->repository->activePatients(),
            activeAsOf: $today,
            treatments: $this->repository->treatmentRanking($month),
            staff: $this->repository->staffLoad($month),
            demand: self::demand($this->repository->demandByWeekdayAndHour($month)),
        );
    }

    /** Completed among the not cancelled appointments on a day already gone, both on a past day (R1). */
    private static function attendance(AppointmentCounts $counts): Rate
    {
        return Rate::of($counts->completedPast, $counts->notCancelledPast);
    }

    /** Cancelled among all the appointments of the month. */
    private static function cancellation(AppointmentCounts $counts): Rate
    {
        return Rate::of($counts->cancelled, $counts->total);
    }

    /**
     * @return array{rate: Rate, comparison: Comparison}
     */
    private function rate(Rate $current, Rate $previous, string $direction): array
    {
        return ['rate' => $current, 'comparison' => Comparison::between($current->value, $previous->value, $direction)];
    }

    /**
     * The week of the month: one row per hour from the first to the last with appointments, one
     * column per day from Monday (0) to Sunday (6), the totals of both and the highest ones (all
     * of them on a tie).
     *
     * @param  array<int, array<int, int>>  $byWeekdayAndHour  ISO weekday => hour => appointments
     * @return array{hours: list<int>, matrix: list<list<int>>, by_weekday: list<int>, by_hour: list<int>, top_weekdays: list<int>, top_hours: list<int>, total: int}
     */
    private static function demand(array $byWeekdayAndHour): array
    {
        $hoursWithAppointments = [];

        foreach ($byWeekdayAndHour as $hours) {
            foreach ($hours as $hour => $appointments) {
                if ($appointments > 0) {
                    $hoursWithAppointments[] = $hour;
                }
            }
        }

        if ($hoursWithAppointments === []) {
            return ['hours' => [], 'matrix' => [], 'by_weekday' => array_fill(0, 7, 0), 'by_hour' => [], 'top_weekdays' => [], 'top_hours' => [], 'total' => 0];
        }

        $hours = range(min($hoursWithAppointments), max($hoursWithAppointments));
        $matrix = array_map(
            fn (int $hour): array => array_map(fn (int $weekday): int => $byWeekdayAndHour[$weekday][$hour] ?? 0, range(1, 7)),
            $hours,
        );
        $byWeekday = array_map(fn (int $day): int => array_sum(array_column($matrix, $day)), range(0, 6));
        $byHour = array_map(fn (array $row): int => array_sum($row), $matrix);

        return [
            'hours' => $hours,
            'matrix' => $matrix,
            'by_weekday' => $byWeekday,
            'by_hour' => $byHour,
            'top_weekdays' => array_keys($byWeekday, max($byWeekday), true),
            'top_hours' => array_values(array_map(fn (int $index): int => $hours[$index], array_keys($byHour, max($byHour), true))),
            'total' => array_sum($byWeekday),
        ];
    }
}
