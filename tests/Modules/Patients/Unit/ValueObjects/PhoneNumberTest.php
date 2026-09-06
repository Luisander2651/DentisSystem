<?php

declare(strict_types=1);

use App\Modules\Patients\Domain\Exceptions\ValueObjects\ContactInfo\PhoneNumberException;
use App\Modules\Patients\Domain\ValueObjects\ContactInfo\PhoneNumber;
use Tests\Support\UsesEris;

uses(UsesEris::class);

it('accepts any string matching the phone format (property)', function () {
    $this->forAll(Eris\Generator\elements([
        '1234567',
        '+1 555 0100',
        '(555) 123-4567',
        '555-123-4567',
        '+52 55 1234 5678',
    ]))->then(function (string $valid) {
        expect(PhoneNumber::fromNullable($valid)->value)->toBe($valid);
    });
});

it('rejects any string outside the phone format (property)', function () {
    $this->forAll(Eris\Generator\elements([
        '123456', // menos de 7 caracteres
        str_repeat('1', 21), // mas de 20 caracteres
        'abc-def-ghij',
        '555.123.4567', // punto no permitido
    ]))->then(function (string $invalid) {
        expect(fn () => PhoneNumber::fromNullable($invalid))->toThrow(PhoneNumberException::class);
    });
});

it('treats null and empty string as absent (example)', function () {
    expect(PhoneNumber::fromNullable(null)->value)->toBeNull();
    expect(PhoneNumber::fromNullable('')->value)->toBeNull();
    expect(PhoneNumber::fromNullable('   ')->value)->toBeNull();
});

it('accepts a known valid phone number (example)', function () {
    expect(PhoneNumber::fromNullable('+1 555 0100')->value)->toBe('+1 555 0100');
});
