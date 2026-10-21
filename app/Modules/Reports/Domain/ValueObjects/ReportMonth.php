<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\ValueObjects;

use App\Modules\Reports\Domain\Exceptions\ReportMonthException;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Spec 019 (CA9, CA11): the month of a report, written AAAA-MM, from January 2020 to the month
 * of "today", which comes in the time of the clinic (the zone of the application). Today may be
 * the mutable Carbon that now() returns.
 */
final readonly class ReportMonth
{
    private const FIRST = '2020-01';

    private const NAMES = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio',
        7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];

    private function __construct(
        private int $year,
        private int $month,
    ) {}

    /**
     * @throws ReportMonthException when the text is not a month AAAA-MM between January 2020
     *                              and the month in course
     */
    public static function create(string $value, CarbonInterface $today): self
    {
        if (preg_match('/^(\d{4})-(\d{2})$/', $value, $parts) !== 1) {
            throw ReportMonthException::outOfRange();
        }

        $month = (int) $parts[2];

        if ($month < 1 || $month > 12 || $value < self::FIRST || $value > self::of($today)->value()) {
            throw ReportMonthException::outOfRange();
        }

        return new self((int) $parts[1], $month);
    }

    /** The last month that has ended by today. */
    public static function lastComplete(CarbonInterface $today): self
    {
        return self::of($today)->previous();
    }

    /**
     * The months a report can be asked for, from the month in course back to January 2020.
     *
     * @return list<self>
     */
    public static function selectable(CarbonInterface $today): array
    {
        $months = [];

        for ($month = self::of($today); $month->value() >= self::FIRST; $month = $month->previous()) {
            $months[] = $month;
        }

        return $months;
    }

    public function previous(): self
    {
        return $this->month === 1 ? new self($this->year - 1, 12) : new self($this->year, $this->month - 1);
    }

    public function value(): string
    {
        return sprintf('%04d-%02d', $this->year, $this->month);
    }

    public function firstDay(): CarbonImmutable
    {
        return CarbonImmutable::create($this->year, $this->month, 1);
    }

    public function lastDay(): CarbonImmutable
    {
        return $this->firstDay()->endOfMonth()->startOfDay();
    }

    public function isCurrent(CarbonInterface $today): bool
    {
        return $this->value() === self::of($today)->value();
    }

    /** "julio de 2026" */
    public function label(): string
    {
        return $this->shortLabel().' de '.$this->year;
    }

    /** "julio" */
    public function shortLabel(): string
    {
        return self::NAMES[$this->month];
    }

    /** "Julio de 2026", for the option of a select. */
    public function optionLabel(): string
    {
        return mb_strtoupper(mb_substr($this->label(), 0, 1)).mb_substr($this->label(), 1);
    }

    private static function of(CarbonInterface $today): self
    {
        return new self($today->year, $today->month);
    }
}
