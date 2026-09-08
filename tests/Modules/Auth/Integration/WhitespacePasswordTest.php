<?php

declare(strict_types=1);

use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\PatientModel;
use Illuminate\Support\Facades\Hash;
use Tests\Modules\Auth\Integration\AuthIntegrationTestCase;
use Tests\Support\AuthGenerators;
use Tests\Support\UsesEris;

uses(AuthIntegrationTestCase::class, UsesEris::class, AuthGenerators::class);

/**
 * BR-23 (finding 11, surfaced by the property-based suite).
 *
 * Leading and trailing whitespace is trimmed consistently across `password`,
 * `confirm_password` and `new_password`. Internal whitespace is untouched.
 */

// --- Registration no longer rejects a padded password ---

it('registers a password padded with whitespace (BR-23)', function () {
    $this->withoutRateLimiting();
    $this->limitTo(4);

    $this->forAll($this->paddedPasswordGenerator())->then(function (string $padded) {
        $payload = $this->validRegisterPayload([
            'password' => $padded,
            'confirm_password' => $padded,
        ]);

        $this->postJson($this->registerUrl(), $payload)->assertCreated();

        // Both the padded and the trimmed form authenticate: they normalise to
        // the same value, so it no longer matters how the text reached the field.
        $this->postJson($this->loginUrl(), [
            'email' => $payload['email'],
            'password' => $padded,
        ])->assertOk();

        $this->forgetAuthState();

        $this->postJson($this->loginUrl(), [
            'email' => $payload['email'],
            'password' => trim($padded),
        ])->assertOk();
    });
});

// --- Reset stores and compares the same value ---

it('lets a padded password authenticate after a reset (BR-23)', function () {
    $patient = $this->createPatient(['password' => Hash::make('OldPassword1!')]);
    $token = $this->generateResetTokenFor($patient->email);

    $padded = '   Sup3rSecret!   ';

    $this->postJson($this->resetUrl(), [
        'token' => $token,
        'new_password' => $padded,
    ])->assertOk();

    $this->postJson($this->loginUrl(), [
        'email' => $patient->email,
        'password' => $padded,
    ])->assertOk();
});

// --- Retroactive rescue of accounts locked out by the old behaviour ---

it('rescues an account whose stored hash is the trimmed value (BR-23)', function () {
    // Reproduces the pre-fix state: the reset trimmed `new_password` before
    // hashing, so the stored hash belongs to the trimmed string while the user
    // keeps typing the padded one. Before BR-23 that was a permanent lockout.
    $patient = $this->createPatient(['password' => Hash::make('Sup3rSecret!')]);

    $this->postJson($this->loginUrl(), [
        'email' => $patient->email,
        'password' => '   Sup3rSecret!   ',
    ])->assertOk();
});

// --- Internal whitespace is still part of the password ---

it('preserves whitespace inside the password (BR-23)', function () {
    $payload = $this->validRegisterPayload([
        'password' => '  Mi Clave 123  ',
        'confirm_password' => '  Mi Clave 123  ',
    ]);

    $this->postJson($this->registerUrl(), $payload)->assertCreated();

    $patient = PatientModel::query()->where('email', $payload['email'])->firstOrFail();
    expect(Hash::check('Mi Clave 123', $patient->password))->toBeTrue();

    // Dropping the internal space is a different password and must not authenticate.
    $this->postJson($this->loginUrl(), [
        'email' => $payload['email'],
        'password' => 'MiClave123',
    ])->assertUnauthorized();
});
