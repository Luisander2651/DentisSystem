<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Tests\Modules\Auth\Integration\AuthIntegrationTestCase;
use Tests\Support\AuthGenerators;
use Tests\Support\UsesEris;

uses(AuthIntegrationTestCase::class, UsesEris::class, AuthGenerators::class);

// P5 - non-enumeration invariant: a failed login looks identical whether or not
// the account exists.

it('returns the same 401 payload for an unknown email and for a wrong password (property)', function () {
    $this->withoutRateLimiting();
    $this->limitTo(15);

    $existing = $this->createPatient(['password' => Hash::make('Sup3rSecret!')]);

    $this->forAll($this->validEmailGenerator())->then(function (string $unknownEmail) use ($existing) {
        $unknown = $this->postJson($this->loginUrl(), [
            'email' => $unknownEmail,
            'password' => 'Sup3rSecret!',
        ]);

        $wrongPassword = $this->postJson($this->loginUrl(), [
            'email' => $existing->email,
            'password' => 'definitely-not-the-password',
        ]);

        $unknown->assertUnauthorized();
        $wrongPassword->assertUnauthorized();
        expect($unknown->json('error'))->toBe($wrongPassword->json('error'));
    });
});

// P6 - single active token invariant (BR-3).

it('leaves exactly one live token after N consecutive logins, and only the last one authenticates (property)', function () {
    $this->withoutRateLimiting();
    $this->limitTo(8);

    $this->forAll(Eris\Generator\choose(2, 5))->then(function (int $logins) {
        $patient = $this->createPatient(['password' => Hash::make('Sup3rSecret!')]);
        $payload = ['email' => $patient->email, 'password' => 'Sup3rSecret!'];

        $cookies = [];

        for ($i = 0; $i < $logins; $i++) {
            $response = $this->postJson($this->loginUrl(), $payload);
            $response->assertOk();
            $cookies[] = $response->getCookie('auth_token', false)->getValue();
        }

        expect($patient->tokens()->count())->toBe(1);

        $last = array_pop($cookies);

        foreach ($cookies as $stale) {
            // Eris replays this closure inside a single test method, so the guard
            // still holds the user it cached on the previous iteration's
            // successful request. It has to be cleared before each check.
            $this->forgetAuthState();

            $this->withHeader('Authorization', "Bearer {$stale}")
                ->postJson($this->logoutUrl())
                ->assertUnauthorized();
        }

        $this->forgetAuthState();

        $this->withHeader('Authorization', "Bearer {$last}")
            ->postJson($this->logoutUrl())
            ->assertOk();
    });
});
