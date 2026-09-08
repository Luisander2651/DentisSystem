<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Tests\Modules\Auth\Integration\AuthIntegrationTestCase;

uses(AuthIntegrationTestCase::class);

it('revokes the token and expires the cookie (BR-8)', function () {
    $patient = $this->createPatient(['password' => Hash::make('Sup3rSecret!')]);

    $token = $this->postJson($this->loginUrl(), [
        'email' => $patient->email,
        'password' => 'Sup3rSecret!',
    ])->assertOk()->getCookie('auth_token', false)->getValue();

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson($this->logoutUrl());

    $response->assertOk()->assertJson(['message' => 'Logout successful']);

    $cookie = $response->getCookie('auth_token', false);
    expect($cookie->getValue())->toBe('');
    expect($cookie->getExpiresTime())->toBeLessThan(time());

    expect($patient->tokens()->count())->toBe(0);
});

it('rejects a request carrying the revoked token afterwards', function () {
    $patient = $this->createPatient(['password' => Hash::make('Sup3rSecret!')]);

    $token = $this->postJson($this->loginUrl(), [
        'email' => $patient->email,
        'password' => 'Sup3rSecret!',
    ])->assertOk()->getCookie('auth_token', false)->getValue();

    $this->withHeader('Authorization', "Bearer {$token}")->postJson($this->logoutUrl())->assertOk();

    $this->forgetAuthState();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson($this->logoutUrl())
        ->assertUnauthorized();
});

it('rejects logging out without authentication', function () {
    $this->postJson($this->logoutUrl())->assertUnauthorized();
});

it('logs a staff member out', function () {
    $user = $this->createUserWithRole('administrador', ['password' => Hash::make('Sup3rSecret!')]);

    $token = $this->postJson($this->loginUrl(), [
        'email' => $user->email,
        'password' => 'Sup3rSecret!',
    ])->assertOk()->getCookie('auth_token', false)->getValue();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson($this->logoutUrl())
        ->assertOk();

    expect($user->tokens()->count())->toBe(0);
});
