<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Tests\Modules\Auth\Integration\AuthIntegrationTestCase;

uses(AuthIntegrationTestCase::class);

// --- Happy path ---

it('logs an active administrator in and returns the token in the body without a cookie', function () {
    $admin = $this->createUserWithRole('Administrador', ['password' => Hash::make('Sup3rSecret!')]);

    $response = $this->postJson($this->mobileLoginUrl(), [
        'email' => $admin->email,
        'password' => 'Sup3rSecret!',
    ]);

    $response->assertOk()
        ->assertJson(['message' => 'Login successful'])
        ->assertJsonPath('data.actorType', 'user')
        ->assertJsonPath('data.actorId', (string) $admin->id)
        ->assertJsonPath('data.email', $admin->email)
        ->assertHeader('Cache-Control', 'no-store, private');

    expect($response->json('data.token'))->toBeString()->not->toBeEmpty();
    expect($response->getCookie('auth_token', false))->toBeNull();
});

it('accepts the returned token as a bearer token', function () {
    $admin = $this->createUserWithRole('Administrador', ['password' => Hash::make('Sup3rSecret!')]);

    $token = $this->postJson($this->mobileLoginUrl(), [
        'email' => $admin->email,
        'password' => 'Sup3rSecret!',
    ])->json('data.token');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/users')
        ->assertOk();
});

// --- Who may sign in ---

it('refuses an active staff member who is not an administrator with 403 and issues no token', function (string $role) {
    $user = $this->createUserWithRole($role, ['password' => Hash::make('Sup3rSecret!')]);

    $response = $this->postJson($this->mobileLoginUrl(), [
        'email' => $user->email,
        'password' => 'Sup3rSecret!',
    ]);

    $response->assertForbidden()->assertJsonStructure(['error']);
    expect($response->json('data'))->toBeNull();
    expect($user->tokens()->count())->toBe(0);
})->with(['Doctor', 'Asistente']);

it('refuses a patient with the right password with 403 and keeps their web session', function () {
    $patient = $this->createPatient(['password' => Hash::make('Sup3rSecret!')]);
    $payload = ['email' => $patient->email, 'password' => 'Sup3rSecret!'];

    $this->postJson($this->loginUrl(), $payload)->assertOk();

    $this->postJson($this->mobileLoginUrl(), $payload)->assertForbidden();

    expect($patient->tokens()->count())->toBe(1);
});

it('rejects a wrong password with 401', function () {
    $admin = $this->createUserWithRole('Administrador', ['password' => Hash::make('Sup3rSecret!')]);

    $this->postJson($this->mobileLoginUrl(), [
        'email' => $admin->email,
        'password' => 'WrongPass1!',
    ])->assertUnauthorized();
});

it('rejects an unknown email with 401', function () {
    $this->postJson($this->mobileLoginUrl(), $this->validLoginPayload())->assertUnauthorized();
});

it('rejects an inactive administrator with 401', function () {
    $admin = $this->createUserWithRole('Administrador', [
        'password' => Hash::make('Sup3rSecret!'),
        'status' => 'inactive',
    ]);

    $this->postJson($this->mobileLoginUrl(), [
        'email' => $admin->email,
        'password' => 'Sup3rSecret!',
    ])->assertUnauthorized();

    expect($admin->tokens()->count())->toBe(0);
});

// --- Simultaneous web and mobile sessions ---

it('keeps the web session alive after a mobile login and the other way round', function () {
    $admin = $this->createUserWithRole('Administrador', ['password' => Hash::make('Sup3rSecret!')]);
    $payload = ['email' => $admin->email, 'password' => 'Sup3rSecret!'];

    $webToken = $this->postJson($this->loginUrl(), $payload)->getCookie('auth_token', false)->getValue();
    $mobileToken = $this->postJson($this->mobileLoginUrl(), $payload)->json('data.token');

    $this->withHeader('Authorization', "Bearer {$webToken}")->getJson('/api/v1/users')->assertOk();

    $secondWebToken = $this->postJson($this->loginUrl(), $payload)->getCookie('auth_token', false)->getValue();

    expect($admin->tokens()->pluck('name')->sort()->values()->all())->toBe(['user-auth', 'user-mobile']);

    $this->forgetAuthState();
    $this->withHeader('Authorization', "Bearer {$mobileToken}")->getJson('/api/v1/users')->assertOk();

    $this->forgetAuthState();
    $this->withHeader('Authorization', "Bearer {$secondWebToken}")->getJson('/api/v1/users')->assertOk();
});

it('invalidates the previous mobile token on each new mobile login', function () {
    $admin = $this->createUserWithRole('Administrador', ['password' => Hash::make('Sup3rSecret!')]);
    $payload = ['email' => $admin->email, 'password' => 'Sup3rSecret!'];

    $firstToken = $this->postJson($this->mobileLoginUrl(), $payload)->json('data.token');
    $this->postJson($this->mobileLoginUrl(), $payload)->assertOk();

    expect($admin->tokens()->count())->toBe(1);

    $this->withHeader('Authorization', "Bearer {$firstToken}")
        ->getJson('/api/v1/users')
        ->assertUnauthorized();
});

it('revokes only the mobile token when the app logs out', function () {
    $admin = $this->createUserWithRole('Administrador', ['password' => Hash::make('Sup3rSecret!')]);
    $payload = ['email' => $admin->email, 'password' => 'Sup3rSecret!'];

    $webToken = $this->postJson($this->loginUrl(), $payload)->getCookie('auth_token', false)->getValue();
    $mobileToken = $this->postJson($this->mobileLoginUrl(), $payload)->json('data.token');

    $this->withHeader('Authorization', "Bearer {$mobileToken}")
        ->postJson($this->logoutUrl())
        ->assertOk();

    expect($admin->tokens()->pluck('name')->all())->toBe(['user-auth']);

    $this->forgetAuthState();
    $this->withHeader('Authorization', "Bearer {$webToken}")->getJson('/api/v1/users')->assertOk();
});

// --- Input validation ---

it('rejects a mobile login with no body with 422', function () {
    $this->postJson($this->mobileLoginUrl(), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);
});

it('rejects a mobile login with a malformed email with 422', function () {
    $this->postJson($this->mobileLoginUrl(), ['email' => 'not-an-email', 'password' => 'Sup3rSecret!'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});
