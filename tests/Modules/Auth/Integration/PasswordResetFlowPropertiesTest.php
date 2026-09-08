<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Events\SendEmailForChangePasswordEvent;
use Illuminate\Support\Facades\Event;
use Tests\Modules\Auth\Integration\AuthIntegrationTestCase;
use Tests\Support\AuthGenerators;
use Tests\Support\UsesEris;

uses(AuthIntegrationTestCase::class, UsesEris::class, AuthGenerators::class);

// P8 - non-enumeration invariant on the reset request (BR-9).

it('never dispatches an event and always answers the same 200 for unknown emails (property)', function () {
    $this->withoutRateLimiting();
    $this->limitTo(15);

    Event::fake([SendEmailForChangePasswordEvent::class]);

    $known = $this->createPatient();
    $baseline = $this->postJson($this->sendResetUrl(), ['email' => $known->email])->assertOk();

    $this->forAll($this->validEmailGenerator())->then(function (string $unknownEmail) use ($baseline) {
        $response = $this->postJson($this->sendResetUrl(), ['email' => $unknownEmail]);

        $response->assertOk();
        expect($response->json())->toBe($baseline->json());
    });

    Event::assertDispatchedTimes(SendEmailForChangePasswordEvent::class, 1);
});

// P9 - round-trip: whatever is registered can immediately authenticate.

it('lets every successfully registered patient log in with the same credentials (property)', function () {
    $this->withoutRateLimiting();
    $this->limitTo(10);

    $this->forAll($this->validPatientNameGenerator())->then(function (array $name) {
        [$firstName, $lastName] = $name;

        $payload = $this->validRegisterPayload([
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);

        $this->postJson($this->registerUrl(), $payload)->assertCreated();

        $login = $this->postJson($this->loginUrl(), [
            'email' => $payload['email'],
            'password' => $payload['password'],
        ]);

        $login->assertOk();
        expect($login->json('data.actorType'))->toBe('patient');
        expect($login->json('data.email'))->toBe($payload['email']);
        expect($login->json('data.name'))->not->toBeEmpty();
    });
});

// P10 - range invariant of the BR-14 password policy.

it('always rejects passwords shorter than 8 characters on register and reset (property)', function () {
    $this->withoutRateLimiting();
    $this->limitTo(15);

    $patient = $this->createPatient();
    $token = $this->generateResetTokenFor($patient->email);

    $this->forAll($this->tooShortPasswordGenerator())->then(function (string $weak) use ($token) {
        $this->postJson($this->registerUrl(), $this->validRegisterPayload([
            'password' => $weak,
            'confirm_password' => $weak,
        ]))->assertStatus(422)->assertJsonValidationErrors('password');

        $this->postJson($this->resetUrl(), [
            'token' => $token,
            'new_password' => $weak,
        ])->assertStatus(422)->assertJsonValidationErrors('new_password');
    });

    $this->forgetResetToken($token);
});

it('never rejects a policy-compliant password for length (property)', function () {
    $this->withoutRateLimiting();
    $this->limitTo(10);

    $this->forAll($this->validPasswordGenerator())->then(function (string $strong) {
        $this->postJson($this->registerUrl(), $this->validRegisterPayload([
            'password' => $strong,
            'confirm_password' => $strong,
        ]))->assertCreated();
    });
});
