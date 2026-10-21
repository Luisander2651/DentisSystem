<?php

declare(strict_types=1);

use App\Modules\Reports\Domain\ValueObjects\Comparison;
use App\Modules\Reports\Domain\ValueObjects\MonthlyReport;
use App\Modules\Reports\Domain\ValueObjects\RankedItem;
use App\Modules\Reports\Domain\ValueObjects\Rate;
use App\Modules\Reports\Domain\ValueObjects\ReportMonth;
use Carbon\CarbonImmutable;
use Tests\Support\UsesEris;

uses(UsesEris::class);

/*
 * Spec 019, CA5 and CA6: for any list of lines, a ranking goes from most to least, by name on a
 * tie, and only the lines with the highest figure are marked as top.
 */

function reportWithRanking(array $items): MonthlyReport
{
    $today = CarbonImmutable::parse('2026-10-19 10:00:00', 'America/Mexico_City');
    $none = Comparison::between(0, 0, 'neutral');

    return MonthlyReport::create(
        month: ReportMonth::create('2026-07', $today),
        dataUntil: null,
        appointments: ['total' => $none, 'completed' => $none, 'cancelled' => $none, 'unclosed' => $none, 'rescheduled_pending' => $none, 'assigned_pending' => $none],
        rates: [
            'attendance' => ['rate' => Rate::of(0, 0), 'comparison' => Comparison::between(null, null, 'up_is_good')],
            'cancellation' => ['rate' => Rate::of(0, 0), 'comparison' => Comparison::between(null, null, 'up_is_bad')],
        ],
        newPatients: $none,
        activePatients: 0,
        activeAsOf: $today,
        treatments: $items,
        staff: $items,
        demand: ['hours' => [], 'matrix' => [], 'by_weekday' => [0, 0, 0, 0, 0, 0, 0], 'by_hour' => [], 'top_weekdays' => [], 'top_hours' => [], 'total' => 0],
    );
}

function rankingNameKey(string $name): string
{
    return strtr(mb_strtolower($name), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
}

it('orders every ranking and marks only its highest lines (property)', function () {
    $this->forAll(Eris\Generator\seq(Eris\Generator\tuple(Eris\Generator\elements('Ana', 'Ángel', 'beatriz', 'Diego', 'Zoe', 'Resina', 'ortodoncia'), Eris\Generator\choose(0, 20))))
        ->then(function (array $lines) {
            $items = array_map(fn (array $line): RankedItem => RankedItem::create($line[0], $line[1]), $lines);
            $report = reportWithRanking($items);

            foreach ([$report->treatments, $report->staff] as $ranking) {
                expect($ranking)->toHaveCount(count($items));

                $top = $ranking === [] ? null : max(array_map(fn (RankedItem $item): int => $item->value, $ranking));

                foreach ($ranking as $index => $item) {
                    expect($item->isTop)->toBe($item->value === $top && $top > 0);

                    if ($index > 0) {
                        $before = $ranking[$index - 1];

                        expect($before->value >= $item->value)->toBeTrue();

                        if ($before->value === $item->value) {
                            expect(strcmp(rankingNameKey($before->name), rankingNameKey($item->name)) <= 0)->toBeTrue();
                        }
                    }
                }
            }
        });
})->skip('019: pendiente de T033');
