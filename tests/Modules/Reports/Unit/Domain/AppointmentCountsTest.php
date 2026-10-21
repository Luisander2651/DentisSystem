<?php

declare(strict_types=1);

use App\Modules\Reports\Domain\Exceptions\MonthlyReportException;
use App\Modules\Reports\Domain\ValueObjects\AppointmentCounts;

/*
 * Spec 019, CA2: the appointments of a month by state. Every appointment is in exactly one of
 * completed, cancelled, pending (assigned or rescheduled, today or later) or unclosed (assigned or
 * rescheduled, on a day already gone).
 */

it('keeps the counts of the month by state', function () {
    $counts = AppointmentCounts::create(
        total: 8,
        completed: 2,
        cancelled: 1,
        rescheduledPending: 0,
        assignedPending: 0,
        unclosed: 5,
        notCancelledPast: 7,
        completedPast: 2,
    );

    expect($counts->total)->toBe(8)
        ->and($counts->completed)->toBe(2)
        ->and($counts->cancelled)->toBe(1)
        ->and($counts->rescheduledPending)->toBe(0)
        ->and($counts->assignedPending)->toBe(0)
        ->and($counts->unclosed)->toBe(5)
        ->and($counts->notCancelledPast)->toBe(7)
        ->and($counts->completedPast)->toBe(2);
});

it('takes a month without appointments', function () {
    expect(AppointmentCounts::empty()->total)->toBe(0)
        ->and(AppointmentCounts::empty()->notCancelledPast)->toBe(0)
        ->and(AppointmentCounts::empty()->completedPast)->toBe(0);
});

it('refuses a negative count', function (string $field) {
    $values = ['total' => 1, 'completed' => 1, 'cancelled' => 0, 'rescheduledPending' => 0, 'assignedPending' => 0, 'unclosed' => 0, 'notCancelledPast' => 1, 'completedPast' => 1];
    $values[$field] = -1;

    expect(fn () => AppointmentCounts::create(...$values))->toThrow(MonthlyReportException::class);
})->with(['total', 'completed', 'cancelled', 'rescheduledPending', 'assignedPending', 'unclosed', 'notCancelledPast', 'completedPast']);

it('refuses a total that is not the sum of the states', function () {
    expect(fn () => AppointmentCounts::create(total: 9, completed: 2, cancelled: 1, rescheduledPending: 0, assignedPending: 0, unclosed: 5, notCancelledPast: 7, completedPast: 2))
        ->toThrow(MonthlyReportException::class);
});

it('refuses more unclosed appointments than not cancelled ones on past days', function () {
    expect(fn () => AppointmentCounts::create(total: 5, completed: 0, cancelled: 0, rescheduledPending: 0, assignedPending: 0, unclosed: 5, notCancelledPast: 4, completedPast: 0))
        ->toThrow(MonthlyReportException::class);
});

it('refuses more completed appointments on past days than not cancelled ones on past days (R1)', function () {
    expect(fn () => AppointmentCounts::create(total: 3, completed: 2, cancelled: 0, rescheduledPending: 0, assignedPending: 0, unclosed: 1, notCancelledPast: 1, completedPast: 2))
        ->toThrow(MonthlyReportException::class);
});

it('refuses more completed appointments on past days than completed ones (R1)', function () {
    expect(fn () => AppointmentCounts::create(total: 2, completed: 1, cancelled: 0, rescheduledPending: 0, assignedPending: 0, unclosed: 1, notCancelledPast: 3, completedPast: 2))
        ->toThrow(MonthlyReportException::class);
});
