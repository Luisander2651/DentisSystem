<?php

declare(strict_types=1);

use App\Modules\Appointments\Domain\Repositories\AppointmentsRepositoryInterface;
use App\Modules\AppointmentTracking\Domain\Repositories\AppointmentCompletionRepositoryInterface;
use App\Modules\AppointmentTracking\Domain\Repositories\PendingTrackingRepositoryInterface;
use Illuminate\Support\Carbon;
use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;

uses(AppointmentsIntegrationTestCase::class);

/*
 * Spec 020 (P7): an unexpected failure in a read of the dashboard or in registering a follow-up
 * answers the generic 500, without the technical message of the failure.
 */

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-22 11:40:00', 'America/Mexico_City'));
    $this->actingAsAdmin();
});

it('answers a generic 500 when today fails unexpectedly', function () {
    $this->mock(AppointmentsRepositoryInterface::class)
        ->shouldReceive('findAllByStatusAndDateOrPatientId')->andThrow(new RuntimeException('detalle interno'));

    $this->getJson('/api/v1/agenda/today-summary')
        ->assertStatus(500)
        ->assertExactJson(['error' => 'Internal server error']);
});

it('answers a generic 500 when the unclosed ones fail unexpectedly', function () {
    $this->mock(AppointmentsRepositoryInterface::class)
        ->shouldReceive('findUnclosedBefore')->andThrow(new RuntimeException('detalle interno'));

    $this->getJson('/api/v1/agenda/unclosed')
        ->assertStatus(500)
        ->assertExactJson(['error' => 'Internal server error']);
});

it('answers a generic 500 when the ones without follow-up fail unexpectedly', function () {
    $this->mock(PendingTrackingRepositoryInterface::class)
        ->shouldReceive('findCompletedWithoutTracking')->andThrow(new RuntimeException('detalle interno'));

    $this->getJson('/api/v1/agenda/without-tracking')
        ->assertStatus(500)
        ->assertExactJson(['error' => 'Internal server error']);
})->skip('020: pendiente de T038');

it('answers a generic 500 when registering a follow-up fails unexpectedly', function () {
    $appointment = $this->createAppointment(['date' => '2026-10-21', 'status' => 'completada']);
    $this->mock(AppointmentCompletionRepositoryInterface::class)
        ->shouldReceive('register')->andThrow(new RuntimeException('detalle interno'));

    $this->postJson("/api/v1/appointments/{$appointment->id}/tracking", [
        'reason' => 'Dolor en una muela',
        'symptoms' => ['dolor'],
        'diagnosis' => 'Caries',
        'procedure_performed' => 'Resina',
        'prescriptions' => [],
    ])->assertStatus(500)
        ->assertExactJson(['error' => 'Internal server error']);

    $this->assertDatabaseMissing('appointment_tracking', ['appointment_id' => $appointment->id]);
})->skip('020: pendiente de T041');
