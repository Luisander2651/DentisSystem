<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Tests\Modules\Patients\Integration\PatientsIntegrationTestCase;

uses(PatientsIntegrationTestCase::class);

it('aggregates patient plus the 3 sub-resources when all exist', function () {
    $this->actingAsNonAdminUser();
    $patient = $this->createPatient();
    $this->createAddress(['patient_id' => $patient->id]);
    $this->createContactInfo(['patient_id' => $patient->id]);
    $this->createMedicalData(['patient_id' => $patient->id]);

    $response = $this->getJson($this->patientRecordUrl($patient->id));

    $response->assertStatus(200)
        ->assertJsonPath('data.patient.id', $patient->id)
        ->assertJsonPath('data.address.city', 'Springfield')
        ->assertJsonPath('data.contact_info.phone_number', '+1 555 0100')
        ->assertJsonPath('data.medical_data.blood_type', 'O+');
});

it('returns null sub-resources when they were never created', function () {
    $this->actingAsNonAdminUser();
    $patient = $this->createPatient();

    $response = $this->getJson($this->patientRecordUrl($patient->id));

    $response->assertStatus(200)
        ->assertJsonPath('data.patient.id', $patient->id)
        ->assertJsonPath('data.address', null)
        ->assertJsonPath('data.contact_info', null)
        ->assertJsonPath('data.medical_data', null);
});

it('confirms any authenticated staff (not just admin) can read the full record, including medical data (BR-5/SECURITY-08)', function () {
    $this->actingAsNonAdminUser();
    $patient = $this->createPatient();
    $this->createMedicalData(['patient_id' => $patient->id, 'allergies' => ['Penicilina']]);

    $response = $this->getJson($this->patientRecordUrl($patient->id));

    $response->assertStatus(200)
        ->assertJsonPath('data.medical_data.allergies', ['Penicilina']);
});

it('returns 404 for a non-existent patient (post-fix BR-6)', function () {
    $this->actingAsNonAdminUser();

    $response = $this->getJson($this->patientRecordUrl((string) Str::uuid()));

    $response->assertStatus(404);
});

it('returns 400 for a malformed patient id (post-fix BR-8)', function () {
    $this->actingAsNonAdminUser();

    $response = $this->getJson($this->patientRecordUrl('not-a-uuid'));

    $response->assertStatus(400);
});
