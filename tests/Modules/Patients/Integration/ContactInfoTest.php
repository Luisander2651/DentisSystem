<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Tests\Modules\Patients\Integration\PatientsIntegrationTestCase;

uses(PatientsIntegrationTestCase::class);

it('allows any authenticated staff (not just admin) to create contact info', function () {
    $this->actingAsNonAdminUser();
    $patient = $this->createPatient();

    $response = $this->postJson($this->contactInfoUrl($patient->id), $this->validCreateContactInfoPayload());

    $response->assertStatus(201);
    $this->assertDatabaseHas('contact_info', ['patient_id' => $patient->id, 'phone_number' => '+1 555 0100']);
});

it('rejects creating a second contact info for the same patient with 409', function () {
    $this->actingAsAdmin();
    $patient = $this->createPatient();
    $this->createContactInfo(['patient_id' => $patient->id]);

    $response = $this->postJson($this->contactInfoUrl($patient->id), $this->validCreateContactInfoPayload());

    $response->assertStatus(409);
});

it('returns 404 when creating contact info for a non-existent patient (post-fix BR-7)', function () {
    $this->actingAsAdmin();

    $response = $this->postJson($this->contactInfoUrl((string) Str::uuid()), $this->validCreateContactInfoPayload());

    $response->assertStatus(404);
    $this->assertDatabaseCount('contact_info', 0);
});

it('returns 400 for a malformed patient id when creating contact info (post-fix BR-8)', function () {
    $this->actingAsAdmin();

    $response = $this->postJson($this->contactInfoUrl('not-a-uuid'), $this->validCreateContactInfoPayload());

    $response->assertStatus(400);
});

it('rejects an invalid value object with 400', function () {
    $this->actingAsAdmin();
    $patient = $this->createPatient();

    $response = $this->postJson($this->contactInfoUrl($patient->id), $this->validCreateContactInfoPayload([
        'contact_email' => 'not-an-email',
    ]));

    $response->assertStatus(400);
});

it('updates existing contact info partially', function () {
    $this->actingAsNonAdminUser();
    $patient = $this->createPatient();
    $this->createContactInfo(['patient_id' => $patient->id, 'phone_number' => '+1 555 0100']);

    $response = $this->putJson($this->contactInfoUrl($patient->id), ['phone_number' => '+1 555 0200']);

    $response->assertStatus(200);
    $this->assertDatabaseHas('contact_info', ['patient_id' => $patient->id, 'phone_number' => '+1 555 0200']);
});

it('rejects an update with no fields provided with 409', function () {
    $this->actingAsAdmin();
    $patient = $this->createPatient();
    $this->createContactInfo(['patient_id' => $patient->id]);

    $response = $this->putJson($this->contactInfoUrl($patient->id), []);

    $response->assertStatus(409);
});

it('returns 404 when updating contact info that was never created (no upsert — BR-9, post-fix BR-6)', function () {
    $this->actingAsAdmin();
    $patient = $this->createPatient();

    $response = $this->putJson($this->contactInfoUrl($patient->id), ['phone_number' => '+1 555 0200']);

    $response->assertStatus(404);
});

it('deletes existing contact info', function () {
    $this->actingAsAdmin();
    $patient = $this->createPatient();
    $this->createContactInfo(['patient_id' => $patient->id]);

    $response = $this->deleteJson($this->contactInfoUrl($patient->id));

    $response->assertStatus(200);
    $this->assertDatabaseMissing('contact_info', ['patient_id' => $patient->id]);
});

it('deleting non-existent contact info is idempotent and still returns 200', function () {
    $this->actingAsAdmin();
    $patient = $this->createPatient();

    $response = $this->deleteJson($this->contactInfoUrl($patient->id));

    $response->assertStatus(200);
});
