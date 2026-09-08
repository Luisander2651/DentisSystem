<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Tests\Modules\Auth\Integration\AuthIntegrationTestCase;

uses(AuthIntegrationTestCase::class);

// --- Happy paths ---

it('logs a patient in and returns the token only in an httpOnly cookie', function () {
    $patient = $this->createPatient(['password' => Hash::make('Sup3rSecret!')]);

    $response = $this->postJson($this->loginUrl(), [
        'email' => $patient->email,
        'password' => 'Sup3rSecret!',
    ]);

    $response->assertOk()
        ->assertJson(['message' => 'Login successful'])
        ->assertJsonPath('data.actorType', 'patient')
        ->assertJsonPath('data.email', $patient->email);

    // BR-4: the token is never part of the JSON body.
    expect($response->json('data'))->not->toHaveKey('token');

    $cookie = $response->getCookie('auth_token', false);
    expect($cookie)->not->toBeNull();
    expect($cookie->isHttpOnly())->toBeTrue();
    expect($cookie->getValue())->not->toBeEmpty();
});

it('logs a staff member in', function () {
    $user = $this->createUserWithRole('administrador', ['password' => Hash::make('Sup3rSecret!')]);

    $this->postJson($this->loginUrl(), [
        'email' => $user->email,
        'password' => 'Sup3rSecret!',
    ])->assertOk()->assertJsonPath('data.actorType', 'user');
});

// --- BR-1: staff is resolved first for a shared email ---

it('resolves a shared email to the staff account first (BR-1)', function () {
    $sharedEmail = 'shared@example.com';

    $this->createUserWithRole('recepcionista', [
        'email' => $sharedEmail,
        'password' => Hash::make('StaffPass1!'),
    ]);
    $this->createPatient([
        'email' => $sharedEmail,
        'password' => Hash::make('PatientPass1!'),
    ]);

    $this->postJson($this->loginUrl(), [
        'email' => $sharedEmail,
        'password' => 'StaffPass1!',
    ])->assertOk()->assertJsonPath('data.actorType', 'user');
});

it('still authenticates the patient behind a shared email with their own password (BR-1)', function () {
    $sharedEmail = 'shared-fallthrough@example.com';

    $this->createUserWithRole('recepcionista', [
        'email' => $sharedEmail,
        'password' => Hash::make('StaffPass1!'),
    ]);
    $this->createPatient([
        'email' => $sharedEmail,
        'password' => Hash::make('PatientPass1!'),
    ]);

    // Precedence only decides which record is TRIED first. A failed password check
    // against the staff row falls through to the patient row, so the patient is not
    // locked out of logging in.
    $this->postJson($this->loginUrl(), [
        'email' => $sharedEmail,
        'password' => 'PatientPass1!',
    ])->assertOk()->assertJsonPath('data.actorType', 'patient');
});

// --- Failure paths ---

it('rejects a wrong password with 401', function () {
    $patient = $this->createPatient(['password' => Hash::make('Sup3rSecret!')]);

    $this->postJson($this->loginUrl(), [
        'email' => $patient->email,
        'password' => 'wrong-password',
    ])->assertUnauthorized()->assertJson(['error' => 'Invalid credentials.']);
});

it('rejects an unknown email with 401', function () {
    $this->postJson($this->loginUrl(), $this->validLoginPayload())
        ->assertUnauthorized()
        ->assertJson(['error' => 'Invalid credentials.']);
});

it('rejects an inactive patient with 401 inactiveAccount', function () {
    $patient = $this->createPatient([
        'password' => Hash::make('Sup3rSecret!'),
        'status' => 'inactive',
    ]);

    $this->postJson($this->loginUrl(), [
        'email' => $patient->email,
        'password' => 'Sup3rSecret!',
    ])->assertUnauthorized()->assertJson(['error' => 'The patient account is inactive.']);
});

it('rejects an inactive staff member with 401 inactiveAccount', function () {
    $user = $this->createUserWithRole('administrador', [
        'password' => Hash::make('Sup3rSecret!'),
        'status' => 'inactive',
    ]);

    $this->postJson($this->loginUrl(), [
        'email' => $user->email,
        'password' => 'Sup3rSecret!',
    ])->assertUnauthorized()->assertJson(['error' => 'The user account is inactive.']);
});

it('checks the password before the account status (BR-2)', function () {
    $patient = $this->createPatient([
        'password' => Hash::make('Sup3rSecret!'),
        'status' => 'inactive',
    ]);

    // Wrong password on an inactive account still reports invalid credentials,
    // never that the account exists but is inactive.
    $this->postJson($this->loginUrl(), [
        'email' => $patient->email,
        'password' => 'wrong-password',
    ])->assertUnauthorized()->assertJson(['error' => 'Invalid credentials.']);
});

// --- BR-3: single active token ---

it('invalidates the previous token on each new login (BR-3)', function () {
    $patient = $this->createPatient(['password' => Hash::make('Sup3rSecret!')]);
    $payload = ['email' => $patient->email, 'password' => 'Sup3rSecret!'];

    $first = $this->postJson($this->loginUrl(), $payload)->assertOk();
    $firstToken = $first->getCookie('auth_token', false)->getValue();

    $this->postJson($this->loginUrl(), $payload)->assertOk();

    expect($patient->tokens()->count())->toBe(1);

    $this->withHeader('Authorization', "Bearer {$firstToken}")
        ->postJson($this->logoutUrl())
        ->assertUnauthorized();
});

// --- BR-20: input validation ---

it('rejects a login with no body with 422 (BR-20)', function () {
    $this->postJson($this->loginUrl(), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password']);
});

it('rejects a malformed email with 422 (BR-20)', function () {
    $this->postJson($this->loginUrl(), [
        'email' => 'not-an-email',
        'password' => 'Sup3rSecret!',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});
