<?php

declare(strict_types=1);

use App\Modules\Reports\Domain\ValueObjects\Rate;
use Tests\Support\UsesEris;

uses(UsesEris::class);

/*
 * Spec 019, CA3: for any numerator up to its denominator, the rate is between 0 and 100 and has at
 * most one decimal; only a denominator of zero leaves it without data.
 */

it('stays between 0 and 100 with one decimal (property)', function () {
    $this->forAll(Eris\Generator\choose(0, 2000), Eris\Generator\choose(1, 2000))->then(function (int $numerator, int $denominator) {
        $numerator = min($numerator, $denominator);
        $value = Rate::of($numerator, $denominator)->value;

        expect($value)->toBeGreaterThanOrEqual(0.0)
            ->and($value)->toBeLessThanOrEqual(100.0)
            ->and(round($value, 1))->toBe($value)
            ->and(abs($value - $numerator / $denominator * 100))->toBeLessThanOrEqual(0.05 + 1e-9);
    });
});

it('has no data only when the denominator is zero (property)', function () {
    $this->forAll(Eris\Generator\choose(0, 2000))->then(function (int $denominator) {
        expect(Rate::of(0, $denominator)->hasData())->toBe($denominator > 0);
    });
});
