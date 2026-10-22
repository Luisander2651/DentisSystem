<?php

declare(strict_types=1);

use App\Modules\Reports\Domain\ValueObjects\Comparison;

/*
 * Spec 019, CA2 and CA3 (design.md → favorability table): a figure against the month before, and
 * whether the change is good or bad for the clinic, not whether it goes up or down.
 */

it('says whether each indicator changed for the better or the worse', function (string $direction, int $current, int $previous, int $difference, string $trend) {
    $comparison = Comparison::between($current, $previous, $direction);

    expect($comparison->current)->toBe($current)
        ->and($comparison->previous)->toBe($previous)
        ->and($comparison->difference)->toBe($difference)
        ->and($comparison->trend)->toBe($trend);
})->with([
    'appointments of the month go up' => ['up_is_good', 8, 0, 8, 'favorable'],
    'appointments of the month go down' => ['up_is_good', 0, 8, -8, 'unfavorable'],
    'completed go up' => ['up_is_good', 2, 1, 1, 'favorable'],
    'new patients go down' => ['up_is_good', 1, 3, -2, 'unfavorable'],
    'cancelled go up' => ['up_is_bad', 1, 0, 1, 'unfavorable'],
    'cancelled go down' => ['up_is_bad', 0, 2, -2, 'favorable'],
    'unclosed go up' => ['up_is_bad', 5, 0, 5, 'unfavorable'],
    'pending go up' => ['neutral', 3, 1, 2, 'neutral'],
    'pending go down' => ['neutral', 0, 4, -4, 'neutral'],
    'no change' => ['up_is_good', 4, 4, 0, 'neutral'],
    'no change in a bad one' => ['up_is_bad', 4, 4, 0, 'neutral'],
]);

it('compares rates in points with one decimal', function () {
    $attendance = Comparison::between(28.6, 25.3, Comparison::UP_IS_GOOD);
    $cancellation = Comparison::between(12.5, 9.2, Comparison::UP_IS_BAD);

    expect($attendance->difference)->toBe(3.3)
        ->and($attendance->trend)->toBe('favorable')
        ->and($cancellation->difference)->toBe(3.3)
        ->and($cancellation->trend)->toBe('unfavorable');
});

it('has no comparison when a side has no data', function (?float $current, ?float $previous) {
    $comparison = Comparison::between($current, $previous, Comparison::UP_IS_GOOD);

    expect($comparison->difference)->toBeNull()
        ->and($comparison->trend)->toBe('none');
})->with([
    'no data last month' => [28.6, null],
    'no data this month' => [null, 28.6],
    'no data in either' => [null, null],
]);

it('names its directions as the dataset above does', function () {
    expect([Comparison::UP_IS_GOOD, Comparison::UP_IS_BAD, Comparison::NEUTRAL])->toBe(['up_is_good', 'up_is_bad', 'neutral']);
});
