<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\ValueObjects;

use App\Modules\Reports\Domain\Exceptions\MonthlyReportException;

/**
 * Spec 019 (CA2): the appointments of a month by state. Each appointment is in exactly one of
 * completed, cancelled, pending (assigned or rescheduled, today or later) or unclosed (assigned or
 * rescheduled, on a day already gone; D7). The not cancelled ones on a past day are the base of
 * the attendance rate.
 */
final readonly class AppointmentCounts
{
    private function __construct(
        public int $total,
        public int $completed,
        public int $cancelled,
        public int $rescheduledPending,
        public int $assignedPending,
        public int $unclosed,
        public int $notCancelledPast,
    ) {}

    /**
     * @throws MonthlyReportException when a count is negative or the counts do not add up
     */
    public static function create(
        int $total,
        int $completed,
        int $cancelled,
        int $rescheduledPending,
        int $assignedPending,
        int $unclosed,
        int $notCancelledPast,
    ): self {
        foreach (compact('total', 'completed', 'cancelled', 'rescheduledPending', 'assignedPending', 'unclosed', 'notCancelledPast') as $name => $count) {
            if ($count < 0) {
                throw MonthlyReportException::negativeCount($name);
            }
        }

        if ($total !== $completed + $cancelled + $rescheduledPending + $assignedPending + $unclosed) {
            throw MonthlyReportException::inconsistentCounts('the total is not the sum of the states');
        }

        if ($unclosed > $notCancelledPast) {
            throw MonthlyReportException::inconsistentCounts('more unclosed appointments than not cancelled ones on past days');
        }

        return new self($total, $completed, $cancelled, $rescheduledPending, $assignedPending, $unclosed, $notCancelledPast);
    }

    public static function empty(): self
    {
        return new self(0, 0, 0, 0, 0, 0, 0);
    }
}
