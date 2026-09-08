<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Events\SendEmailForChangePasswordEvent;
use Illuminate\Support\Facades\Event;
use Tests\Modules\Auth\Integration\AuthIntegrationTestCase;

uses(AuthIntegrationTestCase::class);

it('dispatches the reset event for a patient', function () {
    Event::fake([SendEmailForChangePasswordEvent::class]);
    $patient = $this->createPatient();

    $this->postJson($this->sendResetUrl(), ['email' => $patient->email])->assertOk();

    Event::assertDispatched(SendEmailForChangePasswordEvent::class, function (SendEmailForChangePasswordEvent $event) use ($patient) {
        return $event->customerEmail === $patient->email && $event->token !== '';
    });
});

it('dispatches the reset event for a staff member', function () {
    Event::fake([SendEmailForChangePasswordEvent::class]);
    $user = $this->createUserWithRole('administrador');

    $this->postJson($this->sendResetUrl(), ['email' => $user->email])->assertOk();

    Event::assertDispatched(SendEmailForChangePasswordEvent::class, function (SendEmailForChangePasswordEvent $event) use ($user) {
        return $event->customerEmail === $user->email;
    });
});

it('answers a neutral 200 without dispatching anything for an unknown email (BR-9)', function () {
    Event::fake([SendEmailForChangePasswordEvent::class]);
    $patient = $this->createPatient();

    $known = $this->postJson($this->sendResetUrl(), ['email' => $patient->email])->assertOk();
    $unknown = $this->postJson($this->sendResetUrl(), ['email' => 'nobody@example.com'])->assertOk();

    expect($unknown->json())->toBe($known->json());
    Event::assertDispatchedTimes(SendEmailForChangePasswordEvent::class, 1);
});

// --- BR-16: no more 500s from a bad email ---

it('rejects a malformed email with 422 instead of 500 (BR-16)', function () {
    $this->postJson($this->sendResetUrl(), ['email' => 'not-an-email'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

it('rejects a missing email with 422 instead of 500 (BR-16)', function () {
    $this->postJson($this->sendResetUrl(), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

// --- FR4: the Brevo boundary is never reached for real ---

it('carries everything the Brevo listener needs without touching the SDK (FR4)', function () {
    $patient = $this->createPatient();

    $this->postJson($this->sendResetUrl(), ['email' => $patient->email])->assertOk();

    // BrevoApi is final, so it cannot be substituted with a mock. The event is
    // faked instead, which stops SendPasswordResetListener - and therefore the
    // real SDK client - from ever being constructed. The assertion pins the
    // payload the listener would have consumed.
    Event::assertDispatched(SendEmailForChangePasswordEvent::class, function (SendEmailForChangePasswordEvent $event) use ($patient): bool {
        return $event->customerEmail === $patient->email
            && $event->customerName !== ''
            && strlen($event->token) === 40;
    });
});
