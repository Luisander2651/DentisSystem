<?php

declare(strict_types=1);

use App\Modules\Patients\Domain\ValueObjects\Patients\PasswordHash as PatientPasswordHash;
use App\Modules\Users\Domain\ValueObjects\PasswordHash as UserPasswordHash;
use Tests\Support\AuthGenerators;
use Tests\Support\UsesEris;

uses(UsesEris::class, AuthGenerators::class);

// P1 - round-trip: hashing then verifying the same plaintext always succeeds.
// Iterations are capped: createFromPlainText() calls password_hash() directly at
// bcrypt's default cost, which the BCRYPT_ROUNDS=4 test setting does not lower.

it('verifies the original plaintext for a patient password hash (property)', function () {
    $this->limitTo(15);

    $this->forAll($this->validPasswordGenerator())->then(function (string $plain) {
        expect(PatientPasswordHash::createFromPlainText($plain)->verify($plain))->toBeTrue();
    });
});

it('verifies the original plaintext for a user password hash (property)', function () {
    $this->limitTo(15);

    $this->forAll($this->validPasswordGenerator())->then(function (string $plain) {
        expect(UserPasswordHash::createFromPlainText($plain)->verify($plain))->toBeTrue();
    });
});

it('rejects any altered plaintext (property)', function () {
    $this->limitTo(15);

    $this->forAll($this->validPasswordGenerator())->then(function (string $plain) {
        // The alteration is prepended, not appended: bcrypt truncates at 72 bytes,
        // so a suffix on a 72-byte password would be silently dropped and the
        // altered value would verify as correct.
        expect(PatientPasswordHash::createFromPlainText($plain)->verify('X'.$plain))->toBeFalse();
    });
});

it('never reuses the same hash for the same plaintext (property)', function () {
    $this->limitTo(15);

    $this->forAll($this->validPasswordGenerator())->then(function (string $plain) {
        $first = PatientPasswordHash::createFromPlainText($plain);
        $second = PatientPasswordHash::createFromPlainText($plain);

        expect($first->value)->not->toBe($second->value);
        expect($first->verify($plain))->toBeTrue();
        expect($second->verify($plain))->toBeTrue();
    });
});
