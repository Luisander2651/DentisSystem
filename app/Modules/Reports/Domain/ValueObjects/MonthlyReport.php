<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\ValueObjects;

use Carbon\CarbonImmutable;

/**
 * Spec 019 (CA2–CA7, CA12): the whole report of a month, the one object the screen and the file
 * are made from (P13). It holds counts, treatments and staff, and nothing about any patient but
 * counts. The rankings go from most to least, by name on a tie (without minding capitals or
 * accents), and the lines with the highest figure above zero are marked as top.
 *
 * @phpstan-type Demand array{hours: list<int>, matrix: list<list<int>>, by_weekday: list<int>, by_hour: list<int>, top_weekdays: list<int>, top_hours: list<int>, total: int}
 */
final readonly class MonthlyReport
{
    /**
     * @param  array<string, Comparison>  $appointments  total, completed, cancelled, unclosed, rescheduled_pending, assigned_pending
     * @param  array<string, array{rate: Rate, comparison: Comparison}>  $rates  attendance, cancellation
     * @param  list<RankedItem>  $treatments
     * @param  list<RankedItem>  $staff
     * @param  Demand  $demand
     */
    private function __construct(
        public ReportMonth $month,
        public ?CarbonImmutable $dataUntil,
        public array $appointments,
        public array $rates,
        public Comparison $newPatients,
        public int $activePatients,
        public CarbonImmutable $activeAsOf,
        public array $treatments,
        public array $staff,
        public array $demand,
    ) {}

    /**
     * @param  array<string, Comparison>  $appointments
     * @param  array<string, array{rate: Rate, comparison: Comparison}>  $rates
     * @param  list<RankedItem>  $treatments
     * @param  list<RankedItem>  $staff
     * @param  Demand  $demand
     */
    public static function create(
        ReportMonth $month,
        ?CarbonImmutable $dataUntil,
        array $appointments,
        array $rates,
        Comparison $newPatients,
        int $activePatients,
        CarbonImmutable $activeAsOf,
        array $treatments,
        array $staff,
        array $demand,
    ): self {
        return new self($month, $dataUntil, $appointments, $rates, $newPatients, $activePatients, $activeAsOf, self::ranked($treatments), self::ranked($staff), $demand);
    }

    /**
     * @param  list<RankedItem>  $items
     * @return list<RankedItem>
     */
    private static function ranked(array $items): array
    {
        usort($items, fn (RankedItem $a, RankedItem $b): int => [$b->value, self::nameKey($a->name)] <=> [$a->value, self::nameKey($b->name)]);

        $top = $items === [] ? 0 : $items[0]->value;

        return array_map(fn (RankedItem $item): RankedItem => $top > 0 && $item->value === $top ? $item->asTop() : $item, $items);
    }

    private static function nameKey(string $name): string
    {
        return strtr(mb_strtolower($name), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    }
}
