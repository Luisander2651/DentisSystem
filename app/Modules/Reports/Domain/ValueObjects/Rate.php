<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\ValueObjects;

use App\Modules\Reports\Domain\Exceptions\MonthlyReportException;

/**
 * Spec 019 (CA3, D16): a rate as a percentage rounded to one decimal, half up (1/16 → 6.3), or no
 * data when there is nothing to divide by.
 */
final readonly class Rate
{
    private function __construct(
        public ?float $value,
        public int $numerator,
        public int $denominator,
    ) {}

    /**
     * @throws MonthlyReportException when a count is negative or the numerator is above the denominator (R1)
     */
    public static function of(int $numerator, int $denominator): self
    {
        if ($numerator < 0) {
            throw MonthlyReportException::negativeCount('numerator');
        }

        if ($denominator < 0) {
            throw MonthlyReportException::negativeCount('denominator');
        }

        if ($numerator > $denominator) {
            throw MonthlyReportException::numeratorAboveDenominator();
        }

        $value = $denominator === 0 ? null : round($numerator * 100 / $denominator, 1, PHP_ROUND_HALF_UP);

        return new self($value, $numerator, $denominator);
    }

    public function hasData(): bool
    {
        return $this->value !== null;
    }
}
