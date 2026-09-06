<?php

declare(strict_types=1);

use App\Modules\Patients\Domain\Exceptions\ValueObjects\Addresses\CityException;
use App\Modules\Patients\Domain\Exceptions\ValueObjects\Addresses\StateException;
use App\Modules\Patients\Domain\Exceptions\ValueObjects\Addresses\StreetException;
use App\Modules\Patients\Domain\ValueObjects\Addresses\City;
use App\Modules\Patients\Domain\ValueObjects\Addresses\State;
use App\Modules\Patients\Domain\ValueObjects\Addresses\Street;
use Tests\Support\UsesEris;

uses(UsesEris::class);

it('Street accepts any length within [1, 150] and rejects above (property)', function () {
    $this->forAll(Eris\Generator\choose(1, 150))->then(function (int $len) {
        expect(Street::fromNullable(str_repeat('a', $len))->value)->toHaveLength($len);
    });

    $this->forAll(Eris\Generator\choose(151, 300))->then(function (int $len) {
        expect(fn () => Street::fromNullable(str_repeat('a', $len)))->toThrow(StreetException::class);
    });
});

it('City accepts any length within [1, 100] and rejects above (property)', function () {
    $this->forAll(Eris\Generator\choose(1, 100))->then(function (int $len) {
        expect(City::fromNullable(str_repeat('a', $len))->value)->toHaveLength($len);
    });

    $this->forAll(Eris\Generator\choose(101, 300))->then(function (int $len) {
        expect(fn () => City::fromNullable(str_repeat('a', $len)))->toThrow(CityException::class);
    });
});

it('State accepts any length within [1, 100] and rejects above (property)', function () {
    $this->forAll(Eris\Generator\choose(1, 100))->then(function (int $len) {
        expect(State::fromNullable(str_repeat('a', $len))->value)->toHaveLength($len);
    });

    $this->forAll(Eris\Generator\choose(101, 300))->then(function (int $len) {
        expect(fn () => State::fromNullable(str_repeat('a', $len)))->toThrow(StateException::class);
    });
});

it('treats null and empty/whitespace-only string as absent for all three (example)', function () {
    expect(Street::fromNullable(null)->value)->toBeNull();
    expect(Street::fromNullable('')->value)->toBeNull();
    expect(Street::fromNullable('   ')->value)->toBeNull();

    expect(City::fromNullable(null)->value)->toBeNull();
    expect(State::fromNullable(null)->value)->toBeNull();
});

it('trims surrounding whitespace (example)', function () {
    expect(City::fromNullable('  Springfield  ')->value)->toBe('Springfield');
});
