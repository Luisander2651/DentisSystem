<?php

declare(strict_types=1);

namespace App\Modules\Reports\Infrastructure\Http\Resources;

use App\Modules\Reports\Domain\ValueObjects\Comparison;
use App\Modules\Reports\Domain\ValueObjects\MonthlyReport;
use App\Modules\Reports\Domain\ValueObjects\RankedItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Spec 019: the report of a month in the shape of the contract (plan.md → Contratos y datos).
 * Every figure, difference and trend comes worked out; the screen only paints them (P13).
 *
 * @property-read MonthlyReport $resource
 */
final class MonthlyReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $report = $this->resource;

        return [
            'month' => $report->month->value(),
            'label' => $report->month->label(),
            'short_label' => $report->month->shortLabel(),
            'previous_label' => $report->month->previous()->label(),
            'previous_short_label' => $report->month->previous()->shortLabel(),
            'is_current' => $report->dataUntil !== null,
            'data_until' => $report->dataUntil?->toDateString(),
            'appointments' => array_map(fn (Comparison $comparison): array => self::comparison($comparison), $report->appointments),
            'rates' => array_map(fn (array $rate): array => [
                'value' => $rate['rate']->value,
                'previous' => $rate['comparison']->previous,
                'difference' => $rate['comparison']->difference,
                'numerator' => $rate['rate']->numerator,
                'denominator' => $rate['rate']->denominator,
                'trend' => $rate['comparison']->trend,
            ], $report->rates),
            'patients' => [
                'new' => self::comparison($report->newPatients),
                'active' => ['value' => $report->activePatients, 'as_of' => $report->activeAsOf->toDateString()],
            ],
            'treatments' => array_map(fn (RankedItem $item): array => [
                'name' => $item->name,
                'appointments' => $item->value,
                'is_top' => $item->isTop,
            ], $report->treatments),
            'staff' => array_map(fn (RankedItem $item): array => [
                'name' => $item->name,
                'appointments' => $item->value,
                'completed' => $item->extra ?? 0,
                'is_top' => $item->isTop,
            ], $report->staff),
            'demand' => $report->demand,
        ];
    }

    /**
     * @return array{value: int|float|null, previous: int|float|null, difference: int|float|null, trend: string}
     */
    private static function comparison(Comparison $comparison): array
    {
        return [
            'value' => $comparison->current,
            'previous' => $comparison->previous,
            'difference' => $comparison->difference,
            'trend' => $comparison->trend,
        ];
    }
}
