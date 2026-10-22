<?php

declare(strict_types=1);

use App\Modules\Reports\Domain\Exceptions\MonthlyReportException;
use App\Modules\Reports\Domain\ValueObjects\AppointmentCounts;
use Tests\Support\UsesEris;

uses(UsesEris::class);

/*
 * Spec 019, CA2: for any split of a month's appointments, the counts add up; any total that does
 * not add up is refused.
 */

it('accepts every split of the appointments whose total adds up (property)', function () {
    $this->forAll(
        Eris\Generator\choose(0, 500),
        Eris\Generator\choose(0, 500),
        Eris\Generator\choose(0, 500),
        Eris\Generator\choose(0, 500),
        Eris\Generator\choose(0, 500),
        Eris\Generator\choose(0, 500),
    )->then(function (int $completed, int $cancelled, int $rescheduledPending, int $assignedPending, int $unclosed, int $completedToday) {
        $completedToday = min($completedToday, $completed);
        $total = $completed + $cancelled + $rescheduledPending + $assignedPending + $unclosed;
        $notCancelledPast = $completed - $completedToday + $unclosed;

        $counts = AppointmentCounts::create(
            total: $total,
            completed: $completed,
            cancelled: $cancelled,
            rescheduledPending: $rescheduledPending,
            assignedPending: $assignedPending,
            unclosed: $unclosed,
            notCancelledPast: $notCancelledPast,
            completedPast: $completed - $completedToday,
        );

        expect($counts->total)->toBeGreaterThanOrEqual($counts->completed + $counts->cancelled)
            ->and($counts->unclosed)->toBeLessThanOrEqual($counts->notCancelledPast);
    });
});

it('refuses every total that does not add up (property)', function () {
    $this->forAll(Eris\Generator\choose(0, 500), Eris\Generator\choose(0, 500), Eris\Generator\choose(1, 50))
        ->then(function (int $completed, int $cancelled, int $gap) {
            expect(fn () => AppointmentCounts::create(
                total: $completed + $cancelled + $gap,
                completed: $completed,
                cancelled: $cancelled,
                rescheduledPending: 0,
                assignedPending: 0,
                unclosed: 0,
                notCancelledPast: $completed,
                completedPast: $completed,
            ))->toThrow(MonthlyReportException::class);
        });
});
