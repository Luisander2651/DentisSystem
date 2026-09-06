<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Tests\Modules\Patients\Integration\PatientsIntegrationTestCase;

uses(PatientsIntegrationTestCase::class);

// --- Crear ---

it('allows an admin to create a patient', function () {
    $this->actingAsAdmin();

    $response = $this->postJson($this->patientsUrl(), $this->validCreatePatientPayload());

    $response->assertStatus(201)
        ->assertJson(['message' => 'Patient created successfully']);

    $this->assertDatabaseCount('patients', 1);
});

it('rejects a non-admin staff member creating a patient with 403 (only.admin middleware)', function () {
    $this->actingAsNonAdminUser();

    $response = $this->postJson($this->patientsUrl(), $this->validCreatePatientPayload());

    $response->assertStatus(403)
        ->assertJson(['error' => 'Only administrators can access this resource.']);

    $this->assertDatabaseCount('patients', 0);
});

it('rejects an authenticated patient creating a patient with 403 (only.admin middleware)', function () {
    $this->actingAsPatient();

    $response = $this->postJson($this->patientsUrl(), $this->validCreatePatientPayload());

    $response->assertStatus(403)
        ->assertJson(['error' => 'Only users can access this resource.']);
});

it('rejects an unauthenticated request with 401', function () {
    $response = $this->postJson($this->patientsUrl(), $this->validCreatePatientPayload());

    $response->assertStatus(401);
});

it('rejects a duplicate email with 409', function () {
    $this->actingAsAdmin();
    $this->createPatient(['email' => 'existing@example.com']);

    $response = $this->postJson($this->patientsUrl(), $this->validCreatePatientPayload(['email' => 'existing@example.com']));

    $response->assertStatus(409);
});

it('rejects an invalid email format with 400', function () {
    $this->actingAsAdmin();

    $response = $this->postJson($this->patientsUrl(), $this->validCreatePatientPayload(['email' => 'not-an-email']));

    $response->assertStatus(400);
});

// --- Actualizar ---

it('allows any authenticated staff (not just admin) to partially update a patient', function () {
    $this->actingAsNonAdminUser();
    $patient = $this->createPatient(['first_name' => 'John']);

    $response = $this->putJson($this->patientUrl($patient->id), ['first_name' => 'Jane']);

    $response->assertStatus(200);
    $this->assertDatabaseHas('patients', ['id' => $patient->id, 'first_name' => 'Jane']);
});

it('rejects an update with no fields provided with 409', function () {
    $this->actingAsAdmin();
    $patient = $this->createPatient();

    $response = $this->putJson($this->patientUrl($patient->id), []);

    $response->assertStatus(409);
});

it('returns 404 when updating a non-existent patient (post-fix BR-6)', function () {
    $this->actingAsAdmin();

    $response = $this->putJson($this->patientUrl((string) Str::uuid()), ['first_name' => 'Jane']);

    $response->assertStatus(404);
});

it('rejects an invalid status value with 400', function () {
    $this->actingAsAdmin();
    $patient = $this->createPatient();

    $response = $this->putJson($this->patientUrl($patient->id), ['status' => 'not-a-status']);

    $response->assertStatus(400);
});

it('returns 400 for a malformed patient id in the URL (post-fix BR-8)', function () {
    $this->actingAsAdmin();

    $response = $this->putJson($this->patientUrl('not-a-uuid'), ['first_name' => 'Jane']);

    $response->assertStatus(400);
});

// --- Listar / Obtener ---

it('lists patients filtered by status', function () {
    $this->actingAsNonAdminUser();
    $this->createPatient(['status' => 'active']);
    $this->createPatient(['status' => 'inactive']);

    $response = $this->getJson($this->patientsUrl().'?status=active');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
});

it('gets a patient by id', function () {
    $this->actingAsNonAdminUser();
    $patient = $this->createPatient();

    $response = $this->getJson($this->patientUrl($patient->id));

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $patient->id);
});

it('returns 404 when getting a non-existent patient (post-fix BR-6)', function () {
    $this->actingAsNonAdminUser();

    $response = $this->getJson($this->patientUrl((string) Str::uuid()));

    $response->assertStatus(404);
});

it('returns 400 for a malformed patient id when getting by id (post-fix BR-8)', function () {
    $this->actingAsNonAdminUser();

    $response = $this->getJson($this->patientUrl('not-a-uuid'));

    $response->assertStatus(400);
});

// --- Eliminar ---

it('allows an admin to delete a patient', function () {
    $this->actingAsAdmin();
    $patient = $this->createPatient();

    $response = $this->deleteJson($this->patientUrl($patient->id));

    $response->assertStatus(200)
        ->assertJson(['message' => 'Patient deleted successfully']);

    $this->assertDatabaseMissing('patients', ['id' => $patient->id]);
});

it('rejects a non-admin staff member deleting a patient with 403 (only.admin middleware)', function () {
    $this->actingAsNonAdminUser();
    $patient = $this->createPatient();

    $response = $this->deleteJson($this->patientUrl($patient->id));

    $response->assertStatus(403);
    $this->assertDatabaseHas('patients', ['id' => $patient->id]);
});

it('returns 404 when deleting a non-existent patient (post-fix BR-6)', function () {
    $this->actingAsAdmin();

    $response = $this->deleteJson($this->patientUrl((string) Str::uuid()));

    $response->assertStatus(404);
});
