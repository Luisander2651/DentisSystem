<?php

declare(strict_types=1);

use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;
use Tests\Support\DecodesQrSvg;

uses(AppointmentsIntegrationTestCase::class, DecodesQrSvg::class);

/*
 * GET /api/v1/portal/appointments/{id}/qr (spec 025, CA7, CA12, CA18, CA20): the QR code of an
 * open appointment of the patient, holding only its id, as in spec 024. An appointment of
 * another patient answers exactly like one that does not exist, whatever its status.
 */

function portalQrUrl(string $id): string
{
    return '/api/v1/portal/appointments/'.$id.'/qr';
}

it('draws the QR code of an open appointment of the patient, holding only its id', function (string $status) {
    $patient = $this->actingAsPatient();
    $appointment = $this->createAppointment(['patient_id' => $patient->id, 'status' => $status]);

    $response = $this->get(portalQrUrl($appointment->id))->assertOk();

    expect($response->headers->get('Content-Type'))->toBe('image/svg+xml')
        ->and($response->headers->get('Cache-Control'))->toContain('no-store')
        ->and($this->decodeQrSvg($response->getContent()))->toBe($appointment->id);
})->with(['asignada', 'reprogramada']);

it('refuses the QR code of a closed appointment of the patient (abuse)', function (string $status) {
    $patient = $this->actingAsPatient();
    $appointment = $this->createAppointment(['patient_id' => $patient->id, 'status' => $status]);

    $this->getJson(portalQrUrl($appointment->id))
        ->assertStatus(409)
        ->assertExactJson(['error' => 'The QR code is only available for open appointments.']);
})->with(['completada', 'cancelada']);

it('answers an appointment of another patient exactly like a missing one (abuse)', function (string $status) {
    $this->actingAsPatient();
    $foreign = $this->createAppointment(['patient_id' => $this->createPatient()->id, 'status' => $status]);

    $missing = $this->getJson(portalQrUrl('0f1e2d3c-4b5a-4968-8778-695a4b3c2d1e'))->assertNotFound();
    $response = $this->getJson(portalQrUrl($foreign->id))->assertNotFound();

    expect($response->getContent())->toBe($missing->getContent())
        ->and($response->json())->toBe(['error' => 'Appointment not found.']);
})->with(['asignada', 'completada', 'cancelada']);

it('answers 400 to an id that is not a UUID', function () {
    $this->actingAsPatient();

    $this->getJson(portalQrUrl('no-es-uuid'))
        ->assertStatus(400)
        ->assertExactJson(['error' => 'Invalid appointment id.']);
});
