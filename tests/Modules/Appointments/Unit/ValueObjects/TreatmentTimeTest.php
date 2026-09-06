<?php

declare(strict_types=1);

use App\Modules\Appointments\Domain\Exceptions\ValueObjects\TreatmentTimeException;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentTime;
use Tests\Support\UsesEris;

uses(UsesEris::class);

it('accepts any integer within [0, 240] (property)', function () {
    $this->forAll(Eris\Generator\choose(0, 240))->then(function (int $minutes) {
        expect(TreatmentTime::fromInt($minutes)->value)->toBe($minutes);
    });
});

it('rejects any integer outside [0, 240] (property)', function () {
    $this->forAll(Eris\Generator\oneOf(
        Eris\Generator\choose(-1000, -1),
        Eris\Generator\choose(241, 10000),
    ))->then(function (int $minutes) {
        expect(fn () => TreatmentTime::fromInt($minutes))
            ->toThrow(TreatmentTimeException::class);
    });
});

it('accepts the boundary values 0 and 240 (example)', function () {
    expect(TreatmentTime::fromInt(0)->value)->toBe(0);
    expect(TreatmentTime::fromInt(240)->value)->toBe(240);
});

it('rejects 241 and -1 (example)', function () {
    expect(fn () => TreatmentTime::fromInt(241))->toThrow(TreatmentTimeException::class);
    expect(fn () => TreatmentTime::fromInt(-1))->toThrow(TreatmentTimeException::class);
});
