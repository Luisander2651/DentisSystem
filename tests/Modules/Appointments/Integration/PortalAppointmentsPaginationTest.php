<?php

declare(strict_types=1);

use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;

uses(AppointmentsIntegrationTestCase::class);

/**
 * Spec 028 (CA2, CA7, CA13, CA14): "Mis citas" answers ten appointments of the patient of the
 * session per page, the most recent first, with its `meta`. The patient is never taken from the
 * request.
 */
beforeEach(function () {
    $this->treatmentId = $this->createTreatment()->id;
    $this->doctorId = $this->createUserWithRole('Doctor')->id;
    $this->seedPortal = function (string $patientId, int $count): void {
        foreach (range(1, $count) as $number) {
            $this->createAppointment([
                'patient_id' => $patientId,
                'treatment_id' => $this->treatmentId,
                'user_id' => $this->doctorId,
                'date' => sprintf('2026-02-%02d', $number),
                'status' => 'completada',
            ]);
        }
    };
});

it('answers ten appointments per page with their meta, the most recent first', function () {
    $patient = $this->actingAsPatient();
    ($this->seedPortal)($patient->id, 25);

    $this->getJson('/api/v1/portal/appointments')
        ->assertOk()
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('data.0.date', '2026-02-25')
        ->assertJsonPath('meta', [
            'page' => 1,
            'per_page' => 10,
            'total' => 25,
            'last_page' => 3,
            'from' => 1,
            'to' => 10,
        ]);
});

it('answers the page asked for, the last one beyond it and never more than fifty', function (string $query, int $count, int $page) {
    $patient = $this->actingAsPatient();
    ($this->seedPortal)($patient->id, 25);

    $this->getJson('/api/v1/portal/appointments'.$query)
        ->assertOk()
        ->assertJsonCount($count, 'data')
        ->assertJsonPath('meta.page', $page);
})->with([
    'third page' => ['?page=3', 5, 3],
    'beyond the last' => ['?page=99', 5, 3],
    'ten thousand per page' => ['?per_page=10000', 25, 1],
]);

it('gives patient B only their own appointments even with the id of patient A', function () {
    $patientA = $this->createPatient();
    ($this->seedPortal)($patientA->id, 12);
    $patientB = $this->actingAsPatient();
    ($this->seedPortal)($patientB->id, 3);

    $response = $this->getJson('/api/v1/portal/appointments?patient_id='.$patientA->id)->assertOk();

    expect($response->json('meta.total'))->toBe(3)
        ->and($response->json('data'))->toHaveCount(3);
});

it('refuses the staff as before, without meta', function () {
    $this->actingAs($this->createUserWithRole('administrador'), 'sanctum');

    $this->getJson('/api/v1/portal/appointments')
        ->assertForbidden()
        ->assertJsonMissingPath('meta')
        ->assertJsonMissingPath('data');
});

it('rejects invalid page parameters with 422', function (string $query, string $field) {
    $this->actingAsPatient();

    $this->getJson('/api/v1/portal/appointments'.$query)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field]);
})->with([
    'page text' => ['?page=abc', 'page'],
    'per_page zero' => ['?per_page=0', 'per_page'],
]);
