<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\Exceptions;

use Exception;

/**
 * Spec 019: a figure of the report that cannot be. It only names the figure, never a value of a
 * patient; it would be a bug of the queries, so the API answers it as an unexpected error.
 */
final class MonthlyReportException extends Exception
{
    public static function negativeCount(string $count): self
    {
        return new self("The count {$count} of a monthly report cannot be negative.");
    }

    public static function inconsistentCounts(string $reason): self
    {
        return new self("The counts of a monthly report do not add up: {$reason}.");
    }

    public static function numeratorAboveDenominator(): self
    {
        return new self('The numerator of a rate of a monthly report cannot be above its denominator.');
    }

    public static function emptyName(): self
    {
        return new self('A line of a ranking needs a name.');
    }

    public static function negativeValue(string $value): self
    {
        return new self("The {$value} of a line of a ranking cannot be negative.");
    }
}
