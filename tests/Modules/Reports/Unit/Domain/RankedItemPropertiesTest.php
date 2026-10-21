<?php

declare(strict_types=1);

use App\Modules\Reports\Domain\ValueObjects\RankedItem;
use Tests\Support\UsesEris;

uses(UsesEris::class);

/*
 * Spec 019, CA5 and CA6: any non-empty name with a figure of zero or more is kept as it is.
 */

it('keeps every non-empty name and figure as they are (property)', function () {
    $this->forAll(Eris\Generator\string(), Eris\Generator\choose(0, 5000), Eris\Generator\choose(0, 5000))
        ->then(function (string $name, int $value, int $extra) {
            if (trim($name) === '') {
                return;
            }

            $item = RankedItem::create($name, $value, $extra);

            expect($item->name)->toBe($name)
                ->and($item->value)->toBe($value)
                ->and($item->extra)->toBe($extra)
                ->and($item->asTop()->value)->toBe($value);
        });
})->skip('019: pendiente de T033');
