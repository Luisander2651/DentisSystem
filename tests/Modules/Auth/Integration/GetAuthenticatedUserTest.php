<?php

declare(strict_types=1);

use App\Modules\Users\Domain\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Tests\Modules\Auth\Integration\AuthIntegrationTestCase;

uses(AuthIntegrationTestCase::class);

// --- Happy path ---

it('returns the active administrator who owns the mobile token', function () {
    $admin = $this->createUserWithRole('Administrador', [
        'first_name' => 'Ana',
        'last_name' => 'López',
        'password' => Hash::make('Sup3rSecret!'),
    ]);
    $token = $this->mobileTokenFor($admin);

    $response = $this->withHeader('Authorization', "Bearer {$token}")->getJson($this->meUrl());

    $response->assertOk()
        ->assertExactJson(['data' => [
            'id' => (string) $admin->id,
            'first_name' => 'Ana',
            'last_name' => 'López',
            'email' => $admin->email,
            'role_id' => 'Administrador',
            'status' => 'active',
        ]])
        ->assertHeader('Cache-Control', 'no-store, private');
});

it('also answers for the token of the web session', function () {
    $admin = $this->createUserWithRole('Administrador', ['password' => Hash::make('Sup3rSecret!')]);
    $token = $this->postJson($this->loginUrl(), [
        'email' => $admin->email,
        'password' => 'Sup3rSecret!',
    ])->getCookie('auth_token', false)->getValue();
    $this->forgetAuthState();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson($this->meUrl())
        ->assertOk()
        ->assertJsonPath('data.id', (string) $admin->id);
});

// --- Invalid session: 401 ---

it('answers 401 without a token', function () {
    $this->getJson($this->meUrl())->assertUnauthorized();
});

it('answers 401 for a token that does not exist', function () {
    $this->withHeader('Authorization', 'Bearer 1|not-a-real-token')
        ->getJson($this->meUrl())
        ->assertUnauthorized();
});

it('answers 401 once the mobile session is closed', function () {
    $admin = $this->createUserWithRole('Administrador', ['password' => Hash::make('Sup3rSecret!')]);
    $token = $this->mobileTokenFor($admin);

    $this->withHeader('Authorization', "Bearer {$token}")->postJson($this->logoutUrl())->assertOk();
    $this->forgetAuthState();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson($this->meUrl())
        ->assertUnauthorized();
});

it('answers 401 once the mobile session expires after 24 hours', function () {
    $admin = $this->createUserWithRole('Administrador', ['password' => Hash::make('Sup3rSecret!')]);
    $token = $this->mobileTokenFor($admin);

    $this->travel(24 * 60 + 1)->minutes();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson($this->meUrl())
        ->assertUnauthorized();
});

// --- No longer an active administrator: 403 ---

it('answers 403 when the administrator was deactivated after signing in', function () {
    $admin = $this->createUserWithRole('Administrador', ['password' => Hash::make('Sup3rSecret!')]);
    $token = $this->mobileTokenFor($admin);
    $admin->update(['status' => 'inactive']);
    $this->forgetAuthState();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson($this->meUrl())
        ->assertForbidden()
        ->assertJsonStructure(['error'])
        ->assertJsonMissingPath('data');
});

it('answers 403 when the administrator lost the role after signing in', function () {
    $admin = $this->createUserWithRole('Administrador', ['password' => Hash::make('Sup3rSecret!')]);
    $token = $this->mobileTokenFor($admin);
    $admin->update(['role_id' => $this->createRole('Doctor')->id]);
    $this->forgetAuthState();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson($this->meUrl())
        ->assertForbidden()
        ->assertJsonMissingPath('data');
});

it('answers 403 to an active staff member who is not an administrator', function (string $role) {
    $this->actingAsNonAdminUser($role);

    $this->getJson($this->meUrl())
        ->assertForbidden()
        ->assertJsonMissingPath('data');
})->with(['Doctor', 'Asistente']);

it('answers 403 to a patient', function () {
    $this->actingAsPatient();

    $this->getJson($this->meUrl())
        ->assertForbidden()
        ->assertJsonMissingPath('data');
});

// --- Unexpected failure ---

it('answers a generic 500 when reading the user fails unexpectedly', function () {
    $this->actingAsAdmin();
    $this->mock(UserRepositoryInterface::class)
        ->shouldReceive('findById')->andThrow(new RuntimeException('detalle interno'));

    $this->getJson($this->meUrl())
        ->assertStatus(500)
        ->assertExactJson(['error' => 'Internal server error']);
});
