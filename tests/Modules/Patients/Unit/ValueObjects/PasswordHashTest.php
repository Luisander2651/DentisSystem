<?php

declare(strict_types=1);

use App\Modules\Patients\Domain\Exceptions\ValueObjects\Patients\PasswordException;
use App\Modules\Patients\Domain\ValueObjects\Patients\PasswordHash;
use Tests\Support\UsesEris;

uses(UsesEris::class);

it('createFromPlainText always produces a 60-char bcrypt hash (property)', function () {
    $this->forAll(Eris\Generator\elements([
        'password',
        'Sup3rSecret!',
        str_repeat('x', 3),
        str_repeat('y', 72),
        'contraseña con acentos',
        '   leading and trailing spaces   ',
    ]))->then(function (string $plain) {
        $hash = PasswordHash::createFromPlainText($plain);

        expect($hash->value)->toHaveLength(60);
        expect(password_get_info($hash->value)['algo'])->toBe(PASSWORD_BCRYPT);
    });
});

it('verify() returns true only for the original plaintext (property)', function () {
    $this->forAll(Eris\Generator\elements([
        'password', 'Sup3rSecret!', 'another-one',
    ]))->then(function (string $plain) {
        $hash = PasswordHash::createFromPlainText($plain);

        expect($hash->verify($plain))->toBeTrue();
        expect($hash->verify($plain.'-wrong'))->toBeFalse();
    });
});

it('rejects a string that is not a valid bcrypt hash (example)', function () {
    expect(fn () => PasswordHash::fromString('not-a-bcrypt-hash'))
        ->toThrow(PasswordException::class);
});

it('fromString round-trips an already-hashed value (example)', function () {
    $original = PasswordHash::createFromPlainText('password');
    $rehydrated = PasswordHash::fromString($original->value);

    expect($rehydrated->value)->toBe($original->value);
    expect($rehydrated->verify('password'))->toBeTrue();
});
