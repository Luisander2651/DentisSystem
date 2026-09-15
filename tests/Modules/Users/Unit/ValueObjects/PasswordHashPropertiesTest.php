<?php

declare(strict_types=1);

use App\Modules\Users\Domain\ValueObjects\PasswordHash;
use Tests\Support\UserGenerators;
use Tests\Support\UsesEris;

uses(UsesEris::class, UserGenerators::class);

// P6 and P7. Iterations are capped: createFromPlainText() calls password_hash()
// directly at bcrypt's default cost, which the BCRYPT_ROUNDS=4 test setting does not
// lower, so each case is genuinely expensive.

it('verifies the original plaintext (property)', function () {
    $this->limitTo(15);

    $this->forAll($this->validUserPasswordGenerator())->then(function (string $plain) {
        expect(PasswordHash::createFromPlainText($plain)->verify($plain))->toBeTrue();
    });
});

it('rejects any altered plaintext (property)', function () {
    $this->limitTo(15);

    $this->forAll($this->validUserPasswordGenerator())->then(function (string $plain) {
        // Prepended, not appended: bcrypt truncates at 72 bytes, so a suffix on a
        // 72-byte password would be dropped and the altered value would verify.
        expect(PasswordHash::createFromPlainText($plain)->verify('X'.$plain))->toBeFalse();
    });
});

// P7 - every hash this VO produces is one it can read back. The constructor demands
// exactly 60 characters and PASSWORD_BCRYPT, so a drift between the two would make
// freshly created users unreadable from the database.

it('always produces a hash that fromString readmits (property)', function () {
    $this->limitTo(15);

    $this->forAll($this->validUserPasswordGenerator())->then(function (string $plain) {
        $hash = PasswordHash::createFromPlainText($plain);

        expect(strlen($hash->value))->toBe(60)
            ->and(password_get_info($hash->value)['algo'])->toBe(PASSWORD_BCRYPT)
            ->and(PasswordHash::fromString($hash->value)->verify($plain))->toBeTrue();
    });
});

it('never reuses the same hash for the same plaintext (property)', function () {
    $this->limitTo(10);

    $this->forAll($this->validUserPasswordGenerator())->then(function (string $plain) {
        $first = PasswordHash::createFromPlainText($plain);
        $second = PasswordHash::createFromPlainText($plain);

        expect($first->value)->not->toBe($second->value)
            ->and($first->verify($plain))->toBeTrue()
            ->and($second->verify($plain))->toBeTrue();
    });
});
