<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Tests\Modules\Auth\Integration\AuthIntegrationTestCase;
use Tests\Support\AuthGenerators;
use Tests\Support\UsesEris;

uses(AuthIntegrationTestCase::class, UsesEris::class, AuthGenerators::class);

// P7 - invariant: a revoked token never authenticates again, for either actor type.

it('permanently invalidates every revoked token (property)', function () {
    $this->withoutRateLimiting();
    $this->limitTo(10);

    $this->forAll(Eris\Generator\elements(['patient', 'staff']))->then(function (string $actorType) {
        $actor = $actorType === 'patient'
            ? $this->createPatient(['password' => Hash::make('Sup3rSecret!')])
            : $this->createUserWithRole('recepcionista', ['password' => Hash::make('Sup3rSecret!')]);

        $login = $this->postJson($this->loginUrl(), [
            'email' => $actor->email,
            'password' => 'Sup3rSecret!',
        ])->assertOk();

        $token = $login->getCookie('auth_token', false)->getValue();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson($this->logoutUrl())
            ->assertOk();

        expect($actor->tokens()->count())->toBe(0);

        $this->forgetAuthState();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson($this->logoutUrl())
            ->assertUnauthorized();
    });
});
