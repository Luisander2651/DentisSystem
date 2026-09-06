<?php

declare(strict_types=1);

use App\Modules\Patients\Domain\Exceptions\ValueObjects\Addresses\PostalCodeException;
use App\Modules\Patients\Domain\ValueObjects\Addresses\PostalCode;
use Tests\Support\UsesEris;

uses(UsesEris::class);

it('accepts any string matching the postal code format (property)', function () {
    $this->forAll(Eris\Generator\elements([
        '123',
        '62704',
        'SW1A 1AA',
        'A1B-2C3',
    ]))->then(function (string $valid) {
        expect(PostalCode::fromNullable($valid)->value)->toBe($valid);
    });
});

it('rejects any string outside the postal code format (property)', function () {
    $this->forAll(Eris\Generator\elements([
        '12', // menos de 3 caracteres
        str_repeat('1', 13), // mas de 12 caracteres
        'AB#123', // caracter no permitido
        'código!',
    ]))->then(function (string $invalid) {
        expect(fn () => PostalCode::fromNullable($invalid))->toThrow(PostalCodeException::class);
    });
});

it('treats null and empty string as absent (example)', function () {
    expect(PostalCode::fromNullable(null)->value)->toBeNull();
    expect(PostalCode::fromNullable('')->value)->toBeNull();
});

it('accepts a known valid postal code (example)', function () {
    expect(PostalCode::fromNullable('62704')->value)->toBe('62704');
});
