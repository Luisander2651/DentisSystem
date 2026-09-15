<?php

declare(strict_types=1);

use App\Modules\Users\Domain\Exceptions\ValueObjects\PasswordException;
use App\Modules\Users\Domain\ValueObjects\PasswordHash;

it('produces a 60-character bcrypt hash', function () {
    $hash = PasswordHash::createFromPlainText('Sup3rSecret!');

    expect(strlen($hash->value))->toBe(60)
        ->and(password_get_info($hash->value)['algo'])->toBe(PASSWORD_BCRYPT);
});

it('verifies the right plaintext and rejects the wrong one', function () {
    $hash = PasswordHash::createFromPlainText('Sup3rSecret!');

    expect($hash->verify('Sup3rSecret!'))->toBeTrue()
        ->and($hash->verify('sup3rsecret!'))->toBeFalse();
});

it('accepts a password shorter than the policy, because the policy lives in the FormRequest', function () {
    // BR-11 is enforced at the HTTP boundary, not here: fromString() has to keep
    // readmitting every hash already stored, whatever policy produced it.
    expect(PasswordHash::createFromPlainText('a')->verify('a'))->toBeTrue();
});

it('rejects anything that is not a bcrypt hash', function (string $candidate) {
    expect(fn () => PasswordHash::fromString($candidate))->toThrow(PasswordException::class);
})->with(['', 'plaintext', str_repeat('x', 60)]);
