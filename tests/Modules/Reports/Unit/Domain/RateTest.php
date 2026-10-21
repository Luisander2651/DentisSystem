<?php

declare(strict_types=1);

use App\Modules\Reports\Domain\Exceptions\MonthlyReportException;
use App\Modules\Reports\Domain\ValueObjects\Rate;

/*
 * Spec 019, CA3: a rate as a percentage with one decimal, half up, or no data when there is
 * nothing to divide by.
 */

it('gives the percentage with one decimal', function (int $numerator, int $denominator, float $percentage) {
    $rate = Rate::of($numerator, $denominator);

    expect($rate->value)->toBe($percentage)
        ->and($rate->numerator)->toBe($numerator)
        ->and($rate->denominator)->toBe($denominator);
})->with([
    'attendance of July 2026' => [2, 7, 28.6],
    'cancellation of July 2026' => [1, 8, 12.5],
    'half up' => [1, 16, 6.3],
    'all of them' => [3, 3, 100.0],
    'none of them' => [0, 4, 0.0],
]);

it('has no data when there is nothing to divide by', function () {
    $rate = Rate::of(0, 0);

    expect($rate->value)->toBeNull()
        ->and($rate->hasData())->toBeFalse()
        ->and(Rate::of(2, 7)->hasData())->toBeTrue();
});

it('refuses negative counts', function (int $numerator, int $denominator) {
    expect(fn () => Rate::of($numerator, $denominator))->toThrow(MonthlyReportException::class);
})->with([[-1, 4], [1, -4]]);

it('refuses a numerator above its denominator (R1)', function (int $numerator, int $denominator) {
    expect(fn () => Rate::of($numerator, $denominator))->toThrow(MonthlyReportException::class);
})->with(['two of one' => [2, 1], 'one of none' => [1, 0]]);
