<?php

declare(strict_types=1);

use App\Modules\Reports\Domain\ValueObjects\Comparison;
use Tests\Support\UsesEris;

uses(UsesEris::class);

/*
 * Spec 019, CA2: for any two counts, the difference is the current one minus the previous one, and
 * swapping them turns a favorable change into an unfavorable one (and back) unless the indicator
 * is neutral.
 */

it('takes the difference as current minus previous (property)', function () {
    $this->forAll(Eris\Generator\choose(0, 2000), Eris\Generator\choose(0, 2000))->then(function (int $current, int $previous) {
        expect(Comparison::between($current, $previous, Comparison::UP_IS_GOOD)->difference)->toBe($current - $previous);
    });
})->skip('019: pendiente de T032');

it('turns the trend around when the change goes the other way (property)', function () {
    $opposite = ['favorable' => 'unfavorable', 'unfavorable' => 'favorable', 'neutral' => 'neutral'];

    $this->forAll(Eris\Generator\choose(0, 2000), Eris\Generator\choose(0, 2000), Eris\Generator\elements(Comparison::UP_IS_GOOD, Comparison::UP_IS_BAD))
        ->then(function (int $current, int $previous, string $direction) use ($opposite) {
            $forth = Comparison::between($current, $previous, $direction)->trend;
            $back = Comparison::between($previous, $current, $direction)->trend;

            expect($back)->toBe($opposite[$forth]);
        });
})->skip('019: pendiente de T032');

it('keeps a neutral indicator neutral whatever it does (property)', function () {
    $this->forAll(Eris\Generator\choose(0, 2000), Eris\Generator\choose(0, 2000))->then(function (int $current, int $previous) {
        expect(Comparison::between($current, $previous, Comparison::NEUTRAL)->trend)->toBe('neutral');
    });
})->skip('019: pendiente de T032');
