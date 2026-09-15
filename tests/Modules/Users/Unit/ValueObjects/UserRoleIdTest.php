<?php

declare(strict_types=1);

use App\Modules\Users\Domain\Exceptions\ValueObjects\UserRoleException;
use App\Modules\Users\Domain\ValueObjects\UserRoleId;

it('exposes the three canonical literals in table order', function () {
    expect(UserRoleId::all())->toBe(['Administrador', 'Asistente', 'Doctor']);
});

it('canonicalises any capitalisation to the literal the roles table stores', function (string $input, string $expected) {
    expect((new UserRoleId($input))->value)->toBe($expected);
})->with([
    ['administrador', 'Administrador'],
    ['ADMINISTRADOR', 'Administrador'],
    ['  Asistente  ', 'Asistente'],
    ['dOcToR', 'Doctor'],
]);

it('rejects the pre-BR-16 vocabulary', function (string $legacy) {
    // `admin` and `asistent` were the ONLY accepted values before BR-16, and they matched
    // nothing in the roles table. They are now invalid, which is why the frontend had to
    // be updated in the same unit.
    expect(fn () => new UserRoleId($legacy))->toThrow(UserRoleException::class);
})->with(['admin', 'asistent']);

it('answers exactly one role predicate', function (string $literal) {
    $role = new UserRoleId($literal);
    $matches = array_filter([$role->isAdministrador(), $role->isAsistente(), $role->isDoctor()]);

    expect($matches)->toHaveCount(1);
})->with(['Administrador', 'Asistente', 'Doctor']);

it('compares by value', function () {
    expect(UserRoleId::administrador()->equals(new UserRoleId('ADMINISTRADOR')))->toBeTrue()
        ->and(UserRoleId::administrador()->equals(UserRoleId::doctor()))->toBeFalse();
});

it('names the offending literal in the exception message', function () {
    expect(fn () => new UserRoleId('recepcionista'))
        ->toThrow(UserRoleException::class, 'The user role ID recepcionista has an invalid format.');
});
