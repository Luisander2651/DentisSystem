<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\ValueObjects;

/**
 * Spec 019 (CA2, CA3, D6; design.md → favorability table): a figure against the month before,
 * and whether the change is good or bad for the clinic, which depends on what the figure counts
 * and not on whether it goes up or down. Rates are compared in points with one decimal.
 */
final readonly class Comparison
{
    public const UP_IS_GOOD = 'up_is_good';

    public const UP_IS_BAD = 'up_is_bad';

    public const NEUTRAL = 'neutral';

    private function __construct(
        public int|float|null $current,
        public int|float|null $previous,
        public int|float|null $difference,
        public string $trend,
    ) {}

    public static function between(int|float|null $current, int|float|null $previous, string $direction): self
    {
        if ($current === null || $previous === null) {
            return new self($current, $previous, null, 'none');
        }

        $difference = is_float($current) || is_float($previous)
            ? round($current - $previous, 1)
            : $current - $previous;

        return new self($current, $previous, $difference, self::trendOf($difference, $direction));
    }

    private static function trendOf(int|float $difference, string $direction): string
    {
        if ($difference == 0 || $direction === self::NEUTRAL) {
            return 'neutral';
        }

        $wentUp = $difference > 0;

        return $wentUp === ($direction === self::UP_IS_GOOD) ? 'favorable' : 'unfavorable';
    }
}
