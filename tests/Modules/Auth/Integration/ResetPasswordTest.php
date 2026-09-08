<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Tests\Modules\Auth\Integration\AuthIntegrationTestCase;

uses(AuthIntegrationTestCase::class);

// --- Happy paths ---

it('resets a patient password so the new one authenticates and the old one does not', function () {
    $patient = $this->createPatient(['password' => Hash::make('OldPassword1!')]);
    $token = $this->requestResetTokenViaApi($patient->email);

    $this->postJson($this->resetUrl(), [
        'token' => $token,
        'new_password' => 'BrandNewPass1!',
    ])->assertOk()->assertJson(['message' => 'Password reset successful']);

    $this->postJson($this->loginUrl(), [
        'email' => $patient->email,
        'password' => 'BrandNewPass1!',
    ])->assertOk();

    $this->postJson($this->loginUrl(), [
        'email' => $patient->email,
        'password' => 'OldPassword1!',
    ])->assertUnauthorized();
});

it('resets a staff password', function () {
    $user = $this->createUserWithRole('administrador', ['password' => Hash::make('OldPassword1!')]);
    $token = $this->requestResetTokenViaApi($user->email);

    $this->postJson($this->resetUrl(), [
        'token' => $token,
        'new_password' => 'BrandNewPass1!',
    ])->assertOk();

    $this->postJson($this->loginUrl(), [
        'email' => $user->email,
        'password' => 'BrandNewPass1!',
    ])->assertOk();
});

// --- BR-18: the token travels in the body ---

it('accepts the token from the request body, not the query string (BR-18)', function () {
    $patient = $this->createPatient(['password' => Hash::make('OldPassword1!')]);
    $token = $this->generateResetTokenFor($patient->email);

    // Same token supplied only as a query parameter is now ignored: the body is
    // the sole source, so validation fails before the use case runs.
    $this->postJson($this->resetUrl()."?token={$token}", [
        'new_password' => 'BrandNewPass1!',
    ])->assertStatus(422)->assertJsonValidationErrors('token');

    $this->postJson($this->resetUrl(), [
        'token' => $token,
        'new_password' => 'BrandNewPass1!',
    ])->assertOk();
});

// --- Token lifecycle (BR-10, BR-17) ---

it('rejects a token that does not exist with 404', function () {
    $this->postJson($this->resetUrl(), [
        'token' => str_repeat('z', 40),
        'new_password' => 'BrandNewPass1!',
    ])->assertNotFound()
        ->assertJson(['error' => 'The provided reset token was not found or has expired.']);
});

it('rejects an expired token with 404', function () {
    $patient = $this->createPatient();
    $token = $this->generateResetTokenFor($patient->email);

    $this->forgetResetToken($token);

    $this->postJson($this->resetUrl(), [
        'token' => $token,
        'new_password' => 'BrandNewPass1!',
    ])->assertNotFound();
});

it('rejects a token that was already consumed with 404 TokenNotFound (BR-17)', function () {
    $patient = $this->createPatient(['password' => Hash::make('OldPassword1!')]);
    $token = $this->generateResetTokenFor($patient->email);

    $this->postJson($this->resetUrl(), [
        'token' => $token,
        'new_password' => 'BrandNewPass1!',
    ])->assertOk();

    // BR-17 deletes the key outright, so the second attempt reports "not found"
    // rather than the previous "already used" message.
    $this->postJson($this->resetUrl(), [
        'token' => $token,
        'new_password' => 'AnotherPass1!',
    ])->assertNotFound()
        ->assertJson(['error' => 'The provided reset token was not found or has expired.']);
});

it('removes the consumed token from Redis (BR-17)', function () {
    $patient = $this->createPatient();
    $token = $this->generateResetTokenFor($patient->email);

    $this->postJson($this->resetUrl(), [
        'token' => $token,
        'new_password' => 'BrandNewPass1!',
    ])->assertOk();

    expect(Redis::get($this->resetTokenKey($token)))->toBeNull();
});

it('returns 404 when the account behind the token no longer exists', function () {
    $token = $this->generateResetTokenFor('ghost@example.com');

    $this->postJson($this->resetUrl(), [
        'token' => $token,
        'new_password' => 'BrandNewPass1!',
    ])->assertNotFound()
        ->assertJson(['error' => 'The user associated with the provided email was not found.']);

    $this->forgetResetToken($token);
});

// --- BR-19: deterministic staff-first resolution ---

it('resets only the staff password when the email exists on both tables (BR-19)', function () {
    $sharedEmail = 'shared-reset@example.com';

    $staff = $this->createUserWithRole('recepcionista', [
        'email' => $sharedEmail,
        'password' => Hash::make('StaffOld1!'),
    ]);
    $patient = $this->createPatient([
        'email' => $sharedEmail,
        'password' => Hash::make('PatientOld1!'),
    ]);

    $token = $this->generateResetTokenFor($sharedEmail);

    $this->postJson($this->resetUrl(), [
        'token' => $token,
        'new_password' => 'BrandNewPass1!',
    ])->assertOk();

    expect(Hash::check('BrandNewPass1!', $staff->fresh()->password))->toBeTrue();
    expect(Hash::check('PatientOld1!', $patient->fresh()->password))->toBeTrue();
});

// --- Validation (BR-20, BR-14) ---

it('rejects a missing token with 422 (BR-20)', function () {
    $this->postJson($this->resetUrl(), ['new_password' => 'BrandNewPass1!'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('token');
});

it('rejects a new password shorter than 8 characters with 422 (BR-14)', function () {
    $patient = $this->createPatient();
    $token = $this->generateResetTokenFor($patient->email);

    $this->postJson($this->resetUrl(), [
        'token' => $token,
        'new_password' => 'short1',
    ])->assertStatus(422)->assertJsonValidationErrors('new_password');

    $this->forgetResetToken($token);
});
