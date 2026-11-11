<?php

declare(strict_types=1);

use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;

uses(AppointmentsIntegrationTestCase::class);

/*
 * Spec 025 (CA3, CA4, CA13, CA20): the patient reads their own appointments, from the most
 * recent to the oldest. The patient is the one of the session: an id sent by the client is
 * ignored. No answer of the portal may be cached, errors included.
 */

const PORTAL_APPOINTMENTS_URL = '/api/v1/portal/appointments';

it('gives every appointment of the patient, from the most recent to the oldest', function () {
    $patient = $this->actingAsPatient();
    $doctor = $this->createUserWithRole('doctor', ['first_name' => 'Daniela', 'last_name' => 'Prueba']);
    $treatment = $this->createTreatment(['name' => 'Resina']);

    foreach ([['2026-09-14', '16:30', 'cancelada'], ['2026-11-26', '12:00', 'reprogramada'], ['2026-10-20', '09:00', 'completada'], ['2026-11-12', '10:30', 'asignada']] as [$date, $time, $status]) {
        $this->createAppointment(['patient_id' => $patient->id, 'user_id' => $doctor->id, 'treatment_id' => $treatment->id, 'date' => $date, 'time' => $time, 'status' => $status]);
    }

    $response = $this->getJson(PORTAL_APPOINTMENTS_URL)->assertOk();

    expect(array_column($response->json('data'), 'date'))->toBe(['2026-11-26', '2026-11-12', '2026-10-20', '2026-09-14'])
        ->and(array_column($response->json('data'), 'status'))->toBe(['reprogramada', 'asignada', 'completada', 'cancelada'])
        ->and(array_keys($response->json('data.0')))->toBe(['id', 'date', 'time', 'status', 'treatment_name', 'doctor_name'])
        ->and($response->json('data.0.treatment_name'))->toBe('Resina')
        ->and($response->json('data.0.doctor_name'))->toBe('Daniela Prueba');
});

it('answers an empty list to a patient without appointments', function () {
    $this->actingAsPatient();
    $this->createAppointment();

    $this->getJson(PORTAL_APPOINTMENTS_URL)->assertOk()->assertExactJson(['data' => []]);
});

it('ignores the id of another patient sent in the query or in the body (abuse)', function () {
    $patient = $this->actingAsPatient();
    $other = $this->createPatient();
    $own = $this->createAppointment(['patient_id' => $patient->id]);
    $this->createAppointment(['patient_id' => $other->id]);

    $byQuery = $this->getJson(PORTAL_APPOINTMENTS_URL.'?patient_id='.$other->id)->assertOk();
    $byBody = $this->json('GET', PORTAL_APPOINTMENTS_URL, ['patient_id' => $other->id])->assertOk();

    expect(array_column($byQuery->json('data'), 'id'))->toBe([$own->id])
        ->and(array_column($byBody->json('data'), 'id'))->toBe([$own->id]);
});

it('asks browsers and proxies not to keep any answer, errors included', function (string $actor, int $status) {
    match ($actor) {
        'patient' => $this->actingAsPatient(),
        'staff' => $this->actingAs($this->createUserWithRole('administrador'), 'sanctum'),
        'inactive patient' => $this->actingAsPatient(['status' => 'inactive']),
        'guest' => null,
    };

    $response = $this->getJson(PORTAL_APPOINTMENTS_URL)->assertStatus($status);

    expect($response->headers->get('Cache-Control'))->toContain('no-store')
        ->and($response->headers->get('Cache-Control'))->toContain('private');
})->with([
    'paciente (200)' => ['patient', 200],
    'sin sesión (401)' => ['guest', 401],
    'personal (403)' => ['staff', 403],
    'paciente inactivo (403)' => ['inactive patient', 403],
]);
