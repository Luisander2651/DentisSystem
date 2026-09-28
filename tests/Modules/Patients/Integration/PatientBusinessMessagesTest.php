<?php

declare(strict_types=1);

use Tests\Modules\Patients\Integration\PatientsIntegrationTestCase;

uses(PatientsIntegrationTestCase::class);

/**
 * Spec 014, CA14 and CA16: a business error keeps explaining which field fails, but no longer
 * echoes the value that was sent - an email, phone, name, postal code or blood type belongs to
 * a patient, and a conflict message must not reveal another patient's data.
 */
it('rejects a duplicate patient email without echoing it', function () {
    $this->actingAsAdmin();
    $this->createPatient(['email' => 'duplicado-prueba@example.com']);

    $response = $this->postJson($this->patientsUrl(), $this->validCreatePatientPayload(['email' => 'duplicado-prueba@example.com']));

    $response->assertStatus(409);
    expect($response->json('error'))->toContain('email')
        ->and($response->getContent())->not->toContain('duplicado-prueba@example.com');
});

it('rejects invalid patient data without echoing the value sent', function (string $target, string $field, string $value) {
    $this->actingAsAdmin();

    $response = match ($target) {
        'patient' => $this->postJson($this->patientsUrl(), $this->validCreatePatientPayload([$field => $value])),
        'contact-info' => $this->postJson($this->contactInfoUrl($this->createPatient()->id), $this->validCreateContactInfoPayload([$field => $value])),
        'address' => $this->postJson($this->addressUrl($this->createPatient()->id), $this->validCreateAddressPayload([$field => $value])),
        'medical-data' => $this->postJson($this->medicalDataUrl($this->createPatient()->id), $this->validCreateMedicalDataPayload([$field => $value])),
    };

    $response->assertStatus(400);
    // Case-insensitive: value objects normalise what they echo (a name comes back capitalised).
    expect(mb_strtolower($response->getContent()))->not->toContain(mb_strtolower($value));
})->with([
    'patient email' => ['patient', 'email', 'correo-invalido-prueba'],
    'patient name' => ['patient', 'first_name', str_repeat('Nombrelargoprueba', 12)],
    'contact phone' => ['contact-info', 'phone_number', 'TELEFONO-INVALIDO-123'],
    'contact email' => ['contact-info', 'contact_email', 'contacto-invalido-prueba'],
    'postal code' => ['address', 'postal_code', 'CP-INVALIDO-XYZ'],
    'blood type' => ['medical-data', 'blood_type', 'Z-INVALIDO'],
]);

it('keeps the text of a missing patient and of a one-to-one conflict', function () {
    $this->actingAsAdmin();
    $missingId = '00000000-0000-4000-8000-000000000000';
    $patientId = $this->createPatient()->id;
    $this->createAddress(['patient_id' => $patientId]);

    $this->getJson($this->patientUrl($missingId))
        ->assertNotFound()
        ->assertExactJson(['error' => "Patient with ID {$missingId} not found."]);

    $this->postJson($this->addressUrl($patientId), $this->validCreateAddressPayload())
        ->assertConflict()
        ->assertExactJson(['error' => 'Address already exists for this patient.']);
});
