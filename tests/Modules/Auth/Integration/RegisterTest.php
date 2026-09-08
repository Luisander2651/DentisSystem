<?php

declare(strict_types=1);

use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\PatientModel;
use Tests\Modules\Auth\Integration\AuthIntegrationTestCase;

uses(AuthIntegrationTestCase::class);

// --- Happy path ---

it('registers a patient, logs them in and returns 201 (BR-5, BR-6)', function () {
    $payload = $this->validRegisterPayload();

    $response = $this->postJson($this->registerUrl(), $payload);

    $response->assertCreated()
        ->assertJson(['message' => 'Patient registered successfully'])
        ->assertJsonPath('data.actorType', 'patient')
        ->assertJsonPath('data.email', $payload['email']);

    expect($response->json('data'))->not->toHaveKey('token');
    expect($response->getCookie('auth_token', false))->not->toBeNull();

    $patient = PatientModel::query()->where('email', $payload['email'])->firstOrFail();
    expect($patient->status)->toBe('active');
    expect($patient->role)->toBe('patient');
});

// --- BR-13: cross-table email uniqueness (new behaviour) ---

it('rejects an email already used by another patient with 409', function () {
    $existing = $this->createPatient();

    $this->postJson($this->registerUrl(), $this->validRegisterPayload([
        'email' => $existing->email,
    ]))->assertStatus(409);
});

it('rejects an email already used by a staff member with 409 (BR-13)', function () {
    $staff = $this->createUserWithRole('recepcionista');

    $this->postJson($this->registerUrl(), $this->validRegisterPayload([
        'email' => $staff->email,
    ]))->assertStatus(409);

    expect(PatientModel::query()->where('email', $staff->email)->exists())->toBeFalse();
});

it('returns the same error message whether the colliding account is a patient or staff (BR-13)', function () {
    $patient = $this->createPatient();
    $staff = $this->createUserWithRole('recepcionista');

    $viaPatient = $this->postJson($this->registerUrl(), $this->validRegisterPayload(['email' => $patient->email]));
    $viaStaff = $this->postJson($this->registerUrl(), $this->validRegisterPayload(['email' => $staff->email]));

    $viaPatient->assertStatus(409);
    $viaStaff->assertStatus(409);

    // The account type must not leak through the error text: once each response's
    // own address is masked out, the two messages must be identical.
    $normalise = fn (string $message, string $email): string => str_replace($email, '{email}', $message);

    expect($normalise($viaPatient->json('error'), $patient->email))
        ->toBe($normalise($viaStaff->json('error'), $staff->email));
});

// --- Validation (BR-20, BR-14) ---

it('rejects a mismatched confirmation with 422 (BR-20)', function () {
    $this->postJson($this->registerUrl(), $this->validRegisterPayload([
        'confirm_password' => 'a-different-password',
    ]))->assertStatus(422)->assertJsonValidationErrors('confirm_password');
});

it('rejects a password shorter than 8 characters with 422 (BR-14)', function () {
    $this->postJson($this->registerUrl(), $this->validRegisterPayload([
        'password' => 'short1',
        'confirm_password' => 'short1',
    ]))->assertStatus(422)->assertJsonValidationErrors('password');
});

it('rejects a malformed email with 422 (BR-20)', function () {
    $this->postJson($this->registerUrl(), $this->validRegisterPayload([
        'email' => 'not-an-email',
    ]))->assertStatus(422)->assertJsonValidationErrors('email');
});

it('rejects an empty body with 422 listing every required field (BR-20)', function () {
    $this->postJson($this->registerUrl(), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['first_name', 'last_name', 'email', 'password', 'confirm_password']);
});

// --- 400: value object rejection that survives validation ---

it('rejects a name that violates PatientName with 400', function () {
    // Each field passes RegisterRequest's max:50 on its own, but PatientName caps
    // the COMBINED name at 50 characters, so the value object rejects it after
    // validation has already passed.
    $this->postJson($this->registerUrl(), $this->validRegisterPayload([
        'first_name' => str_repeat('A', 30),
        'last_name' => str_repeat('B', 30),
    ]))->assertStatus(400);
});
