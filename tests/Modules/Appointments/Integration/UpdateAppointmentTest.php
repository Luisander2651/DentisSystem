<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;

uses(AppointmentsIntegrationTestCase::class);

it('reschedules an appointment successfully', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['date' => '2026-09-01', 'time' => '10:00']);

    $response = $this->putJson($this->appointmentUrl($appointment->id), [
        'date' => '2026-09-02',
        'time' => '11:00',
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'date' => '2026-09-02',
        'time' => '11:00:00',
        'status' => 'reprogramada',
    ]);
});

it('any authenticated active staff can reschedule (not just admin)', function () {
    $this->actingAsNonAdminUser();
    $appointment = $this->createAppointment(['date' => '2026-09-01', 'time' => '10:00']);

    $response = $this->putJson($this->appointmentUrl($appointment->id), [
        'date' => '2026-09-02',
        'time' => '11:00',
    ]);

    $response->assertStatus(200);
});

it('returns 409 when rescheduling into a slot occupied by another appointment (post-fix behavior)', function () {
    $this->actingAsAdmin();
    $treatment = $this->createTreatment(['time' => 30]);
    $this->createAppointment(['treatment_id' => $treatment->id, 'date' => '2026-09-01', 'time' => '09:00']);
    $toReschedule = $this->createAppointment(['treatment_id' => $treatment->id, 'date' => '2026-09-01', 'time' => '12:00']);

    $response = $this->putJson($this->appointmentUrl($toReschedule->id), [
        'date' => '2026-09-01',
        'time' => '09:15',
    ]);

    $response->assertStatus(409);
});

it('rescheduling to its own current slot does not conflict with itself', function () {
    $this->actingAsAdmin();
    $treatment = $this->createTreatment(['time' => 30]);
    $appointment = $this->createAppointment(['treatment_id' => $treatment->id, 'date' => '2026-09-01', 'time' => '10:00']);

    $response = $this->putJson($this->appointmentUrl($appointment->id), [
        'date' => '2026-09-01',
        'time' => '10:00',
    ]);

    $response->assertStatus(200);
});

it('returns 409 when only date is provided without time', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->putJson($this->appointmentUrl($appointment->id), ['date' => '2026-09-05']);

    $response->assertStatus(409);
});

it('returns 409 when only time is provided without date', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->putJson($this->appointmentUrl($appointment->id), ['time' => '15:00']);

    $response->assertStatus(409);
});

it('returns 409 when no fields are provided', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->putJson($this->appointmentUrl($appointment->id), []);

    $response->assertStatus(409);
});

it('completes an appointment via status field', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->putJson($this->appointmentUrl($appointment->id), ['status' => 'completada']);

    $response->assertStatus(200);
    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'completada']);
});

it('cancels an appointment via status field', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->putJson($this->appointmentUrl($appointment->id), ['status' => 'cancelada']);

    $response->assertStatus(200);
    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'cancelada']);
});

it('returns 409 for an invalid status transition value', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->putJson($this->appointmentUrl($appointment->id), ['status' => 'asignada']);

    $response->assertStatus(409)
        ->assertJson(['error' => 'Invalid status value. Allowed values are: completada, cancelada.']);
});

it('toggles the whatsapp reminder flag', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['whatsapp_reminder' => false]);

    $response = $this->putJson($this->appointmentUrl($appointment->id), ['whatsapp_reminder' => true]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'whatsapp_reminder' => true]);
});

it('returns 409 when the appointment does not exist', function () {
    $this->actingAsAdmin();

    $response = $this->putJson($this->appointmentUrl((string) Str::uuid()), ['status' => 'completada']);

    $response->assertStatus(409);
});

it('rejects unauthenticated request', function () {
    $appointment = $this->createAppointment();

    $response = $this->putJson($this->appointmentUrl($appointment->id), ['status' => 'completada']);

    $response->assertStatus(401);
});
