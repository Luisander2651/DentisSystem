<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
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
        ->and($response->json('data.0.time'))->toBe('12:00')
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

/*
 * GET /api/v1/portal/appointments/next (spec 025, CA1, CA2): with the clinic's clock at
 * 2026-09-17 11:40, an open appointment of today counts even when its hour has gone by.
 */

const PORTAL_NEXT_URL = '/api/v1/portal/appointments/next';

it('gives the nearest open appointment from today on, in the clinic time zone', function () {
    $this->travelTo(Carbon::parse('2026-09-17 11:40:00', 'America/Mexico_City'));
    $patient = $this->actingAsPatient();
    $this->createAppointment(['patient_id' => $patient->id, 'date' => '2026-09-16', 'time' => '18:00', 'status' => 'asignada']);
    $this->createAppointment(['patient_id' => $patient->id, 'date' => '2026-09-17', 'time' => '16:00', 'status' => 'cancelada']);
    $this->createAppointment(['patient_id' => $patient->id, 'date' => '2026-09-18', 'time' => '09:00', 'status' => 'reprogramada']);
    $today = $this->createAppointment(['patient_id' => $patient->id, 'date' => '2026-09-17', 'time' => '09:00', 'status' => 'asignada']);

    $response = $this->getJson(PORTAL_NEXT_URL)->assertOk();

    expect($response->json('data.id'))->toBe($today->id)
        ->and(array_keys($response->json('data')))->toBe(['id', 'date', 'time', 'status', 'treatment_name', 'doctor_name'])
        ->and($response->headers->get('Cache-Control'))->toContain('no-store');
});

it('answers null when no open appointment falls on today or later', function () {
    $this->travelTo(Carbon::parse('2026-09-17 11:40:00', 'America/Mexico_City'));
    $patient = $this->actingAsPatient();
    $this->createAppointment(['patient_id' => $patient->id, 'date' => '2026-09-16', 'time' => '18:00', 'status' => 'asignada']);
    $this->createAppointment(['patient_id' => $patient->id, 'date' => '2026-09-20', 'time' => '10:00', 'status' => 'completada']);

    $this->getJson(PORTAL_NEXT_URL)->assertOk()->assertExactJson(['data' => null]);
});

it('ignores the id of another patient sent with the next appointment (abuse)', function () {
    $this->travelTo(Carbon::parse('2026-09-17 11:40:00', 'America/Mexico_City'));
    $this->actingAsPatient();
    $other = $this->createPatient();
    $this->createAppointment(['patient_id' => $other->id, 'date' => '2026-09-18', 'time' => '09:00', 'status' => 'asignada']);

    $this->getJson(PORTAL_NEXT_URL.'?patient_id='.$other->id)->assertOk()->assertExactJson(['data' => null]);
});
