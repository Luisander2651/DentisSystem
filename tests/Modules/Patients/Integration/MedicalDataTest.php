<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Tests\Modules\Patients\Integration\PatientsIntegrationTestCase;

uses(PatientsIntegrationTestCase::class);

it('allows any authenticated staff (not just admin) to create medical data', function () {
    $this->actingAsNonAdminUser();
    $patient = $this->createPatient();

    $response = $this->postJson($this->medicalDataUrl($patient->id), $this->validCreateMedicalDataPayload());

    $response->assertStatus(201);
    $this->assertDatabaseHas('medical_data', ['patient_id' => $patient->id, 'blood_type' => 'O+']);
});

it('rejects creating a second medical data record for the same patient with 409', function () {
    $this->actingAsAdmin();
    $patient = $this->createPatient();
    $this->createMedicalData(['patient_id' => $patient->id]);

    $response = $this->postJson($this->medicalDataUrl($patient->id), $this->validCreateMedicalDataPayload());

    $response->assertStatus(409);
});

it('returns 404 when creating medical data for a non-existent patient (post-fix BR-7)', function () {
    $this->actingAsAdmin();

    $response = $this->postJson($this->medicalDataUrl((string) Str::uuid()), $this->validCreateMedicalDataPayload());

    $response->assertStatus(404);
    $this->assertDatabaseCount('medical_data', 0);
});

it('returns 400 for a malformed patient id when creating medical data (post-fix BR-8)', function () {
    $this->actingAsAdmin();

    $response = $this->postJson($this->medicalDataUrl('not-a-uuid'), $this->validCreateMedicalDataPayload());

    $response->assertStatus(400);
});

it('rejects an invalid blood type with 400', function () {
    $this->actingAsAdmin();
    $patient = $this->createPatient();

    $response = $this->postJson($this->medicalDataUrl($patient->id), $this->validCreateMedicalDataPayload([
        'blood_type' => 'X+',
    ]));

    $response->assertStatus(400);
});

it('rejects allergies with a non-string element with 400', function () {
    $this->actingAsAdmin();
    $patient = $this->createPatient();

    $response = $this->postJson($this->medicalDataUrl($patient->id), $this->validCreateMedicalDataPayload([
        'allergies' => ['Penicilina', 123],
    ]));

    $response->assertStatus(400);
});

it('rejects medications with a non-string element with 400', function () {
    $this->actingAsAdmin();
    $patient = $this->createPatient();

    $response = $this->postJson($this->medicalDataUrl($patient->id), $this->validCreateMedicalDataPayload([
        'medications' => [null],
    ]));

    $response->assertStatus(400);
});

it('updates existing medical data partially', function () {
    $this->actingAsNonAdminUser();
    $patient = $this->createPatient();
    $this->createMedicalData(['patient_id' => $patient->id, 'blood_type' => 'O+']);

    $response = $this->putJson($this->medicalDataUrl($patient->id), ['blood_type' => 'A+']);

    $response->assertStatus(200);
    $this->assertDatabaseHas('medical_data', ['patient_id' => $patient->id, 'blood_type' => 'A+']);
});

it('rejects an update with no fields provided with 409', function () {
    $this->actingAsAdmin();
    $patient = $this->createPatient();
    $this->createMedicalData(['patient_id' => $patient->id]);

    $response = $this->putJson($this->medicalDataUrl($patient->id), []);

    $response->assertStatus(409);
});

it('returns 404 when updating medical data that was never created (no upsert — BR-9, post-fix BR-6)', function () {
    $this->actingAsAdmin();
    $patient = $this->createPatient();

    $response = $this->putJson($this->medicalDataUrl($patient->id), ['blood_type' => 'A+']);

    $response->assertStatus(404);
});

it('deletes existing medical data', function () {
    $this->actingAsAdmin();
    $patient = $this->createPatient();
    $this->createMedicalData(['patient_id' => $patient->id]);

    $response = $this->deleteJson($this->medicalDataUrl($patient->id));

    $response->assertStatus(200);
    $this->assertDatabaseMissing('medical_data', ['patient_id' => $patient->id]);
});

it('deleting non-existent medical data is idempotent and still returns 200', function () {
    $this->actingAsAdmin();
    $patient = $this->createPatient();

    $response = $this->deleteJson($this->medicalDataUrl($patient->id));

    $response->assertStatus(200);
});
