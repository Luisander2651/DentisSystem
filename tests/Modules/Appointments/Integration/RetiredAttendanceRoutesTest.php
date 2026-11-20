<?php

declare(strict_types=1);

use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;

uses(AppointmentsIntegrationTestCase::class);

/*
 * The QR code of the appointments (specs 024 and 025) and the queries of the attendance
 * microservice (spec 023) moved to DentissaCoreAsistencia on 2026-11-20. Their routes no longer
 * exist here: the staff, the patient and a caller with a service key all get 404.
 */

it('no longer serves the QR code of an appointment to the staff', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $this->getJson('/api/v1/appointments/'.$appointment->id.'/qr')->assertNotFound();
});

it('no longer serves the QR code of an appointment to the patient', function () {
    $patient = $this->actingAsPatient();
    $appointment = $this->createAppointment(['patient_id' => $patient->id, 'status' => 'asignada']);

    $this->getJson('/api/v1/portal/appointments/'.$appointment->id.'/qr')->assertNotFound();
});

it('no longer answers the queries of the attendance microservice', function (string $path) {
    $appointment = $this->createAppointment();

    $this->withHeader('X-Service-Key', 'any-key')
        ->getJson(str_replace('{id}', $appointment->id, $path))
        ->assertNotFound();
})->with([
    'citas de hoy' => '/api/v1/internal/appointments/today',
    'una cita' => '/api/v1/internal/appointments/{id}',
]);
