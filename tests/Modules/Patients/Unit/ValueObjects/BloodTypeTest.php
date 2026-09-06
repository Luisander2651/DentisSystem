<?php

declare(strict_types=1);

use App\Modules\Patients\Domain\Exceptions\ValueObjects\MedicalData\BloodTypeException;
use App\Modules\Patients\Domain\ValueObjects\MedicalData\BloodType;
use Tests\Support\UsesEris;

uses(UsesEris::class);

it('accepts only the 8 whitelisted blood types, case-insensitive (property)', function () {
    $this->forAll(Eris\Generator\elements([
        'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-',
        'a+', 'a-', 'b+', 'ab-', 'o+',
    ]))->then(function (string $valid) {
        expect(BloodType::fromNullable($valid)->value)->toBe(strtoupper($valid));
    });
});

it('rejects any string outside the whitelist (property)', function () {
    $this->forAll(Eris\Generator\elements([
        'C+', 'AB', 'O', 'A++', 'unknown', 'AB++',
    ]))->then(function (string $invalid) {
        expect(fn () => BloodType::fromNullable($invalid))->toThrow(BloodTypeException::class);
    });
});

it('treats null and empty string as absent (example)', function () {
    expect(BloodType::fromNullable(null)->value)->toBeNull();
    expect(BloodType::fromNullable('')->value)->toBeNull();
});

it('normalizes to uppercase (example)', function () {
    expect(BloodType::fromNullable('o+')->value)->toBe('O+');
});
