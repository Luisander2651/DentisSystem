<?php

declare(strict_types=1);

use App\Modules\Appointments\Aplication\Support\QrCodeGeneratorInterface;
use App\Modules\Appointments\Domain\Repositories\AppointmentsRepositoryInterface;
use App\Modules\AppointmentTracking\Domain\Repositories\AppointmentTrackingRepositoryInterface;
use Illuminate\Testing\TestResponse;
use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;

uses(AppointmentsIntegrationTestCase::class);

/*
 * Spec 025 (CA19, TM9, P7): an unexpected failure in any route of the portal answers the generic
 * 500, without the message, the class or anything of the request.
 */

const PORTAL_FAILURE_DETAIL = 'detalle interno de la falla';

function assertPortalGenericError(TestResponse $response): void
{
    $response->assertStatus(500)->assertExactJson(['error' => 'Internal server error']);

    expect($response->getContent())->not->toContain(PORTAL_FAILURE_DETAIL);
}

it('answers a generic 500 when the appointments fail to load', function (string $url) {
    $this->actingAsPatient();
    $this->mock(AppointmentsRepositoryInterface::class)
        ->shouldReceive('findAllByStatusAndDateOrPatientId')->andThrow(new RuntimeException(PORTAL_FAILURE_DETAIL));

    assertPortalGenericError($this->getJson($url));
})->with([
    'mis citas' => '/api/v1/portal/appointments',
    'próxima cita' => '/api/v1/portal/appointments/next',
]);

it('answers a generic 500 when an appointment fails to load', function (string $suffix) {
    $this->actingAsPatient();
    $this->mock(AppointmentsRepositoryInterface::class)
        ->shouldReceive('findById')->andThrow(new RuntimeException(PORTAL_FAILURE_DETAIL));

    assertPortalGenericError($this->getJson('/api/v1/portal/appointments/9b2e6c1a-4f1d-4c3e-8a7b-2d5f0e9c1a34/'.$suffix));
})->with(['qr', 'prescriptions']);

it('answers a generic 500 when the follow-up fails to load', function () {
    $patient = $this->actingAsPatient();
    $appointment = $this->createAppointment(['patient_id' => $patient->id, 'status' => 'completada']);
    $this->mock(AppointmentTrackingRepositoryInterface::class)
        ->shouldReceive('findByAppointmentId')->andThrow(new RuntimeException(PORTAL_FAILURE_DETAIL));

    assertPortalGenericError($this->getJson('/api/v1/portal/appointments/'.$appointment->id.'/prescriptions'));
});

it('answers a generic 500 when the QR code fails to draw', function () {
    $patient = $this->actingAsPatient();
    $appointment = $this->createAppointment(['patient_id' => $patient->id, 'status' => 'asignada']);
    $this->mock(QrCodeGeneratorInterface::class)
        ->shouldReceive('svg')->andThrow(new RuntimeException(PORTAL_FAILURE_DETAIL));

    assertPortalGenericError($this->getJson('/api/v1/portal/appointments/'.$appointment->id.'/qr'));
});
