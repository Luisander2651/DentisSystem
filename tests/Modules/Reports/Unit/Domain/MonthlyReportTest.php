<?php

declare(strict_types=1);

use App\Modules\Reports\Domain\ValueObjects\Comparison;
use App\Modules\Reports\Domain\ValueObjects\MonthlyReport;
use App\Modules\Reports\Domain\ValueObjects\RankedItem;
use App\Modules\Reports\Domain\ValueObjects\Rate;
use App\Modules\Reports\Domain\ValueObjects\ReportMonth;
use Carbon\CarbonImmutable;

/*
 * Spec 019, CA2–CA7 and CA12: the whole report of a month, with the figures of July 2026 of
 * design.md, and nothing about any patient but counts.
 */

/**
 * @param  list<RankedItem>  $treatments
 * @param  list<RankedItem>  $staff
 */
function julyReport(array $treatments = [], array $staff = []): MonthlyReport
{
    $today = CarbonImmutable::parse('2026-10-19 10:00:00', 'America/Mexico_City');

    return MonthlyReport::create(
        month: ReportMonth::create('2026-07', $today),
        dataUntil: null,
        appointments: [
            'total' => Comparison::between(8, 0, 'up_is_good'),
            'completed' => Comparison::between(2, 0, 'up_is_good'),
            'cancelled' => Comparison::between(1, 0, 'up_is_bad'),
            'unclosed' => Comparison::between(5, 0, 'up_is_bad'),
            'rescheduled_pending' => Comparison::between(0, 0, 'neutral'),
            'assigned_pending' => Comparison::between(0, 0, 'neutral'),
        ],
        rates: [
            'attendance' => ['rate' => Rate::of(2, 7), 'comparison' => Comparison::between(28.6, null, 'up_is_good')],
            'cancellation' => ['rate' => Rate::of(1, 8), 'comparison' => Comparison::between(12.5, null, 'up_is_bad')],
        ],
        newPatients: Comparison::between(0, 0, 'up_is_good'),
        activePatients: 2,
        activeAsOf: $today,
        treatments: $treatments,
        staff: $staff,
        demand: [
            'hours' => [9, 10, 11, 12, 13, 14, 15, 16],
            'matrix' => [[1, 0, 1, 0, 0, 0, 0], [0, 1, 0, 0, 0, 0, 0], [0, 0, 1, 0, 0, 0, 0], [1, 0, 1, 0, 0, 0, 0], [0, 0, 0, 0, 0, 0, 0], [0, 0, 0, 0, 0, 0, 0], [0, 0, 0, 0, 0, 0, 0], [0, 0, 1, 0, 0, 0, 0]],
            'by_weekday' => [2, 1, 4, 0, 0, 0, 0],
            'by_hour' => [2, 1, 1, 2, 0, 0, 0, 1],
            'top_weekdays' => [2],
            'top_hours' => [9, 12],
            'total' => 7,
        ],
    );
}

it('keeps the figures of the month', function () {
    $report = julyReport();

    expect($report->month->value())->toBe('2026-07')
        ->and($report->dataUntil)->toBeNull()
        ->and($report->appointments['total']->current)->toBe(8)
        ->and($report->appointments['unclosed']->trend)->toBe('unfavorable')
        ->and($report->rates['attendance']['rate']->value)->toBe(28.6)
        ->and($report->rates['cancellation']['comparison']->trend)->toBe('none')
        ->and($report->newPatients->trend)->toBe('neutral')
        ->and($report->activePatients)->toBe(2)
        ->and($report->demand['top_hours'])->toBe([9, 12]);
});

it('orders the rankings from most to least and by name on a tie, marking the top ones', function () {
    $report = julyReport(
        treatments: [RankedItem::create('Resina de prueba', 2), RankedItem::create('Limpieza dental de prueba', 3), RankedItem::create('Ortodoncia de prueba', 2)],
        staff: [RankedItem::create('Diego Prueba', 2, 0), RankedItem::create('Daniela Prueba', 5, 2)],
    );

    expect(array_map(fn (RankedItem $item): string => $item->name, $report->treatments))->toBe(['Limpieza dental de prueba', 'Ortodoncia de prueba', 'Resina de prueba'])
        ->and(array_map(fn (RankedItem $item): bool => $item->isTop, $report->treatments))->toBe([true, false, false])
        ->and(array_map(fn (RankedItem $item): string => $item->name, $report->staff))->toBe(['Daniela Prueba', 'Diego Prueba'])
        ->and($report->staff[0]->isTop)->toBeTrue();
});

it('orders names on a tie without minding capitals or accents', function () {
    $report = julyReport(treatments: [RankedItem::create('beatriz', 1), RankedItem::create('Ángel', 1), RankedItem::create('Zoe', 1)]);

    expect(array_map(fn (RankedItem $item): string => $item->name, $report->treatments))->toBe(['Ángel', 'beatriz', 'Zoe'])
        ->and(array_map(fn (RankedItem $item): bool => $item->isTop, $report->treatments))->toBe([true, true, true]);
});

it('has nothing about a patient but counts', function () {
    $properties = array_map(fn (ReflectionProperty $property): string => $property->getName(), (new ReflectionClass(MonthlyReport::class))->getProperties());

    expect($properties)->toEqualCanonicalizing(['month', 'dataUntil', 'appointments', 'rates', 'newPatients', 'activePatients', 'activeAsOf', 'treatments', 'staff', 'demand']);
});
