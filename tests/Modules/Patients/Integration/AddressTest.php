<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Tests\Modules\Patients\Integration\PatientsIntegrationTestCase;

uses(PatientsIntegrationTestCase::class);

it('allows any authenticated staff (not just admin) to create an address', function () {
    $this->actingAsNonAdminUser();
    $patient = $this->createPatient();

    $response = $this->postJson($this->addressUrl($patient->id), $this->validCreateAddressPayload());

    $response->assertStatus(201);
    $this->assertDatabaseHas('addresses', ['patient_id' => $patient->id, 'city' => 'Springfield']);
});

it('rejects creating a second address for the same patient with 409', function () {
    $this->actingAsAdmin();
    $patient = $this->createPatient();
    $this->createAddress(['patient_id' => $patient->id]);

    $response = $this->postJson($this->addressUrl($patient->id), $this->validCreateAddressPayload());

    $response->assertStatus(409);
});

it('returns 404 when creating an address for a non-existent patient (post-fix BR-7)', function () {
    $this->actingAsAdmin();

    $response = $this->postJson($this->addressUrl((string) Str::uuid()), $this->validCreateAddressPayload());

    $response->assertStatus(404);
    $this->assertDatabaseCount('addresses', 0);
});

it('returns 400 for a malformed patient id when creating an address (post-fix BR-8)', function () {
    $this->actingAsAdmin();

    $response = $this->postJson($this->addressUrl('not-a-uuid'), $this->validCreateAddressPayload());

    $response->assertStatus(400);
});

it('rejects an invalid value object with 400', function () {
    $this->actingAsAdmin();
    $patient = $this->createPatient();

    $response = $this->postJson($this->addressUrl($patient->id), $this->validCreateAddressPayload([
        'postal_code' => 'a', // menos de 3 caracteres
    ]));

    $response->assertStatus(400);
});

it('updates an existing address partially', function () {
    $this->actingAsNonAdminUser();
    $patient = $this->createPatient();
    $this->createAddress(['patient_id' => $patient->id, 'city' => 'Springfield']);

    $response = $this->putJson($this->addressUrl($patient->id), ['city' => 'Shelbyville']);

    $response->assertStatus(200);
    $this->assertDatabaseHas('addresses', ['patient_id' => $patient->id, 'city' => 'Shelbyville']);
});

it('rejects an update with no fields provided with 409', function () {
    $this->actingAsAdmin();
    $patient = $this->createPatient();
    $this->createAddress(['patient_id' => $patient->id]);

    $response = $this->putJson($this->addressUrl($patient->id), []);

    $response->assertStatus(409);
});

it('returns 404 when updating an address that was never created (no upsert — BR-9, post-fix BR-6)', function () {
    $this->actingAsAdmin();
    $patient = $this->createPatient();

    $response = $this->putJson($this->addressUrl($patient->id), ['city' => 'Shelbyville']);

    $response->assertStatus(404);
});

it('deletes an existing address', function () {
    $this->actingAsAdmin();
    $patient = $this->createPatient();
    $this->createAddress(['patient_id' => $patient->id]);

    $response = $this->deleteJson($this->addressUrl($patient->id));

    $response->assertStatus(200);
    $this->assertDatabaseMissing('addresses', ['patient_id' => $patient->id]);
});

it('deleting a non-existent address is idempotent and still returns 200', function () {
    $this->actingAsAdmin();
    $patient = $this->createPatient();

    $response = $this->deleteJson($this->addressUrl($patient->id));

    $response->assertStatus(200);
});
