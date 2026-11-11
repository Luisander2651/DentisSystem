<?php

declare(strict_types=1);

use App\Core\Authorization\CurrentActorAuthorizationService;
use App\Core\Authorization\Exceptions\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ActingAsPatient;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class, ActingAsStaff::class, ActingAsPatient::class);

/*
 * Spec 025 (CA13, CA15, D2): the portal takes the patient from the session, never from the
 * request. currentPatientId() is the first call of every portal use case, as assertCan() is for
 * staff: it gives the id of an active patient and refuses anyone else.
 */

beforeEach(function () {
    $this->service = new CurrentActorAuthorizationService;
});

it('gives the id of the active patient of the session', function () {
    $patient = $this->actingAsPatient();

    expect($this->service->currentPatientId())->toBe((string) $patient->id);
});

it('refuses a patient whose account the clinic deactivated', function () {
    $this->actingAsPatient(['status' => 'inactive']);

    expect(fn () => $this->service->currentPatientId())
        ->toThrow(AuthorizationException::class, 'Your account is inactive.');
});

it('refuses a staff member, whatever the role', function (string $role) {
    $this->actingAs($this->createUserWithRole($role), 'sanctum');

    expect(fn () => $this->service->currentPatientId())
        ->toThrow(AuthorizationException::class, 'Authentication is required.');
})->with(['administrador', 'asistente', 'doctor']);

it('refuses when nobody is authenticated', function () {
    expect(fn () => $this->service->currentPatientId())
        ->toThrow(AuthorizationException::class, 'Authentication is required.');
});
