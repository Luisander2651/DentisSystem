<?php

declare(strict_types=1);

use App\Modules\AppointmentTracking\Domain\Repositories\AppointmentTrackingRepositoryInterface;
use App\Modules\AppointmentTracking\Domain\Repositories\PrescriptionRepositoryInterface;
use Tests\Modules\AppointmentTracking\Integration\AppointmentTrackingIntegrationTestCase;

uses(AppointmentTrackingIntegrationTestCase::class);

/**
 * Spec 014, CA12: an unexpected error in the clinical tracking answers a generic 500,
 * without the technical message of the failure.
 */
it('answers a generic 500 when reading the tracking of an appointment fails unexpectedly', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();
    $this->mock(AppointmentTrackingRepositoryInterface::class)
        ->shouldReceive('findByAppointmentId')->andThrow(new RuntimeException('detalle interno'));

    $this->getJson("/api/v1/appointments/{$appointment->id}/tracking")
        ->assertStatus(500)
        ->assertExactJson(['error' => 'Internal server error']);
});

it('answers a generic 500 when deleting a prescription fails unexpectedly', function () {
    $this->actingAsAdmin();
    $this->mock(PrescriptionRepositoryInterface::class)
        ->shouldReceive('findById')->andThrow(new RuntimeException('detalle interno'));

    $this->deleteJson('/api/v1/appointment-tracking/prescriptions/00000000-0000-4000-8000-000000000000')
        ->assertStatus(500)
        ->assertExactJson(['error' => 'Internal server error']);
});
